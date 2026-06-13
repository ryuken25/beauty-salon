"""
Emit yEd-compatible .graphml for DFD and ERD.

yEd Live users almost always re-run a layout (e.g. Hierarchical), so the goal
here is: valid GraphML, every node label visible, every edge label visible,
sane non-overlapping initial coordinates, and data stores as a SINGLE node
(never split into three sub-nodes — that is what breaks on auto-layout).
"""

from __future__ import annotations

import html
from typing import Dict, List, Tuple

from .model import DFD, ERD

EXT_W, EXT_H = 150, 64
PROC_W, PROC_H = 140, 140
STORE_W, STORE_H = 168, 46
COL_GAP = 240
V_GAP = 64
MARGIN = 60

_HEAD = """<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<graphml xmlns="http://graphml.graphdrawing.org/xmlns"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:y="http://www.yworks.com/xml/graphml"
  xmlns:yed="http://www.yworks.com/xml/yed/3"
  xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://www.yworks.com/xml/schema/graphml/1.1/ygraphml.xsd">
  <key for="node" id="d6" yfiles.type="nodegraphics"/>
  <key for="edge" id="d10" yfiles.type="edgegraphics"/>
  <graph edgedefault="directed" id="G">
"""
_TAIL = "  </graph>\n</graphml>\n"


def _esc(s: str) -> str:
    return html.escape(str(s), quote=True)


def _stack(ids, x, sizes, mid_y):
    if not ids:
        return {}
    heights = [sizes[i][1] for i in ids]
    total = sum(heights) + V_GAP * (len(ids) - 1)
    y = mid_y - total / 2
    out = {}
    for i in ids:
        w, h = sizes[i]
        out[i] = (x, y, w, h)
        y += h + V_GAP
    return out


def _node(nid, label, x, y, w, h, shape="rectangle", fill="#FFFFFF",
          border="#000000") -> str:
    return f"""    <node id="{_esc(nid)}">
      <data key="d6"><y:ShapeNode>
        <y:Geometry height="{h}" width="{w}" x="{x:.0f}" y="{y:.0f}"/>
        <y:Fill color="{fill}" transparent="false"/>
        <y:BorderStyle color="{border}" type="line" width="1.0"/>
        <y:NodeLabel alignment="center" autoSizePolicy="content" fontSize="12"
          modelName="internal" modelPosition="c" visible="true">{_esc(label)}</y:NodeLabel>
        <y:Shape type="{shape}"/>
      </y:ShapeNode></data>
    </node>
"""


def _edge(eid, src, dst, label) -> str:
    lab = ""
    if label:
        lab = (f'<y:EdgeLabel backgroundColor="#FFFFFF" fontSize="10" '
               f'modelName="centered" visible="true">{_esc(label)}</y:EdgeLabel>')
    return f"""    <edge id="{_esc(eid)}" source="{_esc(src)}" target="{_esc(dst)}">
      <data key="d10"><y:PolyLineEdge>
        <y:LineStyle color="#000000" type="line" width="1.0"/>
        <y:Arrows source="none" target="standard"/>
        {lab}
        <y:BendStyle smoothed="false"/>
      </y:PolyLineEdge></data>
    </edge>
"""


def build_dfd_graphml(dfd: DFD, kind: str = "level") -> str:
    sizes: Dict[str, Tuple[int, int]] = {}
    for e in dfd.externals:
        sizes[e.id] = (EXT_W, EXT_H)
    for p in dfd.processes:
        sizes[p.id] = (PROC_W, PROC_H)
    for s in dfd.stores:
        sizes[s.id] = (STORE_W, STORE_H)

    ext_ids = [e.id for e in dfd.externals]
    proc_ids = [p.id for p in dfd.processes]
    store_ids = [s.id for s in dfd.stores]

    x_ext = MARGIN
    x_proc = x_ext + EXT_W + COL_GAP
    x_store = x_proc + PROC_W + COL_GAP

    def col_h(ids):
        return sum(sizes[i][1] for i in ids) + V_GAP * max(0, len(ids) - 1)
    height = max(col_h(ext_ids), col_h(proc_ids), col_h(store_ids), 320) + 2 * MARGIN
    mid_y = height / 2

    boxes = {}
    boxes.update(_stack(ext_ids, x_ext, sizes, mid_y))
    boxes.update(_stack(proc_ids, x_proc, sizes, mid_y))
    boxes.update(_stack(store_ids, x_store, sizes, mid_y))

    out = [_HEAD]
    for e in dfd.externals:
        x, y, w, h = boxes[e.id]
        out.append(_node(e.id, e.name, x, y, w, h, shape="rectangle"))
    for p in dfd.processes:
        x, y, w, h = boxes[p.id]
        lbl = f"{p.no}\n{p.name}" if p.no else p.name
        out.append(_node(p.id, lbl, x, y, w, h, shape="ellipse"))
    for s in dfd.stores:
        x, y, w, h = boxes[s.id]
        out.append(_node(s.id, f"{s.code}  {s.name}".strip(), x, y, w, h,
                         shape="rectangle", fill="#F5F5F5"))
    for i, f in enumerate(dfd.flows):
        out.append(_edge(f"e{i}", f.src, f.dst, f.label))
    out.append(_TAIL)
    return "".join(out)


def build_erd_graphml(erd: ERD, cols: int = 3) -> str:
    def esize(ent):
        return (200, 36 + len(ent.attributes) * 20 + 12)
    sizes = {e.id: esize(e) for e in erd.entities}
    ids = [e.id for e in erd.entities]
    col_w = max(sizes[i][0] for i in ids) + 140
    n = len(ids)
    rows = (n + cols - 1) // cols
    row_h = [0.0] * rows
    for idx, eid in enumerate(ids):
        row_h[idx // cols] = max(row_h[idx // cols], sizes[eid][1])
    row_y = [MARGIN]
    for r in range(1, rows):
        row_y.append(row_y[r - 1] + row_h[r - 1] + 120)
    boxes = {}
    for idx, eid in enumerate(ids):
        r, c = idx // cols, idx % cols
        w, h = sizes[eid]
        boxes[eid] = (MARGIN + c * col_w, row_y[r], w, h)

    by_id = {e.id: e for e in erd.entities}
    out = [_HEAD]
    for eid in ids:
        ent = by_id[eid]
        rows_txt = [ent.name]
        for a in ent.attributes:
            pref = "PK " if a.is_pk else ("FK " if a.is_fk else "")
            rows_txt.append(pref + a.name)
        x, y, w, h = boxes[eid]
        out.append(_node(eid, "\n".join(rows_txt), x, y, w, h, shape="rectangle"))
    for i, rel in enumerate(erd.relations):
        card = {"one": "1", "many": "N", "mandone": "1", "zeromany": "0..N"}
        lbl = f"{card.get(rel.src_card,'1')}  {rel.label}  {card.get(rel.dst_card,'N')}"
        out.append(_edge(f"r{i}", rel.src, rel.dst, lbl))
    out.append(_TAIL)
    return "".join(out)
