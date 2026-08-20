#!/usr/bin/env python3
"""Composite a clean contract-app 'Vandaag' screen into the lifestyle phone photo."""

from __future__ import annotations

import math

from PIL import Image, ImageChops, ImageDraw, ImageFilter, ImageFont

PORTAL = "/Users/tosun/Projecten/nexa-saas/nexa-cursor/backend/public/assets/marketing/images/feature-contract-portal.png"
OUT = "/Users/tosun/Projecten/nexa-saas/nexa-cursor/backend/public/assets/marketing/images/feature-contract-vandaag.png"
FONT = "/System/Library/Fonts/HelveticaNeue.ttc"

BG = (18, 18, 20, 255)
CARD = (28, 28, 30, 255)
LINE = (255, 255, 255, 28)
TEXT = (255, 255, 255, 255)
MUTED = (156, 163, 175, 255)
SOFT = (209, 213, 219, 255)
ORANGE = (249, 115, 22, 255)
GREEN = (34, 197, 94, 255)
RED = (239, 68, 68, 255)
PILL_BLUE_BG = (30, 58, 110, 255)
PILL_BLUE_FG = (147, 197, 253, 255)
PILL_GRAY_BG = (46, 48, 53, 255)
PILL_GRAY_FG = (203, 213, 225, 255)
CHROME = (22, 22, 24, 255)

# Inner LCD corners (TL, TR, BR, BL) and the content band below the header.
SCREEN = [(720, 90), (1040, 78), (1112, 842), (748, 854)]


def lerp(a, b, t):
    return (a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t)


def content_quad():
    tl, tr, br, bl = SCREEN
    top = 0.255
    bot = 0.858
    inset = 0.018
    top_l = lerp(lerp(tl, tr, inset), lerp(bl, br, inset), top)
    top_r = lerp(lerp(tl, tr, 1 - inset), lerp(bl, br, 1 - inset), top)
    bot_r = lerp(lerp(tl, tr, 1 - inset), lerp(bl, br, 1 - inset), bot)
    bot_l = lerp(lerp(tl, tr, inset), lerp(bl, br, inset), bot)
    return [top_l, top_r, bot_r, bot_l]


def solve(A, b):
    n = len(b)
    M = [row[:] + [b[i]] for i, row in enumerate(A)]
    for i in range(n):
        pivot = max(range(i, n), key=lambda r: abs(M[r][i]))
        M[i], M[pivot] = M[pivot], M[i]
        div = M[i][i]
        for j in range(i, n + 1):
            M[i][j] /= div
        for r in range(n):
            if r == i:
                continue
            f = M[r][i]
            for j in range(i, n + 1):
                M[r][j] -= f * M[i][j]
    return [M[i][n] for i in range(n)]


def perspective_coeffs(source, dest):
    matrix, b = [], []
    for (x, y), (u, v) in zip(dest, source):
        matrix.append([x, y, 1, 0, 0, 0, -u * x, -u * y])
        b.append(u)
        matrix.append([0, 0, 0, x, y, 1, -v * x, -v * y])
        b.append(v)
    return tuple(solve(matrix, b))


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(FONT, size, index=1 if bold else 0)


def rounded_mask(size, radius: int) -> Image.Image:
    m = Image.new("L", size, 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, size[0] - 1, size[1] - 1), radius=radius, fill=255)
    return m


def text_size(f, text):
    l, t, r, b = f.getbbox(text)
    return r - l, b - t


def t(d, xy, text, f, fill):
    d.text(xy, text, font=f, fill=fill, anchor="lt")


def draw_status_bar(d, w, s):
    t(d, (16 * s, 12 * s), "09:41", font(15 * s, True), TEXT)
    x = w - 18 * s
    d.rounded_rectangle((x - 22 * s / 3, 14 * s, x, 24 * s), radius=3, outline=TEXT, width=max(2, s))
    d.rectangle((x - 18 * s / 3, 17 * s, x - 5 * s / 3, 21 * s), fill=TEXT)
    d.rectangle((x + s / 3, 17 * s, x + s, 21 * s), fill=TEXT)
    d.arc((x - 44 * s / 3, 12 * s, x - 26 * s / 3, 28 * s), start=200, end=340, fill=TEXT, width=max(2, s))
    d.ellipse((x - 36 * s / 3, 20 * s, x - 32 * s / 3, 24 * s), fill=TEXT)
    for i, hgt in enumerate((5, 8, 11, 14)):
        bx = x - 78 * s / 3 + i * 6 * s / 3
        d.rectangle((bx, 24 * s - hgt * s / 3, bx + 3 * s / 3, 24 * s), fill=TEXT)


