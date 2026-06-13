"""
Build clean .drawio.xml from the logical model.

Why this exists
---------------
The old pack asked the AI to hand-write mxGraph <x,y> coordinates. With no way
to *see* the result, the AI produced overlapping labels, diagonal spaghetti and
"1/N" text on the ERD. This module removes that failure mode: you describe the
LOGICAL structure (entities / processes / stores / typed flows) and the layout
is computed deterministically so it always comes out clean:

  * orthogonal edges only (right angles), arc line-jumps at crossings,
  * every flow label sits on its OWN horizontal track at a unique Y, so no two
    labels ever overlap (this is the single rule that makes a DFD readable),
  * crow's-foot ERD markers (ERone / ERmany / ...), never "1"/"N" text,
  * generous spacing; processes are ellipses, stores open-ended rectangles,
    externals plain rectangles -- matching the Indonesian skripsi convention.

You never edit coordinates by hand. Fill the model, call build_*.
"""

from __future__ import annotations

import html
import math
import re
from typing import Dict, List, Optional, Tuple

from .model import DFD, ERD

Box = Tuple[float, float, float, float]  # x, y, w, h

# --------------------------------------------------------------------------- #
# Styles (skripsi convention: white shapes, black strokes)
# --------------------------------------------------------------------------- #
EXTERNAL_STYLE = ("rounded=0;whiteSpace=wrap;html=1;fillColor=#FFFFFF;"
                  "strokeColor=#000000;fontSize=12;fontColor=#000000;"
                  "verticalAlign=middle;align=center;")

PROCESS_STYLE = ("ellipse;whiteSpace=wrap;html=1;fillColor=#FFFFFF;"
                 "strokeColor=#000000;fontSize=12;fontColor=#000000;"
                 "verticalAlign=middle;align=center;perimeter=ellipsePerimeter;")

STORE_STYLE = ("shape=partialRectangle;rounded=0;whiteSpace=wrap;html=1;"
               "fillColor=#F5F5F5;strokeColor=#000000;fontSize=12;"
               "fontColor=#000000;top=1;bottom=1;left=1;right=0;"
               "verticalAlign=middle;align=center;")

ENTITY_STYLE = ("rounded=0;whiteSpace=wrap;html=1;fillColor=#FFFFFF;"
                "strokeColor=#000000;fontSize=11;fontColor=#000000;"
                "verticalAlign=top;align=left;spacingLeft=8;spacingTop=4;"
                "spacingRight=6;")

# orthogonal edge + arc jumps; label drawn on a white pill so it reads over lines
EDGE_BASE = ("edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;html=1;"
             "jettySize=auto;fontSize=11;fontColor=#000000;"
             "labelBackgroundColor=#FFFFFF;jumpStyle=arc;jumpSize=10;"
             "endArrow=block;endFill=1;startArrow=none;")

ER_EDGE = ("edgeStyle=entityRelationEdgeStyle;rounded=0;html=1;fontSize=11;"
           "fontColor=#000000;labelBackgroundColor=#FFFFFF;jumpStyle=arc;"
           "jumpSize=10;")

ER_CARD = {
    "one": "ERone",
    "many": "ERmany",
    "mandone": "ERmandOne",
    "zeromany": "ERzeroToMany",
}

# --------------------------------------------------------------------------- #
# Geometry
# --------------------------------------------------------------------------- #
EXT_W, EXT_H = 150, 60
PROC_W, PROC_H = 150, 130
STORE_W, STORE_H = 180, 46
ENT_W = 210

TRACK = 36          # vertical distance between parallel flow tracks (=> labels)
MARGIN = 50
CORRIDOR = 330      # horizontal room for trunks + labels between columns
BAND_GAP = 30       # vertical gap between process bands (level dfd)
PROC_PAD = 26


def _esc(s: str) -> str:
    return html.escape(str(s), quote=True)


def _clampfrac(v: float, lo: float = 0.08, hi: float = 0.92) -> float:
    return max(lo, min(hi, v))


def _cy(box: Box) -> float:
    return box[1] + box[3] / 2


# --------------------------------------------------------------------------- #
# Routed edge container
# --------------------------------------------------------------------------- #
class _R:
    __slots__ = ("src", "dst", "label", "exit", "entry", "points",
                 "label_xy", "start_arrow", "end_arrow")

    def __init__(self, src, dst, label):
        self.src = src
        self.dst = dst
        self.label = label
        self.exit: Optional[Tuple[float, float]] = None     # (fx, fy) on src
        self.entry: Optional[Tuple[float, float]] = None    # (fx, fy) on dst
        self.points: List[Tuple[float, float]] = []
        self.label_xy: Optional[Tuple[float, float]] = None
        self.start_arrow = "none"
        self.end_arrow = "block"


# --------------------------------------------------------------------------- #
# CONTEXT diagram: 1 process centred, externals split left / right.
# Every flow is a pure horizontal track at a unique Y -> nothing overlaps.
# --------------------------------------------------------------------------- #
def _layout_context(dfd: DFD):
    proc = dfd.processes[0]

    flows_by_ext: Dict[str, List[Tuple]] = {e.id: [] for e in dfd.externals}
    for f in dfd.flows:
        if f.src == proc.id and f.dst in flows_by_ext:
            flows_by_ext[f.dst].append((f, "out"))      # process -> external
        elif f.dst == proc.id and f.src in flows_by_ext:
            flows_by_ext[f.src].append((f, "in"))        # external -> process

    exts = list(dfd.externals)
    # split into two sides, balancing number of flows
    by_load = sorted(exts, key=lambda e: -len(flows_by_ext[e.id]))
    left, right, lc, rc = [], [], 0, 0
    for e in by_load:
        if lc <= rc:
            left.append(e); lc += max(1, len(flows_by_ext[e.id]))
        else:
            right.append(e); rc += max(1, len(flows_by_ext[e.id]))
    left.sort(key=exts.index)
    right.sort(key=exts.index)

    n_left = sum(max(1, len(flows_by_ext[e.id])) for e in left)
    n_right = sum(max(1, len(flows_by_ext[e.id])) for e in right)
    n_side = max(n_left, n_right, 1)

    proc_h = max(PROC_H, n_side * TRACK + 2 * PROC_PAD)
    proc_w = max(PROC_W, proc_h * 0.62)
    ctx_corridor = 220
    proc_x = MARGIN + EXT_W + ctx_corridor
    proc_y = MARGIN
    proc_box: Box = (proc_x, proc_y, proc_w, proc_h)

    boxes: Dict[str, Box] = {proc.id: proc_box}
    routed: List[_R] = []

    def place_side(group, side):
        # assign global tracks for this side, top -> bottom, grouped per external
        flat: List[Tuple[str, Tuple]] = []
        for e in group:
            fs = flows_by_ext[e.id] or []
            for item in fs:
                flat.append((e.id, item))
            if not fs:                      # external with no flow still shown
                flat.append((e.id, None))
        k = len(flat)
        if k == 0:
            return
        usable = proc_h - 2 * PROC_PAD
        step = usable / (k - 1) if k > 1 else 0
        track_y = [proc_y + PROC_PAD + i * step for i in range(k)]
        if k == 1:
            track_y = [proc_y + proc_h / 2]

        # external boxes: cover the tracks that belong to them
        idx = 0
        for e in group:
            cnt = max(1, len(flows_by_ext[e.id]))
            ys = track_y[idx: idx + cnt]
            y0, y1 = ys[0], ys[-1]
            h = max(EXT_H, (y1 - y0) + EXT_H)
            cy = (y0 + y1) / 2
            ex = MARGIN if side == "left" else (proc_x + proc_w + ctx_corridor)
            boxes[e.id] = (ex, cy - h / 2, EXT_W, h)
            idx += cnt

        # routes
        idx = 0
        for e in group:
            fs = flows_by_ext[e.id] or [None]
            for item in fs:
                ty = track_y[idx]
                idx += 1
                if item is None:
                    continue
                f, direction = item
                eb = boxes[e.id]
                efx = 1.0 if side == "left" else 0.0
                pfx = 0.0 if side == "left" else 1.0
                efy = _clampfrac((ty - eb[1]) / eb[3])
                pfy = _clampfrac((ty - proc_y) / proc_h)
                r = _R(e.id, proc.id, f.label)
                r.exit = (efx, efy)
                r.entry = (pfx, pfy)
                # waypoint just outside the ellipse keeps the approach horizontal
                wx = proc_x - 14 if side == "left" else proc_x + proc_w + 14
                r.points = [(wx, ty)]
                if direction == "out":
                    r.start_arrow, r.end_arrow = "block", "none"
                else:
                    r.start_arrow, r.end_arrow = "none", "block"
                if side == "left":
                    lx = (eb[0] + EXT_W + wx) / 2
                else:
                    lx = (wx + eb[0]) / 2
                r.label_xy = (lx, ty)
                routed.append(r)

    place_side(left, "left")
    place_side(right, "right")

    width = proc_x + proc_w + (ctx_corridor + EXT_W if right else 0) + MARGIN
    if not right:
        width = proc_x + proc_w + MARGIN
    height = proc_y + proc_h + MARGIN
    return boxes, routed, int(width), int(height)


