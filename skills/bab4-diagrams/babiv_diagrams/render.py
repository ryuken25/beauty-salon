"""
Render a .drawio.xml (or raw mxGraphModel/mxfile string) to a PNG using the
bundled draw.io viewer driven by Playwright.

Why this exists: the model cannot "see" coordinates. Rendering with the REAL
draw.io engine means orthogonal routing, arc line-jumps, crow's foot markers
and edge-label placement come out exactly as draw.io would draw them, and you
get a PNG you can actually look at and drop into the .docx.

Offline: the viewer JS is vendored in ../vendor/viewer-static.min.js, so no
network is required at render time.

CLI:
    python -m babiv_diagrams.render in.drawio.xml out.png [--scale 2.5] [--pad 24]
"""

from __future__ import annotations

import argparse
import base64
import pathlib
import sys

_HERE = pathlib.Path(__file__).resolve().parent
_VENDOR = _HERE.parent / "vendor" / "viewer-static.min.js"

_HTML = """<!DOCTYPE html><html><head><meta charset="utf-8">
<style>html,body{margin:0;padding:0;background:#ffffff;}
div.mxgraph{background:#ffffff;}
div.mxgraph svg{background:#ffffff !important;}
svg{background:#ffffff;}</style>
<script>
window.STYLE_PATH='.';window.SHAPES_PATH='.';window.STENCIL_PATH='.';
window.PROXY_URL='.';window.mxLoadStylesheets=false;window.mxLoadResources=false;
</script>
<script>__VIEWER__</script>
</head><body>
<div class="mxgraph" id="g"></div>
<script>
  var xml = decodeURIComponent(escape(atob("__B64__")));
  var cfg = {"highlight":"none","nav":false,"resize":true,"toolbar":null,
             "border":8,"xml":xml};
  var el = document.getElementById("g");
  el.setAttribute("data-mxgraph", JSON.stringify(cfg));
  try { GraphViewer.processElements(); window.__ok=true; }
  catch(e){ window.__err=String(e); }
</script>
</body></html>"""


def render_xml(xml: str, out_png: str, scale: float = 2.5,
               pad: int = 24, timeout_ms: int = 4000) -> str:
    """Render mxGraph/mxfile XML to a tightly-cropped PNG. Returns out_png."""
    try:
        from playwright.sync_api import sync_playwright
    except Exception as exc:  # pragma: no cover
        raise SystemExit("Playwright not installed. Run:\n"
                         "  pip install playwright --break-system-packages\n"
                         "  python -m playwright install chromium") from exc

    if not _VENDOR.exists():
        raise SystemExit(f"Bundled viewer not found at {_VENDOR}")

    viewer = _VENDOR.read_text(encoding="utf-8", errors="ignore")
    b64 = base64.b64encode(xml.encode("utf-8")).decode("ascii")
    html_doc = _HTML.replace("__VIEWER__", viewer).replace("__B64__", b64)

    tmp = pathlib.Path(out_png).with_suffix(".render.html")
    tmp.write_text(html_doc, encoding="utf-8")

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(args=["--no-sandbox",
                                              "--force-color-profile=srgb"])
            page = browser.new_page(viewport={"width": 1600, "height": 1200},
                                    device_scale_factor=scale)
            page.goto("file://" + str(tmp.resolve()))
            page.wait_for_selector("div.mxgraph svg", timeout=timeout_ms)
            page.wait_for_timeout(400)
            err = page.evaluate("window.__err || null")
            if err:
                raise RuntimeError("Viewer error: " + str(err))
            # Force a solid white background directly on the SVG so the element
            # screenshot is never transparent (independent of PIL / viewer theme).
            page.evaluate(
                "()=>{var s=document.querySelector('div.mxgraph svg');"
                "if(s){s.style.background='#ffffff';"
                "var r=document.createElementNS('http://www.w3.org/2000/svg','rect');"
                "r.setAttribute('x','-100000');r.setAttribute('y','-100000');"
                "r.setAttribute('width','400000');r.setAttribute('height','400000');"
                "r.setAttribute('fill','#ffffff');s.insertBefore(r,s.firstChild);}}")
            page.wait_for_timeout(80)
            # measure SVG content box, add padding, screenshot a clip
            box = page.evaluate(
                "()=>{var s=document.querySelector('div.mxgraph svg');"
                "var r=s.getBoundingClientRect();"
                "return {x:r.x,y:r.y,w:r.width,h:r.height};}")
            page.evaluate(f"document.body.style.padding='{pad}px';")
            page.wait_for_timeout(150)
            el = page.query_selector("div.mxgraph svg")
            el.screenshot(path=out_png)
            browser.close()
    finally:
        try:
            tmp.unlink()
        except OSError:
            pass

    _add_white_padding(out_png, pad)
    return out_png


def _add_white_padding(png_path: str, pad: int) -> None:
    """Flatten onto white and add an even white margin (clean print look)."""
    try:
        from PIL import Image
    except Exception:
        return
    im = Image.open(png_path).convert("RGBA")
    bg = Image.new("RGBA", im.size, (255, 255, 255, 255))
    bg.alpha_composite(im)
    im = bg.convert("RGB")
    w, h = im.size
    canvas = Image.new("RGB", (w + 2 * pad, h + 2 * pad), (255, 255, 255))
    canvas.paste(im, (pad, pad))
    canvas.save(png_path, "PNG")


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Render a .drawio.xml to PNG (offline).")
    ap.add_argument("infile")
    ap.add_argument("outfile")
    ap.add_argument("--scale", type=float, default=2.5,
                    help="device scale factor (DPI multiplier). 2.5 ~ 240dpi.")
    ap.add_argument("--pad", type=int, default=24, help="white margin in px")
    args = ap.parse_args(argv)
    xml = pathlib.Path(args.infile).read_text(encoding="utf-8")
    render_xml(xml, args.outfile, scale=args.scale, pad=args.pad)
    print(f"rendered -> {args.outfile}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
