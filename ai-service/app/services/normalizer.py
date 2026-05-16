"""
Skill and seniority normalization service.

Uses static knowledge corpus (JSON files) for deterministic,
explainable normalization. No ML or fuzzy matching — purely
alias→canonical mapping with domain awareness.

Usage:
    from app.services.normalizer import SkillNormalizer
    n = SkillNormalizer()
    n.normalize_skill("reactjs")       # → "React"
    n.normalize_skills(["js", "node"]) # → ["JavaScript", "Node.js"]
    n.normalize_seniority("jr")        # → "junior"
"""
from __future__ import annotations

import json
import logging
import os
from dataclasses import dataclass
from functools import lru_cache
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)

_KNOWLEDGE_DIR = Path(__file__).resolve().parent.parent / "knowledge"


@lru_cache(maxsize=1)
def _load_skill_synonyms() -> dict[str, str]:
    """Load skill synonym mapping (lowercase alias → canonical name)."""
    path = _KNOWLEDGE_DIR / "skill_synonyms.json"
    try:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        # Filter out metadata keys
        return {k: v for k, v in data.items() if not k.startswith("_")}
    except Exception as exc:
        logger.warning("Could not load skill_synonyms.json: %s", exc)
        return {}


@lru_cache(maxsize=1)
def _load_seniority_rules() -> dict[str, Any]:
    """Load seniority normalization rules."""
    path = _KNOWLEDGE_DIR / "seniority_rules.json"
    try:
        with open(path, encoding="utf-8") as f:
            return json.load(f)
    except Exception as exc:
        logger.warning("Could not load seniority_rules.json: %s", exc)
        return {}


@lru_cache(maxsize=1)
def _load_domain_keywords() -> dict[str, Any]:
    """Load domain keyword taxonomy."""
    path = _KNOWLEDGE_DIR / "domain_keywords.json"
    try:
        with open(path, encoding="utf-8") as f:
            return json.load(f)
    except Exception as exc:
        logger.warning("Could not load domain_keywords.json: %s", exc)
        return {}


@lru_cache(maxsize=1)
def _load_skill_relations() -> list[dict[str, Any]]:
    """Load graph-lite skill relations corpus."""
    path = _KNOWLEDGE_DIR / "skill_relations.json"
    try:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
        return data.get("relations", [])
    except Exception as exc:
        logger.warning("Could not load skill_relations.json: %s", exc)
        return []


class SkillNormalizer:
    """Deterministic skill name normalization using static synonym corpus.

    Normalization priority:
      1. Exact lowercase match in synonym table → canonical form
      2. Original skill preserved as-is (no destructive normalization)

    Deduplication: after normalization, duplicate canonical forms are removed.
    """

    def __init__(self) -> None:
        self._synonyms = _load_skill_synonyms()
        # Build reverse index: canonical → set of aliases (for matching enrichment)
        self._canonical_to_aliases: dict[str, set[str]] = {}
        for alias, canonical in self._synonyms.items():
            canon_lower = canonical.lower()
            if canon_lower not in self._canonical_to_aliases:
                self._canonical_to_aliases[canon_lower] = set()
            self._canonical_to_aliases[canon_lower].add(alias)

    def normalize_skill(self, raw: str) -> str:
        """Normalize a single skill name to its canonical form."""
        key = raw.strip().lower()
        if key in self._synonyms:
            return self._synonyms[key]
        return raw.strip()  # preserve original casing if unknown

    def normalize_skills(self, raw_skills: list[str]) -> list[str]:
        """Normalize and deduplicate a list of skill names."""
        seen: dict[str, str] = {}  # canonical_lower → display form
        result: list[str] = []
        for raw in raw_skills:
            canonical = self.normalize_skill(raw)
            canon_lower = canonical.lower()
            if canon_lower not in seen:
                seen[canon_lower] = canonical
                result.append(canonical)
        return result

    def are_equivalent(self, skill_a: str, skill_b: str) -> bool:
        """Check if two skill names resolve to the same canonical form."""
        return self.normalize_skill(skill_a).lower() == self.normalize_skill(skill_b).lower()

    def match_skills(
        self,
        candidate_skills: list[str],
        required_skills: list[str],
    ) -> tuple[list[str], list[str]]:
        """Match candidate skills against required skills using normalization.

        Returns (matched_canonical, missing_canonical).
        A candidate skill matches a required skill if they share the same
        canonical form after normalization.
        """
        # Normalize both sides
        c_normalized = {self.normalize_skill(s).lower(): self.normalize_skill(s) for s in candidate_skills}
        r_normalized = {self.normalize_skill(s).lower(): self.normalize_skill(s) for s in required_skills}

        matched_keys = set(c_normalized.keys()) & set(r_normalized.keys())
        missing_keys = set(r_normalized.keys()) - set(c_normalized.keys())

        matched = sorted([r_normalized[k] for k in matched_keys])
        missing = sorted([r_normalized[k] for k in missing_keys])

        return matched, missing

    @property
    def corpus_size(self) -> int:
        """Number of aliases in the synonym table."""
        return len(self._synonyms)


