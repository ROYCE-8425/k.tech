# Smart CV Matcher - Agent Skills Registry

## Available Skills

### Core Matching Skills
- **cv-matcher-agent**: Multi-agent orchestration for CV-JD matching
- **rag-knowledge-agent**: Retrieval-augmented generation for grounding
- **explainer-agent**: Explainability and reasoning generation

### Quality Assurance Skills
- **evaluator-agent**: Benchmarking and performance metrics
- **guardrails-agent**: Safety, PII redaction, bias detection

## Installation
```bash
# Skills are auto-discovered from skills/ directory
# No additional installation needed
```

## Usage in Verdent
```
@cv-matcher-agent match candidate 1 with job 1
@rag-knowledge-agent retrieve evidence for "korean hiring culture"
@explainer-agent explain why candidate scored 85
@evaluator-agent evaluate precision@5
@guardrails-agent check for PII in output
```

## Architecture
```
skills/
├── cv-matcher-agent/
│   └── SKILL.md
├── rag-knowledge-agent/
│   └── SKILL.md
├── explainer-agent/
│   └── SKILL.md
├── evaluator-agent/
│   └── SKILL.md
├── guardrails-agent/
│   └── SKILL.md
└── OPENSPEC.md
```

## Contributing
Add new skills by creating a directory under `skills/` with a `SKILL.md` file following the OpenSpec format.

## References
- [OpenSpec Standard](OPENSPEC.md)
- [AI Service Docs](../ai-service/)
- [Backend API Docs](../backend/)
