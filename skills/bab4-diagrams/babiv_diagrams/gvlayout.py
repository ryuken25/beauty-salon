"""
gvlayout — compute clean node positions (and edge routing waypoints) with
Graphviz, so diagrams come out with minimal edge crossings and no lines
cutting through boxes.

We only use Graphviz for *geometry*. The actual diagram (crow's-foot ER
markers, Chen ellipses/diamonds, DFD shapes) is still emitted as draw.io XML
and rendered with the bundled draw.io engine via Playwright — so the look is
consistent and verifiable.

If the `dot`/`neato` binary is missing, callers fall back to the built-in
pure-Python layout in drawio.py.

Coordinates returned are in PIXELS with a top-left origin (draw.io convention).
"""

from __future__ import annotations

import shutil
import subprocess
from typing import Dict, List, Tuple

Pt = Tuple[float, float]
PX_PER_INCH = 96.0


def have(engine: str = "dot") -> bool:
    return shutil.which(engine) is not None


def _quote(s: str) -> str:
    return '"' + s.replace("\\", "\\\\").replace('"', '\\"') + '"'


def layout(node_sizes: Dict[str, Tuple[float, float]],
           edges: List[Tuple[str, str]],
           *, engine: str = "dot", rankdir: str = "LR",
           nodesep: float = 0.6, ranksep: float = 1.2,
           splines: str = "spline",
           directed: bool = True,
           overlap: str = "false",
           timeout: int = 40):
    """Return (positions, edge_paths, (W,H)).

    positions: {id: (cx, cy)}            centre of each node, px, top-left origin
    edge_paths: [(tail, head, [pts])]    spline waypoints per edge, px
    (W, H): canvas size in px
    """
    ids = list(node_sizes)
    alias = {nid: f"n{i}" for i, nid in enumerate(ids)}
    rev = {v: k for k, v in alias.items()}

    head = "digraph" if directed else "graph"
    conn = "->" if directed else "--"
    lines = [f"{head} G {{"]
    g = [f"rankdir={rankdir}", f"nodesep={nodesep}", f"ranksep={ranksep}",
         f"splines={splines}"]
    if engine in ("neato", "fdp", "sfdp"):
        g.append(f"overlap={overlap}")
        g.append("sep=\"+18,18\"")
    lines.append("  graph [" + ", ".join(g) + "];")
    lines.append("  node [shape=box, fixedsize=true];")
    for nid, (w, h) in node_sizes.items():
        lines.append(f"  {alias[nid]} [width={w / PX_PER_INCH:.3f}, "
                     f"height={h / PX_PER_INCH:.3f}];")
    for t, h in edges:
        if t in alias and h in alias:
            lines.append(f"  {alias[t]} {conn} {alias[h]};")
    lines.append("}")
    src = "\n".join(lines)

    out = subprocess.run([engine, "-Tplain"], input=src,
                         capture_output=True, text=True, timeout=timeout)
    if out.returncode != 0:
        raise RuntimeError(f"graphviz {engine} failed: {out.stderr.strip()}")

    W = H = 0.0
    pos: Dict[str, Pt] = {}
    raw_edges: List[Tuple[str, str, List[Pt]]] = []
    for line in out.stdout.splitlines():
        p = line.split()
        if not p:
            continue
        if p[0] == "graph":
            W = float(p[2]); H = float(p[3])
        elif p[0] == "node":
            name = p[1]
            pos[rev[name]] = (float(p[2]), float(p[3]))
        elif p[0] == "edge":
            n = int(p[3])
            coords = p[4:4 + 2 * n]
            pts = [(float(coords[i]), float(coords[i + 1]))
                   for i in range(0, 2 * n, 2)]
            raw_edges.append((rev.get(p[1]), rev.get(p[2]), pts))

    def cv(pt: Pt) -> Pt:
        # inches, bottom-left origin -> px, top-left origin
        return (pt[0] * PX_PER_INCH, (H - pt[1]) * PX_PER_INCH)

    P = {k: cv(v) for k, v in pos.items()}
    E = [(t, h, [cv(pt) for pt in pts]) for t, h, pts in raw_edges]
    return P, E, (W * PX_PER_INCH, H * PX_PER_INCH)
