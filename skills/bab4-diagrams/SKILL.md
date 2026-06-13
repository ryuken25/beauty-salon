---
name: bab4-diagrams
description: >-
  Generate a clean, print-ready BAB IV (skripsi chapter 4) asset pack for an
  information-system project: Diagram Konteks, DFD Level 0, DFD Level 1 per
  process, a crow's-foot ERD, and a properly formatted Word document
  (BAB_IV.docx) with auto-numbered headings and SEQ caption fields. Use this
  whenever the user asks to build/redo BAB IV, DFD, ERD, "diagram alur data",
  "diagram konteks", or a chapter-4 Word document for a CodeIgniter/Laravel/PHP
  thesis. You describe the LOGICAL structure in spec.json; the toolkit computes
  orthogonal, non-overlapping layouts and renders verifiable PNGs with a bundled
  offline draw.io engine. Never hand-write diagram XML coordinates or type
  figure/section numbers.
---

# bab4-diagrams — DFD/ERD + BAB IV Word generator

## What this does

You give it a **logical** `spec.json` (actors, processes, data stores, typed
flows, entities, relations, UI mockups, SUS data). It produces:

```
BabIvAssets/
  context/   dfd 0/   dfd 1.X/   erd/      # each: .drawio.xml + .graphml + .png
  erd/erd.png        crow's-foot ERD       erd/erd_chen.png   Chen-notation ERD
  mockup 4.4/                              # lite wireframes for section 4.4 (auto)
  BAB_IV.docx                              # ALL sections 4.1-4.7 in ONE document
  README.txt
```

Node positions are computed deterministically (Graphviz used if present, but
fully optional) so diagrams come out with minimal crossings. DFDs use the
Indonesian-skripsi **store-above-process** layout: processes in a central column,
each data store sitting directly **above the process that owns it** (short
read+write arrows), and external entities as **tall boxes on the left/right**.
Edges stay **orthogonal** with **arc line-jumps**, and arrows may enter an
external from any side; labels never overlap. ERD ships in
**crow's foot** (balanced grid + orthogonal channel routing, no line cutting
through boxes) and **Chen** (concentric, balanced — no empty quadrant) notations.
Section 4.4 renders **lite grayscale wireframes** (LOGO text, image placeholders
crossed with an X, dummy data — no DB); 4.5 embeds **real screenshots**; **4.6 is
Black Box testing** (one scenario table per feature); **4.7 is SUS** (questionnaire
table + per-respondent converted-score table + interpretation, auto-computed from
an xlsx). Table cells are flush-left (no stray first-line indent). The Word
document uses real heading styles bound to a multilevel list (so "4.1", "4.1.1", …
are generated) and **Word SEQ fields** for captions (so "Gambar 4.x"/"Tabel 4.x"
never gap). Helper scripts: `capture_screenshots.py` (Playwright capture for 4.5)
and `verify_diagrams.py` (balance/quality check of every rendered PNG).

**Every diagram is editable.** Alongside each `.png`, the generator writes a
`.drawio.xml` (and `.graphml` for yEd). Open the `.drawio.xml` at
https://app.diagrams.net (or draw.io desktop) to nudge any box/edge/label by
hand, then re-export PNG. Graphviz only affects ERD layout — DFDs always use the
built-in draw.io engine and stay fully editable in draw.io.

## When to use

- "buatkan / perbaiki BAB IV", "DFD level 0/1", "diagram konteks", "ERD",
  "struktur tabel", "bab 4 word".
- Redoing a messy chapter-4 where figure numbers gapped or diagrams overlapped.

## Install (once)

```bash
pip install python-docx playwright openpyxl pillow --break-system-packages
python -m playwright install chromium
# OPTIONAL (slightly tidier DFD-level layout): graphviz
#   Linux:   sudo apt-get install -y graphviz
#   Windows: winget install graphviz
```

The draw.io viewer JS is **vendored offline** at `vendor/viewer-static.min.js`,
so rendering needs **no network**. **Graphviz is OPTIONAL** — every diagram,
including the **Chen ERD** (built-in concentric layout) and DFDs (built-in
orthogonal draw.io layout with arc line-jumps), renders fully without it. DFDs
always render via the draw.io engine (cleaner per-track labels than Graphviz
`splines=ortho`); when `dot`/`neato` are present they only tidy the ERD layout,
otherwise the built-in balanced layouts are used and nothing is skipped.
`openpyxl` powers the 4.7 SUS computation; **`pillow`** flattens rendered PNGs
onto a guaranteed white background (without it the engine still forces white via
the SVG itself).

## Workflow

1. **Audit the real source code first** (CI4: `app/Config/Routes.php`,
   `app/Controllers`, `app/Models`, migrations, `app/Views`). Derive actors,
   processes (≤ 8 per level), stores/tables, entities + PK/FK, relations.
2. **Copy `spec.example.json`** and replace its contents. Full field reference:
   `spec.schema.md`.
