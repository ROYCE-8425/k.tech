"""
Multi-provider LLM abstraction for structured extraction.

Supported providers:
  - openai   (default) — via openai SDK
  - gemini   — via google-generativeai SDK
  - xai      — via OpenAI-compatible endpoint (api.x.ai)

Provider selection: LLM_PROVIDER env var.
Each provider exposes a single `complete_json()` method for structured extraction.
Deterministic scoring is NOT affected by provider choice.

Fallback behavior:
  - If provider key is missing → returns None from create_provider()
  - If provider call fails → caller (ExtractionService) catches and uses FallbackExtractor
"""
from __future__ import annotations

import json
import logging
import os
from abc import ABC, abstractmethod
from typing import Any

logger = logging.getLogger(__name__)


class LLMProvider(ABC):
    """Base abstraction for LLM providers used in structured extraction."""

    @property
    @abstractmethod
    def provider_name(self) -> str:
        """Short identifier for logging/tracing (e.g. 'openai', 'gemini', 'xai')."""
        ...

    @property
    @abstractmethod
    def model_name(self) -> str:
        """The model being used for extraction."""
        ...

    @abstractmethod
    async def complete_json(
        self,
        system_prompt: str,
        user_text: str,
        max_tokens: int = 1024,
        timeout: float = 30.0,
    ) -> dict[str, Any]:
        """Send a structured extraction request and return parsed JSON dict.

        Args:
            system_prompt: System instruction for the extraction task.
            user_text: User-provided text (CV or JD), pre-truncated by caller.
            max_tokens: Maximum tokens in response.
            timeout: Request timeout in seconds.

        Returns:
            Parsed JSON dict from the LLM response.

        Raises:
            Exception on provider errors (caller handles fallback).
        """
        ...

    def check_health(self) -> dict[str, Any]:
        """Return health status for this provider (no secrets exposed)."""
        return {
            "provider": self.provider_name,
            "model": self.model_name,
            "initialized": True,
        }


# ---------------------------------------------------------------------------
# OpenAI Provider
# ---------------------------------------------------------------------------

class OpenAIProvider(LLMProvider):
    """OpenAI chat completion with JSON mode."""

    def __init__(self) -> None:
        import openai  # lazy import

        self._api_key = os.getenv("OPENAI_API_KEY", "")
        self._model = os.getenv(
            "OPENAI_EXTRACTION_MODEL",
            os.getenv("OPENAI_MODEL", "gpt-4o-mini"),
        )
        self._client = openai.AsyncOpenAI(api_key=self._api_key)

    @property
    def provider_name(self) -> str:
        return "openai"

    @property
    def model_name(self) -> str:
        return self._model

    async def complete_json(
        self,
        system_prompt: str,
        user_text: str,
        max_tokens: int = 1024,
        timeout: float = 30.0,
    ) -> dict[str, Any]:
        response = await self._client.chat.completions.create(
            model=self._model,
            response_format={"type": "json_object"},
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_text},
            ],
            temperature=0.0,
            max_tokens=max_tokens,
            timeout=timeout,
        )
        raw = response.choices[0].message.content or "{}"
        return json.loads(raw)

    def check_health(self) -> dict[str, Any]:
        return {
            "provider": self.provider_name,
            "model": self.model_name,
            "key_present": bool(self._api_key),
            "key_prefix": self._api_key[:8] + "..." if len(self._api_key) > 8 else "[short]",
            "initialized": True,
        }


# ---------------------------------------------------------------------------
# Gemini Provider
# ---------------------------------------------------------------------------

class GeminiProvider(LLMProvider):
    """Google Gemini via google-generativeai SDK."""

    def __init__(self) -> None:
        self._api_key = os.getenv("GEMINI_API_KEY", "")
        self._model = os.getenv("GEMINI_EXTRACTION_MODEL", "gemini-2.0-flash")

    @property
    def provider_name(self) -> str:
        return "gemini"

    @property
    def model_name(self) -> str:
        return self._model

    async def complete_json(
        self,
        system_prompt: str,
        user_text: str,
        max_tokens: int = 1024,
        timeout: float = 45.0,
    ) -> dict[str, Any]:
        import google.generativeai as genai  # type: ignore[reportMissingImports]  # lazy optional import

        genai.configure(api_key=self._api_key)
        model = genai.GenerativeModel(
            self._model,
            system_instruction=system_prompt,
            generation_config=genai.GenerationConfig(
                response_mime_type="application/json",
                temperature=0.0,
                max_output_tokens=max_tokens,
            ),
        )

        # google-generativeai uses sync generate_content; wrap for async compat
        import asyncio
        response = await asyncio.wait_for(
            asyncio.to_thread(model.generate_content, user_text),
            timeout=timeout,
        )

        raw = response.text or "{}"
        return json.loads(raw)

    def check_health(self) -> dict[str, Any]:
        return {
            "provider": self.provider_name,
            "model": self.model_name,
            "key_present": bool(self._api_key),
            "key_prefix": self._api_key[:8] + "..." if len(self._api_key) > 8 else "[short]",
            "initialized": True,
        }


