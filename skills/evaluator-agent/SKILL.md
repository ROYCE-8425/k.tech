# Evaluator Agent Skill

## Description
Evaluation and benchmarking suite for CV Matcher AI performance metrics.

## Triggers
- "evaluate model"
- "benchmark scores"
- "precision at k"
- "ndcg score"
- "model performance"

## Capabilities
- Compute Precision@K
- Compute nDCG (normalized Discounted Cumulative Gain)
- Calculate MAE/RMSE for regression
- Generate confusion matrices
- Compare against baselines

## Metrics
| Metric | Description | Target |
|--------|-------------|--------|
| Precision@5 | Top-5 accuracy | ≥ 0.85 |
| nDCG@10 | Ranking quality | ≥ 0.80 |
| MAE | Mean absolute error | ≤ 5.0 |
| RMSE | Root mean squared error | ≤ 7.0 |

## Usage
```python
from app.services.evaluator import EvaluatorAgent

evaluator = EvaluatorAgent()
metrics = evaluator.evaluate(predictions, ground_truth)
```

## Test Dataset Format
```json
{
  "cv_jd_pairs": [
    {
      "candidate_id": 1,
      "job_id": 1,
      "human_score": 85,
      "ai_score": 82
    }
  ]
}
```

## Version
0.1.0