class SeniorityNormalizer:
    """Deterministic seniority level normalization."""

    CANONICAL_LEVELS = ["intern", "fresher", "junior", "mid", "senior", "lead", "principal"]

    def __init__(self) -> None:
        rules = _load_seniority_rules()
        self._aliases: dict[str, str] = {}
        if "aliases" in rules:
            self._aliases = {k.lower(): v for k, v in rules["aliases"].items()}
        self._level_order: dict[str, int] = rules.get("level_order", {
            level: i for i, level in enumerate(self.CANONICAL_LEVELS)
        })
        self._experience_hints: dict[str, dict] = rules.get("experience_hints", {})

    def normalize(self, raw: str | None) -> str | None:
        """Normalize a seniority string to canonical form."""
        if not raw:
            return None
        key = raw.strip().lower()
        if key in self._aliases:
            return self._aliases[key]
        # Check if already canonical
        if key in self._level_order:
            return key
        return None

    def level_index(self, seniority: str) -> int:
        """Return numeric index for gap scoring. -1 if unknown."""
        return self._level_order.get(seniority, -1)

    def gap(self, candidate: str | None, job: str | None) -> int | None:
        """Compute seniority gap. None if either side is unknown."""
        c = self.normalize(candidate) if candidate else None
        j = self.normalize(job) if job else None
        if not c or not j:
            return None
        c_idx = self.level_index(c)
        j_idx = self.level_index(j)
        if c_idx < 0 or j_idx < 0:
            return None
        return abs(c_idx - j_idx)

    def infer_from_experience(self, years: float | None) -> str | None:
        """Infer seniority from experience years using hints."""
        if years is None:
            return None
        best_match = None
        best_distance = float("inf")
        for level, hint in self._experience_hints.items():
            mid = (hint.get("min", 0) + hint.get("max", 0)) / 2
            dist = abs(years - mid)
            if dist < best_distance:
                best_distance = dist
                best_match = level
        return best_match


class DomainClassifier:
    """Classify job domain from title and keywords."""

    def __init__(self) -> None:
        data = _load_domain_keywords()
        self._families: dict[str, dict] = data.get("job_families", {})
        self._title_map: dict[str, str] = {
            k.lower(): v for k, v in data.get("title_to_family", {}).items()
        }

    def classify_from_title(self, title: str) -> str | None:
        """Classify job family from title."""
        lower = title.strip().lower()
        # Exact match
        if lower in self._title_map:
            return self._title_map[lower]
        # Substring match
        for title_key, family in self._title_map.items():
            if title_key in lower:
                return family
        return None

    def get_domain_keywords(self, family: str) -> list[str]:
        """Get canonical domain keywords for a job family."""
        entry = self._families.get(family, {})
        return entry.get("keywords", [])

    def get_related_skills(self, family: str) -> list[str]:
        """Get skills commonly associated with a job family."""
        entry = self._families.get(family, {})
        return entry.get("related_skills", [])

    def enrich_domain_keywords(self, title: str, existing: list[str]) -> list[str]:
        """Enrich domain keywords using job title classification."""
        family = self.classify_from_title(title)
        if not family:
            return existing
        corpus_keywords = self.get_domain_keywords(family)
        # Merge without duplicates
        existing_lower = {k.lower() for k in existing}
        enriched = list(existing)
        for kw in corpus_keywords:
            if kw.lower() not in existing_lower:
                enriched.append(kw)
                existing_lower.add(kw.lower())
        return enriched


