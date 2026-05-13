import os
import re
import json
from flask import Flask, request, jsonify
from dotenv import load_dotenv
import requests

load_dotenv()

app = Flask(__name__)

GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "").strip()
GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-flash-latest").strip()
REQUEST_TIMEOUT = int(os.getenv("AI_TIMEOUT_SECONDS", "25"))


def parse_recommendations_text(text: str):
    lines = re.split(r"\r?\n+", text or "")
    items = []

    for line in lines:
        clean = line.strip()
        if not clean:
            continue

        clean = re.sub(r"^\s*(?:[-*•]+|\d+[\).\s-]+)\s*", "", clean).strip()
        if clean:
            items.append(clean)

    deduped = []
    seen = set()
    for item in items:
        key = item.lower()
        if key not in seen:
            seen.add(key)
            deduped.append(item)

    return deduped[:5]


def call_gemini(strengths, improvements):
    if not GEMINI_API_KEY:
        return None, {"message": "GEMINI_API_KEY is missing or empty."}

    strength_text = "None identified" if not strengths else ", ".join(strengths)
    improvement_text = "None identified" if not improvements else ", ".join(improvements)

    prompt = (
        "You are assisting with NSC exam feedback for maritime crew.\n"
        "Create practical, concise recommendations.\n"
        "Return ONLY a numbered list with 3 to 5 items.\n"
        "Each item must be one sentence and actionable.\n\n"
        f"Strengths: {strength_text}\n"
        f"Areas for Improvement: {improvement_text}\n"
    )

    url = (
        "https://generativelanguage.googleapis.com/v1beta/models/"
        f"{requests.utils.quote(GEMINI_MODEL, safe='')}:generateContent"
        f"?key={requests.utils.quote(GEMINI_API_KEY, safe='')}"
    )

    payload = {
        "contents": [{"parts": [{"text": prompt}]}],
        "generationConfig": {"temperature": 0.4, "maxOutputTokens": 400},
    }

    try:
        resp = requests.post(url, json=payload, timeout=REQUEST_TIMEOUT)
    except Exception as exc:
        return None, {"message": "Gemini request failed.", "details": str(exc)}

    if resp.status_code < 200 or resp.status_code >= 300:
        details = None
        try:
            details = resp.json()
        except Exception:
            details = resp.text
        return None, {
            "message": f"Gemini HTTP error {resp.status_code}",
            "details": details,
        }

    try:
        data = resp.json()
    except Exception:
        return None, {"message": "Gemini returned invalid JSON.", "details": resp.text}

    text = (
        data.get("candidates", [{}])[0]
        .get("content", {})
        .get("parts", [{}])[0]
        .get("text")
    )

    if not isinstance(text, str) or not text.strip():
        return None, {"message": "Gemini returned empty text.", "details": data}

    parsed = parse_recommendations_text(text)
    return parsed, None


@app.post("/recommendations")
def recommendations():
    body = request.get_json(silent=True) or {}
    strengths = body.get("strengths", [])
    improvements = body.get("improvements", [])
    provider = (body.get("provider") or "gemini").strip().lower()

    if not isinstance(strengths, list):
        strengths = []
    if not isinstance(improvements, list):
        improvements = []

    strengths = [str(x).strip() for x in strengths if str(x).strip()]
    improvements = [str(x).strip() for x in improvements if str(x).strip()]

    if provider != "gemini":
        return jsonify({
            "ok": False,
            "error": {
                "message": f"Unsupported provider: {provider}",
                "details": "Currently only 'gemini' is implemented."
            }
        }), 400

    recommendations, err = call_gemini(strengths, improvements)

    if err is not None:
        return jsonify({"ok": False, "error": err, "recommendations": []}), 502

    return jsonify({"ok": True, "recommendations": recommendations})


@app.get("/health")
def health():
    return jsonify({
        "ok": True,
        "service": "python-ai-service",
        "provider": "gemini",
        "model": GEMINI_MODEL
    })


if __name__ == "__main__":
    port = int(os.getenv("PORT", "5001"))
    app.run(host="127.0.0.1", port=port, debug=True)
