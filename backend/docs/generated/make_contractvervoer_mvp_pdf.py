from __future__ import annotations

from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, JpegImagePlugin  # noqa: F401

ROOT = Path(__file__).resolve().parents[3]
SOURCE = ROOT / "backend/docs/NEXA-TAXI-CONTRACTVERVOER-MVP.md"
OUT = ROOT / "backend/docs/NEXA-TAXI-CONTRACTVERVOER-MVP.pdf"

PAGE_W, PAGE_H = 1240, 1754
MARGIN_X = 92
MARGIN_TOP = 88
MARGIN_BOTTOM = 92
BODY_W = PAGE_W - (MARGIN_X * 2)

FONT_REGULAR = "/System/Library/Fonts/Supplemental/Arial.ttf"
FONT_BOLD = "/System/Library/Fonts/Supplemental/Arial Bold.ttf"

ACCENT = "#16a34a"
ACCENT_DARK = "#14532d"
ACCENT_LIGHT = "#dcfce7"


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    path = FONT_BOLD if bold and Path(FONT_BOLD).exists() else FONT_REGULAR
    if not Path(path).exists():
        path = "/System/Library/Fonts/SFNS.ttf"
    return ImageFont.truetype(path, size=size)


F_TITLE = font(44, True)
F_H1 = font(34, True)
F_H2 = font(25, True)
F_BODY = font(22)
F_BODY_BOLD = font(22, True)
F_SMALL = font(17)
F_TABLE = font(17)
F_TABLE_BOLD = font(17, True)


def text_width(draw: ImageDraw.ImageDraw, text: str, fnt: ImageFont.FreeTypeFont) -> int:
    box = draw.textbbox((0, 0), text, font=fnt)
    return box[2] - box[0]


def wrap_to_width(draw: ImageDraw.ImageDraw, text: str, fnt: ImageFont.FreeTypeFont, width: int) -> list[str]:
    if not text:
        return [""]
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words:
        candidate = word if current == "" else current + " " + word
        if text_width(draw, candidate, fnt) <= width:
            current = candidate
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines or [text]


