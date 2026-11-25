# Uitbreiden: nieuwe blokken, afwijkingen en de starter zelf

De starter is een vertrekpunt. Past iets uit het ontwerp er niet in, dan bouw je het voor het project. Je maakt het ontwerp niet passend voor de starter.

## Een blok toevoegen (in een project)

1. **contentmodel.yaml.** Voeg het blok toe onder `blokken`, met de velden die het nodig heeft. Gebruik waar het kan bestaande velden met een eigen label. Voeg alleen nieuwe velden toe onder `velden` als het echt iets nieuws is.
   ```yaml
   blokken:
     logoband: { naam: Logoband, icoon: images, velden: ["kop | Kop boven de logo's", logoItems!] }
   ```
   `logoItems` is dan een nieuw Matrix-veld onder `velden`, met een nieuw type `logo` onder `typen`.
2. **Template.** Maak `templates/_blokken/logoband.twig`. Begin met een bestaand blok dat erop lijkt. Gebruik de tokens (`text-h2`, `bg-vlak`, `container-site`) en `_components/image` en `_components/link`.
3. **Seed.** Zet een voorbeeld van het blok in `seed/content.json`, op de pagina waar het in Figma staat en op **Alle blokken**.
4. **Controleren.** `npm run prototype && npm run screenshots`. In Craft: `ddev craft site/setup/schema`.
5. **Documenteren.** Voeg een regel toe aan `docs/afwijkingen.md`, zodat je weet dat het blok niet uit de starter komt.

Heeft het blok JavaScript nodig, maak dan een module in `src/js/modules/` en roep die aan in `app.js`. Laat de module zelf controleren of zijn elementen op de pagina staan.

## Afwijkingen bijhouden

`docs/afwijkingen.md` (door `nieuw-project.sh` aangemaakt uit het sjabloon) is de lijst van alles waarin het project afwijkt van de starter. Denk aan nieuwe blokken, aangepaste starterblokken, extra plugins of libraries, een andere sectiestructuur, meertaligheid en koppelingen. Noteer per afwijking:

- wat er anders is en waarom (welk Figma-onderdeel of welke eis van de klant)
- of het herbruikbaar is voor andere projecten: **ja**, **nee** of **misschien**

Bij elk stoppunt meld je de nieuwe afwijkingen. Voor alles wat met **ja** is gemarkeerd, stel je voor om de starter uit te breiden.

## De starter uitbreiden

Een afwijking gaat pas naar de starter als de gebruiker of projectleider akkoord geeft. Daarna:

1. Maak de oplossing generiek. Haal alle klantinhoud weg (teksten, beelden, kleuren, namen). Gebruik alleen tokens en neutrale dummytekst.
2. Voeg het toe aan de starter-repo: contentmodel, template, seed (ook op **Alle blokken**), [blokken.md](blokken.md) en eventuele JavaScript.
3. Controleer: `npm run prototype` zonder fouten of kapotte links, screenshots op 3 breedtes zonder horizontale scroll, `php -l` op gewijzigde PHP.
4. Werk `CHANGELOG.md` bij met een nieuwe versie (V1.1, V1.2, enzovoort; bij wijzigingen die bestaande projecten breken een nieuwe hoofdversie).
5. Commit met een duidelijke boodschap, bijvoorbeeld `Blok logoband toegevoegd (uit project X)`. Zet de naam van de klant er alleen in als dat geen vertrouwelijke informatie is.

Bestaande projecten nemen een nieuwe starterversie niet vanzelf over. Wil een project iets uit een nieuwe versie, dan kopieer je dat onderdeel over en noteer je het in de afwijkingen.
