"""
FG brand logo generator for plg_system_fgremovegenerator (v2 - correct brand colors,
sampled from FGcodework/plg_fgeditorswitcher/assets/logo.png).
Navy gradient background (#081D32 -> #113758), coral (#FF6B4A) accent.
Motif: a "tag" shape (meta/generator tag) with a coral "no/remove" prohibition slash.
"""
from PIL import Image, ImageDraw
import math

SIZE = 512
NAVY_TOP = (8, 29, 50)       # #081D32
NAVY_BOTTOM = (17, 55, 88)   # #113758
CORAL = (255, 107, 74)       # #FF6B4A
WHITE = (255, 255, 255)

def rounded_rect_mask(size, radius):
    mask = Image.new("L", (size, size), 0)
    d = ImageDraw.Draw(mask)
    d.rounded_rectangle([0, 0, size - 1, size - 1], radius=radius, fill=255)
    return mask

def make_gradient(size, top, bottom):
    grad = Image.new("RGB", (1, size), 0)
    for y in range(size):
        t = y / (size - 1)
        r = int(top[0] + (bottom[0] - top[0]) * t)
        g = int(top[1] + (bottom[1] - top[1]) * t)
        b = int(top[2] + (bottom[2] - top[2]) * t)
        grad.putpixel((0, y), (r, g, b))
    return grad.resize((size, size))

def main():
    bg = make_gradient(SIZE, NAVY_TOP, NAVY_BOTTOM).convert("RGBA")
    mask = rounded_rect_mask(SIZE, radius=100)

    canvas = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
    canvas.paste(bg, (0, 0), mask)

    draw = ImageDraw.Draw(canvas)

    # --- Tag icon (meta/generator tag motif) ---
    cx, cy = SIZE * 0.42, SIZE * 0.52
    tag_w, tag_h = 200, 140
    pts = [
        (cx - tag_w/2 + 40, cy - tag_h/2),
        (cx + tag_w/2, cy - tag_h/2),
        (cx + tag_w/2, cy + tag_h/2),
        (cx - tag_w/2 + 40, cy + tag_h/2),
        (cx - tag_w/2, cy),
    ]
    draw.polygon(pts, fill=WHITE)
    hole_r = 14
    hole_cx, hole_cy = cx - tag_w/2 + 62, cy
    draw.ellipse(
        [hole_cx - hole_r, hole_cy - hole_r, hole_cx + hole_r, hole_cy + hole_r],
        fill=NAVY_TOP
    )

    # --- Coral "no/remove" prohibition ring + slash ---
    ring_cx, ring_cy = SIZE * 0.64, SIZE * 0.66
    ring_r = 92
    ring_width = 26
    bbox = [ring_cx - ring_r, ring_cy - ring_r, ring_cx + ring_r, ring_cy + ring_r]
    draw.ellipse(bbox, outline=CORAL, width=ring_width)
    slash_len = ring_r * 1.35
    angle = math.radians(45)
    dx = slash_len * math.cos(angle)
    dy = slash_len * math.sin(angle)
    draw.line(
        [(ring_cx - dx, ring_cy + dy), (ring_cx + dx, ring_cy - dy)],
        fill=CORAL, width=ring_width
    )

    r, g, b, a = canvas.split()
    a = a.point(lambda p: 255 if p > 127 else 0)
    canvas = Image.merge("RGBA", (r, g, b, a))

    canvas.save("/home/claude/fgremovegenerator_build/logo_v2.png")
    print("Logo v2 generated.")

if __name__ == "__main__":
    main()
