"""
mockup — render lightweight, grayscale UI wireframes (for BAB IV section 4.4
"Perancangan Antarmuka") and save them as PNG using Playwright.

Why: section 4.4 should show the *design* of each screen, not a live
screenshot. So we draw a clean low-fidelity wireframe:
  * image / photo / logo placeholders = outlined box with a diagonal cross
  * NO database data -- dummy rows like "Siswa A", "Siswa B"
  * light borders, neutral greys, system sans-serif

You pass a small declarative spec (a dict). Supported component types:

  {"type":"navbar",  "logo":true, "title":"E-Rapor", "menu":["Dashboard","Nilai"]}
  {"type":"sidebar", "items":["Dashboard","Data Siswa","Nilai","Logout"]}
  {"type":"heading", "text":"Data Siswa"}
  {"type":"text",    "text":"Keterangan singkat ..."}
  {"type":"form",    "title":"Form Login",
                     "fields":[{"label":"Username"},{"label":"Password","type":"password"}],
                     "submit":"Masuk"}
  {"type":"table",   "title":"Daftar Siswa",
                     "columns":["No","Nama","Kelas","Aksi"],
                     "rows":[["1","Siswa A","X-1","Edit | Hapus"], ...]}   # or "dummy_rows":3
  {"type":"image",   "label":"Foto Siswa", "w":180, "h":120}
  {"type":"cards",   "items":[{"title":"Total Siswa","value":"120"}, ...]}
  {"type":"buttons", "items":["Simpan","Batal"]}

Layout: a top navbar (if any) spans full width; a sidebar (if any) sits on the
left; everything else stacks in the main content area.
"""

from __future__ import annotations

import html as _html
from typing import Any, Dict, List

_CSS = """
* { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
body { margin:0; background:#ffffff; color:#333; }
#wrap { width: WIDTHpx; background:#fff; border:1px solid #c9c9c9; }
.nav { display:flex; align-items:center; gap:16px; padding:10px 16px;
       border-bottom:1px solid #c9c9c9; background:#f3f3f3; }
.nav .brand { font-weight:bold; font-size:15px; }
.nav .menu { margin-left:auto; display:flex; gap:18px; color:#555; font-size:13px; }
.body { display:flex; min-height: 320px; }
.side { width:180px; border-right:1px solid #c9c9c9; background:#fafafa; padding:10px 0; }
.side div { padding:8px 16px; font-size:13px; color:#444; border-bottom:1px solid #eee; }
.side div.active { background:#e8e8e8; font-weight:bold; }
.main { flex:1; padding:18px 20px; }
h2.mk { font-size:18px; margin:0 0 10px; }
p.mk { font-size:13px; color:#555; margin:0 0 14px; line-height:1.5; }
.ph { border:1px solid #9a9a9a;
      background:
        linear-gradient(to top right, transparent calc(50% - 1px), #9a9a9a calc(50% - 1px),
                        #9a9a9a calc(50% + 1px), transparent calc(50% + 1px)),
        linear-gradient(to top left, transparent calc(50% - 1px), #9a9a9a calc(50% - 1px),
                        #9a9a9a calc(50% + 1px), transparent calc(50% + 1px));
      display:flex; align-items:center; justify-content:center;
      color:#777; font-size:12px; }
.ph span { background:#fff; padding:2px 6px; }
.logo { width:90px; height:34px; }
.form { max-width:380px; }
.fld { margin-bottom:12px; }
.fld label { display:block; font-size:12px; color:#555; margin-bottom:4px; }
.fld .inp { height:34px; border:1px solid #b3b3b3; border-radius:3px; background:#fff; }
.btn { display:inline-block; padding:8px 16px; border:1px solid #777; border-radius:3px;
       background:#ececec; font-size:13px; color:#333; margin-right:8px; }
.btn.primary { background:#d9d9d9; font-weight:bold; }
table.mk { width:100%; border-collapse:collapse; font-size:13px; }
table.mk th, table.mk td { border:1px solid #c4c4c4; padding:7px 9px; text-align:left; }
table.mk th { background:#ededed; }
.cards { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.card { flex:1; min-width:130px; border:1px solid #c4c4c4; border-radius:4px; padding:12px 14px; }
.card .t { font-size:12px; color:#666; }
.card .v { font-size:22px; font-weight:bold; margin-top:4px; }
.row { margin-bottom:16px; }
""".strip()

_DUMMY_NAMES = ["Siswa A", "Siswa B", "Siswa C", "Siswa D", "Siswa E"]


def _esc(s: Any) -> str:
    return _html.escape(str(s))


def _ph(label: str, w=None, h=120, cls="") -> str:
    style = ""
    if w:
        style += f"width:{w}px;"
    style += f"height:{h}px;"
    return f'<div class="ph {cls}" style="{style}"><span>{_esc(label)}</span></div>'


