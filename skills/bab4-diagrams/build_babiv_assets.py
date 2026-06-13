#!/usr/bin/env python3
"""
build_babiv_assets.py — one command that turns a logical spec.json into a
complete, print-ready BAB IV asset pack:

    BabIvAssets/
      context/        diagram konteks   (.drawio.xml + .graphml + .png)
      dfd 0/          DFD Level 0        (.drawio.xml + .graphml + .png)
      dfd 1.1/        DFD Level 1 proses 1.0
      dfd 1.2/        ...                (one folder per decomposed process)
      erd/            ERD (crow's foot)  (.drawio.xml + .graphml + .png)
      BAB_IV.docx     Word doc (real heading styles + SEQ captions, figures embedded)
      README.txt

Design rule (the whole reason this pack exists)
-----------------------------------------------
You DO NOT hand-write diagram coordinates or type figure numbers. You describe
the LOGICAL structure in spec.json; this script computes clean orthogonal,
non-overlapping layouts (crow's foot for ERD), renders them with the bundled
draw.io engine so they are verifiable PNGs, and assembles a Word document whose
headings auto-number and whose captions use Word SEQ fields (so figure/table
numbers can never gap).

Usage
-----
    pip install python-docx playwright --break-system-packages
    python -m playwright install chromium
    python build_babiv_assets.py spec.json --out ./out

    # diagrams only (skip the Word document):
    python build_babiv_assets.py spec.json --out ./out --no-docx
"""

from __future__ import annotations

import argparse
import json
import os
import pathlib
import re
import sys

# make sibling modules importable no matter the CWD
_HERE = pathlib.Path(__file__).resolve().parent
if str(_HERE) not in sys.path:
    sys.path.insert(0, str(_HERE))

from babiv_diagrams.model import (DFD, ExternalEntity, Process, DataStore,
                                  Flow, ERD, Entity, Relation)
from babiv_diagrams.drawio import (build_dfd_drawio, build_erd_drawio,
                                   build_erd_chen_drawio)
from babiv_diagrams.graphml import build_dfd_graphml, build_erd_graphml
from babiv_diagrams import gvlayout

SYSTEM_ID = "sys"          # id used for the single process in the context diagram
MAX_PROCESSES = 8          # DFD 7±2 rule: never exceed 8 processes on a level


# --------------------------------------------------------------------------- #
# spec -> model
# --------------------------------------------------------------------------- #
def _externals(spec):
    return [ExternalEntity(e["id"], e["name"]) for e in spec.get("external_entities", [])]


def _stores(spec):
    return [DataStore(s["id"], s["code"], s["name"]) for s in spec.get("data_stores", [])]


def _flows(items):
    return [Flow(f["src"], f["dst"], f["label"]) for f in items]


def _ids_in_flows(flows):
    out = set()
    for f in flows:
        out.add(f["src"]); out.add(f["dst"])
    return out


def validate_balance(dfd: DFD, label: str, processes_only: bool = False) -> list:
    """Warn when a node has flows only IN or only OUT. A proper DFD has every
    process, external entity and data store BOTH receiving and sending data
    (no input-only / output-only nodes). Returns the list of warnings."""
    from collections import defaultdict
    ins, outs = defaultdict(int), defaultdict(int)
    for f in dfd.flows:
        outs[f.src] += 1
        ins[f.dst] += 1
    names = {}
    for p in dfd.processes:
        names[p.id] = ("proses", f"{p.no} {p.name}")
    if not processes_only:
        for e in dfd.externals:
            names[e.id] = ("entitas eksternal", e.name)
        for s in dfd.stores:
            names[s.id] = ("data store", f"{s.code} {s.name}")
    warns = []
    for nid, (kind, nm) in names.items():
        i, o = ins.get(nid, 0), outs.get(nid, 0)
        if i == 0 and o == 0:
            continue  # node not used in this diagram
        if i == 0:
            warns.append(f"{kind} '{nm}' hanya punya aliran KELUAR (tidak ada masuk)")
        if o == 0:
            warns.append(f"{kind} '{nm}' hanya punya aliran MASUK (tidak ada keluar)")
    if warns:
        print(f"    \u26a0 {label}: input/output BELUM seimbang —")
        for w in warns:
            print(f"        - {w}")
        print("      (tiap node harus punya minimal 1 aliran masuk DAN 1 keluar; "
              "perbaiki flows di spec.json lalu jalankan ulang)")
    return warns


