from __future__ import annotations

import base64
import json
import sys
from io import BytesIO
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from pypdf.generic import DecodedStreamObject, NameObject
from reportlab.graphics import renderPDF, renderSVG
from reportlab.graphics.barcode import qr
from reportlab.graphics.shapes import Drawing
from reportlab.lib.colors import HexColor
from reportlab.pdfgen import canvas


REFERENCE_WIDTH = 612.0
REFERENCE_HEIGHT = 198.0

TICKET_NO_BOX = (474.0, 119.0, 116.0, 24.0)
NAME_BOX = (474.0, 107.0, 116.0, 20.0)
DETAIL_BOX = (474.0, 58.0, 116.0, 46.0)
DETAIL_TEXT_BOX = (478.0, 86.0, 108.0, 12.0)
QR_BOX = (496.0, 31.0, 68.0, 46.0)

TEXT_COLOR = HexColor("#6B0F12")


def scale_box(box: tuple[float, float, float, float], page_width: float, page_height: float):
    x, y, width, height = box
    return (
        x * page_width / REFERENCE_WIDTH,
        y * page_height / REFERENCE_HEIGHT,
        width * page_width / REFERENCE_WIDTH,
        height * page_height / REFERENCE_HEIGHT,
    )


def decode_payload(value: str) -> dict[str, str]:
    try:
        decoded = base64.b64decode(value.encode("utf-8")).decode("utf-8")
        payload = json.loads(decoded)
    except Exception as exc:  # noqa: BLE001
        raise ValueError(f"Unable to decode ticket payload: {exc}") from exc

    if not isinstance(payload, dict):
        raise ValueError("Ticket payload must decode to an object.")

    return {str(key): str(item) for key, item in payload.items()}


def fit_font_size(pdf_canvas: canvas.Canvas, text: str, font_name: str, start_size: float, min_size: float, max_width: float) -> float:
    size = start_size

    while size > min_size and pdf_canvas.stringWidth(text, font_name, size) > max_width:
        size -= 0.25

    return max(size, min_size)


def draw_centered_text(
    pdf_canvas: canvas.Canvas,
    text: str,
    font_name: str,
    start_size: float,
    min_size: float,
    box: tuple[float, float, float, float],
    page_width: float,
    page_height: float,
    y_offset: float = 0.0,
):
    x, y, width, height = scale_box(box, page_width, page_height)
    trimmed = " ".join(text.split()) if text.strip() else "-"
    font_size = fit_font_size(pdf_canvas, trimmed, font_name, start_size, min_size, width - 8)

    pdf_canvas.setFont(font_name, font_size)
    pdf_canvas.drawCentredString(x + (width / 2), y + (height / 2) - (font_size * 0.34) + y_offset, trimmed)


def build_qr_drawing(qr_value: str, size: float) -> Drawing:
    widget = qr.QrCodeWidget(qr_value)
    left, bottom, right, top = widget.getBounds()
    width = right - left or 1
    height = top - bottom or 1
    drawing = Drawing(size, size, transform=[size / width, 0, 0, size / height, 0, 0])
    drawing.add(widget)
    return drawing


def build_overlay(page_width: float, page_height: float, payload: dict[str, str]):
    buffer = BytesIO()
    overlay = canvas.Canvas(buffer, pagesize=(page_width, page_height))
    overlay.setFillColor(TEXT_COLOR)

    draw_centered_text(
        overlay,
        payload.get("ticketCode", ""),
        "Helvetica-Bold",
        13.5,
        8.0,
        TICKET_NO_BOX,
        page_width,
        page_height,
    )
    draw_centered_text(
        overlay,
        payload.get("holderName", ""),
        "Helvetica-Bold",
        10.5,
        6.25,
        NAME_BOX,
        page_width,
        page_height,
        y_offset=-0.5,
    )
    draw_centered_text(
        overlay,
        payload.get("ticketTypePriceLabel", ""),
        "Helvetica-Bold",
        9.25,
        6.0,
        DETAIL_TEXT_BOX,
        page_width,
        page_height,
    )

    qr_value = payload.get("qrValue", payload.get("ticketCode", "")).strip()
    if qr_value:
        qr_x, qr_y, qr_width, qr_height = scale_box(QR_BOX, page_width, page_height)
        qr_size = min(qr_width, qr_height)
        qr_drawing = build_qr_drawing(qr_value, qr_size)
        renderPDF.draw(qr_drawing, overlay, qr_x + ((qr_width - qr_size) / 2), qr_y + ((qr_height - qr_size) / 2))

    overlay.save()

    buffer.seek(0)
    return PdfReader(buffer).pages[0]


