"""
babiv_diagrams — declarative DFD / Crow's-foot ERD builder for BAB IV.

Typical use:

    from babiv_diagrams.model import (DFD, ExternalEntity, Process, DataStore,
                                      Flow, ERD, Entity, Relation)
    from babiv_diagrams.drawio import build_dfd_drawio, build_erd_drawio
    from babiv_diagrams.graphml import build_dfd_graphml, build_erd_graphml
    from babiv_diagrams.render import render_xml

You describe the LOGICAL structure; layout, orthogonal routing, arc line
jumps and crow's-foot markers are computed for you. Then render to PNG with
the bundled draw.io engine so the result is verifiable and print-ready.
"""

from .model import (  # noqa: F401
    DFD, ExternalEntity, Process, DataStore, Flow,
    ERD, Entity, Attribute, Relation,
    CARD_ONE, CARD_MANY, CARD_ONE_MANDATORY, CARD_ZERO_MANY,
)
from .drawio import (build_dfd_drawio, build_erd_drawio, build_erd_chen_drawio,  # noqa: F401
                     render_dfd_graphviz)
from .graphml import build_dfd_graphml, build_erd_graphml  # noqa: F401
from .render import render_xml  # noqa: F401

__all__ = [
    "DFD", "ExternalEntity", "Process", "DataStore", "Flow",
    "ERD", "Entity", "Attribute", "Relation",
    "CARD_ONE", "CARD_MANY", "CARD_ONE_MANDATORY", "CARD_ZERO_MANY",
    "build_dfd_drawio", "build_erd_drawio", "build_erd_chen_drawio",
    "build_dfd_graphml", "build_erd_graphml",
    "render_xml",
]
