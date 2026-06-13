#!/usr/bin/env python3
"""
verify_diagrams.py — automatic QUALITY/BALANCE check for the rendered diagrams.

Why this exists
---------------
The agent cannot reliably "eyeball" balance, and the classic failure was an ERD
(especially the Chen notation) with everything crammed into one corner and a big
empty quadrant — the "kok kanan atas kosong" complaint. This script opens every
rendered PNG and measures, objectively:

  * fill ratio        — how much of the canvas actually has ink (too low = sparse
                        layout marooned in a sea of white),
  * quadrant balance  — ink spread across the 4 quadrants (one near-empty quadrant
                        while others are dense = unbalanced),
  * content centring  — whether the drawing hugs one edge.

It prints a PASS/WARN line per diagram and a summary. Pair it with Playwright
screenshots of the .docx pages if you also want to eyeball the final document.
Pillow only — no network, no Graphviz needed.

Usage
-----
    python verify_diagrams.py /path/to/BabIvAssets
    python verify_diagrams.py /path/to/BabIvAssets --strict   # non-zero exit on WARN
"""

from __future__ import annotations

import argparse
import pathlib
import sys


def _analyse(path: pathlib.Path, ink_thresh: int = 245):
    """Return metrics dict for one PNG (ink = pixels darker than ink_thresh)."""
    from PIL import Image
    im = Image.open(path).convert("L")
    w, h = im.size
    px = im.load()
    # downsample stride for speed on big canvases
    step = max(1, min(w, h) // 600)
    cols = [0, 0]
    quad = [0, 0, 0, 0]   # TL, TR, BL, BR
    ink = 0
    minx, miny, maxx, maxy = w, h, 0, 0
    samples = 0
    for y in range(0, h, step):
        for x in range(0, w, step):
            samples += 1
            if px[x, y] < ink_thresh:
                ink += 1
                if x < minx: minx = x
                if y < miny: miny = y
                if x > maxx: maxx = x
                if y > maxy: maxy = y
                top = y < h / 2
                left = x < w / 2
                quad[(0 if top else 2) + (0 if left else 1)] += 1
    fill = ink / samples if samples else 0.0
    if ink == 0:
        return {"empty": True, "fill": 0.0}
    bbox_w = (maxx - minx) / w
    bbox_h = (maxy - miny) / h
    bbox_fill = ink / max(1, ((maxx - minx) // step + 1) * ((maxy - miny) // step + 1))
    qsum = sum(quad) or 1
    qfrac = [q / qsum for q in quad]
    return {
        "empty": False, "w": w, "h": h, "fill": fill,
        "bbox_w": bbox_w, "bbox_h": bbox_h, "bbox_fill": bbox_fill,
        "quad": qfrac, "aspect": w / h,
    }


QUAD_NAMES = ["kiri-atas", "kanan-atas", "kiri-bawah", "kanan-bawah"]


def _check(name: str, m: dict):
    warns = []
    low = name.lower().replace("\\", "/")
    is_mockup = "mockup" in low
    is_erd = "/erd" in low or low.startswith("erd")
    if m.get("empty"):
        return ["GAMBAR KOSONG (tidak ada garis terdeteksi) — render gagal?"]
    if is_mockup:
        # wireframes are intentionally form-shaped (a login page is mostly a
        # centred card) — only flag a totally blank render.
        return warns
    # content hugging one side -> big empty band on the opposite side
    # (loose for DFD/context: a single store legitimately sits in one corner;
    #  strict for ERD, where lopsided layout was the actual complaint)
    w_floor = 0.55 if is_erd else 0.4
    h_floor = 0.5 if is_erd else 0.35
    if m["bbox_w"] < w_floor:
        warns.append(f"konten hanya mengisi {m['bbox_w']*100:.0f}% lebar kanvas "
                     "(banyak ruang kosong kiri/kanan)")
    if m["bbox_h"] < h_floor:
        warns.append(f"konten hanya mengisi {m['bbox_h']*100:.0f}% tinggi kanvas "
                     "(banyak ruang kosong atas/bawah)")
    # one quadrant nearly empty while the rest is dense — ERD only (a DFD with a
    # lone data store in one corner naturally leaves the opposite corner light).
    if is_erd:
        q = m["quad"]
        weakest = min(range(4), key=lambda i: q[i])
        if q[weakest] < 0.06 and max(q) > 0.32:
            warns.append(f"kuadran {QUAD_NAMES[weakest]} nyaris kosong "
                         f"({q[weakest]*100:.0f}% tinta) — diagram tidak seimbang")
    return warns


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Periksa keseimbangan/kualitas PNG diagram.")
    ap.add_argument("assets", help="folder BabIvAssets (atau folder berisi *.png)")
    ap.add_argument("--strict", action="store_true",
                    help="keluar dengan kode !=0 bila ada peringatan")
    args = ap.parse_args(argv)

    try:
        from PIL import Image  # noqa: F401
    except Exception:
        print("Pillow belum terpasang: pip install pillow --break-system-packages")
        return 2

    root = pathlib.Path(args.assets)
    pngs = sorted(root.rglob("*.png"))
    if not pngs:
        print(f"Tidak ada PNG di {root}")
        return 2

    total_warn = 0
    print(f"Memeriksa {len(pngs)} diagram di {root}\n" + "=" * 60)
    for p in pngs:
        rel = p.relative_to(root)
        try:
            m = _analyse(p)
        except Exception as exc:
            print(f"  ! {rel}: gagal dibaca ({exc})")
            total_warn += 1
            continue
        warns = _check(str(rel), m)
        if warns:
            total_warn += len(warns)
            print(f"  \u26a0 {rel}")
            for w in warns:
                print(f"      - {w}")
        else:
            fill = m["fill"] * 100
            print(f"  \u2713 {rel}  (isi kanvas {m['bbox_w']*100:.0f}%×"
                  f"{m['bbox_h']*100:.0f}%, tinta {fill:.1f}%)")
    print("=" * 60)
    if total_warn:
        print(f"{total_warn} peringatan. Perbaiki spec.json "
              "(pecah/seimbangkan aliran, atau pakai graphviz untuk ERD) lalu "
              "render ulang.")
    else:
        print("Semua diagram lolos pemeriksaan keseimbangan dasar.")
    return 1 if (total_warn and args.strict) else 0


if __name__ == "__main__":
    sys.exit(main())