def render_ticket_pdf(template_path: Path, payload: dict[str, str]) -> bytes:
    reader = PdfReader(str(template_path))
    writer = PdfWriter()

    for index, page in enumerate(reader.pages):
        if index == 0:
            ensure_trailing_whitespace_in_contents(page)
            overlay_page = build_overlay(float(page.mediabox.width), float(page.mediabox.height), payload)
            page.merge_page(overlay_page)

        writer.add_page(page)

    output = BytesIO()
    writer.write(output)
    return output.getvalue()


def render_qr_svg(qr_value: str) -> str:
    drawing = build_qr_drawing(qr_value, 240.0)
    return renderSVG.drawToString(drawing)


def ensure_trailing_whitespace_in_contents(page) -> None:
    contents = page.get("/Contents")
    if contents is None:
        return

    stream = contents.get_object()
    data = stream.get_data()
    if data.endswith((b"\n", b"\r", b"\t", b" ")):
        return

    normalized = DecodedStreamObject()
    normalized.set_data(data + b"\n")
    page[NameObject("/Contents")] = normalized


def legacy_pdf_mode(template_path: Path, ticket_code: str) -> int:
    payload = {
        "ticketCode": ticket_code.strip(),
        "holderName": "",
        "ticketTypePriceLabel": "",
        "qrValue": ticket_code.strip(),
    }

    if payload["ticketCode"] == "":
        sys.stderr.write("Ticket code is required for PDF stamping.\n")
        return 1

    try:
        sys.stdout.buffer.write(render_ticket_pdf(template_path, payload))
        return 0
    except Exception as exc:  # noqa: BLE001
        sys.stderr.write(f"Unable to stamp the ticket PDF template: {exc}\n")
        return 1


def pdf_mode(template_path: Path, payload_arg: str) -> int:
    try:
        payload = decode_payload(payload_arg)
        ticket_code = payload.get("ticketCode", "").strip()
        if ticket_code == "":
            sys.stderr.write("Ticket code is required for PDF stamping.\n")
            return 1

        sys.stdout.buffer.write(render_ticket_pdf(template_path, payload))
        return 0
    except Exception as exc:  # noqa: BLE001
        sys.stderr.write(f"Unable to stamp the ticket PDF template: {exc}\n")
        return 1


def qr_svg_mode(payload_arg: str) -> int:
    try:
        payload = decode_payload(payload_arg)
        qr_value = payload.get("qrValue", "").strip()
        if qr_value == "":
            sys.stderr.write("QR value is required to render the ticket QR asset.\n")
            return 1

        sys.stdout.write(render_qr_svg(qr_value))
        return 0
    except Exception as exc:  # noqa: BLE001
        sys.stderr.write(f"Unable to render the ticket QR asset: {exc}\n")
        return 1


def main() -> int:
    if len(sys.argv) < 2:
        sys.stderr.write("Ticket asset generation requires a mode.\n")
        return 1

    mode = sys.argv[1].strip().lower()

    if mode == "pdf":
        if len(sys.argv) < 4:
            sys.stderr.write("PDF stamping requires a template path and payload.\n")
            return 1

        template_path = Path(sys.argv[2]).resolve()
        if not template_path.exists():
            sys.stderr.write("Ticket PDF template file was not found.\n")
            return 1

        return pdf_mode(template_path, sys.argv[3])

    if mode == "qr-svg":
        if len(sys.argv) < 3:
            sys.stderr.write("QR asset rendering requires a payload.\n")
            return 1

        return qr_svg_mode(sys.argv[2])

    if len(sys.argv) >= 3:
        template_path = Path(sys.argv[1]).resolve()
        if not template_path.exists():
            sys.stderr.write("Ticket PDF template file was not found.\n")
            return 1

        return legacy_pdf_mode(template_path, sys.argv[2])

    sys.stderr.write("Ticket asset generation arguments are invalid.\n")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