def rounded_rect(draw: ImageDraw.ImageDraw, xy, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


class Renderer:
    def __init__(self):
        self.pages: list[Image.Image] = []
        self.page_no = 0
        self.new_page()

    def new_page(self):
        self.page_no += 1
        self.img = Image.new("RGB", (PAGE_W, PAGE_H), "#f8fafc")
        self.draw = ImageDraw.Draw(self.img)
        self.y = MARGIN_TOP
        self.draw.rectangle((0, 0, PAGE_W, 28), fill=ACCENT)
        footer = f"Nexa Taxi Contractvervoer MVP - pagina {self.page_no}"
        self.draw.text((MARGIN_X, PAGE_H - 54), footer, font=F_SMALL, fill="#64748b")
        self.pages.append(self.img)

    def ensure_space(self, height: int):
        if self.y + height > PAGE_H - MARGIN_BOTTOM:
            self.new_page()

    def paragraph(self, text: str, indent: int = 0, bullet: str | None = None):
        fnt = F_BODY
        prefix_w = 0
        x = MARGIN_X + indent
        if bullet:
            prefix_w = 28
            self.ensure_space(40)
            self.draw.text((x, self.y), bullet, font=F_BODY_BOLD, fill=ACCENT)
        lines = wrap_to_width(self.draw, text, fnt, BODY_W - indent - prefix_w)
        self.ensure_space(len(lines) * 31 + 16)
        for line in lines:
            self.draw.text((x + prefix_w, self.y), line, font=fnt, fill="#334155")
            self.y += 31
        self.y += 11

    def h1(self, text: str):
        if self.y > MARGIN_TOP + 20:
            self.y += 18
        self.ensure_space(72)
        self.draw.text((MARGIN_X, self.y), text, font=F_H1, fill="#0f172a")
        self.y += 52
        self.draw.line((MARGIN_X, self.y, PAGE_W - MARGIN_X, self.y), fill="#cbd5e1", width=2)
        self.y += 28

    def h2(self, text: str):
        self.y += 12
        self.ensure_space(56)
        self.draw.text((MARGIN_X, self.y), text, font=F_H2, fill=ACCENT_DARK)
        self.y += 42

    def code_block(self, lines: list[str]):
        line_h = 27
        height = len(lines) * line_h + 34
        self.ensure_space(height)
        rounded_rect(self.draw, (MARGIN_X, self.y, PAGE_W - MARGIN_X, self.y + height), 16, "#0f172a")
        y = self.y + 18
        mono = font(18)
        for line in lines:
            self.draw.text((MARGIN_X + 24, y), line, font=mono, fill="#e2e8f0")
            y += line_h
        self.y += height + 22

    def table(self, rows: list[list[str]]):
        if not rows:
            return
        cols = len(rows[0])
        if cols == 2:
            widths = [360, BODY_W - 360]
        elif cols == 3:
            widths = [180, 420, BODY_W - 600]
        else:
            widths = [BODY_W // cols] * cols
        cell_pad = 14
        line_h = 23
        wrapped_rows = []
        for r, row in enumerate(rows):
            wrapped = []
            max_lines = 1
            for c, cell in enumerate(row):
                fnt = F_TABLE_BOLD if r == 0 else F_TABLE
                lines = wrap_to_width(self.draw, cell.strip(), fnt, widths[c] - (cell_pad * 2))
                wrapped.append(lines)
                max_lines = max(max_lines, len(lines))
            wrapped_rows.append((wrapped, max_lines * line_h + 26))
        total_h = sum(h for _, h in wrapped_rows)
        self.ensure_space(min(total_h, 720) + 30)
        x0 = MARGIN_X
        for r, (wrapped, row_h) in enumerate(wrapped_rows):
            if self.y + row_h > PAGE_H - MARGIN_BOTTOM:
                self.new_page()
            x = x0
            fill = ACCENT_LIGHT if r == 0 else ("#ffffff" if r % 2 else "#f8fafc")
            for c, lines in enumerate(wrapped):
                self.draw.rectangle((x, self.y, x + widths[c], self.y + row_h), fill=fill, outline="#cbd5e1", width=1)
                y_text = self.y + 13
                fnt = F_TABLE_BOLD if r == 0 else F_TABLE
                color = "#0f172a" if r == 0 else "#334155"
                for line in lines:
                    self.draw.text((x + cell_pad, y_text), line, font=fnt, fill=color)
                    y_text += line_h
                x += widths[c]
            self.y += row_h
        self.y += 28

    def title_page(self):
        self.draw.rectangle((0, 0, PAGE_W, PAGE_H), fill="#f8fafc")
        self.draw.rectangle((0, 0, PAGE_W, 260), fill=ACCENT)
        self.draw.text((MARGIN_X, 96), "Nexa Taxi", font=F_TITLE, fill="#ffffff")
        self.draw.text((MARGIN_X, 158), "Contractvervoer — MVP-plan", font=font(38, True), fill=ACCENT_LIGHT)
        rounded_rect(self.draw, (MARGIN_X, 330, PAGE_W - MARGIN_X, 620), 28, "#ffffff", "#cbd5e1", 2)
        intro = (
            "Implementatieplan voor abonnementen, groepsritten met meerdere stops, "
            "individuele contractritten, chauffeursapp, routeplanning en maandfacturatie."
        )
        y = 380
        for line in wrap_to_width(self.draw, intro, F_BODY, BODY_W - 80):
            self.draw.text((MARGIN_X + 40, y), line, font=F_BODY, fill="#334155")
            y += 34
        self.draw.text((MARGIN_X + 40, 520), "Datum: 2026-06-16", font=F_BODY_BOLD, fill="#0f172a")
        self.draw.text((MARGIN_X + 40, 560), "Module: Nexa Taxi", font=F_BODY, fill="#64748b")
        self.y = 680


def parse_markdown(md: str) -> list[list[str]]:
    return [page.strip().splitlines() for page in md.split("---PAGE---")]


def is_table_start(lines: list[str], idx: int) -> bool:
    return idx + 1 < len(lines) and lines[idx].strip().startswith("|") and set(lines[idx + 1].strip()) <= set("|-: ")


def parse_table(lines: list[str], idx: int) -> tuple[list[list[str]], int]:
    rows = []
    while idx < len(lines) and lines[idx].strip().startswith("|"):
        line = lines[idx].strip()
        if set(line) <= set("|-: "):
            idx += 1
            continue
        rows.append([cell.strip() for cell in line.strip("|").split("|")])
        idx += 1
    return rows, idx


def render():
    md = SOURCE.read_text(encoding="utf-8")
    pages = parse_markdown(md)
    r = Renderer()
    r.title_page()

    first = True
    for page in pages:
        if first:
            first = False
        else:
            r.new_page()
        i = 0
        while i < len(page):
            line = page[i].rstrip()
            if not line:
                i += 1
                continue
            if line.startswith("# "):
                r.h1(line[2:].strip())
                i += 1
                continue
            if line.startswith("## "):
                r.h1(line[3:].strip())
                i += 1
                continue
            if line.startswith("### "):
                r.h2(line[4:].strip())
                i += 1
                continue
            if line.startswith("```"):
                code = []
                i += 1
                while i < len(page) and not page[i].startswith("```"):
                    code.append(page[i])
                    i += 1
                i += 1
                r.code_block(code)
                continue
            if is_table_start(page, i):
                rows, i = parse_table(page, i)
                r.table(rows)
                continue
            stripped = line.strip()
            if stripped.startswith("- "):
                r.paragraph(stripped[2:].strip(), indent=18, bullet="•")
                i += 1
                continue
            if stripped[:2].isdigit() and ". " in stripped[:5]:
                number, rest = stripped.split(". ", 1)
                r.paragraph(rest.strip(), indent=18, bullet=number + ".")
                i += 1
                continue
            r.paragraph(stripped)
            i += 1

    images = r.pages
    first_img, rest = images[0], images[1:]
    first_img.save(OUT, save_all=True, append_images=rest, resolution=150.0)
    print(OUT)


if __name__ == "__main__":
    render()