def build_context(spec) -> DFD:
    name = spec["system_name"]
    flows = spec.get("context", {}).get("flows", [])
    return DFD(
        title=f"Diagram Konteks {name}",
        externals=_externals(spec),
        processes=[Process(SYSTEM_ID, "0", name)],
        stores=[],
        flows=_flows(flows),
    )


def build_level0(spec) -> DFD:
    name = spec["system_name"]
    procs = spec.get("level0", {}).get("processes", [])
    if len(procs) > MAX_PROCESSES:
        print(f"  ! WARNING: {len(procs)} proses di DFD Level 0 (>{MAX_PROCESSES}). "
              "Pertimbangkan menggabungkan proses; aturan DFD maks 7±2.")
    flows = spec.get("level0", {}).get("flows", [])
    return DFD(
        title=f"DFD Level 0 {name}",
        externals=_externals(spec),
        processes=[Process(p["id"], p["no"], p["name"]) for p in procs],
        stores=_stores(spec),
        flows=_flows(flows),
    )


def build_level1(spec, entry) -> DFD:
    """Build one Level-1 DFD from a spec.level1[] entry.

    Externals/stores are auto-restricted to those referenced by the entry's
    flows (so the diagram only shows what is relevant), unless explicit
    *_used id lists are provided.
    """
    flows = entry.get("flows", [])
    used = _ids_in_flows(flows)

    all_ext = {e["id"]: e for e in spec.get("external_entities", [])}
    all_store = {s["id"]: s for s in spec.get("data_stores", [])}

    ext_ids = entry.get("externals_used") or [i for i in all_ext if i in used]
    store_ids = entry.get("stores_used") or [i for i in all_store if i in used]

    externals = [ExternalEntity(all_ext[i]["id"], all_ext[i]["name"])
                 for i in ext_ids if i in all_ext]
    stores = [DataStore(all_store[i]["id"], all_store[i]["code"], all_store[i]["name"])
              for i in store_ids if i in all_store]
    procs = [Process(p["id"], p["no"], p["name"]) for p in entry.get("processes", [])]
    if len(procs) > MAX_PROCESSES:
        print(f"  ! WARNING: {len(procs)} sub-proses di DFD Level 1 "
              f"proses {entry.get('parent_no')} (>{MAX_PROCESSES}).")

    parent = entry.get("parent_no", "?")
    return DFD(
        title=f"DFD Level 1 Proses {parent}",
        externals=externals,
        processes=procs,
        stores=stores,
        flows=_flows(flows),
    )


def build_erd(spec) -> ERD:
    ents = []
    for e in spec.get("erd", {}).get("entities", []):
        ents.append(Entity.make(
            id=e["id"], name=e["name"],
            pk=e.get("pk"), fks=e.get("fks", []), attrs=e.get("attrs", []),
        ))
    rels = []
    for r in spec.get("erd", {}).get("relations", []):
        rels.append(Relation(
            src=r["src"], dst=r["dst"], label=r.get("label", ""),
            src_card=r.get("src_card", "one"), dst_card=r.get("dst_card", "many"),
        ))
    return ERD(title=f"ERD {spec['system_name']}", entities=ents, relations=rels)


# --------------------------------------------------------------------------- #
# emit one diagram folder
# --------------------------------------------------------------------------- #
def _slug(s: str) -> str:
    s = re.sub(r"[^\w]+", "_", s.strip().lower())
    return re.sub(r"_+", "_", s).strip("_") or "diagram"


def emit_dfd(dfd: DFD, kind: str, folder: pathlib.Path, slug: str,
             render: bool) -> str:
    folder.mkdir(parents=True, exist_ok=True)
    xml = build_dfd_drawio(dfd, kind)
    (folder / f"{slug}.drawio.xml").write_text(xml, encoding="utf-8")
    (folder / f"{slug}.graphml").write_text(build_dfd_graphml(dfd), encoding="utf-8")
    png = folder / f"{slug}.png"
    if render:
        # Render from the draw.io engine for ALL DFDs (context + levels). Its
        # per-track label placement + orthogonal arc-jump routing reads far
        # cleaner than Graphviz's `splines=ortho` xlabel output (which overlaps
        # labels and scatters processes). Deterministic, graphviz-independent.
        from babiv_diagrams.render import render_xml
        render_xml(xml, str(png))
        print(f"    -> {png.relative_to(folder.parent.parent)}")
    return str(png)


