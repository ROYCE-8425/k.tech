# Guardrails Agent Skill

## Description
Safety and guardrails agent for CV Matcher AI - prevents hallucination, bias, and PII leaks.

## Triggers
- "guardrails"
- "safety check"
- "pii redaction"
- "bias detection"
- "content moderation"

## Capabilities
- PII detection and redaction
- Bias detection (gender, age, region)
- Hallucination prevention
- Off-topic rejection
- Confidence threshold enforcement
- Fallback to rule-based scoring

## Guardrails
```yaml
pii_guardrails:
  - name: email_redaction
    pattern: "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}"
    action: redact
  
  - name: phone_redaction
    pattern: "\\b\\d{3}[-.]?\\d{3}[-.]?\\d{4}\\b"
    action: redact

bias_guardrails:
  - name: gender_neutral
    check: avoid_gendered_language
    action: flag
  
  - name: age_blind
    check: remove_age_indicators
    action: redact

confidence_guardrails:
  - name: low_confidence_fallback
    threshold: 0.3
    action: fallback_to_rule_based
```

## Usage
```python
from app.services.guardrails import GuardrailsAgent

guardrails = GuardrailsAgent()
safe_output = guardrails.check(raw_output)
```

## Version
0.1.0
