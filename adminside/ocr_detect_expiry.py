import json
import os
import re
import sys
import zipfile
import tempfile
from datetime import datetime

import pytesseract
from PIL import Image
import fitz  # pymupdf
import cv2
import numpy as np

pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"


def normalize_date(raw: str):
    if not raw:
        return None
    s = raw.strip().replace(".", "/").replace("_", "/")

    m = re.match(r"^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$", s)
    if m:
        y, mo, d = map(int, m.groups())
        try:
            return datetime(y, mo, d).strftime("%Y-%m-%d")
        except ValueError:
            return None

    m = re.match(r"^(\d{1,2})[-/](\d{1,2})[-/](\d{4})$", s)
    if m:
        mo, d, y = map(int, m.groups())
        try:
            return datetime(y, mo, d).strftime("%Y-%m-%d")
        except ValueError:
            return None

    month_map = {
        "jan": 1, "january": 1, "feb": 2, "february": 2, "mar": 3, "march": 3,
        "apr": 4, "april": 4, "may": 5, "jun": 6, "june": 6, "jul": 7, "july": 7,
        "aug": 8, "august": 8, "sep": 9, "sept": 9, "september": 9, "oct": 10,
        "october": 10, "nov": 11, "november": 11, "dec": 12, "december": 12
    }

    m = re.match(r"^([a-zA-Z]+)\s+(\d{1,2}),?\s+(\d{4})$", s)
    if m:
        mon = month_map.get(m.group(1).lower())
        d = int(m.group(2))
        y = int(m.group(3))
        if mon:
            try:
                return datetime(y, mon, d).strftime("%Y-%m-%d")
            except ValueError:
                return None

    m = re.match(r"^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$", s)
    if m:
        d = int(m.group(1))
        mon = month_map.get(m.group(2).lower())
        y = int(m.group(3))
        if mon:
            try:
                return datetime(y, mon, d).strftime("%Y-%m-%d")
            except ValueError:
                return None

    return None


def detect_from_text(text: str):
    if not text:
        return None, None
    t = re.sub(r"\s+", " ", text.lower())

    kw = r"(expiration|expiry|expire|valid until|validity)"
    date_pat = r"(\d{4}[-/]\d{1,2}[-/]\d{1,2}|\d{1,2}[-/]\d{1,2}[-/]\d{4}|[a-zA-Z]+\s+\d{1,2},?\s+\d{4}|\d{1,2}\s+[a-zA-Z]+\s+\d{4})"

    m = re.search(rf"{kw}\s*(date)?\s*[:\-]?\s*{date_pat}", t, re.I)
    if m:
        d = normalize_date(m.group(3))
        if d:
            return d, "keyword_then_date"

    m = re.search(rf"{date_pat}\s*(?:-|to|until)?\s*{kw}", t, re.I)
    if m:
        d = normalize_date(m.group(1))
        if d:
            return d, "date_then_keyword"

    if re.search(kw, t, re.I):
        for p in [
            r"\d{4}[-/]\d{1,2}[-/]\d{1,2}",
            r"\d{1,2}[-/]\d{1,2}[-/]\d{4}",
            r"[a-zA-Z]+\s+\d{1,2},?\s+\d{4}",
            r"\d{1,2}\s+[a-zA-Z]+\s+\d{4}",
        ]:
            for raw in re.findall(p, t, re.I):
                d = normalize_date(raw)
                if d:
                    return d, "keyword_any_date_fallback"

    return None, None


def extract_docx_text(path: str):
    chunks = []
    media_count = 0
    try:
        with zipfile.ZipFile(path, "r") as zf:
            names = set(zf.namelist())
            media_count = len([n for n in names if n.startswith("word/media/")])

            parts = ["word/document.xml"]
            parts += [f"word/header{i}.xml" for i in range(1, 11)]
            parts += [f"word/footer{i}.xml" for i in range(1, 11)]
            parts += ["word/footnotes.xml", "word/endnotes.xml", "word/comments.xml"]

            for part in parts:
                if part in names:
                    data = zf.read(part).decode("utf-8", errors="ignore")
                    data = re.sub(r"</w:t>|</w:p>|</w:tr>|</w:tc>|</w:tbl>|<w:tab/>|<w:br/>", " ", data)
                    data = re.sub(r"<[^>]+>", " ", data)
                    data = re.sub(r"\s+", " ", data)
                    chunks.append(data)
    except Exception:
        return "", 0
    return " ".join(chunks), media_count


def preprocess_for_ocr(pil_img: Image.Image):
    img = np.array(pil_img.convert("RGB"))
    gray = cv2.cvtColor(img, cv2.COLOR_RGB2GRAY)
    gray = cv2.GaussianBlur(gray, (3, 3), 0)
    th = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 31, 15)
    return Image.fromarray(th)