def emit_erd(erd: ERD, folder: pathlib.Path, slug: str, render: bool) -> str:
    folder.mkdir(parents=True, exist_ok=True)
    xml = build_erd_drawio(erd)
    (folder / f"{slug}.drawio.xml").write_text(xml, encoding="utf-8")
    (folder / f"{slug}.graphml").write_text(build_erd_graphml(erd), encoding="utf-8")
    png = folder / f"{slug}.png"
    if render:
        from babiv_diagrams.render import render_xml
        render_xml(xml, str(png))
        print(f"    -> {png.relative_to(folder.parent.parent)}")
    return str(png)


def emit_erd_chen(erd: ERD, folder: pathlib.Path, slug: str, render: bool):
    """Chen-notation ERD. Uses Graphviz (neato) for a radial layout when present,
    otherwise a built-in pure-Python circular layout, so it ALWAYS renders."""
    folder.mkdir(parents=True, exist_ok=True)
    xml = build_erd_chen_drawio(erd)
    (folder / f"{slug}.drawio.xml").write_text(xml, encoding="utf-8")
    png = folder / f"{slug}.png"
    if render:
        from babiv_diagrams.render import render_xml
        render_xml(xml, str(png))
        eng = "layout konsentris (seimbang, editable di draw.io)"
        print(f"    -> {png.relative_to(folder.parent.parent)}  [{eng}]")
    return str(png)


def emit_mockups(spec, assets: pathlib.Path, render: bool):
    """Render lite wireframes for every 4.4 antarmuka item that declares a
    'mockup' component list, and set item['image'] to the rendered PNG."""
    items = spec.get("antarmuka") or []
    have_any = any(it.get("mockup") for it in items)
    if not have_any:
        return
    folder = assets / "mockup 4.4"
    folder.mkdir(parents=True, exist_ok=True)
    from babiv_diagrams import mockup as mk
    for i, it in enumerate(items):
        m = it.get("mockup")
        if not m:
            continue
        slug = _slug(it.get("title", f"mockup_{i+1}"))
        png = folder / f"{slug}.png"
        if render:
            spec_m = m if isinstance(m, dict) else {"components": m}
            try:
                mk.render_mockup(spec_m, str(png), width=int(it.get("mockup_width", 1100)))
                print(f"    -> {png.relative_to(assets.parent)}")
            except Exception as exc:
                print(f"    ! gagal render mockup '{slug}': {exc}")
                continue
        it["image"] = str(png)


