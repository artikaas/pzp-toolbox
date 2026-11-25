# Van Figma naar website met de starter

Deze werkwijze sluit aan op de fases van de skill **figma-naar-website**. Sluit elke fase af met een kort stoppunt: wat er klaar is, wat er nog openstaat, welke afwijkingen van de starter er zijn, wat de gebruiker moet doen, en de vraag om akkoord.

## 0. Opzetten

1. Maak een nieuwe (private) repo voor het project en zet de inhoud van de starter erin. Gebruik geen fork, dan blijft de klantcode los van de starter.
2. Stel de projectnaam in: `bash scripts/nieuw-project.sh projectnaam`. Dit past de DDEV-naam, de `CRAFT_APP_ID`, de lokale URL en de pakketnamen aan, en zet de versie op 0.1.0.
3. In `docs/afwijkingen.md` houd je bij waar het project van de starter afwijkt.

## 1. Figma vertalen naar de starter

| Figma | In de starter |
| --- | --- |
| Kleurvariabelen, fonts, tekststijlen, radius | Het `@theme`-blok in `src/css/app.css` |
| Webfonts | `web/fonts/` plus `@font-face` in `app.css` (alleen met webfontlicentie) |
| Terugkerende secties | Blokken: hergebruik een starterblok of maak een nieuw blok |
| Paginasoorten | Typen (`home`, `pagina`, `artikel`) in `contentmodel.yaml` |
| Menu, footer, contactgegevens | Globals in `contentmodel.yaml` |
| Dummyteksten en beelden | `seed/content.json` en `seed/bestanden/` |

Werkvolgorde:

1. **Tokens.** Neem kleuren en tekststijlen één keer over in `@theme`. Templates gebruiken alleen tokennamen (`bg-primair`, `text-h2`), dus een kleur aanpassen doe je op één plek.
2. **Blokken.** Leg elke sectie uit Figma naast de blokken in [blokken.md](blokken.md). Komt een sectie overeen, pas dan het template aan het ontwerp aan. Wijkt hij af, maak dan een nieuw blok (zie [uitbreiden.md](uitbreiden.md)). Het ontwerp gaat altijd voor, niet de starter.
3. **Contentmodel.** Voeg velden, blokken en typen toe in `contentmodel.yaml`. Blokken die het project niet gebruikt kun je weghalen. Doe dat vóór de eerste `site/setup`, want daarna haal je niets meer weg via de YAML.
4. **Seed.** Vervang de voorbeeldinhoud door de pagina's en teksten uit Figma, letterlijk. Ontbrekende beelden worden placeholders: genereer ze met `python3 scripts/placeholderbeelden.py seed/bestanden` of zet de exports uit Figma erin.
5. **Controleren.** Draai `npm run prototype && npm run screenshots` en leg de screenshots op 390, 1024 en 1512 px naast Figma. Controleer ook dat er geen horizontale scroll is en dat alle links werken (het render-script meldt kapotte links).

Begin met één pagina, meestal de home, en vraag daarop akkoord voordat je de rest bouwt.

## 2a. Prototype opleveren

- `npm run prototype` en sleep `prototype/dist` naar Netlify Drop, of koppel de repo aan Netlify.
- Het formulier werkt in het prototype via Netlify Forms. Zet in Netlify een e-mailnotificatie aan.
- Het prototype staat op `noindex`. Deel de link alleen met de klant.

## 2b. Craft-site opzetten

```bash
ddev start
ddev composer install
ddev craft setup/security-key
ddev craft install
ddev craft site/setup
ddev npm install && ddev npm run build
ddev launch
```

- Wat het setupcommando aanmaakt staat daarna in `config/project/`. Commit die map.
- Andere omgevingen (een collega, staging, productie) draaien `ddev craft up` of `php craft up`.
- Collega's laten meekijken: `ddev share`. Zet `PRIMARY_SITE_URL` op de share-link. Craft Solo heeft één gebruiker. Wil je meer gebruikers in het CP, dan heb je Team of Pro nodig, of de trial.

## 3. Ontwerpwijzigingen verwerken

1. Pas `contentmodel.yaml`, de templates en de seed aan.
2. `ddev craft site/setup/schema`: dit vult het contentmodel aan zonder content te verliezen.
3. Alleen als de demo-inhoud terug moet: `ddev craft site/setup/seed`. **Let op:** dit overschrijft wat de klant in de demo-entries en globals heeft aangepast. Vraag het eerst, of voer de wijziging met de hand in het CP door.
4. `ddev npm run build`, en controleer.

## Veelvoorkomende problemen

| Melding | Oorzaak | Oplossing |
| --- | --- | --- |
| `mkcert: executable file not found` | Homebrew staat niet in het zoekpad | `eval "$(/opt/homebrew/bin/brew shellenv)"`, dezelfde regel in `~/.bash_profile` of `~/.zprofile`, daarna `mkcert -install` |
| `web container is unhealthy` | De container is maar half gestart | `ddev poweroff`, dan `ddev start`. Helpt dat niet, kijk dan in `ddev logs -s web` |
| Nieuw veld of nieuwe knop niet zichtbaar | Het schema is niet gedraaid, de CSS is niet gebouwd of de cache is oud | Kijk in `config/project/` of het veld bestaat. Draai `ddev craft clear-caches/all` en ververs de pagina hard |
| `allowAdminChanges staat uit` | Productie-instelling | Draai het schema alleen lokaal, of zet `CRAFT_ALLOW_ADMIN_CHANGES=true` in `.env` |
| Fout in `contentmodel.yaml` | Tikfout of veld bestaat niet | `ddev craft site/setup/controleer` laat precies zien wat er mis is |