# ---------------------------------------------------------------------------
# Graph-lite skill relation matching (v2: supports bounded two-hop)
# ---------------------------------------------------------------------------

# Two-hop reasoning constraints (locked per Phase 15 spec):
# - Two-hop similarity = hop1_sim * hop2_sim * TWO_HOP_DECAY
# - Hard cap: two-hop similarity never exceeds _TWO_HOP_CAP (0.40)
# - One-hop cap remains at 0.85 (always stronger than two-hop)
# - Maximum _MAX_TWO_HOP_MATCHES per shortlist call
# - No traversal beyond 2 hops
# - One candidate skill covers at most 2 missing job skills (unchanged)

_TWO_HOP_DECAY: float = 0.50
_TWO_HOP_CAP: float = 0.40
_MAX_TWO_HOP_MATCHES: int = 3


@dataclass
class RelatedMatch:
    """A single related-skill match with provenance."""
    candidate_skill: str    # canonical skill the candidate has
    target_skill: str       # canonical skill the job requires
    relation_type: str      # e.g. "framework_of", "alternative_to"
    similarity: float       # 0.0-0.85 (one-hop) or 0.0-0.40 (two-hop)
    hop_count: int = 1      # 1 = one-hop direct, 2 = two-hop indirect
    via_skill: str | None = None  # intermediate skill for two-hop provenance


