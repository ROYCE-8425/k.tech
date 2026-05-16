# RAG Knowledge Agent Skill

## Description
Retrieval-Augmented Generation agent for Korean hiring culture and company-specific knowledge grounding.

## Triggers
- "rag agent"
- "knowledge retrieval"
- "company culture"
- "korean hiring"
- "grounding evidence"

## Capabilities
- Vector search with pgvector
- Domain-specific corpus retrieval
- Citation-backed evidence extraction
- Korean business culture knowledge base

## Knowledge Sources
- Korean hiring best practices
- Company onboarding handbooks
- Tech evaluation guidelines
- Cultural fit assessment criteria

## Usage
```python
from app.services.agents import RAGAgent

rag = RAGAgent()
evidence = rag.run(extracted_features, job_data)
```

## Database Schema
```sql
CREATE TABLE knowledge_documents (
    id SERIAL PRIMARY KEY,
    source VARCHAR(255),
    content TEXT,
    embedding vector(1536),
    tags TEXT[],
    created_at TIMESTAMP
);
```

## Version
0.1.0
