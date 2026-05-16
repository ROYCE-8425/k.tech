# Explainer Agent Skill

## Description
Explainability agent that generates human-readable rationale for AI matching decisions.

## Triggers
- "explain match"
- "why this candidate"
- "match reasoning"
- "explain score"
- "citation rationale"

## Capabilities
- Generate structured reasoning chains
- Cite evidence sources
- Highlight skill matches/gaps
- Provide actionable recommendations
- Support multiple languages (EN/KR/VN)

## Output Format
```json
{
  "reasoning": [
    "Matched 3 core skills: python, django, postgresql.",
    "Missing important skills: kubernetes, aws.",
    "Grounded by top evidence source: jd-best-practices."
  ],
  "evidence": [
    {
      "source": "korean-hiring-guide",
      "excerpt": "...",
      "score": 0.8
    }
  ]
}
```

## Usage
```python
from app.services.agents import ExplainerAgent

explainer = ExplainerAgent()
reasons = explainer.run(matching_result, evidence)
```

## Version
0.1.0
