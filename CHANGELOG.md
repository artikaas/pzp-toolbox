# Changelog pzp-toolbox

## V0.13
- Stappen zijn losse entries in de sectie Stappen (structure, één niveau) in plaats van een Matrix op de toolbox; de toolbox laadt de eerste vijf in de volgorde van de structuur. Vraagt een schone installatie (zie README).
- Gereedschapskist terug naast Lees meer (120 px), zodat lange tips het blok niet meer oprekken.
- Tabblad Hulpmiddelen weer met het witte lijnicoon.
- Geen scrollen op de achtergrond zolang een paneel open is (scrollbalkruimte blijft staan).
- Rolverdeling: meer ruimte tussen de punten.

## V0.12 (B3: templates en frontend)
- Templates uit prototype V0.10 overgezet naar de Craft-API: `_entries/home`, `_components/wiel`, `stapblok`, `paneel-stap`, `paneel-voordat`, `paneel-menu`, `checklist`, `linkrij`; header en footer vervangen.
- Startertemplates die niet gebruikt worden weggehaald (blokken, pagina, artikel, hero, kaarten).
- CSS: tokens van de toolbox in `@theme` (De Bron-namen blijven bestaan), componenten uit het prototype in `@layer components`; preflight van Tailwind blijft aan, lijsten krijgen hun opsommingsteken expliciet. Koppen krijgen hun lettertype per component.
- JavaScript als modules: `panelen.js`, `wiel.js`, `checklist.js`, `navbar.js` (menu, reveal, video en contactformulier weg).
- Fonts in `web/fonts`, ster en ballonpunt in `src/assets` (via Vite).
- Prototype via de mock en Twing van De Bron: geen fouten, geen kapotte links, geen JavaScript-fouten.

## V0.11 (B2: contentmodel en seed)
- Project gestart vanuit De Bron V1.0 (zie CHANGELOG-starter.md).
- `contentmodel.yaml` volgens de blokkenspecificatie: singles Toolbox en Voordat je begint, sectie Rollen, stappen als Matrix (vast op vijf), globals Algemeen, Toolbox, Labels en Footer.
- `seed/content.json` gegenereerd uit prototype V0.10 met `scripts/prototype-naar-seed.py`; teksten letterlijk.
- Footertekst: 'is geïnspireerd door' in plaats van 'is een product van' (besluit Kurt).
- Prototype V0.10 bewaard in `archief/prototype-v0.10` als bron voor de templates (B3).

## V0.1 tot en met V0.10
- Statisch prototype (twig.js en Tailwind-CLI), zie `archief/prototype-v0.10/docs/afwijkingen.md`.