3. **Generate**:
   ```bash
   python build_babiv_assets.py /path/to/spec.json --out ./out
   ```
   Flags: `--no-docx` (diagrams only), `--no-render` (xml/graphml only).
4. **Verify by looking at the PNGs** and the docx. If something is off, fix the
   **spec** and re-run — do not edit coordinates by hand.

## Core rules (do not break)

- **No hand-written diagram XML / coordinates.** Fill `spec.json`; the layout
  engine (`babiv_diagrams/drawio.py` + `gvlayout.py`) does the geometry.
- **No manually typed figure/table/section numbers.** `babiv_docx.py` handles
  numbering via heading styles + SEQ fields.
- **Flow labels:** input → `data_xxx`, output → `info_xxx` (snake_case, no spaces).
- **Every node has flows IN and OUT.** Each external, process, and data store
  must have ≥1 incoming AND ≥1 outgoing flow (no input-only / output-only).
  The generator prints a balance WARNING listing offenders.
- **Decompose every major Level-0 process into Level 1** (one `level1` entry
  each) so folders `dfd 1.1, 1.2, 1.3, …` all appear — don't do only some.
- **ERD uses crow's foot** via cardinality keywords `one`/`many`/`mandone`/
  `zeromany` — never literal "1"/"N" text. Set `"chen": true` to also emit a
  Chen-notation ERD (`erd_chen.png`).
- **Max 8 processes per level** (DFD 7±2). Merge if more.
- **Section 4.4 = lite wireframe mockups** (`antarmuka[].mockup`), not real
  screenshots: LOGO text, image boxes crossed with X, dummy data, no DB.
- **Section 4.5 = real screenshots** (`implementasi[].image`) of **every** page
  (use `capture_screenshots.py`; the count is the real page count, never invented).
- **Section 4.6 = Black Box testing** (`pengujian.blackbox`): one table per feature
  (No | Data Input | Hasil yang Diharapkan | Hasil Pengamatan | Kesimpulan).
- **Section 4.7 = SUS** auto-computed from `sus.xlsx` (questionnaire + per-respondent
  converted-score table + interpretation).
- **Everything lands in ONE `BAB_IV.docx`** (4.1–4.7); no page left undocumented.

## Editing a diagram by hand (cosmetic only) and re-rendering

The AI cannot "see" XML, so always **render to PNG and look** after any manual
tweak. The `.drawio.xml` files are plain mxGraph and open in
https://app.diagrams.net. To re-render after editing:

```bash
python -m babiv_diagrams.render path/to/file.drawio.xml path/to/file.png
# options: --scale 2.5 --pad 24
```

### Rendering gotcha (important if you call the viewer yourself)

`render.py` feeds the XML to the bundled GraphViewer by **base64-encoding it and
injecting via JavaScript `setAttribute`** — *not* as a raw HTML attribute.
Putting raw XML in the `data-mxgraph` attribute breaks on quotes/newlines
("Bad control character"). A `403` from an external stylesheet during render is
harmless and ignored (`mxLoadStylesheets=false`). Keep using `render_xml()` and
this is handled for you.

## Programmatic use (optional)

```python
from babiv_diagrams.model import DFD, ExternalEntity, Process, DataStore, Flow
from babiv_diagrams.drawio import build_dfd_drawio
from babiv_diagrams.render import render_xml

dfd = DFD(title="DFD Level 0", externals=[...], processes=[...],
          stores=[...], flows=[Flow("owner","p1","data_login_owner"), ...])
xml = build_dfd_drawio(dfd, "level")        # kind: "context" or "level"
render_xml(xml, "level0.png")
```

ERD: `from babiv_diagrams.drawio import build_erd_drawio, build_erd_chen_drawio`
with `ERD`, `Entity`, `Relation` from `babiv_diagrams.model`. SUS:
`from babiv_diagrams.sus import compute_sus`. Wireframes:
`from babiv_diagrams.mockup import render_mockup`. yEd output:
`build_dfd_graphml` / `build_erd_graphml` in `babiv_diagrams.graphml`.

## Files

```
SKILL.md                 this file
build_babiv_assets.py    orchestrator: spec.json -> BabIvAssets/
babiv_docx.py            Word generator (heading styles + SEQ captions, 4.1-4.7)
capture_screenshots.py   Playwright capture of running app pages -> shots/ (for 4.5)
verify_diagrams.py       balance/quality check of every rendered PNG (Pillow)
requirements.txt         python deps
spec.example.json        worked example (ChelisNet)
babiv_diagrams/          layout + render library
  model.py drawio.py graphml.py render.py __init__.py
  gvlayout.py            Graphviz layout helper (positions + edge waypoints)
  sus.py                 System Usability Scale computation (xlsx -> 4.7)
  mockup.py              lite wireframe generator (4.4), rendered via Playwright
vendor/viewer-static.min.js   offline draw.io viewer (no network at render)
```
