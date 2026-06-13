"""
sus — read a System Usability Scale (SUS) questionnaire export (.xlsx) and
compute the standard SUS score, ready to drop into BAB IV section 4.6.

How SUS scoring works
---------------------
10 items on a 1-5 Likert scale, alternating tone:
  - odd items (1,3,5,7,9)  are positive: contribution = value - 1
  - even items (2,4,6,8,10) are negative: contribution = 5 - value
Per-respondent SUS = (sum of 10 contributions) * 2.5  -> 0..100
Final SUS = mean across respondents.

The 10 item columns are auto-detected as the columns whose values are mostly
numbers in 1..5 (so Timestamp / Email / Nama / consent columns are skipped).
"""

from __future__ import annotations

from typing import Any, Dict, List, Optional


def _is_likert(v) -> bool:
    try:
        f = float(v)
    except (TypeError, ValueError):
        return False
    return 1.0 <= f <= 5.0


def compute_sus(xlsx_path: str, category_header_hint: str = "kategori") -> Dict[str, Any]:
    import openpyxl
    wb = openpyxl.load_workbook(xlsx_path, data_only=True)
    ws = wb.worksheets[0]
    rows = list(ws.iter_rows(values_only=True))
    if not rows:
        raise ValueError("SUS xlsx kosong")
    header = [(_clean(h)) for h in rows[0]]
    data = [r for r in rows[1:] if any(c is not None and str(c).strip() for c in r)]

    ncol = len(header)
    # score each column for "likert-ness" across data rows
    likert_cols: List[int] = []
    for c in range(ncol):
        vals = [r[c] for r in data if c < len(r) and r[c] is not None]
        if not vals:
            continue
        good = sum(1 for v in vals if _is_likert(v))
        if good >= max(3, int(0.8 * len(vals))):
            likert_cols.append(c)
    if len(likert_cols) < 10:
        raise ValueError(f"Hanya menemukan {len(likert_cols)} kolom skala 1-5; "
                         "butuh 10 item SUS. Periksa file.")
    item_cols = likert_cols[:10]

    # find a category column (e.g., "Kategori responden")
    cat_col: Optional[int] = None
    for c in range(ncol):
        if category_header_hint in (header[c] or "").lower():
            cat_col = c
            break

    per_resp: List[Dict[str, Any]] = []
    for idx, r in enumerate(data, start=1):
        contribs = []
        raw = []
        ok = True
        for k, c in enumerate(item_cols):
            v = r[c] if c < len(r) else None
            if not _is_likert(v):
                ok = False
                break
            v = int(round(float(v)))
            raw.append(v)
            contribs.append((v - 1) if k % 2 == 0 else (5 - v))
        if not ok:
            continue
        total = sum(contribs)
        score = total * 2.5
        cat = _clean(r[cat_col]) if (cat_col is not None and cat_col < len(r)) else ""
        per_resp.append({"no": len(per_resp) + 1, "kategori": cat or "-",
                         "raw": raw, "contribs": contribs, "total": total,
                         "score": round(score, 1)})

    n = len(per_resp)
    if n == 0:
        raise ValueError("Tidak ada baris responden valid di SUS xlsx.")
    scores = [p["score"] for p in per_resp]
    mean = sum(scores) / n
    # per-item mean (raw 1-5)
    item_means = []
    for c in item_cols:
        vv = [float(r[c]) for r in data if c < len(r) and _is_likert(r[c])]
        item_means.append(round(sum(vv) / len(vv), 2) if vv else 0.0)
    # category breakdown
    cat_counts: Dict[str, int] = {}
    for p in per_resp:
        cat_counts[p["kategori"]] = cat_counts.get(p["kategori"], 0) + 1

    grade, adjective, acceptability = _interpret(mean)
    return {
        "n": n,
        "mean": round(mean, 1),
        "min": round(min(scores), 1),
        "max": round(max(scores), 1),
        "grade": grade,
        "adjective": adjective,
        "acceptability": acceptability,
        "per_respondent": per_resp,
        "item_means": item_means,
        "category_counts": cat_counts,
        "n_items": len(item_cols),
        "item_headers": [header[c] for c in item_cols],
    }


def _interpret(score: float):
    # Bangor et al. (2009) adjective + Sauro acceptability + letter grade
    if score >= 84.1:
        grade, adj = "A+", "Best Imaginable"
    elif score >= 80.3:
        grade, adj = "A", "Excellent"
    elif score >= 74:
        grade, adj = "B", "Good"
    elif score >= 68:
        grade, adj = "C", "Good (di atas rata-rata)"
    elif score >= 51:
        grade, adj = "D", "OK (di bawah rata-rata)"
    else:
        grade, adj = "F", "Poor"
    if score >= 70:
        accept = "Acceptable (dapat diterima)"
    elif score >= 50:
        accept = "Marginal"
    else:
        accept = "Not Acceptable (tidak dapat diterima)"
    return grade, adj, accept


def _clean(v) -> str:
    if v is None:
        return ""
    return " ".join(str(v).split())
