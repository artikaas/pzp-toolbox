# PZP-toolbox V0.10 (statisch prototype)

Twentse PZP-toolbox voor OWDWS. Eén pagina met de cirkel, vijf stappen, een "Lees meer"-paneel per stap en het paneel "Voordat je begint" achter de ster. Staat op noindex. Niet voor livegang.

## Bouwen en bekijken
- `npm install`
- `npm run prototype` rendert `templates/` met `seed/content.json` naar `prototype/dist/`, bouwt de CSS met de Tailwind-CLI (v4) en meldt kapotte interne links.
- `npm run screenshots` maakt screenshots op 390, 1024 en 1512 px in `prototype/screenshots/` en meldt horizontale scroll. Zet zo nodig `CHROMIUM_PATH`.
- Delen: sleep `prototype/dist` naar Netlify Drop.

## Opbouw (Craft-klaar, zelfde indeling als De Bron)
- `templates/_layouts`, `_partials`, `_blokken` (hero-cirkel, stap), `_components` (panelen, checklist, linkrij, macros), `_svg` (cirkel per aantal stappen)
- `seed/content.json` alle inhoud, letterlijk uit het brondocument (`scripts/bron-naar-seed.py`)
- `src/css/app.css` Tailwind-invoer: tokens in `@theme`, componentstijlen in `@layer components`; animaties als Tailwind-klassen in de templates, `src/js/app.js` panelen en checklist (slaat niets op)
- `web/fonts`, `web/assets` (assets uit Figma, read-only geëxporteerd)
- `scripts/cirkel-evenredig.py` maakt `templates/_svg/cirkel-4/5/6.svg` (evenredige cirkel in Figma-stijl)
- `docs/meta.xml` nulmeting Figma (frame 174:423), `docs/afwijkingen.md`

## Fonts
- Railroad Gothic ATF Black zelf gehost (aangeleverd OTF, omgezet naar woff2). Anton (OFL) is alleen nog reserve.
- Campton Book, SemiBold en Bold zelf gehost (omgezet uit de aangeleverde OTF's). Medium ontbreekt; de knop gebruikt SemiBold.
- Bebas Neue zelf gehost (OFL, via Fontsource).
- Later alles zelf hosten; webfontlicentie Campton controleren vóór livegang.
