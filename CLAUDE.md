# Instructies voor Claude in dit project

Dit project is gemaakt met de Artica website-starter. De werkwijze staat in de skill **figma-naar-website** en in `docs/`. Hieronder de regels die je bij elk verzoek toepast.

## Werkwijze
- Nederlands, informeel-professioneel, kort. Geen em-dashes.
- Stop na elke fase voor akkoord. Gebruik daarvoor het stoppuntformat: wat er klaar is, open punten, afwijkingen van de starter, wat de gebruiker moet doen, en de vraag om akkoord.
- Neem teksten uit Figma letterlijk over. Verzin niets. Ontbrekende beelden, fonts of teksten worden placeholders en open punten.
- Het ontwerp gaat voor, niet de starter. Bouw wat nodig is en noteer het in `docs/afwijkingen.md`. Stel bij herbruikbare afwijkingen voor om de starter uit te breiden (`docs/uitbreiden.md`).
- Wijs op de rechten van fonts en beelden. Werk richting klant gaat langs de creatief eindverantwoordelijke en de eindredactie.

## Techniek
- Het contentmodel staat in `contentmodel.yaml`. Na een wijziging: `npm run prototype` en, in Craft, `ddev craft site/setup/schema`.
- Haal nooit velden weg en hernoem nooit handles via de YAML op een site met content. Dat doe je in het CP (zie `docs/contentmodel.md`).
- `site/setup/seed` overschrijft de demo-entries en globals. Vraag dat altijd eerst en bied de handmatige route in het CP aan.
- Templates draaien met strict variables. Velden die per type kunnen ontbreken benader je met `??`.
- Twig-valkuilen: de linkerkant van `??` moet een variabele of attribuut zijn, geen expressie. Zet `('a' ~ b)` tussen haakjes naast `??`. Splits accenttekst niet na `|e` (zie de macro `accent`).
- Ruimte tussen blokken komt van `.blokken`, niet van het blok. Gebruik alleen tokens uit `@theme` voor kleuren en tekststijlen.
- Controleer voor elk stoppunt: `npm run prototype` (geen fouten of kapotte links), `npm run screenshots` (390, 1024 en 1512 px naast Figma, geen horizontale scroll) en `php -l` op gewijzigde PHP. Laat grotere PHP-wijzigingen nalopen door een aparte reviewer.
- Werk je in een map op de computer van de gebruiker: gebruik git, controleer met md5-sommen voor en na het terugzetten, en laat geen testbestanden achter.

## Versies
V0.x tijdens het concept, V1.0 is de eerste interne definitieve versie, V2.0 de definitieve versie richting klant. Zet de versie in `package.json`, `CHANGELOG.md` en in de namen van zips.
