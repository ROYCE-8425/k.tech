# CV Matcher Agent Skill

## Description
Multi-agent CV-JD matching orchestration for Smart CV Matcher hackathon.
Implements Level 5 AI architecture with explainability and grounding.

## Triggers
- "match cv to job"
- "score candidate"
- "cv matcher"
- "ai matching"
- "smart cv"

## Capabilities
- Extract structured features from CV and JD
- Retrieve grounding evidence from knowledge corpus
- Compute skill overlap and fit scores
- Generate citation-aware rationale
- Validate and adjust confidence scores

## Architecture
```
ExtractorAgent → RAGAgent → MatcherAgent → ExplainerAgent → CriticAgent
```

## Usage
```python
from app.services.orchestrator import MatchOrchestrator

orchestrator = MatchOrchestrator()
result = await orchestrator.run(payload)
```

## Endpoints
- `POST /api/v1/match` - AI Service
- `POST /api/ml/ai-match` - Laravel Backend

## Dependencies
- fastapi
- pydantic
- numpy

## Version
0.1.0
