# Het statische prototype

`npm run prototype` rendert de Craft-templates zonder Craft. Er is geen PHP of database nodig: alleen Node.

## Hoe het werkt

- `prototype/mock.mjs` doet de Craft-API na met `contentmodel.yaml` en `seed/content.json`. Velden die in de seed ontbreken krijgen dezelfde lege waarde als in Craft. Het meldt seedvelden die niet in het contentmodel staan.
- `prototype/render.mjs` rendert alle pagina's met Twing (Twig voor JavaScript) in **strict mode**, net als Craft in devMode. Een tikfout in een veldnaam geeft dus ook hier een fout. Daarna controleert het of alle interne links en beelden bestaan.
- In de templates is `prototype` true. Ze gebruiken dat voor Netlify Forms in plaats van Contact Form, voor `noindex` en voor paginering via `/pagina-2/` in plaats van `?pagina=2`.
- `prototype/screenshots.mjs` maakt screenshots op 390, 1024 en 1512 px en meldt horizontale scroll. Eenmalig nodig: `npx playwright-core install chromium`. Een eigen Chrome of Chromium kan ook, via `CHROMIUM_PATH`.

## Grenzen

Een prototype is om te laten zien en te testen, niet om live te gaan.

- Geen beeldtransformaties: elk beeld wordt in zijn originele formaat geladen.
- Formulieren alleen via Netlify Forms. Er is geen echte verwerking of opslag.
- Geen zoekfunctie over echte content en geen beheer.
- Gebruik je in templates Craft-functies die de mock niet kent (bijv. `search`, `craft.categories()`, eigen Twig-functies), breid dan `mock.mjs` of `render.mjs` uit. Doe dat niet om de template heen.

## Netlify

- **Drag-and-drop:** sleep `prototype/dist` naar app.netlify.com/drop. Binnen een minuut heb je een deelbare link.
- **Via Git:** koppel de repo. `netlify.toml` bouwt met `npm run prototype` en publiceert `prototype/dist`.
- Zet `PROTOTYPE_URL` in Netlify op de definitieve URL, dan kloppen canonical en og-tags.
- Het prototype staat op `noindex` (robots.txt, meta-tag en header).
