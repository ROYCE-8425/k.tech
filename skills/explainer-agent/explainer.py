# Explainer Agent - Implementation

from dataclasses import dataclass
from typing import Any


@dataclass
class ExplainerAgent:
    """Generate human-readable explanations for AI decisions"""
    
    def explain_match(
        self,
        candidate_name: str,
        job_title: str,
        fit_score: float,
        matched_skills: list[str],
        missing_skills: list[str],
        evidence: list[dict[str, Any]],
        language: str = "en"
    ) -> dict[str, Any]:
        """Generate comprehensive match explanation"""
        
        explanations = {
            "en": self._explain_en,
            "kr": self._explain_kr,
            "vn": self._explain_vn,
        }
        
        explainer = explanations.get(language, self._explain_en)
        return explainer(candidate_name, job_title, fit_score, matched_skills, missing_skills, evidence)
    
    def _explain_en(
        self,
        candidate_name: str,
        job_title: str,
        fit_score: float,
        matched_skills: list[str],
        missing_skills: list[str],
        evidence: list[dict[str, Any]]
    ) -> dict[str, Any]:
        """English explanation"""
        reasons = []
        
        # Skill match explanation
        if matched_skills:
            reasons.append(
                f"{candidate_name} matches {len(matched_skills)} required skills: {', '.join(matched_skills)}."
            )
        else:
            reasons.append(f"{candidate_name} does not match any required skills.")
        
        # Missing skills
        if missing_skills:
            reasons.append(
                f"Missing skills: {', '.join(missing_skills)}. "
                f"Consider training or mentorship for these areas."
            )
        
        # Score interpretation
        if fit_score >= 80:
            reasons.append(f"High fit score ({fit_score}/100) indicates strong candidate potential.")
        elif fit_score >= 60:
            reasons.append(f"Medium fit score ({fit_score}/100) suggests potential with some gaps.")
        else:
            reasons.append(f"Low fit score ({fit_score}/100) indicates significant skill gaps.")
        
        # Evidence grounding
        if evidence:
            top_evidence = evidence[0]
            reasons.append(
                f"Recommendation supported by {top_evidence['source']}: "
                f"\"{top_evidence['excerpt'][:100]}...\""
            )
        
        return {
            "language": "en",
            "summary": f"{candidate_name} - {job_title}: {fit_score}/100",
            "reasoning": reasons,
            "recommendation": self._get_recommendation_en(fit_score),
        }
    
    def _explain_kr(
        self,
        candidate_name: str,
        job_title: str,
        fit_score: float,
        matched_skills: list[str],
        missing_skills: list[str],
        evidence: list[dict[str, Any]]
    ) -> dict[str, Any]:
        """Korean explanation"""
        reasons = []
        
        if matched_skills:
            reasons.append(
                f"{candidate_name}님은 {len(matched_skills)}개의 필수 스킬을 보유하고 있습니다: {', '.join(matched_skills)}."
            )
        else:
            reasons.append(f"{candidate_name}님은 필수 스킬을 보유하고 있지 않습니다.")
        
        if missing_skills:
            reasons.append(f"부족한 스킬: {', '.join(missing_skills)}.")
        
        if fit_score >= 80:
            reasons.append(f"높은 적합도 점수 ({fit_score}/100)는 강력한 후보자 잠재력을 나타냅니다.")
        elif fit_score >= 60:
            reasons.append(f"중간 적합도 점수 ({fit_score}/100)는 일부 부족함이 있지만 잠재력이 있습니다.")
        else:
            reasons.append(f"낮은 적합도 점수 ({fit_score}/100)는 상당한 스킬 격차를 나타냅니다.")
        
        return {
            "language": "kr",
            "summary": f"{candidate_name} - {job_title}: {fit_score}/100",
            "reasoning": reasons,
            "recommendation": self._get_recommendation_kr(fit_score),
        }
    
    def _explain_vn(
        self,
        candidate_name: str,
        job_title: str,
        fit_score: float,
        matched_skills: list[str],
        missing_skills: list[str],
        evidence: list[dict[str, Any]]
    ) -> dict[str, Any]:
        """Vietnamese explanation"""
        reasons = []
        
        if matched_skills:
            reasons.append(
                f"{candidate_name} có {len(matched_skills)} kỹ năng phù hợp: {', '.join(matched_skills)}."
            )
        else:
            reasons.append(f"{candidate_name} không có kỹ năng phù hợp.")
        
        if missing_skills:
            reasons.append(f"Kỹ năng còn thiếu: {', '.join(missing_skills)}.")
        
        if fit_score >= 80:
            reasons.append(f"Điểm phù hợp cao ({fit_score}/100) cho thấy tiềm năng ứng viên mạnh.")
        elif fit_score >= 60:
            reasons.append(f"Điểm phù hợp trung bình ({fit_score}/100) cho thấy tiềm năng nhưng còn thiếu sót.")
        else:
            reasons.append(f"Điểm phù hợp thấp ({fit_score}/100) cho thấy khoảng cách kỹ năng đáng kể.")
        
        return {
            "language": "vn",
            "summary": f"{candidate_name} - {job_title}: {fit_score}/100",
            "reasoning": reasons,
            "recommendation": self._get_recommendation_vn(fit_score),
        }
    
    def _get_recommendation_en(self, fit_score: float) -> str:
        if fit_score >= 80:
            return "Strong recommend - Schedule interview immediately"
        elif fit_score >= 60:
            return "Recommend - Consider for next round with skill assessment"
        else:
            return "Not recommended - Significant skill gaps"
    
    def _get_recommendation_kr(self, fit_score: float) -> str:
        if fit_score >= 80:
            return "강력 추천 - 즉시 면접 일정 잡기"
        elif fit_score >= 60:
            return "추천 - 스킬 평가와 함께 다음 라운드 고려"
        else:
            return "비추천 - 상당한 스킬 격차"
    
    def _get_recommendation_vn(self, fit_score: float) -> str:
        if fit_score >= 80:
            return "Đề xuất mạnh - Sắp xếp phỏng vấn ngay"
        elif fit_score >= 60:
            return "Đề xuất - Cân nhắc vòng tiếp theo với đánh giá kỹ năng"
        else:
            return "Không đề xuất - Khoảng cách kỹ năng đáng kể"