def draw_sun(d, cx, cy, r):
    d.ellipse((cx - r, cy - r, cx + r, cy + r), outline=MUTED, width=max(2, r // 5))
    for ang in range(0, 360, 45):
        rad = math.radians(ang)
        d.line(
            (
                cx + int((r + 3) * math.cos(rad)),
                cy + int((r + 3) * math.sin(rad)),
                cx + int((r + 10) * math.cos(rad)),
                cy + int((r + 10) * math.sin(rad)),
            ),
            fill=MUTED,
            width=max(2, r // 6),
        )


def draw_icon_cal(d, x, y, size, color, width):
    d.rounded_rectangle((x, y + size * 0.12, x + size, y + size), radius=max(2, size // 8), outline=color, width=width)
    d.line((x, y + size * 0.38, x + size, y + size * 0.38), fill=color, width=width)
    d.line((x + size * 0.28, y, x + size * 0.28, y + size * 0.28), fill=color, width=width)
    d.line((x + size * 0.72, y, x + size * 0.72, y + size * 0.28), fill=color, width=width)


def draw_icon_list(d, x, y, size, color, width):
    d.rounded_rectangle((x, y, x + size, y + size), radius=max(2, size // 8), outline=color, width=width)
    for frac in (0.32, 0.52, 0.72):
        d.line((x + size * 0.2, y + size * frac, x + size * 0.8, y + size * frac), fill=color, width=width)


def draw_icon_x(d, x, y, size, color, width):
    d.ellipse((x, y, x + size, y + size), outline=color, width=width)
    m = size * 0.28
    d.line((x + m, y + m, x + size - m, y + size - m), fill=color, width=width)
    d.line((x + size - m, y + m, x + m, y + size - m), fill=color, width=width)


def draw_icon_user(d, x, y, size, color, width):
    head = size * 0.22
    d.ellipse((x + size / 2 - head, y + size * 0.08, x + size / 2 + head, y + size * 0.08 + head * 2), outline=color, width=width)
    d.arc((x + size * 0.08, y + size * 0.42, x + size * 0.92, y + size * 1.15), start=200, end=340, fill=color, width=width)


def pill(d, xy, text, bg, fg, f, s):
    tw, th = text_size(f, text)
    pad_x, pad_y = 6 * s, 4 * s
    x, y = xy
    box = (x, y, x + tw + pad_x * 2, y + th + pad_y * 2)
    radius = (box[3] - box[1]) / 2
    d.rounded_rectangle(box, radius=radius, fill=bg)
    t(d, (x + pad_x, y + pad_y), text, f, fg)
    return int(box[2] + 4 * s), int(box[3] - box[1])


def render_card(scale: int = 3) -> Image.Image:
    """Ride card only, on the app background, for compositing into the photo screen."""
    s = scale
    w, h = 358 * s, 455 * s
    img = Image.new("RGBA", (w, h), BG)
    d = ImageDraw.Draw(img)
    pad = 4 * s
    card_left, card_top = pad, pad
    card_right, inner = w - pad, pad + 14 * s
    inner_right = card_right - 14 * s
    d.rounded_rectangle((card_left, card_top, card_right, h - pad), radius=14 * s, fill=CARD)

    cy = card_top + 14 * s
    t(d, (inner, cy), "Jan de Vries", font(17 * s, True), TEXT)
    chx = inner_right - 16 * s
    d.polygon([(chx, cy + 12 * s), (chx + 14 * s, cy + 12 * s), (chx + 7 * s, cy)], fill=MUTED)
    cy += 26 * s
    t(d, (inner, cy), "Heen 08:00", font(13 * s, False), MUTED)
    cy += 28 * s
    t(d, (inner, cy), "HEEN", font(11 * s, True), MUTED)
    cy += 22 * s

    dot = 11 * s
    text_x = inner + dot + 10 * s
    pickup_dot_y = cy + 2 * s
    drop_dot_y = pickup_dot_y + 86 * s
    dash_x = inner + dot // 2
    dash_y = pickup_dot_y + dot + 4
    while dash_y < drop_dot_y - 4:
        d.line((dash_x, dash_y, dash_x, min(dash_y + 7, drop_dot_y - 4)), fill=(156, 163, 175, 150), width=max(2, s))
        dash_y += 13

    def stop(dot_y, color, label, main, sub):
        d.ellipse((inner, dot_y, inner + dot, dot_y + dot), fill=color)
        t(d, (text_x, dot_y), label, font(13 * s, True), SOFT)
        t(d, (text_x, dot_y + 20 * s), main, font(15 * s, True), TEXT)
        t(d, (text_x, dot_y + 42 * s), sub, font(12 * s, False), MUTED)

    stop(pickup_dot_y, GREEN, "Ophalen", "Stationsplein 1", "Amsterdam")
    stop(drop_dot_y, ORANGE, "Afzetten", "Basisschool De Linden", "Schoolstraat 10, Amsterdam")

    pill_f = font(12 * s, True)
    px, py = inner, drop_dot_y + 72 * s
    row_h = 0
    for label, bg, fg in (
        ("Gepland", PILL_BLUE_BG, PILL_BLUE_FG),
        ("Nog niet opgehaald", PILL_GRAY_BG, PILL_GRAY_FG),
        ("Bestemming nog niet bereikt", PILL_GRAY_BG, PILL_GRAY_FG),
    ):
        tw, _th = text_size(pill_f, label)
        width = tw + 10 * s
        if px + width > inner_right and px > inner:
            px = inner
            py += row_h + 6 * s
        nx, ph = pill(d, (px, py), label, bg, fg, pill_f, s)
        row_h = ph
        px = nx

    cy = py + row_h + 16 * s
    btn_f = font(13 * s, True)
    btn = "Afmelden"
    tw, th = text_size(btn_f, btn)
    btn_h, btn_w = th + 12 * s, tw + 24 * s
    d.rounded_rectangle((inner, cy, inner + btn_w, cy + btn_h), radius=8 * s, fill=RED)
    t(d, (inner + 12 * s, cy + 6 * s), btn, btn_f, TEXT)
    return img


def expand_quad(quad, px: float):
    cx = sum(p[0] for p in quad) / 4
    cy = sum(p[1] for p in quad) / 4
    out = []
    for x, y in quad:
        dx, dy = x - cx, y - cy
        n = (dx * dx + dy * dy) ** 0.5 or 1
        out.append((x + px * dx / n, y + px * dy / n))
    return out


def fill_quad(base: Image.Image, quad, color) -> None:
    overlay = Image.new("RGBA", base.size, (0, 0, 0, 0))
    ImageDraw.Draw(overlay).polygon([(int(x), int(y)) for x, y in quad], fill=color)
    blurred = overlay.filter(ImageFilter.GaussianBlur(radius=1.6))
    base.alpha_composite(blurred)


def screen_clip(size, quad) -> Image.Image:
    mask = Image.new("L", size, 0)
    ImageDraw.Draw(mask).polygon([(int(x), int(y)) for x, y in quad], fill=255)
    return mask.filter(ImageFilter.GaussianBlur(radius=0.7))


def main() -> None:
    portal = Image.open(PORTAL).convert("RGBA")
    card = render_card(scale=3)
    card.convert("RGB").save("/tmp/screen-flat.png")
    dest = content_quad()
    cover = expand_quad(dest, 16)
    cw, ch = card.size
    src = [(0, 0), (cw - 1, 0), (cw - 1, ch - 1), (0, ch - 1)]
    fill_quad(portal, cover, BG)
    coeffs = perspective_coeffs(src, dest)
    warped = card.transform(portal.size, Image.Transform.PERSPECTIVE, coeffs, Image.Resampling.BICUBIC)
    clip = screen_clip(portal.size, dest)
    r, g, b, a = warped.split()
    warped = Image.merge("RGBA", (r, g, b, ImageChops.multiply(a, clip)))
    out = Image.alpha_composite(portal, warped).convert("RGB")
    out.save(OUT, "PNG", optimize=True)
    out.crop((660, 40, 1160, 920)).save("/tmp/vandaag-phone.png")
    out.resize((768, 512)).save("/tmp/vandaag-preview.png")
    print("wrote", OUT, "quad", dest)


if __name__ == "__main__":
    main()
