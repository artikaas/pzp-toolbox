# Genereert neutrale placeholderbeelden (eigen werk, geen rechten van derden) voor de seed.
import random, sys
from PIL import Image, ImageDraw, ImageFont, ImageFilter
OUT = sys.argv[1]
PRIMAIR = (31, 43, 77); ACCENT = (242, 163, 58); VLAK = (244, 241, 234); RAND = (220, 215, 204); DONKER = (20, 28, 51)
def font(sz):
    for f in ['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf']:
        try: return ImageFont.truetype(f, sz)
        except Exception: pass
    return ImageFont.load_default()
def beeld(naam, w, h, seed, palet='licht', label=True):
    rnd = random.Random(seed)
    bg = {'licht': VLAK, 'donker': PRIMAIR, 'accent': ACCENT}[palet]
    img = Image.new('RGB', (w, h), bg)
    d = ImageDraw.Draw(img, 'RGBA')
    kleuren = [PRIMAIR, ACCENT, RAND, DONKER, (255, 255, 255)]
    for i in range(7):
        c = rnd.choice([k for k in kleuren if k != bg])
        r = rnd.randint(int(min(w, h) * .15), int(min(w, h) * .55))
        x = rnd.randint(-r // 2, w); y = rnd.randint(-r // 2, h)
        a = rnd.randint(60, 200)
        if rnd.random() < .5: d.ellipse([x - r, y - r, x + r, y + r], fill=c + (a,))
        else: d.rounded_rectangle([x - r, y - r // 2, x + r, y + r // 2], radius=r // 3, fill=c + (a,))
    img = img.filter(ImageFilter.GaussianBlur(min(w, h) / 90))
    if label:
        d = ImageDraw.Draw(img, 'RGBA')
        t = f'Placeholder {w} x {h}'
        f = font(max(16, w // 45))
        tw = d.textlength(t, font=f)
        pad = w // 80
        x0 = w - pad * 3 - tw
        d.rounded_rectangle([x0, pad, w - pad, pad * 2 + f.size + 10], radius=8, fill=(255, 255, 255, 200))
        d.text((x0 + pad, pad * 1.5 + 2), t, fill=DONKER, font=f)
    img.save(f'{OUT}/{naam}', quality=78, optimize=True, progressive=True)
beeld('header-home.jpg', 2400, 1400, 1, 'donker', label=False)
beeld('header-pagina.jpg', 2400, 1200, 2, 'donker', label=False)
for i in range(1, 7): beeld(f'beeld-{i}.jpg', 1600, 1067, 10 + i, ['licht', 'accent', 'licht'][i % 3])
for i in range(1, 5): beeld(f'stap-{i}.jpg', 800, 600, 20 + i, 'licht')
for i in range(1, 5): beeld(f'artikel-{i}.jpg', 1600, 1067, 30 + i, ['donker', 'licht'][i % 2])
beeld('video-still.jpg', 1920, 1080, 40, 'donker')
beeld('deelbeeld.jpg', 1200, 630, 50, 'donker', label=False)
