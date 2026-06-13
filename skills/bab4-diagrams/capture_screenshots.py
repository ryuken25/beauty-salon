#!/usr/bin/env python3
"""
capture_screenshots.py — capture REAL screenshots of the running app for BAB IV
section 4.5 (Implementasi), using Playwright. This is the honest alternative to
claiming "29 screenshot" that never exist: it actually opens each page of the
running system and saves a PNG you can embed.

How to use
----------
1. Run your app locally, e.g. CodeIgniter 4:
       php spark serve            # http://localhost:8080
   (or)  php -S localhost:8080 -t public

2. Write a small `shots.json` describing the login (optional) and the pages.
   A starter file is written for you with:  --init

       python capture_screenshots.py --init

   Then edit shots.json:
   {
     "base_url": "http://localhost:8080",
     "out_dir": "shots",
     "viewport": {"width": 1366, "height": 900},
     "full_page": true,
     "login": {                      // OMIT this block if no auth is needed
       "url": "/login",
       "fields": {"#username": "admin", "#password": "admin123"},
       "submit": "button[type=submit]",
       "wait_after": "networkidle"
     },
     "pages": [
       {"name": "login",     "url": "/login", "no_auth": true},
       {"name": "dashboard", "url": "/dashboard"},
       {"name": "data-barang", "url": "/barang"}
     ]
   }

3. Capture:
       python capture_screenshots.py shots.json

   PNGs land in shots/<name>.png. Put those paths into spec.json ->
   implementasi[].image, then re-run build_babiv_assets.py.

Notes
-----
* The screenshot COUNT is whatever you list in "pages" — the number of real
  pages your system has. Do not invent a count.
* CSS selectors for login fields use whatever your form uses (id, name=...).
* Needs: pip install playwright --break-system-packages
         python -m playwright install chromium
"""

from __future__ import annotations

import argparse
import json
import pathlib
import sys

_STARTER = {
    "base_url": "http://localhost:8080",
    "out_dir": "shots",
    "viewport": {"width": 1366, "height": 900},
    "full_page": True,
    "login": {
        "url": "/login",
        "fields": {"input[name=username]": "admin", "input[name=password]": "admin123"},
        "submit": "button[type=submit]",
        "wait_after": "networkidle",
    },
    "pages": [
        {"name": "login", "url": "/login", "no_auth": True},
        {"name": "dashboard", "url": "/dashboard"},
    ],
}


def _do_login(page, login: dict, base: str):
    url = login["url"]
    if not url.startswith("http"):
        url = base.rstrip("/") + "/" + url.lstrip("/")
    page.goto(url, wait_until="domcontentloaded")
    for sel, val in (login.get("fields") or {}).items():
        page.fill(sel, val)
    if login.get("submit"):
        page.click(login["submit"])
    page.wait_for_load_state(login.get("wait_after", "networkidle"))


def capture(cfg: dict) -> int:
    try:
        from playwright.sync_api import sync_playwright
    except Exception:
        print("Playwright belum terpasang:\n"
              "  pip install playwright --break-system-packages\n"
              "  python -m playwright install chromium")
        return 2

    base = cfg.get("base_url", "http://localhost:8080").rstrip("/")
    out = pathlib.Path(cfg.get("out_dir", "shots"))
    out.mkdir(parents=True, exist_ok=True)
    vp = cfg.get("viewport", {"width": 1366, "height": 900})
    full = bool(cfg.get("full_page", True))
    login = cfg.get("login")
    pages = cfg.get("pages", [])
    if not pages:
        print("shots.json: daftar 'pages' kosong.")
        return 2

    ok = 0
    with sync_playwright() as p:
        browser = p.chromium.launch(args=["--no-sandbox"])
        context = browser.new_context(viewport=vp)
        page = context.new_page()

        if login:
            try:
                _do_login(page, login, base)
                print(f"  login OK ({login.get('url')})")
            except Exception as exc:
                print(f"  ! login gagal: {exc} (lanjut tanpa auth)")

        for it in pages:
            name = it["name"]
            url = it["url"]
            if not url.startswith("http"):
                url = base + "/" + url.lstrip("/")
            dst = out / f"{name}.png"
            try:
                page.goto(url, wait_until="networkidle", timeout=20000)
                page.wait_for_timeout(350)
                page.screenshot(path=str(dst), full_page=full)
                print(f"  -> {dst}")
                ok += 1
            except Exception as exc:
                print(f"  ! gagal {name} ({url}): {exc}")
        browser.close()

    print(f"\nSelesai: {ok}/{len(pages)} halaman tertangkap di {out}/")
    print("Masukkan path PNG tsb ke spec.json -> implementasi[].image, "
          "lalu jalankan build_babiv_assets.py lagi.")
    return 0 if ok else 1


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description="Tangkap screenshot halaman sistem (4.5).")
    ap.add_argument("config", nargs="?", help="path shots.json")
    ap.add_argument("--init", action="store_true",
                    help="tulis shots.json contoh lalu keluar")
    args = ap.parse_args(argv)

    if args.init:
        p = pathlib.Path("shots.json")
        if p.exists():
            print("shots.json sudah ada — tidak ditimpa.")
            return 0
        p.write_text(json.dumps(_STARTER, indent=2, ensure_ascii=False),
                     encoding="utf-8")
        print(f"Ditulis {p}. Edit base_url, login, dan daftar pages, "
              "lalu: python capture_screenshots.py shots.json")
        return 0

    if not args.config:
        ap.print_help()
        return 2
    cfg = json.loads(pathlib.Path(args.config).read_text(encoding="utf-8"))
    return capture(cfg)


if __name__ == "__main__":
    sys.exit(main())