def ocr_image(pil_img: Image.Image):
    try:
        pre = preprocess_for_ocr(pil_img)
        text = pytesseract.image_to_string(pre, config="--oem 3 --psm 6")
        return text or ""
    except Exception:
        return ""


def ocr_pdf(path: str):
    texts = []
    try:
        doc = fitz.open(path)
        for i in range(min(len(doc), 5)):  # first 5 pages for speed
            page = doc.load_page(i)
            pix = page.get_pixmap(matrix=fitz.Matrix(2, 2), alpha=False)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)
            texts.append(ocr_image(img))
        doc.close()
    except Exception:
        return ""
    return "\n".join(texts)


def ocr_docx_images(path: str):
    texts = []
    media_count = 0
    ocr_image_count = 0
    try:
        with zipfile.ZipFile(path, "r") as zf, tempfile.TemporaryDirectory() as td:
            media_files = [name for name in zf.namelist() if name.startswith("word/media/")]
            media_count = len(media_files)

            for name in media_files:
                if name.lower().endswith((".png", ".jpg", ".jpeg", ".bmp", ".tif", ".tiff")):
                    out = os.path.join(td, os.path.basename(name))
                    with open(out, "wb") as f:
                        f.write(zf.read(name))
                    try:
                        img = Image.open(out)
                        texts.append(ocr_image(img))
                        ocr_image_count += 1
                    except Exception:
                        pass
    except Exception:
        return "", 0, 0
    return "\n".join(texts), media_count, ocr_image_count


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "message": "Missing file path"}))
        return

    file_path = sys.argv[1]
    original_filename = sys.argv[2] if len(sys.argv) > 2 else ""
    mime_type = sys.argv[3] if len(sys.argv) > 3 else ""

    if not os.path.exists(file_path):
        print(json.dumps({"success": False, "message": "File not found"}))
        return

    filename = original_filename if original_filename else os.path.basename(file_path)
    ext = os.path.splitext(filename)[1].lower()

    # fallback to mime when extension is missing in temp uploaded file
    if (not ext or ext == ".tmp") and mime_type:
        mt = mime_type.lower()
        if "pdf" in mt:
            ext = ".pdf"
        elif "wordprocessingml" in mt:
            ext = ".docx"

    # 1) Filename quick detect
    date, source = detect_from_text(filename)
    if date:
        print(json.dumps({"success": True, "expiration_date": date, "expiry_source": "filename"}))
        return

    # 2) Native text extraction
    if ext == ".docx":
        text, media_count = extract_docx_text(file_path)
        date, source = detect_from_text(text)
        if date:
            print(json.dumps({
                "success": True,
                "expiration_date": date,
                "expiry_source": "docx_text",
                "debug": {
                    "docx_text_len": len(text),
                    "docx_media_count": media_count,
                    "docx_text_sample": text[:250]
                }
            }))
            return

    # 3) OCR fallback (images/scanned docs)
    if ext == ".docx":
        ocr_text, media_count2, ocr_image_count = ocr_docx_images(file_path)
        date, source = detect_from_text(ocr_text)
        if date:
            print(json.dumps({
                "success": True,
                "expiration_date": date,
                "expiry_source": "docx_ocr",
                "debug": {
                    "docx_media_count": media_count2,
                    "docx_ocr_image_count": ocr_image_count,
                    "docx_ocr_text_len": len(ocr_text),
                    "docx_ocr_sample": ocr_text[:250]
                }
            }))
            return
    elif ext == ".pdf":
        ocr_text = ocr_pdf(file_path)
        date, source = detect_from_text(ocr_text)
        if date:
            print(json.dumps({"success": True, "expiration_date": date, "expiry_source": "pdf_ocr"}))
            return

    debug_payload = {
        "file_ext": ext,
        "filename": filename
    }

    if ext == ".docx":
        text, media_count = extract_docx_text(file_path)
        ocr_text, media_count2, ocr_image_count = ocr_docx_images(file_path)
        lowered = (text + " " + ocr_text).lower()
        debug_payload.update({
            "docx_text_len": len(text),
            "docx_media_count": media_count if media_count else media_count2,
            "docx_ocr_image_count": ocr_image_count,
            "docx_ocr_text_len": len(ocr_text),
            "has_exp_keyword": any(k in lowered for k in ["expiration", "expiry", "expire", "valid until", "validity"]),
            "docx_text_sample": text[:200],
            "docx_ocr_sample": ocr_text[:200]
        })

    print(json.dumps({"success": True, "expiration_date": None, "expiry_source": "none", "debug": debug_payload}))


if __name__ == "__main__":
    main()
