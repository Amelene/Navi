import os
import re
from flask import Flask, request, jsonify
from dotenv import load_dotenv
import requests

load_dotenv()

app = Flask(__name__)

AI_PROVIDER = os.getenv("AI_PROVIDER", "gemini").strip().lower()
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "").strip()
GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-flash-latest").strip()
GROQ_API_KEY = os.getenv("GROQ_API_KEY", "").strip()
GROQ_MODEL = os.getenv("GROQ_MODEL", "llama-3.1-8b-instant").strip()
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


def build_prompt(strengths, improvements):
    strength_text = "None identified" if not strengths else ", ".join(strengths)
    improvement_text = "None identified" if not improvements else ", ".join(improvements)

    return (
        "You are assisting with NSC exam feedback for maritime crew.\n"
        "Create practical, concise recommendations.\n"
        "Return ONLY a numbered list with 3 to 5 items.\n"
        "Each item must be one sentence and actionable.\n\n"
        f"Strengths: {strength_text}\n"
        f"Areas for Improvement: {improvement_text}\n"
    )


def call_gemini(strengths, improvements):
    if not GEMINI_API_KEY:
        return None, {"message": "GEMINI_API_KEY is missing or empty."}

    prompt = build_prompt(strengths, improvements)

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


def call_groq(strengths, improvements):
    if not GROQ_API_KEY:
        return None, {"message": "GROQ_API_KEY is missing or empty."}

    prompt = build_prompt(strengths, improvements)
    url = "https://api.groq.com/openai/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {GROQ_API_KEY}",
        "Content-Type": "application/json",
    }
    payload = {
        "model": GROQ_MODEL,
        "messages": [
            {"role": "system", "content": "You provide concise maritime exam recommendations."},
            {"role": "user", "content": prompt},
        ],
        "temperature": 0.4,
        "max_tokens": 400,
    }

    try:
        resp = requests.post(url, headers=headers, json=payload, timeout=REQUEST_TIMEOUT)
    except Exception as exc:
        return None, {"message": "Groq request failed.", "details": str(exc)}

    if resp.status_code < 200 or resp.status_code >= 300:
        try:
            details = resp.json()
        except Exception:
            details = resp.text
        return None, {
            "message": f"Groq HTTP error {resp.status_code}",
            "details": details,
        }

    try:
        data = resp.json()
    except Exception:
        return None, {"message": "Groq returned invalid JSON.", "details": resp.text}

    text = data.get("choices", [{}])[0].get("message", {}).get("content")

    if not isinstance(text, str) or not text.strip():
        return None, {"message": "Groq returned empty text.", "details": data}

    parsed = parse_recommendations_text(text)
    return parsed, None


@app.post("/recommendations")
def recommendations():
    body = request.get_json(silent=True) or {}
    strengths = body.get("strengths", [])
    improvements = body.get("improvements", [])
    provider = (body.get("provider") or AI_PROVIDER or "gemini").strip().lower()

    if not isinstance(strengths, list):
        strengths = []
    if not isinstance(improvements, list):
        improvements = []

    strengths = [str(x).strip() for x in strengths if str(x).strip()]
    improvements = [str(x).strip() for x in improvements if str(x).strip()]

    if provider == "gemini":
        recs, err = call_gemini(strengths, improvements)
    elif provider == "groq":
        recs, err = call_groq(strengths, improvements)
    else:
        return jsonify({
            "ok": False,
            "error": {
                "message": f"Unsupported provider: {provider}",
                "details": "Supported providers: gemini, groq",
            },
            "recommendations": [],
        }), 400

    if err is not None:
        return jsonify({"ok": False, "error": err, "recommendations": []}), 502

    return jsonify({"ok": True, "recommendations": recs})


@app.get("/health")
def health():
    return jsonify({
        "ok": True,
        "service": "python-ai-service",
        "provider": AI_PROVIDER,
        "models": {
            "gemini": GEMINI_MODEL,
            "groq": GROQ_MODEL,
        },
    })


if __name__ == "__main__":
    port = int(os.getenv("PORT", "5001"))
    app.run(host="127.0.0.1", port=port, debug=True)