# --------------------------------------------------------------------------- #
# main
# --------------------------------------------------------------------------- #
def main():
    ap = argparse.ArgumentParser(description="Build BAB IV diagram + docx asset pack from spec.json")
    ap.add_argument("spec", help="path to spec.json")
    ap.add_argument("--out", default="./BabIvOutput", help="output directory")
    ap.add_argument("--no-render", action="store_true", help="skip PNG rendering (xml/graphml only)")
    ap.add_argument("--no-docx", action="store_true", help="skip the Word document")
    args = ap.parse_args()

    spec = json.loads(pathlib.Path(args.spec).read_text(encoding="utf-8"))
    render = not args.no_render

    out = pathlib.Path(args.out)
    assets = out / "BabIvAssets"
    assets.mkdir(parents=True, exist_ok=True)
    print(f"Sistem : {spec['system_name']}")
    print(f"Output : {assets}")

    figures = {}

    # context
    print("[1/5] Diagram Konteks")
    ctx = build_context(spec)
    validate_balance(ctx, "Diagram Konteks")
    figures["context"] = emit_dfd(ctx, "context", assets / "context", "diagram_konteks", render)

    # level 0
    print("[2/5] DFD Level 0")
    lv0 = build_level0(spec)
    validate_balance(lv0, "DFD Level 0")
    figures["level0"] = emit_dfd(lv0, "level", assets / "dfd 0", "dfd_level_0", render)

    # level 1 (per decomposed process)
    print("[3/5] DFD Level 1")
    figures["level1"] = {}
    level1 = spec.get("level1", []) or []
    if len(level1) > MAX_PROCESSES:
        print(f"  ! WARNING: {len(level1)} diagram Level 1 (>{MAX_PROCESSES}).")
    for entry in level1:
        parent_no = str(entry.get("parent_no", "")).strip()
        k = parent_no.split(".")[0] or "x"        # "1.0" -> "1"
        folder = assets / f"dfd 1.{k}"
        dfd = build_level1(spec, entry)
        # Level 1 decomposes ONE process: a store may legitimately be read-only or
        # write-only here, so only processes are required to be balanced. Strict
        # store/external balance is enforced at Context + Level 0 above.
        validate_balance(dfd, f"DFD Level 1 (proses {parent_no})", processes_only=True)
        slug = f"dfd_level_1_proses_{k}"
        path = emit_dfd(dfd, "level", folder, slug, render)
        # find parent process name for nicer captions
        pname = ""
        for p in spec.get("level0", {}).get("processes", []):
            if str(p["no"]) == parent_no:
                pname = p["name"]; break
        figures["level1"][parent_no] = {"path": path, "name": pname}

    # erd
    print("[4/5] ERD")
    erd = build_erd(spec)
    figures["erd"] = emit_erd(erd, assets / "erd", "erd", render)
    if spec.get("erd", {}).get("chen", True):
        chen = emit_erd_chen(erd, assets / "erd", "erd_chen", render)
        if chen:
            figures["erd_chen"] = chen

    # 4.4 wireframe mockups (lite) -> sets item['image'] on antarmuka items
    emit_mockups(spec, assets, render)

    # 4.6 SUS (optional): compute from xlsx referenced in spec
    sus_data = None
    sus_cfg = spec.get("sus") or {}
    sus_path = sus_cfg.get("xlsx")
    if sus_path:
        p = pathlib.Path(sus_path)
        if not p.is_absolute():
            p = pathlib.Path(args.spec).resolve().parent / sus_path
        if p.exists():
            try:
                from babiv_diagrams import sus as sus_mod
                sus_data = sus_mod.compute_sus(str(p))
                print(f"  SUS: {sus_data['n']} responden, rata-rata "
                      f"{sus_data['mean']} (grade {sus_data['grade']})")
            except Exception as exc:
                print(f"  ! gagal hitung SUS ({exc}); 4.6 dilewati.")
        else:
            print(f"  ! file SUS tidak ditemukan: {p}")

    # docx
    if not args.no_docx:
        print("[5/5] BAB_IV.docx")
        try:
            from babiv_docx import BabIvDoc
        except Exception as exc:
            print(f"  ! python-docx tidak tersedia ({exc}); lewati docx.")
        else:
            doc = BabIvDoc(
                chapter=int(spec.get("chapter", 4)),
                bab_title=spec.get("bab_title", "Hasil dan Pembahasan"),
                system_name=spec["system_name"],
            )
            doc.build_from_spec(spec, figures if render else {}, sus_data=sus_data)
            docx_path = assets / "BAB_IV.docx"
            doc.save(str(docx_path))
            print(f"    -> {docx_path.relative_to(out)}")
    else:
        print("[5/5] BAB_IV.docx  (dilewati: --no-docx)")

    _write_readme(assets, spec, figures)
    print("\nSelesai. Buka folder:", assets)


def _write_readme(assets, spec, figures):
    n1 = len(figures.get("level1", {}))
    lines = [
        f"BabIvAssets — {spec['system_name']}",
        "=" * 60,
        "",
        "Struktur:",
        "  context/        Diagram Konteks (proses tunggal '0', input/output 1:1)",
        "  dfd 0/          DFD Level 0 (proses utama 1.0, 2.0, ...)",
        "  dfd 1.X/        DFD Level 1 yang mendekomposisi Proses X.0",
        f"                  ({n1} diagram level 1 dibuat)",
        "  erd/            erd.png = ERD crow's foot (konseptual basis data)",
        "                  erd_chen.png = ERD notasi Chen (persegi/elips/belah ketupat)",
        "  mockup 4.4/     wireframe lite untuk 4.4 (LOGO teks, kotak gambar bersilang X)",
        "  BAB_IV.docx     SEMUA bagian dalam SATU dokumen: 4.1 Analisis, 4.2 DFD,",
        "                  4.3 ERD/Basis Data, 4.4 Antarmuka (wireframe),",
        "                  4.5 Implementasi (screenshot), 4.6 Pengujian (SUS).",
        "",
        "Tiap folder diagram berisi: *.drawio.xml (draw.io), *.graphml (yEd), *.png.",
        "",
        "Catatan:",
        "  - DFD ortogonal; ERD ditata graphviz (minim persilangan, tak menembus kotak).",
        "  - data_xxx = aliran masuk (input), info_xxx = aliran keluar (output).",
        "  - 4.4 = wireframe (data dummy, bukan DB); 4.5 = screenshot sistem asli.",
        "  - Regenerasi semua: jalankan build_babiv_assets.py lagi.",
        "",
        "Buka BAB_IV.docx di Word, lalu tekan Ctrl+A kemudian F9 untuk",
        "memperbarui semua nomor (heading & caption) bila perlu.",
    ]
    (assets / "README.txt").write_text("\n".join(lines), encoding="utf-8")


if __name__ == "__main__":
    main()
