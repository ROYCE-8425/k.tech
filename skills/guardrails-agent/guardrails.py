# Guardrails Agent - Implementation

import re
from dataclasses import dataclass
from typing import Any


@dataclass
class GuardrailsAgent:
    """Safety and guardrails for CV Matcher AI"""
    
    PII_PATTERNS = {
        "email": re.compile(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"),
        "phone": re.compile(r"\b\d{3}[-.]?\d{3}[-.]?\d{4}\b"),
        "ssn": re.compile(r"\b\d{3}-\d{2}-\d{4}\b"),
    }
    
    BIAS_KEYWORDS = {
        "gender": ["male", "female", "man", "woman", "he", "she", "his", "her"],
        "age": ["young", "old", "senior", "junior", "fresh graduate", "retired"],
        "region": ["rural", "urban", "province", "countryside"],
    }
    
    def redact_pii(self, text: str) -> str:
        """Redact personally identifiable information"""
        result = text
        for pii_type, pattern in self.PII_PATTERNS.items():
            result = pattern.sub(f"[{pii_type.upper()}_REDACTED]", result)
        return result
    
    def check_bias(self, text: str) -> list[dict[str, Any]]:
        """Check for biased language"""
        flags = []
        text_lower = text.lower()
        
        for bias_type, keywords in self.BIAS_KEYWORDS.items():
            found = [kw for kw in keywords if kw in text_lower]
            if found:
                flags.append({
                    "type": bias_type,
                    "keywords_found": found,
                    "severity": "medium",
                })
        
        return flags
    
    def check_confidence(self, fit_score: float, confidence: float, threshold: float = 0.3) -> dict[str, Any]:
        """Check if confidence meets threshold"""
        if confidence < threshold:
            return {
                "passed": False,
                "reason": f"Confidence {confidence} below threshold {threshold}",
                "action": "fallback_to_rule_based",
            }
        return {"passed": True}
    
    def check_hallucination(self, evidence: list[dict[str, Any]], claims: list[str]) -> list[dict[str, Any]]:
        """Check for unsupported claims"""
        unsupported = []
        evidence_text = " ".join([e.get("excerpt", "") for e in evidence]).lower()
        
        for claim in claims:
            # Simple check: claim should be supported by evidence
            claim_keywords = set(claim.lower().split())
            evidence_keywords = set(evidence_text.split())
            overlap = claim_keywords.intersection(evidence_keywords)
            
            if len(overlap) < len(claim_keywords) * 0.3:
                unsupported.append({
                    "claim": claim,
                    "confidence": "low",
                    "reason": "Insufficient evidence support",
                })
        
        return unsupported
    
    def check(self, output: dict[str, Any]) -> dict[str, Any]:
        """Run full guardrails check"""
        results = {
            "pii_check": {"passed": True, "redacted_text": output.get("text", "")},
            "bias_check": {"passed": True, "flags": []},
            "confidence_check": {"passed": True},
            "hallucination_check": {"passed": True, "unsupported": []},
        }
        
        # PII check
        if "text" in output:
            redacted = self.redact_pii(output["text"])
            results["pii_check"]["redacted_text"] = redacted
            results["pii_check"]["passed"] = redacted == output["text"]
        
        # Bias check
        text_to_check = output.get("text", "") + " ".join(output.get("reasoning", []))
        bias_flags = self.check_bias(text_to_check)
        if bias_flags:
            results["bias_check"]["passed"] = False
            results["bias_check"]["flags"] = bias_flags
        
        # Confidence check
        if "fit_score" in output and "confidence" in output:
            conf_check = self.check_confidence(output["fit_score"], output["confidence"])
            results["confidence_check"] = conf_check
        
        # Hallucination check
        if "reasoning" in output and "evidence" in output:
            unsupported = self.check_hallucination(output["evidence"], output["reasoning"])
            if unsupported:
                results["hallucination_check"]["passed"] = False
                results["hallucination_check"]["unsupported"] = unsupported
        
        # Overall pass/fail
        results["passed"] = all([
            results["pii_check"]["passed"],
            results["bias_check"]["passed"],
            results["confidence_check"].get("passed", True),
            results["hallucination_check"]["passed"],
        ])
        
        return results