# --------------------------------------------------------------------------- #
# LEVEL diagram: externals | processes | stores, with per-process bands.
# Each process owns a vertical band tall enough to give every one of its flows
# its own horizontal track -> labels never collide.
# --------------------------------------------------------------------------- #
def _layout_level(dfd: DFD):
    """DFD level layout, Indonesian-skripsi 'store-above-process' style.

    Processes stack in a single CENTRE column. Each data store sits directly
    ABOVE the process that owns it, joined by a short read (store->proc, info_)
    and write (proc->store, data_) pair -> every store therefore has BOTH an
    input and an output. External entities become TALL boxes on the LEFT and
    RIGHT; their flows are horizontal tracks meeting the process at its centre.
    A store used by a process that does not own it (cross-link) is routed
    orthogonally through the right corridor. This removes the long crossing
    spaghetti of the old 'all stores in one far column' layout.
    """
    from collections import defaultdict
    procs = list(dfd.processes)
    externals = list(dfd.externals)
    stores = list(dfd.stores)
    pid = {p.id for p in procs}
    eid = {e.id for e in externals}
    sid = {s.id for s in stores}
    pidx = {p.id: i for i, p in enumerate(procs)}

    # ---- classify flows ----
    ps = defaultdict(list)     # (proc,store) -> [(flow,'r'|'w')]
    pe = defaultdict(list)     # (proc,ext)   -> [(flow,'in'|'out')]
    pp = []
    for f in dfd.flows:
        s, d = f.src, f.dst
        if s in pid and d in sid: ps[(s, d)].append((f, 'w'))
        elif s in sid and d in pid: ps[(d, s)].append((f, 'r'))
        elif s in pid and d in eid: pe[(s, d)].append((f, 'out'))
        elif s in eid and d in pid: pe[(d, s)].append((f, 'in'))
        elif s in pid and d in pid: pp.append(f)

    # ---- store ownership: the process with the most flows to that store ----
    cnt = defaultdict(lambda: defaultdict(int))
    for (p, s), lst in ps.items():
        cnt[s][p] += len(lst)
    owner = {}
    owned = defaultdict(list)
    for s in stores:
        c = cnt.get(s.id, {})
        best = (max(c.items(), key=lambda kv: (kv[1], -pidx[kv[0]]))[0]
                if c else (procs[0].id if procs else None))
        owner[s.id] = best
        if best is not None:
            owned[best].append(s.id)

    # ---- external sides (balance flow load between left & right) ----
    eflow = {e.id: sum(len(v) for (p, x), v in pe.items() if x == e.id)
             for e in externals}
    side = {}
    lc = rc = 0
    for e in sorted(externals, key=lambda e: -eflow[e.id]):
        if lc <= rc:
            side[e.id] = 'L'; lc += max(1, eflow[e.id])
        else:
            side[e.id] = 'R'; rc += max(1, eflow[e.id])
    left_exts = [e for e in externals if side.get(e.id) == 'L']
    right_exts = [e for e in externals if side.get(e.id) == 'R']

    # ---- geometry ----
    EXTW, EXTH_MIN = 140, 70
    PW, PH = 132, 128
    SW, SH = 184, 42
    TRACK = 30
    SGAP_V = 64          # store-row -> process gap
    SGAP_H = 46          # gap between sibling stores above one process
    ROWGAP = 92
    SROW_GAP = 42        # vertical gap between wrapped store rows
    SCOLS_MAX = 4        # max stores per row above a process
    EXT_CORR = 200       # corridor width for external flow labels

    def _grid_dims(n):
        if n <= 0:
            return (0, 0, 0.0)
        sc = min(SCOLS_MAX, n)
        sr = -(-n // sc)
        return (sc, sr, sc * SW + (sc - 1) * SGAP_H)

    # widest store grid (half-width) drives how far apart the side columns sit
    _half_cell = (SW + SGAP_H) / 2.0
    max_half = PW / 2.0
    for p in procs:
        _, srows_, gw = _grid_dims(len(owned[p.id]))
        eff = gw / 2.0 + (_half_cell if srows_ > 1 else 0.0)
        max_half = max(max_half, eff)
    n_xlinks = sum(len(v) for (pp_, ss_), v in ps.items()
                   if owner.get(ss_) != pp_)
    xlane_span = n_xlinks * 22 + 36          # room for cross-link lanes (right)

    left_zone = (EXTW + EXT_CORR) if left_exts else 70
    cx = MARGIN + left_zone + max_half
    right_x = cx + max_half + xlane_span + EXT_CORR * 0.5
    width = right_x + (EXTW if right_exts else MARGIN) + MARGIN

    def ntr(p, exts):
        return sum(len(pe[(p, e.id)]) for e in exts if (p, e.id) in pe)

    # ---- vertical placement of processes + their owned stores ----
    boxes: Dict[str, Box] = {}
    proc_cy: Dict[str, float] = {}
    store_meta: Dict[str, dict] = {}
    y = float(MARGIN)
    half_cell = (SW + SGAP_H) / 2.0
    for p in procs:
        n_store = len(owned[p.id])
        side_tracks = max(ntr(p.id, left_exts), ntr(p.id, right_exts), 1)
        ph = max(PH, side_tracks * TRACK + 34)
        scols, srows, _ = _grid_dims(n_store)
        band = (srows * SH + (srows - 1) * SROW_GAP + SGAP_V) if n_store else 0
        ptop = y + band
        cyv = ptop + ph / 2.0
        boxes[p.id] = (cx - PW / 2, cyv - PH / 2, PW, PH)
        proc_cy[p.id] = cyv
        if n_store:
            for j, s in enumerate(owned[p.id]):
                r, c = divmod(j, scols)
                sy = y + r * (SH + SROW_GAP)
                ncols_here = min(scols, n_store - r * scols)
                row_w = ncols_here * SW + (ncols_here - 1) * SGAP_H
                # stagger odd rows half a cell so a row-above store's drop-line
                # falls in the GAP between the row-below stores, not through one
                stag = half_cell if (srows > 1 and r % 2 == 1) else 0.0
                rx0 = cx - row_w / 2.0 + stag
                sx = rx0 + c * (SW + SGAP_H)
                boxes[s] = (sx, sy, SW, SH)
                store_meta[s] = {"top": r == 0, "bottom": r == srows - 1,
                                 "left": c == 0, "right": c == ncols_here - 1,
                                 "cx": sx + SW / 2.0, "sy_top": sy,
                                 "multi": srows > 1}
        y = cyv + ph / 2.0 + ROWGAP
    total_h = y - ROWGAP + MARGIN

    # ---- external boxes (clamped to the column height; never explode) ----
    def _meany(e):
        ys = [proc_cy[p.id] for p in procs if (p.id, e.id) in pe]
        return sum(ys) / len(ys) if ys else total_h / 2.0

    def place_exts(exts, x):
        n = len(exts)
        if n == 0:
            return
        if n == 1:
            e = exts[0]
            ys = [proc_cy[p.id] for p in procs if (p.id, e.id) in pe]
            if ys:
                top = max(MARGIN, min(ys) - 44)
                bot = min(total_h - MARGIN, max(ys) + 44)
            else:
                top, bot = MARGIN, total_h - MARGIN
            boxes[e.id] = (x, top, EXTW, max(EXTH_MIN, bot - top))
            return
        # multiple on one side -> equal vertical bands inside [MARGIN, total_h]
        order = sorted(exts, key=_meany)
        band = (total_h - 2 * MARGIN) / n
        for i, e in enumerate(order):
            top = MARGIN + i * band
            h = band - 26
            boxes[e.id] = (x, top, EXTW, max(EXTH_MIN, h))
    place_exts(left_exts, MARGIN)
    place_exts(right_exts, right_x)

    routed: List[_R] = []

    # ---- owned store: read (left, store->proc) + write (right, proc->store) ----
    for p in procs:
        pb = boxes[p.id]
        for s in owned[p.id]:
            sb = boxes[s]
            reads = [f for (f, dr) in ps[(p.id, s)] if dr == 'r']
            writes = [f for (f, dr) in ps[(p.id, s)] if dr == 'w']
            laby = sb[1] + sb[3] + 12       # just below this store
            for f in reads:
                r = _R(s, p.id, f.label)
                r.exit = (0.32, 1.0); r.entry = (0.40, 0.0)
                r.start_arrow, r.end_arrow = 'none', 'block'
                r.label_xy = (sb[0] + sb[2] * 0.28 - 6, laby)
                routed.append(r)
            for f in writes:
                r = _R(p.id, s, f.label)
                r.exit = (0.60, 0.0); r.entry = (0.68, 1.0)
                r.start_arrow, r.end_arrow = 'none', 'block'
                r.label_xy = (sb[0] + sb[2] * 0.72 + 8, laby)
                routed.append(r)

    # ---- external horizontal flows (own track per flow, near proc centre) ----
    for p in procs:
        pb = boxes[p.id]
        cyv = proc_cy[p.id]
        for exts, sideflag in ((left_exts, 'L'), (right_exts, 'R')):
            items = [(e, f, dr) for e in exts
                     for (f, dr) in pe.get((p.id, e.id), [])]
            n = len(items)
            if not n:
                continue
            for k, (e, f, dr) in enumerate(items):
                ty = cyv + (k - (n - 1) / 2.0) * TRACK
                eb = boxes[e.id]
                pfrac = _clampfrac((ty - pb[1]) / pb[3])
                efrac = _clampfrac((ty - eb[1]) / eb[3])
                if sideflag == 'L':
                    if dr == 'in':
                        r = _R(e.id, p.id, f.label)
                        r.exit = (1.0, efrac); r.entry = (0.0, pfrac)
                    else:
                        r = _R(p.id, e.id, f.label)
                        r.exit = (0.0, pfrac); r.entry = (1.0, efrac)
                    eright = eb[0] + EXTW
                    lx = eright + (pb[0] - eright) * 0.42   # toward the external box
                else:
                    if dr == 'in':
                        r = _R(e.id, p.id, f.label)
                        r.exit = (0.0, efrac); r.entry = (1.0, pfrac)
                    else:
                        r = _R(p.id, e.id, f.label)
                        r.exit = (1.0, pfrac); r.entry = (0.0, efrac)
                    pright = pb[0] + pb[2]
                    lx = pright + (eb[0] - pright) * 0.60   # toward the external box
                r.start_arrow, r.end_arrow = 'none', 'block'
                r.label_xy = (lx, ty - 9)
                routed.append(r)

    # ---- cross-links: process <-> store it does NOT own ----
    # Enter the store from its TOP (via a lane in the gap above its row) so the
    # line crosses other LINES (clean arc-jumps) instead of slicing through the
    # sibling store BOXES, which was the unreadable part.
    lane = 0
    xseen_p = defaultdict(int)          # cross-links drawn from each process
    xseen_s = defaultdict(int)          # cross-links drawn into each store
    for (p, s), flows in ps.items():
        if owner.get(s) == p:
            continue
        sb = boxes.get(s); pb = boxes.get(p)
        if sb is None or pb is None:
            continue
        meta = store_meta.get(s, {})
        for (f, dr) in flows:
            lx = cx + max_half + 30 + lane * 22
            lane += 1
            kp = xseen_p[p]; xseen_p[p] += 1
            ks = xseen_s[s]; xseen_s[s] += 1
            s_cx = meta.get("cx", sb[0] + sb[2] * 0.5)
            top_y = sb[1] - 20 - ks * 14          # lane in the gap above the store
            pfrac = 0.30 if dr == 'r' else 0.70
            py = pb[1] + pb[3] * pfrac
            if dr == 'r':                 # store -> proc (read)
                r = _R(s, p, f.label)
                r.exit = (0.5, 0.0)               # leave store from the TOP
                r.entry = (1.0, pfrac)            # into process side
                r.points = [(s_cx, top_y), (lx, top_y), (lx, py)]
            else:                         # proc -> store (write)
                r = _R(p, s, f.label)
                r.exit = (1.0, pfrac)
                r.entry = (0.5, 0.0)              # into store TOP
                r.points = [(lx, py), (lx, top_y), (s_cx, top_y)]
            r.start_arrow, r.end_arrow = 'none', 'block'
            # label just outside the source process (clear gap), staggered
            ly = (py - 16 - kp * 17) if dr == 'r' else (py + 16 + kp * 17)
            r.label_xy = (lx + 10, ly)
            routed.append(r)

    # ---- process <-> process (thin lane on the far left of the column) ----
    lane2 = 0
    for f in pp:
        a, b = boxes.get(f.src), boxes.get(f.dst)
        if a is None or b is None:
            continue
        lx = cx - PW / 2 - 26 - lane2 * 16
        lane2 += 1
        ay, by = _cy(a), _cy(b)
        r = _R(f.src, f.dst, f.label)
        r.exit = (0.0, 0.5); r.entry = (0.0, 0.5)
        r.points = [(lx, ay), (lx, by)]
        r.start_arrow, r.end_arrow = 'none', 'block'
        r.label_xy = (lx - 6, (ay + by) / 2.0)
        routed.append(r)

    bottom = max((bx[1] + bx[3] for bx in boxes.values()), default=total_h)
    height = int(max(total_h, bottom + MARGIN))
    return boxes, routed, int(width), height


# --------------------------------------------------------------------------- #
# XML emission
# --------------------------------------------------------------------------- #
def _node_xml(cid, value, style, box: Box) -> str:
    x, y, w, h = box
    return (f'<mxCell id="{_esc(cid)}" value="{_esc(value)}" style="{style}" '
            f'vertex="1" parent="1">'
            f'<mxGeometry x="{x:.0f}" y="{y:.0f}" width="{w:.0f}" '
            f'height="{h:.0f}" as="geometry"/></mxCell>')


def _edge_xml(eid, r: _R, style_extra: str) -> str:
    parts = [EDGE_BASE, style_extra]
    if r.exit is not None:
        parts.append(f"exitX={r.exit[0]};exitY={r.exit[1]:.3f};exitDx=0;exitDy=0;")
    if r.entry is not None:
        parts.append(f"entryX={r.entry[0]};entryY={r.entry[1]:.3f};entryDx=0;entryDy=0;")
    parts.append(f"startArrow={r.start_arrow};endArrow={r.end_arrow};")
    if r.start_arrow != "none":
        parts.append("startFill=1;")
    style = "".join(parts)
    pts = ""
    if r.points:
        inner = "".join(f'<mxPoint x="{px:.0f}" y="{py:.0f}"/>' for px, py in r.points)
        pts = f'<Array as="points">{inner}</Array>'
    # NOTE: the flow label is emitted separately as an absolute-positioned vertex
    # (see _label_xml) so it lands exactly on its reserved track Y and never
    # collides with another label. The edge itself carries no text.
    geo = f'<mxGeometry relative="1" as="geometry">{pts}</mxGeometry>'
    return (f'<mxCell id="{_esc(eid)}" value="" style="{style}" '
            f'edge="1" parent="1" source="{_esc(r.src)}" '
            f'target="{_esc(r.dst)}">{geo}</mxCell>')


LABEL_STYLE = ("text;html=1;align=center;verticalAlign=middle;resizable=0;"
               "labelBackgroundColor=#FFFFFF;fontSize=11;fontColor=#000000;"
               "spacing=2;")


def _label_xml(lid: str, text: str, cx: float, cy: float) -> str:
    if not text:
        return ""
    w = max(34, int(len(text) * 6.6) + 12)
    h = 18
    x, y = cx - w / 2, cy - h / 2
    return (f'<mxCell id="{_esc(lid)}" value="{_esc(text)}" style="{LABEL_STYLE}" '
            f'vertex="1" connectable="0" parent="1">'
            f'<mxGeometry x="{x:.0f}" y="{y:.0f}" width="{w}" height="{h}" '
            f'as="geometry"/></mxCell>')


def _wrap(title, cells: List[str], w: int, h: int) -> str:
    body = "".join(cells)
    return (f'<mxfile host="app.diagrams.net" type="device">'
            f'<diagram id="d1" name="{_esc(title)}">'
            f'<mxGraphModel dx="{w}" dy="{h}" grid="0" gridSize="10" guides="1" '
            f'tooltips="1" connect="1" arrows="1" fold="1" page="0" pageScale="1" '
            f'pageWidth="{w}" pageHeight="{h}" math="0" shadow="0">'
            f'<root><mxCell id="0"/><mxCell id="1" parent="0"/>{body}</root>'
            f'</mxGraphModel></diagram></mxfile>')


# --------------------------------------------------------------------------- #
# Public: DFD
# --------------------------------------------------------------------------- #
def _gv_wrap(name, every=14):
    """Word-wrap a label for Graphviz (\\n line breaks)."""
    words = str(name).split()
    out, cur = [], ""
    for w in words:
        if len(cur) + len(w) + 1 > every and cur:
            out.append(cur); cur = w
        else:
            cur = (cur + " " + w).strip()
    if cur:
        out.append(cur)
    return "\\n".join(out)


def build_dfd_dot(dfd: DFD, kind: str = "level") -> str:
    """Graphviz DOT for a DFD: processes = circles, externals & stores = boxes,
    orthogonal edges, data_/info_ labels as xlabels (ortho-safe). rankdir=LR so
    externals sit on the left, processes in the middle, stores on the right."""
    ext = {e.id: e.name for e in dfd.externals}
    proc = {p.id: (p.no, p.name) for p in dfd.processes}
    store = {s.id: (s.code, s.name) for s in dfd.stores}

    def nid(i):
        if i in ext: return "X_" + i
        if i in proc: return "P_" + i
        if i in store: return "D_" + i
        return None

    L = ["digraph G {",
         "  graph [rankdir=LR, splines=ortho, nodesep=0.5, ranksep=1.9, "
         "bgcolor=white, pad=0.3, forcelabels=true];",
         '  node [fontname="Arial", fontsize=12, penwidth=1.2, color="#000000"];',
         '  edge [fontname="Arial", fontsize=10, penwidth=1.1, arrowsize=0.8, '
         'color="#000000"];']
    for i, n in ext.items():
        L.append(f'  X_{i} [shape=box, label="{_gv_wrap(n)}", width=1.7, '
                 f'height=0.75, fixedsize=true, style=filled, fillcolor=white];')
    for i, (no, nm) in proc.items():
        lab = (f"{no}\\n{_gv_wrap(nm, 13)}" if no else _gv_wrap(nm, 13))
        L.append(f'  P_{i} [shape=circle, label="{lab}", style=filled, '
                 f'fillcolor=white];')
    for i, (c, nm) in store.items():
        L.append(f'  D_{i} [shape=box, label="{_esc_dot(c + "  " + nm)}", '
                 f'height=0.5, fixedsize=false, margin="0.16,0.05", '
                 f'style=filled, fillcolor="#f5f5f5"];')
    if ext:
        L.append("  {rank=source; " + "; ".join("X_" + i for i in ext) + ";}")
    if store:
        L.append("  {rank=sink; " + "; ".join("D_" + i for i in store) + ";}")
    for f in dfd.flows:
        s, t = nid(f.src), nid(f.dst)
        if s and t:
            L.append(f'  {s} -> {t} [xlabel="{_esc_dot(f.label)}"];')
    L.append("}")
    return "\n".join(L)


def _esc_dot(s: str) -> str:
    return str(s).replace("\\", "\\\\").replace('"', '\\"')


def render_dfd_graphviz(dfd: DFD, out_png: str, kind: str = "level",
                        dpi: int = 150) -> Optional[str]:
    """Render a DFD straight to PNG with Graphviz (clean orthogonal lines, white
    background, labels on their edges). Returns out_png, or None if Graphviz is
    unavailable (caller then falls back to the draw.io renderer)."""
    from . import gvlayout
    import subprocess
    if not gvlayout.have("dot"):
        return None
    dot = build_dfd_dot(dfd, kind)
    try:
        r = subprocess.run(["dot", "-Tpng", f"-Gdpi={dpi}", "-o", out_png],
                           input=dot, capture_output=True, text=True, timeout=60)
        if r.returncode != 0:
            return None
    except Exception:
        return None
    return out_png


def build_dfd_drawio(dfd: DFD, kind: str) -> str:
    """Editable .drawio.xml for a DFD (orthogonal, balanced 1:1 context).
    kind in {'context','level'}. The high-quality PNG is rendered separately by
    render_dfd_graphviz() when Graphviz is available; this XML is what the user
    opens in draw.io to tweak by hand."""
    if kind == "context" or len(dfd.processes) <= 1:
        boxes, routed, w, h = _layout_context(dfd)
    else:
        boxes, routed, w, h = _layout_level(dfd)

    cells: List[str] = []
    for e in dfd.externals:
        cells.append(_node_xml(e.id, e.name, EXTERNAL_STYLE, boxes[e.id]))
    for p in dfd.processes:
        val = f"{p.no}\n{p.name}" if p.no else p.name
        cells.append(_node_xml(p.id, val, PROCESS_STYLE, boxes[p.id]))
    for s in dfd.stores:
        val = f"{s.code}  {s.name}".strip()
        cells.append(_node_xml(s.id, val, STORE_STYLE, boxes[s.id]))
    for i, r in enumerate(routed):
        cells.append(_edge_xml(f"e{i}", r, ""))
    for i, r in enumerate(routed):
        if r.label and r.label_xy is not None:
            cells.append(_label_xml(f"l{i}", r.label, r.label_xy[0], r.label_xy[1]))
    return _wrap(dfd.title, cells, w, h)


# --------------------------------------------------------------------------- #
# Public: ERD (crow's foot)
# --------------------------------------------------------------------------- #
def _entity_label(ent) -> str:
    lines = [f"<b>{_esc(ent.name)}</b>", '<hr size="1"/>']
    for a in ent.attributes:
        prefix = "PK " if a.is_pk else ("FK " if a.is_fk else "")
        nm = f"<u>{_esc(a.name)}</u>" if a.is_pk else _esc(a.name)
        lines.append(prefix + nm)
    return "<br>".join(lines)


def _entity_size(ent) -> Tuple[int, int]:
    rows = len(ent.attributes)
    return ENT_W, 34 + rows * 20 + 10


def _erd_order(erd) -> List[str]:
    """Order entities so related ones land in adjacent grid cells: BFS from the
    highest-degree hub, disconnected entities appended last."""
    ids = [e.id for e in erd.entities]
    adj: Dict[str, List[str]] = {i: [] for i in ids}
    deg: Dict[str, int] = {i: 0 for i in ids}
    for rel in erd.relations:
        if rel.src in adj and rel.dst in adj:
            adj[rel.src].append(rel.dst)
            adj[rel.dst].append(rel.src)
            deg[rel.src] += 1
            deg[rel.dst] += 1
    order: List[str] = []
    seen = set()
    for start in sorted(ids, key=lambda i: -deg[i]):
        if start in seen:
            continue
        queue = [start]
        seen.add(start)
        while queue:
            cur = queue.pop(0)
            order.append(cur)
            for nb in sorted(adj[cur], key=lambda i: -deg[i]):
                if nb not in seen:
                    seen.add(nb)
                    queue.append(nb)
    for i in ids:                       # safety: any left over
        if i not in order:
            order.append(i)
    return order


# Chen-notation styles
CHEN_ENTITY_STYLE = ("rounded=0;whiteSpace=wrap;html=1;fillColor=#FFFFFF;"
                     "strokeColor=#000000;fontSize=12;fontStyle=1;fontColor=#000000;"
                     "verticalAlign=middle;align=center;")
CHEN_ATTR_STYLE = ("ellipse;whiteSpace=wrap;html=1;fillColor=#FFFFFF;"
                   "strokeColor=#000000;fontSize=10;fontColor=#000000;"
                   "verticalAlign=middle;align=center;")
CHEN_REL_STYLE = ("rhombus;whiteSpace=wrap;html=1;fillColor=#FFFFFF;"
                  "strokeColor=#000000;fontSize=11;fontColor=#000000;"
                  "verticalAlign=middle;align=center;")
CHEN_EDGE = ("edgeStyle=none;html=1;rounded=0;endArrow=none;startArrow=none;"
             "strokeColor=#000000;fontSize=11;fontColor=#000000;"
             "labelBackgroundColor=#FFFFFF;")

_CARD_TEXT = {"one": "1", "many": "N", "mandone": "1", "zeromany": "N"}


def _waypoints_xml(path, margin, drop_ends=True):
    """Turn a graphviz spline path (px) into a draw.io <Array as='points'>."""
    if not path:
        return ""
    mid = path[1:-1] if (drop_ends and len(path) >= 3) else path
    if not mid:
        return ""
    inner = "".join(f'<mxPoint x="{x + margin:.0f}" y="{y + margin:.0f}"/>'
                    for x, y in mid)
    return f'<Array as="points">{inner}</Array>'


def _erd_crowsfoot_gv(erd: ERD, margin: int = 40) -> Optional[str]:
    """Crow's-foot ERD positioned by Graphviz `dot` (clean, few crossings).
    Returns None if Graphviz is unavailable."""
    from . import gvlayout
    if not gvlayout.have("dot"):
        return None
    by_id = {e.id: e for e in erd.entities}
    sizes = {e.id: _entity_size(e) for e in erd.entities}
    edges = [(r.src, r.dst) for r in erd.relations if r.src in sizes and r.dst in sizes]
    try:
        pos, epaths, (W, H) = gvlayout.layout(
            sizes, edges, engine="dot", rankdir="LR",
            nodesep=0.5, ranksep=1.5, splines="spline")
    except Exception:
        return None
    epath = {(t, h): p for t, h, p in epaths}

    cells: List[str] = []
    for e in erd.entities:
        w, h = sizes[e.id]
        cx, cy = pos.get(e.id, (w / 2, h / 2))
        box = (cx - w / 2 + margin, cy - h / 2 + margin, w, h)
        cells.append(_node_xml(e.id, _entity_label(e), ENTITY_STYLE, box))
    for i, rel in enumerate(erd.relations):
        sa = ER_CARD.get(rel.src_card, "ERone")
        ea = ER_CARD.get(rel.dst_card, "ERmany")
        style = ("edgeStyle=none;html=1;rounded=0;fontSize=11;fontColor=#000000;"
                 "labelBackgroundColor=#FFFFFF;strokeColor=#000000;"
                 f"startArrow={sa};startFill=0;endArrow={ea};endFill=0;")
        pts = _waypoints_xml(epath.get((rel.src, rel.dst), []), margin)
        cells.append(
            f'<mxCell id="r{i}" value="{_esc(rel.label)}" style="{style}" '
            f'edge="1" parent="1" source="{_esc(rel.src)}" target="{_esc(rel.dst)}">'
            f'<mxGeometry relative="1" as="geometry">{pts}</mxGeometry></mxCell>')
    return _wrap(erd.title, cells, int(W + 2 * margin), int(H + 2 * margin))



def _fr_layout(sizes, edges, iterations=500, seed=7, k=None,
               min_gap=40, area_factor=2.5):
    """Pure-Python Fruchterman-Reingold force-directed layout + overlap removal.
    Returns {node_id: (cx, cy)} centre coordinates. Deterministic (fixed seed).
    Mimics what Graphviz `neato` does, so ERD/Chen come out spread with few
    crossings even when Graphviz is not installed."""
    import random
    nodes = list(sizes.keys())
    n = len(nodes)
    if n == 0:
        return {}
    if n == 1:
        return {nodes[0]: (0.0, 0.0)}
    total = sum((w + min_gap) * (h + min_gap) for (w, h) in sizes.values())
    area = total * area_factor
    if k is None:
        k = math.sqrt(area / n)
    rng = random.Random(seed)
    R = math.sqrt(area) / 2.0
    px = [0.0] * n
    py = [0.0] * n
    for i in range(n):
        a = 2 * math.pi * i / n
        px[i] = R * math.cos(a) + rng.uniform(-2, 2)
        py[i] = R * math.sin(a) + rng.uniform(-2, 2)
    idx = {nid: i for i, nid in enumerate(nodes)}
    E = [(idx[u], idx[v]) for (u, v) in edges if u in idx and v in idx]
    temp = R * 0.4
    cool = temp / (iterations + 1)
    for _ in range(iterations):
        dx = [0.0] * n
        dy = [0.0] * n
        for i in range(n):
            pxi, pyi = px[i], py[i]
            for j in range(i + 1, n):
                ddx = pxi - px[j]
                ddy = pyi - py[j]
                dist = math.hypot(ddx, ddy) or 0.01
                f = k * k / dist
                ux = ddx / dist
                uy = ddy / dist
                dx[i] += ux * f; dy[i] += uy * f
                dx[j] -= ux * f; dy[j] -= uy * f
        for (i, j) in E:
            ddx = px[i] - px[j]
            ddy = py[i] - py[j]
            dist = math.hypot(ddx, ddy) or 0.01
            f = dist * dist / k
            ux = ddx / dist
            uy = ddy / dist
            dx[i] -= ux * f; dy[i] -= uy * f
            dx[j] += ux * f; dy[j] += uy * f
        for i in range(n):
            d = math.hypot(dx[i], dy[i]) or 0.01
            step = min(d, temp)
            px[i] += dx[i] / d * step
            py[i] += dy[i] / d * step
        temp -= cool
    # overlap removal (rectangles must not touch; keep a gap)
    for _ in range(200):
        moved = False
        for i in range(n):
            wi, hi = sizes[nodes[i]]
            for j in range(i + 1, n):
                wj, hj = sizes[nodes[j]]
                ddx = px[j] - px[i]
                ddy = py[j] - py[i]
                needx = (wi + wj) / 2.0 + min_gap
                needy = (hi + hj) / 2.0 + min_gap * 0.55
                ox = needx - abs(ddx)
                oy = needy - abs(ddy)
                if ox > 0 and oy > 0:
                    if ox <= oy:
                        s = (ox / 2.0 + 0.5) * (1 if ddx >= 0 else -1)
                        px[i] -= s; px[j] += s
                    else:
                        s = (oy / 2.0 + 0.5) * (1 if ddy >= 0 else -1)
                        py[i] -= s; py[j] += s
                    moved = True
        if not moved:
            break
    return {nodes[i]: (px[i], py[i]) for i in range(n)}


def _shift_positive(pos, sizes, margin):
    minx = min(pos[i][0] - sizes[i][0] / 2 for i in sizes)
    miny = min(pos[i][1] - sizes[i][1] / 2 for i in sizes)
    maxx = max(pos[i][0] + sizes[i][0] / 2 for i in sizes)
    maxy = max(pos[i][1] + sizes[i][1] / 2 for i in sizes)
    ox, oy = margin - minx, margin - miny
    W = maxx - minx + 2 * margin
    H = maxy - miny + 2 * margin
    return ox, oy, W, H


def _erd_force(erd, margin: int = 40) -> str:
    """Crow's-foot ERD positioned by the built-in force-directed layout
    (no Graphviz). Entities spread out, straight relationship lines with
    crow's-foot markers and white-backed labels so crossings stay readable."""
    by_id = {e.id: e for e in erd.entities}
    sizes = {e.id: _entity_size(e) for e in erd.entities}
    if not sizes:
        return _wrap(erd.title, [], 400, 300)
    edges = [(r.src, r.dst) for r in erd.relations
             if r.src in sizes and r.dst in sizes]
    pos = _fr_layout(sizes, edges, iterations=560, seed=7,
                     min_gap=70, area_factor=2.1)
    ox, oy, W, H = _shift_positive(pos, sizes, margin)
    cells = []
    for eid, e in by_id.items():
        w, h = sizes[eid]
        cx, cy = pos[eid]
        cells.append(_node_xml(eid, _entity_label(e), ENTITY_STYLE,
                               (cx - w / 2 + ox, cy - h / 2 + oy, w, h)))
    for i, rel in enumerate(erd.relations):
        if rel.src not in sizes or rel.dst not in sizes:
            continue
        sa = ER_CARD.get(rel.src_card, "ERone")
        ea = ER_CARD.get(rel.dst_card, "ERmany")
        style = ("edgeStyle=none;rounded=0;html=1;fontSize=11;"
                 "labelBackgroundColor=#FFFFFF;strokeColor=#000000;"
                 f"startArrow={sa};startFill=0;endArrow={ea};endFill=0;")
        cells.append(
            f'<mxCell id="r{i}" value="{_esc(rel.label)}" style="{style}" '
            f'edge="1" parent="1" source="{_esc(rel.src)}" target="{_esc(rel.dst)}">'
            f'<mxGeometry relative="1" as="geometry"></mxGeometry></mxCell>')
    return _wrap(erd.title, cells, int(W), int(H))


def _chen_force(erd, margin: int = 40) -> str:
    """Chen-notation ERD positioned by the built-in force-directed layout
    (no Graphviz): entities=rectangles, attributes=ellipses, relationships=
    diamonds, cardinality 1 / N on the edges, PK underlined."""
    ENT_H = 46
    ATTR_W, ATTR_H = 104, 40
    REL_W, REL_H = 120, 76
    ENTW = 140
    sizes = {}
    meta = {}
    edges = []
    edge_card = {}
    for e in erd.entities:
        eid = f"E_{e.id}"
        sizes[eid] = (max(ENTW, 24 + len(e.name) * 9), ENT_H)
        meta[eid] = ("ent", e.name)
        for j, a in enumerate(e.attributes):
            aid = f"A_{e.id}_{j}"
            sizes[aid] = (max(ATTR_W, 20 + len(a.name) * 7), ATTR_H)
            meta[aid] = ("attr", a.name, a.is_pk)
            edges.append((eid, aid))
    for k, rel in enumerate(erd.relations):
        e1, e2 = f"E_{rel.src}", f"E_{rel.dst}"
        if e1 in sizes and e2 in sizes:
            rid = f"R_{k}"
            sizes[rid] = (REL_W, REL_H)
            meta[rid] = ("rel", rel.label)
            edges.append((e1, rid)); edge_card[(e1, rid)] = _CARD_TEXT.get(rel.src_card, "1")
            edges.append((rid, e2)); edge_card[(rid, e2)] = _CARD_TEXT.get(rel.dst_card, "N")
    if not sizes:
        return _wrap(f"{erd.title} (Chen)", [], 400, 300)
    pos = _fr_layout(sizes, edges, iterations=700, seed=7,
                     min_gap=16, area_factor=1.15)
    ox, oy, W, H = _shift_positive(pos, sizes, margin)
    cells = []
    for nid, (w, h) in sizes.items():
        cx, cy = pos[nid]
        box = (cx - w / 2 + ox, cy - h / 2 + oy, w, h)
        kind = meta[nid][0]
        if kind == "ent":
            cells.append(_node_xml(nid, meta[nid][1], CHEN_ENTITY_STYLE, box))
        elif kind == "rel":
            cells.append(_node_xml(nid, meta[nid][1], CHEN_REL_STYLE, box))
        else:
            lab = f"<u>{_esc(meta[nid][1])}</u>" if meta[nid][2] else _esc(meta[nid][1])
            cells.append(_node_xml(nid, lab, CHEN_ATTR_STYLE, box))
    for i, (t, h) in enumerate(edges):
        card = edge_card.get((t, h), "")
        cells.append(
            f'<mxCell id="ce{i}" value="{_esc(card)}" style="{CHEN_EDGE}" '
            f'edge="1" parent="1" source="{_esc(t)}" target="{_esc(h)}">'
            f'<mxGeometry relative="1" as="geometry"/></mxCell>')
    return _wrap(f"{erd.title} (Chen)", cells, int(W), int(H))


# orthogonal crow's-foot edge: right-angle routing, arc line-jumps, white labels.
ER_EDGE_ORTHO = ("edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;orthogonalLoop=1;"
                 "jettySize=auto;fontSize=11;fontColor=#000000;"
                 "labelBackgroundColor=#FFFFFF;jumpStyle=arc;jumpSize=10;"
                 "strokeColor=#000000;exitDx=0;exitDy=0;entryDx=0;entryDy=0;")


def _erd_grid(erd: ERD, cols: int = 0) -> str:
    """Pure-Python crow's-foot ERD on a BALANCED grid with ORTHOGONAL edges
    (used when Graphviz is unavailable). Entities are ordered by BFS so related
    ones land in adjacent cells, the grid is sized to a near-square aspect so the
    canvas is never mostly empty (the failure mode of the old force layout), and
    relationships use right-angle connectors with crow's-foot markers + arc
    line-jumps — so it reads cleanly without diagonal spaghetti."""
    by_id = {e.id: e for e in erd.entities}
    sizes = {e.id: _entity_size(e) for e in erd.entities}
    ids = _erd_order(erd)
    n = len(ids)
    if n == 0:
        return _wrap(erd.title, [], 400, 300)
    if cols <= 0:
        # near-square, biased slightly wide (landscape reads better on a page)
        cols = max(1, int(round(math.sqrt(n) * 1.25)))
        cols = min(cols, n)
    rows = (n + cols - 1) // cols

    col_w = max((sizes[i][0] for i in ids), default=ENT_W)
    # generous gaps give the orthogonal router room to skirt boxes (no cut-through)
    col_gap = 150
    row_gap = 110
    cell_w = col_w + col_gap

    row_h = [0.0] * rows
    for idx, eid in enumerate(ids):
        row_h[idx // cols] = max(row_h[idx // cols], sizes[eid][1])
    row_y = [float(MARGIN)]
    for r in range(1, rows):
        row_y.append(row_y[r - 1] + row_h[r - 1] + row_gap)

    boxes: Dict[str, Box] = {}
    gridpos: Dict[str, Tuple[int, int]] = {}
    for idx, eid in enumerate(ids):
        r, c = idx // cols, idx % cols
        w, h = sizes[eid]
        cc = c if r % 2 == 0 else (cols - 1 - c)        # serpentine: keep BFS neighbours close
        x = MARGIN + cc * cell_w + (col_w - w) / 2.0     # centre narrower boxes in their column
        y = row_y[r] + (row_h[r] - h) / 2.0
        boxes[eid] = (x, y, w, h)
        gridpos[eid] = (r, cc)

    width = MARGIN + cols * cell_w - col_gap + MARGIN
    height = int(row_y[-1] + row_h[-1] + MARGIN) if row_y else 600

    cells: List[str] = []
    for eid in ids:
        cells.append(_node_xml(by_id[eid].id, _entity_label(by_id[eid]), ENTITY_STYLE, boxes[eid]))

    # Relationship edges carry NO text; each label is emitted as its own
    # positioned vertex placed in CLEAR space (never on top of an entity box and
    # never on top of another label) so the crow's-foot diagram stays readable.
    box_list = list(boxes.values())

    def _center(b: Box):
        return (b[0] + b[2] / 2.0, b[1] + b[3] / 2.0)

    def _in_box(x: float, y: float, lw: float) -> bool:
        for bx, by, bw, bh in box_list:
            if (bx - 6 <= x + lw / 2 and x - lw / 2 <= bx + bw + 6
                    and by - 6 <= y <= by + bh + 6):
                return True
        return False

    used: List[Tuple[float, float]] = []

    def _clear(x: float, y: float, lw: float):
        # try the midpoint, then walk outward vertically into the row gaps until
        # the spot clears every box; finally de-overlap against other labels.
        for dy in (0, 24, -24, 48, -48, 72, -72, 96, -96, 120, -120, 150, -150):
            yy = y + dy
            if not _in_box(x, yy, lw):
                y = yy
                break
        for _ in range(40):
            if all((x - ux) ** 2 + (y - uy) ** 2 > 24 ** 2 for ux, uy in used):
                break
            y += 20
        used.append((x, y))
        return x, y

    def _channel_pts(a: str, b: str):
        """Waypoints + exit/entry that keep a relation from slicing through a
        third box. Returns (points, exit, entry) or (None, None, None) for the
        default orthogonal route (adjacent cells route fine on their own)."""
        ra, ca = gridpos[a]
        rb, cb = gridpos[b]
        ax, ay, aw, ah = boxes[a]
        bx, by, bw, bh = boxes[b]
        acx, acy = ax + aw / 2, ay + ah / 2
        bcx, bcy = bx + bw / 2, by + bh / 2
        # same row, with at least one box between -> staple over (or under) the row
        if ra == rb and abs(ca - cb) >= 2:
            top = min(ay, by)
            chan = top - row_gap * 0.45            # clear horizontal channel above the row
            if ra == 0:
                chan = max(ay + ah, by + bh) + row_gap * 0.45   # row 0: go under instead
                ex = ent = (0.5, 1.0)
            else:
                ex = ent = (0.5, 0.0)
            return ([(acx, chan), (bcx, chan)], ex, ent)
        # same column, rows apart -> route out to the side channel
        if ca == cb and abs(ra - rb) >= 2:
            right = max(ax + aw, bx + bw)
            chan = right + col_gap * 0.42
            return ([(chan, acy), (chan, bcy)], (1.0, 0.5), (1.0, 0.5))
        return (None, None, None)

    for i, rel in enumerate(erd.relations):
        if rel.src not in boxes or rel.dst not in boxes:
            continue
        sa = ER_CARD.get(rel.src_card, "ERone")
        ea = ER_CARD.get(rel.dst_card, "ERmany")
        pts, ex, ent = _channel_pts(rel.src, rel.dst)
        extra = ""
        if ex is not None:
            extra = (f"exitX={ex[0]};exitY={ex[1]};entryX={ent[0]};entryY={ent[1]};")
        style = (f"{ER_EDGE_ORTHO}{extra}startArrow={sa};startFill=0;"
                 f"endArrow={ea};endFill=0;")
        parr = ""
        if pts:
            inner = "".join(f'<mxPoint x="{px:.0f}" y="{py:.0f}"/>' for px, py in pts)
            parr = f'<Array as="points">{inner}</Array>'
        cells.append(
            f'<mxCell id="r{i}" value="" style="{style}" '
            f'edge="1" parent="1" source="{_esc(rel.src)}" target="{_esc(rel.dst)}">'
            f'<mxGeometry relative="1" as="geometry">{parr}</mxGeometry></mxCell>')
        if rel.label:
            sx, sy = _center(boxes[rel.src])
            dx2, dy2 = _center(boxes[rel.dst])
            lw = max(34, len(rel.label) * 6.6 + 12)
            lx, ly = _clear((sx + dx2) / 2.0, (sy + dy2) / 2.0, lw)
            cells.append(_label_xml(f"rl{i}", rel.label, lx, ly))
    return _wrap(erd.title, cells, int(width), int(height))


def build_erd_drawio(erd: ERD, cols: int = 3) -> str:
    """Conceptual ERD in crow's-foot notation. Uses Graphviz layout when
    available (clean, minimal crossings) and falls back to a balanced
    orthogonal grid otherwise (never the old force layout, which left isolated
    entities marooned in a sea of white)."""
    out = _erd_crowsfoot_gv(erd)
    return out if out is not None else _erd_grid(erd)



def _chen_circular(erd: "ERD", margin: int = 40) -> str:
    """Pure-Python Chen-notation ERD (no Graphviz needed). Concentric rings:
    relationship diamonds sit toward the center (on the chord between their two
    entities), entity rectangles on a middle ring, attribute ellipses fanned out
    on an outer ring grouped beside their entity. Edges are straight lines with
    1 / N cardinality labels. Rendered via the draw.io engine like everything
    else, so it always works even when `neato` is missing."""
    ENT_W, ENT_H = 140, 46
    ATTR_W, ATTR_H = 104, 40
    REL_W, REL_H = 120, 76

    ent_list = []
    for e in erd.entities:
        attrs = [(f"A_{e.id}_{j}", a.name, a.is_pk)
                 for j, a in enumerate(e.attributes)]
        ent_list.append((f"E_{e.id}", e.name, attrs))
    n = len(ent_list)
    if n == 0:
        return _wrap(f"{erd.title} (Chen)", [], 400, 300)
    total_attr = sum(len(a) for _, _, a in ent_list)

    ent_w_max = max((max(ENT_W, 24 + len(nm) * 9) for _, nm, _ in ent_list),
                    default=ENT_W)
    Rent = max(300.0, n * (ent_w_max + 110) / (2 * math.pi))
    attr_cell = ATTR_W + 34
    Rattr = max(Rent + 200.0, total_attr * attr_cell / (2 * math.pi))

    Cx = Cy = Rattr + ATTR_W + margin
    weights = [len(a) + 1.4 for _, _, a in ent_list]
    tw = sum(weights) or 1.0

    positions: Dict[str, Tuple[float, float]] = {}
    sizes: Dict[str, Tuple[int, int]] = {}
    meta: Dict[str, tuple] = {}

    ang = -math.pi / 2.0
    for i, (eid, name, attrs) in enumerate(ent_list):
        slice_w = 2 * math.pi * weights[i] / tw
        center = ang + slice_w / 2.0
        ew = max(ENT_W, 24 + len(name) * 9)
        positions[eid] = (Cx + Rent * math.cos(center),
                          Cy + Rent * math.sin(center))
        sizes[eid] = (ew, ENT_H); meta[eid] = ("ent", name)
        k = len(attrs)
        if k:
            pad = slice_w * 0.12
            lo, hi = ang + pad, ang + slice_w - pad
            for j, (aid, aname, ispk) in enumerate(attrs):
                t = (j + 0.5) / k
                aang = lo + (hi - lo) * t if hi > lo else center
                aw = max(ATTR_W, 20 + len(aname) * 7)
                positions[aid] = (Cx + Rattr * math.cos(aang),
                                  Cy + Rattr * math.sin(aang))
                sizes[aid] = (aw, ATTR_H); meta[aid] = ("attr", aname, ispk)
        ang += slice_w

    edges: List[Tuple[str, str]] = []
    edge_card: Dict[Tuple[str, str], str] = {}
    pair_count: Dict[tuple, int] = {}
    for k, rel in enumerate(erd.relations):
        e1, e2 = f"E_{rel.src}", f"E_{rel.dst}"
        if e1 not in positions or e2 not in positions:
            continue
        rid = f"R_{k}"
        p1, p2 = positions[e1], positions[e2]
        mx, my = (p1[0] + p2[0]) / 2.0, (p1[1] + p2[1]) / 2.0
        # pull a little toward centre so the diamond sits inside the entity ring
        mx += (Cx - mx) * 0.18; my += (Cy - my) * 0.18
        key = tuple(sorted((rel.src, rel.dst)))
        c = pair_count.get(key, 0); pair_count[key] = c + 1
        if c:
            dx, dy = p2[0] - p1[0], p2[1] - p1[1]
            L = math.hypot(dx, dy) or 1.0
            mx += (-dy / L) * 80 * c; my += (dx / L) * 80 * c
        positions[rid] = (mx, my); sizes[rid] = (REL_W, REL_H)
        meta[rid] = ("rel", rel.label)
        edges.append((e1, rid)); edge_card[(e1, rid)] = _CARD_TEXT.get(rel.src_card, "1")
        edges.append((rid, e2)); edge_card[(rid, e2)] = _CARD_TEXT.get(rel.dst_card, "N")
    for eid, _, attrs in ent_list:
        for aid, _, _ in attrs:
            edges.append((eid, aid))

    minx = min(px - sizes[k][0] / 2 for k, (px, _) in positions.items())
    miny = min(py - sizes[k][1] / 2 for k, (_, py) in positions.items())
    maxx = max(px + sizes[k][0] / 2 for k, (px, _) in positions.items())
    maxy = max(py + sizes[k][1] / 2 for k, (_, py) in positions.items())
    ox, oy = margin - minx, margin - miny

    cells: List[str] = []
    for nid, (w, h) in sizes.items():
        px, py = positions[nid]
        box = (px - w / 2 + ox, py - h / 2 + oy, w, h)
        kind = meta[nid][0]
        if kind == "ent":
            cells.append(_node_xml(nid, meta[nid][1], CHEN_ENTITY_STYLE, box))
        elif kind == "rel":
            cells.append(_node_xml(nid, meta[nid][1], CHEN_REL_STYLE, box))
        else:
            lab = f"<u>{_esc(meta[nid][1])}</u>" if meta[nid][2] else _esc(meta[nid][1])
            cells.append(_node_xml(nid, lab, CHEN_ATTR_STYLE, box))
    for i, (t, h) in enumerate(edges):
        card = edge_card.get((t, h), "")
        cells.append(
            f'<mxCell id="ce{i}" value="{_esc(card)}" style="{CHEN_EDGE}" '
            f'edge="1" parent="1" source="{_esc(t)}" target="{_esc(h)}">'
            f'<mxGeometry relative="1" as="geometry"/></mxCell>')
    W = maxx - minx + 2 * margin
    H = maxy - miny + 2 * margin
    return _wrap(f"{erd.title} (Chen)", cells, int(W), int(H))


def build_erd_chen_drawio(erd: ERD, margin: int = 40) -> str:
    """Chen-notation ERD: entities = rectangles, attributes = ellipses,
    relationships = diamonds; cardinality shown as 1 / N.

    Uses the deterministic CONCENTRIC layout (entities on a ring, attributes
    fanned outward, relationship diamonds pulled toward the centre). This is
    balanced by construction and fills the canvas evenly — more reliable than
    Graphviz `neato`, which sometimes marooned weakly connected entities and
    left an empty quadrant. The .drawio.xml stays fully editable in draw.io."""
    return _chen_circular(erd, margin)
