# Evaluator Agent - Implementation

from dataclasses import dataclass
from typing import Any
import numpy as np


@dataclass
class EvaluatorAgent:
    """Benchmarking and performance metrics for CV Matcher"""
    
    def precision_at_k(self, predictions: list[dict], ground_truth: list[dict], k: int = 5) -> float:
        """Calculate Precision@K"""
        if not predictions or not ground_truth:
            return 0.0
        
        # Sort by score descending
        sorted_preds = sorted(predictions, key=lambda x: x.get("fit_score", 0), reverse=True)
        top_k = sorted_preds[:k]
        
        # Count relevant items in top-k
        relevant = 0
        for pred in top_k:
            gt_item = next((gt for gt in ground_truth if gt["candidate_id"] == pred["candidate_id"]), None)
            if gt_item and gt_item.get("human_score", 0) >= 70:
                relevant += 1
        
        return round(relevant / k, 4)
    
    def ndcg_at_k(self, predictions: list[dict], ground_truth: list[dict], k: int = 10) -> float:
        """Calculate nDCG@K"""
        if not predictions or not ground_truth:
            return 0.0
        
        # Sort predictions by score
        sorted_preds = sorted(predictions, key=lambda x: x.get("fit_score", 0), reverse=True)
        top_k = sorted_preds[:k]
        
        # Calculate DCG
        dcg = 0.0
        for i, pred in enumerate(top_k):
            gt_item = next((gt for gt in ground_truth if gt["candidate_id"] == pred["candidate_id"]), None)
            relevance = gt_item.get("human_score", 0) / 100.0 if gt_item else 0
            dcg += relevance / np.log2(i + 2)
        
        # Calculate ideal DCG
        ideal_relevances = sorted([gt.get("human_score", 0) / 100.0 for gt in ground_truth], reverse=True)
        idcg = sum(rel / np.log2(i + 2) for i, rel in enumerate(ideal_relevances[:k]))
        
        return round(dcg / idcg, 4) if idcg > 0 else 0.0
    
    def mae(self, predictions: list[dict], ground_truth: list[dict]) -> float:
        """Mean Absolute Error"""
        errors = []
        for pred in predictions:
            gt_item = next((gt for gt in ground_truth if gt["candidate_id"] == pred["candidate_id"]), None)
            if gt_item:
                errors.append(abs(pred.get("fit_score", 0) - gt_item.get("human_score", 0)))
        
        return round(np.mean(errors), 2) if errors else 0.0
    
    def rmse(self, predictions: list[dict], ground_truth: list[dict]) -> float:
        """Root Mean Squared Error"""
        errors = []
        for pred in predictions:
            gt_item = next((gt for gt in ground_truth if gt["candidate_id"] == pred["candidate_id"]), None)
            if gt_item:
                errors.append((pred.get("fit_score", 0) - gt_item.get("human_score", 0)) ** 2)
        
        return round(np.sqrt(np.mean(errors)), 2) if errors else 0.0
    
    def evaluate(self, predictions: list[dict], ground_truth: list[dict]) -> dict[str, Any]:
        """Run full evaluation suite"""
        return {
            "precision_at_5": self.precision_at_k(predictions, ground_truth, 5),
            "ndcg_at_10": self.ndcg_at_k(predictions, ground_truth, 10),
            "mae": self.mae(predictions, ground_truth),
            "rmse": self.rmse(predictions, ground_truth),
            "total_samples": len(predictions),
        }
