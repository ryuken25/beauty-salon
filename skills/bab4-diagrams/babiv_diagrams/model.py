"""
Logical data model for DFD and ERD diagrams.

The whole point of this package is that you DO NOT hand-write mxGraph
coordinates. You describe the *logical* structure (entities, processes,
data stores, typed flows / relations) and the builders compute a clean,
orthogonal, non-overlapping layout for you.

All flow labels follow the project standard:
  - input  to a store/process : data_xxx
  - output from a store/process: info_xxx

Nothing here renders anything; see drawio.py / graphml.py / render.py.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import List, Optional


# --------------------------------------------------------------------------- #
# DFD
# --------------------------------------------------------------------------- #
@dataclass
class ExternalEntity:
    """A terminator / external entity (drawn as a rectangle)."""
    id: str
    name: str


@dataclass
class Process:
    """A process bubble (drawn as an ellipse). `no` is the DFD number, e.g. '1.0' or '1.1'."""
    id: str
    no: str
    name: str


@dataclass
class DataStore:
    """A data store (open-ended box). `code` is e.g. 'D1', `name` is the table name."""
    id: str
    code: str
    name: str


@dataclass
class Flow:
    """A directed data flow. `src`/`dst` reference node ids.

    label must already follow the data_/info_ convention.
    """
    src: str
    dst: str
    label: str


@dataclass
class DFD:
    title: str
    externals: List[ExternalEntity] = field(default_factory=list)
    processes: List[Process] = field(default_factory=list)
    stores: List[DataStore] = field(default_factory=list)
    flows: List[Flow] = field(default_factory=list)

    def node_kind(self, node_id: str) -> str:
        if any(e.id == node_id for e in self.externals):
            return "external"
        if any(p.id == node_id for p in self.processes):
            return "process"
        if any(s.id == node_id for s in self.stores):
            return "store"
        raise KeyError(f"Unknown node id in flow: {node_id!r}")


# --------------------------------------------------------------------------- #
# ERD (Crow's foot)
# --------------------------------------------------------------------------- #
@dataclass
class Attribute:
    name: str
    is_pk: bool = False
    is_fk: bool = False


@dataclass
class Entity:
    id: str
    name: str
    attributes: List[Attribute] = field(default_factory=list)

    @classmethod
    def make(cls, id: str, name: str, pk: Optional[str] = None,
             fks: Optional[List[str]] = None, attrs: Optional[List[str]] = None) -> "Entity":
        fks = fks or []
        attrs = attrs or []
        a: List[Attribute] = []
        if pk:
            a.append(Attribute(pk, is_pk=True))
        for f in fks:
            a.append(Attribute(f, is_fk=True))
        for x in attrs:
            a.append(Attribute(x))
        return cls(id=id, name=name, attributes=a)


# cardinality keywords accepted on relations
CARD_ONE = "one"            # exactly one      ->  ER 'one' bar
CARD_MANY = "many"          # zero/one .. many ->  crow's foot
CARD_ONE_MANDATORY = "mandone"
CARD_ZERO_MANY = "zeromany"


@dataclass
class Relation:
    src: str               # entity id
    dst: str               # entity id
    label: str             # verb phrase, e.g. 'memiliki'
    src_card: str = CARD_ONE
    dst_card: str = CARD_MANY


@dataclass
class ERD:
    title: str
    entities: List[Entity] = field(default_factory=list)
    relations: List[Relation] = field(default_factory=list)
