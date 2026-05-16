# RAG Knowledge Agent - Implementation

from dataclasses import dataclass
from typing import Any
import numpy as np


@dataclass
class RAGKnowledgeAgent:
    """Retrieval-Augmented Generation for domain knowledge"""
    
    def __init__(self):
        self.corpus = self._load_corpus()
    
    def _load_corpus(self) -> list[dict[str, Any]]:
        """Load knowledge corpus"""
        return [
            {
                "id": 1,
                "source": "korean-hiring-guide",
                "title": "Korean Hiring Best Practices",
                "excerpt": "Korean employers value clear role-fit evidence and practical project outcomes over academic credentials alone.",
                "tags": ["culture", "hiring", "fit", "korea"],
                "embedding": None,  # Would be populated from pgvector
            },
            {
                "id": 2,
                "source": "jd-best-practices",
                "title": "Job Description Best Practices",
                "excerpt": "Candidates with 60-70% core skills and high learning velocity can still be top performers.",
                "tags": ["skills", "matching", "evaluation"],
                "embedding": None,
            },
            {
                "id": 3,
                "source": "tech-evaluation-note",
                "title": "Technical Evaluation Guidelines",
                "excerpt": "For AI hiring products, explainability and citation-backed ranking improve trust and adoption.",
                "tags": ["ai", "explainability", "trust"],
                "embedding": None,
            },
            {
                "id": 4,
                "source": "korean-business-culture",
                "title": "Korean Business Culture",
                "excerpt": "Hierarchy and respect for seniority are important. Clear communication and punctuality are highly valued.",
                "tags": ["culture", "korea", "business", "communication"],
                "embedding": None,
            },
            {
                "id": 5,
                "source": "cv-screening-guide",
                "title": "CV Screening Guidelines",
                "excerpt": "Look for quantifiable achievements, relevant project experience, and continuous learning indicators.",
                "tags": ["cv", "screening", "evaluation"],
                "embedding": None,
            },
        ]
    
    def _compute_similarity(self, query: str, document: dict[str, Any]) -> float:
        """Compute similarity between query and document (simplified)"""
        query_words = set(query.lower().split())
        doc_text = f"{document.get('title', '')} {document.get('excerpt', '')} {' '.join(document.get('tags', []))}"
        doc_words = set(doc_text.lower().split())
        
        if not query_words or not doc_words:
            return 0.0
        
        intersection = query_words.intersection(doc_words)
        union = query_words.union(doc_words)
        
        return len(intersection) / len(union) if union else 0.0
    
    def retrieve(self, query: str, top_k: int = 3, filters: dict[str, Any] | None = None) -> list[dict[str, Any]]:
        """Retrieve top-k relevant documents"""
        scored_docs = []
        
        for doc in self.corpus:
            # Apply filters if provided
            if filters:
                if "tags" in filters and not any(tag in doc.get("tags", []) for tag in filters["tags"]):
                    continue
                if "source" in filters and doc.get("source") != filters["source"]:
                    continue
            
            score = self._compute_similarity(query, doc)
            scored_docs.append({**doc, "score": score})
        
        # Sort by score descending
        scored_docs.sort(key=lambda x: x["score"], reverse=True)
        
        return scored_docs[:top_k]
    
    def retrieve_for_job(self, job_title: str, job_requirements: str, top_k: int = 3) -> list[dict[str, Any]]:
        """Retrieve relevant knowledge for a specific job"""
        query = f"{job_title} {job_requirements}"
        
        # Add Korean context if relevant
        if "korea" in query.lower() or "korean" in query.lower():
            return self.retrieve(query, top_k=top_k, filters={"tags": ["korea", "culture"]})
        
        return self.retrieve(query, top_k=top_k)
    
    def format_evidence(self, documents: list[dict[str, Any]]) -> list[dict[str, Any]]:
        """Format documents as evidence items"""
        return [
            {
                "source": doc["source"],
                "excerpt": doc["excerpt"],
                "score": round(doc.get("score", 0), 2),
            }
            for doc in documents
        ]