def _component(c: Dict[str, Any]) -> str:
    t = c.get("type")
    if t == "heading":
        return f'<h2 class="mk">{_esc(c.get("text",""))}</h2>'
    if t == "text":
        return f'<p class="mk">{_esc(c.get("text",""))}</p>'
    if t == "image":
        return ('<div class="row">'
                + _ph(c.get("label", "Gambar"), c.get("w"), c.get("h", 120)) + '</div>')
    if t == "cards":
        cards = "".join(
            f'<div class="card"><div class="t">{_esc(i.get("title",""))}</div>'
            f'<div class="v">{_esc(i.get("value",""))}</div></div>'
            for i in c.get("items", []))
        return f'<div class="cards">{cards}</div>'
    if t == "buttons":
        btns = "".join(
            f'<span class="btn {"primary" if k==0 else ""}">{_esc(b)}</span>'
            for k, b in enumerate(c.get("items", [])))
        return f'<div class="row">{btns}</div>'
    if t == "form":
        flds = []
        for f in c.get("fields", []):
            flds.append(f'<div class="fld"><label>{_esc(f.get("label",""))}</label>'
                        f'<div class="inp"></div></div>')
        submit = c.get("submit")
        btn = f'<span class="btn primary">{_esc(submit)}</span>' if submit else ""
        title = f'<h2 class="mk">{_esc(c["title"])}</h2>' if c.get("title") else ""
        return f'{title}<div class="form">{"".join(flds)}{btn}</div>'
    if t == "table":
        cols = c.get("columns", [])
        rows = c.get("rows")
        if not rows:
            dn = int(c.get("dummy_rows", 3))
            rows = []
            for i in range(dn):
                row = []
                for j, col in enumerate(cols):
                    cl = col.lower()
                    if j == 0 and cl in ("no", "#"):
                        row.append(str(i + 1))
                    elif "nama" in cl or "siswa" in cl:
                        row.append(_DUMMY_NAMES[i % len(_DUMMY_NAMES)])
                    elif "aksi" in cl:
                        row.append("Edit | Hapus")
                    else:
                        row.append("...")
                rows.append(row)
        th = "".join(f"<th>{_esc(x)}</th>" for x in cols)
        trs = "".join("<tr>" + "".join(f"<td>{_esc(v)}</td>" for v in r) + "</tr>"
                      for r in rows)
        title = f'<h2 class="mk">{_esc(c["title"])}</h2>' if c.get("title") else ""
        return f'{title}<table class="mk"><thead><tr>{th}</tr></thead><tbody>{trs}</tbody></table>'
    return ""


def build_html(spec: Dict[str, Any], width: int = 1100) -> str:
    comps: List[Dict[str, Any]] = list(spec.get("components", []))
    navbar = next((c for c in comps if c.get("type") == "navbar"), None)
    sidebar = next((c for c in comps if c.get("type") == "sidebar"), None)
    body_comps = [c for c in comps if c.get("type") not in ("navbar", "sidebar")]

    nav_html = ""
    if navbar:
        logo = _ph("LOGO", cls="logo", h=34) if navbar.get("logo") else ""
        brand = f'<span class="brand">{_esc(navbar.get("title",""))}</span>'
        menu = "".join(f"<span>{_esc(m)}</span>" for m in navbar.get("menu", []))
        nav_html = (f'<div class="nav">{logo}{brand}'
                    f'<span class="menu">{menu}</span></div>')

    side_html = ""
    if sidebar:
        items = sidebar.get("items", [])
        side_html = '<div class="side">' + "".join(
            f'<div class="{"active" if k==0 else ""}">{_esc(it)}</div>'
            for k, it in enumerate(items)) + '</div>'

    main_html = '<div class="main">' + "".join(_component(c) for c in body_comps) + '</div>'
    css = _CSS.replace("WIDTH", str(width))
    return (f'<!DOCTYPE html><html><head><meta charset="utf-8"><style>{css}</style>'
            f'</head><body><div id="wrap">{nav_html}'
            f'<div class="body">{side_html}{main_html}</div></div></body></html>')


def render_mockup(spec: Dict[str, Any], out_png: str, width: int = 1100,
                  timeout_ms: int = 8000) -> str:
    """Render a wireframe spec to PNG (the #wrap element bounds)."""
    try:
        from playwright.sync_api import sync_playwright
    except Exception as exc:  # pragma: no cover
        raise SystemExit("Playwright not installed. Run:\n"
                         "  pip install playwright --break-system-packages\n"
                         "  python -m playwright install chromium") from exc
    html_str = build_html(spec, width)
    with sync_playwright() as p:
        browser = p.chromium.launch(args=["--no-sandbox", "--force-color-profile=srgb"])
        page = browser.new_page(viewport={"width": width + 40, "height": 800},
                                device_scale_factor=2)
        page.set_content(html_str, wait_until="networkidle", timeout=timeout_ms)
        el = page.query_selector("#wrap")
        (el or page).screenshot(path=out_png)
        browser.close()
    return out_png
