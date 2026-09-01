#!/usr/bin/env python3
"""Export the pitch HTML to PDF via per-slide screenshots.

Chrome print-to-pdf drops near-white backgrounds, so mockup padding
showed the slide gray. Screenshots keep the on-screen colors.
"""
from __future__ import annotations

import shutil
import subprocess
from pathlib import Path

import pymupdf

HERE = Path(__file__).resolve().parent
HTML = HERE / "nexa-advise-pitch.html"
PDF = HERE.parent.parent / "NEXA-ADVISE-KLANTPORTAAL.pdf"
CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
SLIDES = 13
SCALE = 3


def screenshot_slide(n: int, dest: Path) -> None:
    dest.parent.mkdir(parents=True, exist_ok=True)
    if dest.exists():
        dest.unlink()
    url = HTML.as_uri() + f"?shot={n}"
    subprocess.run(
        [
            CHROME,
            "--headless=new",
            "--disable-gpu",
            "--hide-scrollbars",
            f"--force-device-scale-factor={SCALE}",
            "--window-size=1920,1080",
            "--virtual-time-budget=8000",
            f"--screenshot={dest}",
            url,
        ],
        check=True,
        capture_output=True,
    )
    if not dest.exists() or dest.stat().st_size < 10_000:
        raise RuntimeError(f"screenshot failed for slide {n}: {dest}")


def build_pdf(pngs: list[Path], dest: Path) -> None:
    doc = pymupdf.open()
    for png in pngs:
        page = doc.new_page(width=1920, height=1080)
        page.insert_image(page.rect, filename=str(png))
    dest.parent.mkdir(parents=True, exist_ok=True)
    doc.save(dest, deflate=True, deflate_images=True, garbage=4)
    doc.close()


def copy_out(src: Path) -> None:
    copies = [
        HERE.parent.parent / "public/docs/NEXA-ADVISE-KLANTPORTAAL.pdf",
        HERE.parent.parent.parent.parent / "NEXA-ADVISE-KLANTPORTAAL.pdf",
        Path.home() / "Downloads/NEXA-ADVISE-KLANTPORTAAL.pdf",
        Path.home() / "Downloads/NEXA-ADVISE-KLANTPORTAAL-print.pdf",
    ]
    for dest in copies:
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(src, dest)


def main() -> None:
    preview = HERE / "preview"
    preview.mkdir(exist_ok=True)
    shot_dir = preview / "slides"
    shot_dir.mkdir(exist_ok=True)
    pngs = []
    for n in range(1, SLIDES + 1):
        png = shot_dir / f"slide-{n:02d}.png"
        screenshot_slide(n, png)
        pngs.append(png)
        print("shot", n, png.stat().st_size)
    shutil.copy2(shot_dir / "slide-07.png", preview / "p07-white.png")
    build_pdf(pngs, PDF)
    copy_out(PDF)
    print("wrote", PDF)


if __name__ == "__main__":
    main()
