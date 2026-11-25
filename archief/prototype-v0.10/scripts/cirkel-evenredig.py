# Genereert de cirkel met N gelijke segmenten in de stijl van Figma-node 174:427:
# zelfde maten (R 420, r 276, middelpunt 720.5/420), zelfde kleuren, zelfde lip-en-uitsparing tussen de segmenten,
# zelfde staart en zelfde zachte binnenschaduw.
import math, sys
CX, CY, R, r = 720.5, 420.0, 420.0, 276.0
LIP = [(357.0, 7.7), (337.5, 8.1)]   # gemeten aan het einde van het bovenste Figma-segment
KLEUREN = ['#DE5981', '#EDA4BA', '#F2BDCD', '#F7D5DF', '#FCEEF2', '#F9E3EA']
def p(rad, deg):
    a = math.radians(deg); return f"{CX + rad*math.cos(a):.2f} {CY + rad*math.sin(a):.2f}"
def segment(a0, a1, lip_begin, lip_eind):
    groot = 1 if a1 - a0 > 180 else 0
    d = f"M{p(R, a0)} A{R} {R} 0 {groot} 1 {p(R, a1)}"
    if lip_eind:
        d += f" L{p(LIP[0][0], a1 + LIP[0][1])} Q{p(LIP[0][0] - 4, a1 + LIP[0][1] + 1.2)} {p(LIP[1][0], a1 + LIP[1][1])}"
    d += f" L{p(r, a1)} A{r} {r} 0 {groot} 0 {p(r, a0)}"
    if lip_begin:
        d += f" L{p(LIP[1][0], a0 + LIP[1][1])} Q{p(LIP[0][0] - 4, a0 + LIP[0][1] + 1.2)} {p(LIP[0][0], a0 + LIP[0][1])}"
    return d + " Z"
def cirkel(n):
    # Segment 1 tot en met n-1 zijn even groot. Het laatste segment schuift onder segment 1 en de staart door,
    # zodat het zichtbare deel net iets kleiner is dan de rest (zo'n 0,85 x), zoals gevraagd.
    # Zichtbaar eindigt het laatste segment rond 158 graden, waar de staart eroverheen loopt.
    stap = (158 + 360 - 180) / (n - 1 + 0.85)
    delen = {}
    for i in range(n):
        a0 = 180 + i * stap
        a1 = 180 + 360 + 2 if i == n - 1 else a0 + stap
        delen[i + 1] = segment(a0, a1, lip_begin=i > 0, lip_eind=i < n - 1)
    # staart: band r 300..444 rond (CX-720, CY), van 0 tot 87 graden, daarna horizontaal buiten beeld (zoals in Figma)
    sx = CX - 720
    def q(rad, deg):
        a = math.radians(deg); return f"{sx + rad*math.cos(a):.2f} {CY + rad*math.sin(a):.2f}"
    staart = (f"M{q(444, -1)} A444 444 0 0 1 {q(444, 87)} L-3000 {CY + 444*math.sin(math.radians(87)):.2f} "
              f"L-3000 {CY + 300*math.sin(math.radians(87)):.2f} L{q(300, 87)} A300 300 0 0 0 {q(300, -1)} Z")   # -1 graad: overlapt segment 1, geen haarlijn
    regels = ['<svg class="cirkel__svg" viewBox="0 0 1141 888" fill="none" aria-hidden="true" focusable="false" overflow="visible" xmlns="http://www.w3.org/2000/svg">',
              f'{{# Gegenereerd door scripts/cirkel-evenredig.py: {n} segmenten in de stijl van Figma 174:427. Segment 1 begint bij de staart, loopt met de klok mee; het laatste segment ligt onder segment 1. Segmentgrootte {stap:.1f} graden. #}}',
              f'<circle cx="{CX}" cy="{CY}" r="300" fill="#fff"/>']
    # segment 1 en de staart vormen samen één doorlopende vorm (zoals in Figma) en liggen bovenop het laatste segment
    for i in list(range(2, n + 1)) + [1]:
        extra = f'\n<path d="{staart}" fill="{KLEUREN[0]}"/>' if i == 1 else ''
        regels.append(f'<a class="segment segment--{i} transition-[translate,filter] duration-500 ease-veer" href="#stap-{i}" tabindex="-1">\n<path d="{delen[i]}" fill="{KLEUREN[i-1] if i <= len(KLEUREN) else KLEUREN[-1]}"/>{extra}\n</a>')
    regels.append('<g class="cirkel__schaduw" opacity="0.1" filter="url(#cirkel-schaduw)"><path fill-rule="evenodd" clip-rule="evenodd" d="'
                  f'M{CX} {CY-348}A348 348 0 1 1 {CX} {CY+348}A348 348 0 1 1 {CX} {CY-348}ZM{CX} {CY-276}A276 276 0 1 0 {CX} {CY+276}A276 276 0 1 0 {CX} {CY-276}Z" fill="#6F0D3B"/></g>')
    regels.append('<defs><filter id="cirkel-schaduw" x="300" y="0" width="840" height="840" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="12"/></filter></defs>')
    regels.append('</svg>')
    return '\n'.join(regels) + '\n'
for n in (4, 5, 6):
    open(f'templates/_svg/cirkel-{n}.svg', 'w').write(cirkel(n))
print('ok')