class SkillRelationGraph:
    """Graph-lite related-skill matching using curated relation corpus.

    Provides partial credit for candidates who have related skills
    to what the job requires, beyond exact/synonym matching.

    v2: Supports bounded two-hop reasoning with strict constraints:
      - Two-hop only triggers when one-hop yields no match
      - Two-hop similarity is always weaker than one-hop
      - One-hop is always weaker than exact/synonym
      - No traversal beyond 2 hops
      - No double counting (one candidate skill covers at most 2 job skills)
    """

    def __init__(self) -> None:
        self._normalizer = SkillNormalizer()
        raw = _load_skill_relations()
        # Build adjacency: source_lower -> list of (target_canonical, relation, similarity)
        self._adj: dict[str, list[tuple[str, str, float]]] = {}
        for entry in raw:
            src = entry.get("source", "").lower()
            tgt = entry.get("target", "")
            rel = entry.get("relation", "adjacent_skill")
            sim = min(0.85, float(entry.get("similarity", 0.0)))
            if src and tgt:
                if src not in self._adj:
                    self._adj[src] = []
                self._adj[src].append((tgt, rel, sim))
        logger.info("SkillRelationGraph loaded %d source nodes", len(self._adj))

    def get_relations(self, canonical_skill: str) -> list[tuple[str, str, float]]:
        """Get all one-hop relations from a canonical skill.

        Returns list of (target_canonical, relation_type, similarity).
        """
        return self._adj.get(canonical_skill.lower(), [])

    def _find_two_hop(
        self,
        candidate_skill_lower: str,
        target_skill_lower: str,
    ) -> tuple[float, str, str, str] | None:
        """Find best two-hop path: candidate_skill -> X -> target_skill.

        Returns (combined_similarity, relation_desc, via_skill, via_canonical)
        or None if no two-hop path exists.
        """
        best_sim = 0.0
        best_result = None

        # Get all one-hop neighbors of the candidate skill
        hop1_edges = self._adj.get(candidate_skill_lower, [])
        for intermediate_skill, rel1, sim1 in hop1_edges:
            intermediate_lower = intermediate_skill.lower()
            # Don't loop back to the candidate skill
            if intermediate_lower == candidate_skill_lower:
                continue

            # Check if intermediate has an edge to the target
            hop2_edges = self._adj.get(intermediate_lower, [])
            for target_canonical, rel2, sim2 in hop2_edges:
                if target_canonical.lower() == target_skill_lower:
                    combined = sim1 * sim2 * _TWO_HOP_DECAY
                    combined = min(combined, _TWO_HOP_CAP)
                    if combined > best_sim:
                        best_sim = combined
                        best_result = (
                            combined,
                            f"{rel1}→{rel2}",
                            intermediate_skill,
                            target_canonical,
                        )

        return best_result if best_result and best_sim >= 0.15 else None

    def find_related_matches(
        self,
        candidate_skills: list[str],
        missing_job_skills: list[str],
        enable_two_hop: bool = True,
    ) -> tuple[list[RelatedMatch], list[str]]:
        """Find related-skill matches for missing job skills.

        Args:
            candidate_skills: Candidate's canonical skills (already normalized).
            missing_job_skills: Job skills not matched by exact/synonym (canonical).
            enable_two_hop: Whether to attempt two-hop reasoning for remaining gaps.

        Returns:
            (related_matches, truly_missing_skills)
            - related_matches: list of RelatedMatch with provenance
            - truly_missing_skills: skills with no exact, synonym, OR related match
        """
        if not missing_job_skills or not candidate_skills:
            return [], list(missing_job_skills)

        # Normalize candidate skills for lookup
        c_canonical = {
            self._normalizer.normalize_skill(s).lower(): self._normalizer.normalize_skill(s)
            for s in candidate_skills
        }

        # Phase 1: One-hop matching
        related: list[RelatedMatch] = []
        still_missing_after_one_hop: list[str] = []
        used_candidate_skills: set[str] = set()

        for job_skill in sorted(missing_job_skills):
            job_canonical = self._normalizer.normalize_skill(job_skill)
            best: RelatedMatch | None = None
            best_sim = 0.0

            for c_lower, c_display in c_canonical.items():
                if c_lower in used_candidate_skills:
                    continue

                for tgt, rel, sim in self.get_relations(c_display):
                    if tgt.lower() == job_canonical.lower() and sim > best_sim:
                        best_sim = sim
                        best = RelatedMatch(
                            candidate_skill=c_display,
                            target_skill=job_canonical,
                            relation_type=rel,
                            similarity=sim,
                            hop_count=1,
                            via_skill=None,
                        )

            if best and best.similarity >= 0.25:
                related.append(best)
                usage_key = best.candidate_skill.lower()
                count = sum(1 for r in related if r.candidate_skill.lower() == usage_key)
                if count >= 2:
                    used_candidate_skills.add(usage_key)
            else:
                still_missing_after_one_hop.append(job_skill)

        # Phase 2: Two-hop matching (only for skills still missing after one-hop)
        if enable_two_hop and still_missing_after_one_hop:
            truly_missing: list[str] = []
            two_hop_count = 0

            for job_skill in still_missing_after_one_hop:
                if two_hop_count >= _MAX_TWO_HOP_MATCHES:
                    truly_missing.append(job_skill)
                    continue

                job_canonical = self._normalizer.normalize_skill(job_skill)
                best_two: RelatedMatch | None = None
                best_two_sim = 0.0

                for c_lower, c_display in c_canonical.items():
                    if c_lower in used_candidate_skills:
                        continue

                    result = self._find_two_hop(c_lower, job_canonical.lower())
                    if result and result[0] > best_two_sim:
                        best_two_sim = result[0]
                        best_two = RelatedMatch(
                            candidate_skill=c_display,
                            target_skill=job_canonical,
                            relation_type=result[1],
                            similarity=result[0],
                            hop_count=2,
                            via_skill=result[2],
                        )

                if best_two and best_two.similarity >= 0.15:
                    related.append(best_two)
                    two_hop_count += 1
                    usage_key = best_two.candidate_skill.lower()
                    count = sum(1 for r in related if r.candidate_skill.lower() == usage_key)
                    if count >= 2:
                        used_candidate_skills.add(usage_key)
                else:
                    truly_missing.append(job_skill)

            return related, truly_missing
        else:
            return related, still_missing_after_one_hop

    @property
    def edge_count(self) -> int:
        """Total number of relation edges."""
        return sum(len(v) for v in self._adj.values())

    @property
    def node_count(self) -> int:
        """Total number of unique source nodes."""
        return len(self._adj)