# ---------------------------------------------------------------------------
# xAI / Grok Provider (OpenAI-compatible API)
# ---------------------------------------------------------------------------

class XAIProvider(LLMProvider):
    """xAI/Grok via OpenAI-compatible endpoint at api.x.ai."""

    def __init__(self) -> None:
        import openai  # lazy import — reuses openai SDK with custom base_url

        self._api_key = os.getenv("XAI_API_KEY", "")
        self._model = os.getenv("XAI_EXTRACTION_MODEL", "grok-3-mini")
        self._base_url = os.getenv("XAI_BASE_URL", "https://api.x.ai/v1")
        self._client = openai.AsyncOpenAI(
            api_key=self._api_key,
            base_url=self._base_url,
        )

    @property
    def provider_name(self) -> str:
        return "xai"

    @property
    def model_name(self) -> str:
        return self._model

    async def complete_json(
        self,
        system_prompt: str,
        user_text: str,
        max_tokens: int = 1024,
        timeout: float = 30.0,
    ) -> dict[str, Any]:
        response = await self._client.chat.completions.create(
            model=self._model,
            response_format={"type": "json_object"},
            messages=[
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_text},
            ],
            temperature=0.0,
            max_tokens=max_tokens,
            timeout=timeout,
        )
        raw = response.choices[0].message.content or "{}"
        return json.loads(raw)

    def check_health(self) -> dict[str, Any]:
        return {
            "provider": self.provider_name,
            "model": self.model_name,
            "key_present": bool(self._api_key),
            "key_prefix": self._api_key[:8] + "..." if len(self._api_key) > 8 else "[short]",
            "base_url": self._base_url,
            "initialized": True,
        }


# ---------------------------------------------------------------------------
# Provider factory
# ---------------------------------------------------------------------------

_PROVIDER_MAP: dict[str, type[LLMProvider]] = {
    "openai": OpenAIProvider,
    "gemini": GeminiProvider,
    "xai": XAIProvider,
    "grok": XAIProvider,  # alias
}

# Maps provider name → required env var for the API key
_KEY_MAP: dict[str, str] = {
    "openai": "OPENAI_API_KEY",
    "gemini": "GEMINI_API_KEY",
    "xai": "XAI_API_KEY",
    "grok": "XAI_API_KEY",
}


def create_provider() -> LLMProvider | None:
    """Resolve LLM provider from environment configuration.

    Resolution order:
      1. LLM_PROVIDER env var (explicit selection)
      2. Auto-detect from available API keys (OPENAI_API_KEY → openai, etc.)
      3. None (no provider available — extraction falls back to heuristics)

    Returns:
        LLMProvider instance or None if no provider is configured.
    """
    provider_name = os.getenv("LLM_PROVIDER", "").strip().lower()

    if provider_name:
        # Explicit provider selection
        provider_cls = _PROVIDER_MAP.get(provider_name)
        if provider_cls is None:
            logger.warning(
                "Unknown LLM_PROVIDER '%s'. Supported: %s. Falling back to heuristics.",
                provider_name,
                ", ".join(_PROVIDER_MAP.keys()),
            )
            return None

        # Check if the required API key is present
        key_env = _KEY_MAP.get(provider_name, "")
        api_key = os.getenv(key_env, "").strip()
        if not api_key:
            logger.warning(
                "LLM_PROVIDER='%s' selected but %s is not set. Falling back to heuristics.",
                provider_name,
                key_env,
            )
            return None

        try:
            provider = provider_cls()
            logger.info(
                "LLM provider initialized: %s (model=%s)",
                provider.provider_name,
                provider.model_name,
            )
            return provider
        except Exception as exc:
            logger.warning(
                "Failed to initialize LLM provider '%s': %s. Falling back to heuristics.",
                provider_name,
                exc,
            )
            return None

    # Auto-detect from available keys (OpenAI first for backward compat)
    for name in ("openai", "gemini", "xai"):
        key_env = _KEY_MAP[name]
        if os.getenv(key_env, "").strip():
            try:
                provider = _PROVIDER_MAP[name]()
                logger.info(
                    "LLM provider auto-detected from %s: %s (model=%s)",
                    key_env,
                    provider.provider_name,
                    provider.model_name,
                )
                return provider
            except Exception as exc:
                logger.warning(
                    "Auto-detect: %s key found but provider init failed: %s",
                    name,
                    exc,
                )
                continue

    logger.info("No LLM provider configured. Extraction will use heuristic fallback.")
    return None
