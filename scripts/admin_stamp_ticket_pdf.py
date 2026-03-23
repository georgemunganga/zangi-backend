from __future__ import annotations

import sys
from io import BytesIO
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from reportlab.pdfgen import canvas


BOX_WIDTH = 172
BOX_HEIGHT = 28
BOX_RADIUS = 10
BOX_MARGIN_RIGHT = 18
BOX_MARGIN_BOTTOM = 18


def build_overlay(page_width: float, page_height: float, ticket_code: str):
    buffer = BytesIO()
    overlay = canvas.Canvas(buffer, pagesize=(page_width, page_height))
    x = max(12, page_width - BOX_WIDTH - BOX_MARGIN_RIGHT)
    y = max(12, BOX_MARGIN_BOTTOM)

    overlay.setFillColorRGB(1, 1, 1)
    overlay.setStrokeColorRGB(23 / 255, 33 / 255, 38 / 255)
    overlay.setLineWidth(0.8)
    overlay.roundRect(x, y, BOX_WIDTH, BOX_HEIGHT, BOX_RADIUS, stroke=1, fill=1)

    overlay.setFillColorRGB(23 / 255, 33 / 255, 38 / 255)
    overlay.setFont("Helvetica-Bold", 14)
    overlay.drawCentredString(x + (BOX_WIDTH / 2), y + 9.5, f"Ticket No: {ticket_code}")
    overlay.save()

    buffer.seek(0)
    return PdfReader(buffer).pages[0]


def main() -> int:
    if len(sys.argv) < 3:
        sys.stderr.write("Ticket PDF stamping requires a template path and ticket code.\n")
        return 1

    template_path = Path(sys.argv[1]).resolve()
    ticket_code = sys.argv[2].strip()

    if not template_path.exists():
        sys.stderr.write("Ticket PDF template file was not found.\n")
        return 1

    if ticket_code == "":
        sys.stderr.write("Ticket code is required for PDF stamping.\n")
        return 1

    try:
        reader = PdfReader(str(template_path))
        writer = PdfWriter()

        for index, page in enumerate(reader.pages):
            if index == 0:
                overlay_page = build_overlay(float(page.mediabox.width), float(page.mediabox.height), ticket_code)
                page.merge_page(overlay_page)

            writer.add_page(page)

        output = BytesIO()
        writer.write(output)
        sys.stdout.buffer.write(output.getvalue())

        return 0
    except Exception as exc:  # noqa: BLE001
        sys.stderr.write(f"Unable to stamp the ticket PDF template: {exc}\n")
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
