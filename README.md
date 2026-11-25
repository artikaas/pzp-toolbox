# De Bron: Artica website-starter

Repo: [github.com/artikaas/deBron](https://github.com/artikaas/deBron)

De vaste codebasis voor websites die we vanuit een Figma-ontwerp bouwen. Je kunt er twee kanten mee op:

- **Snel statisch prototype.** Dezelfde templates, gevuld met de dummycontent uit `seed/content.json`, als statische site op Netlify. Je hebt alleen Node nodig.
- **Functionele Craft CMS 5-site.** Het contentmodel staat in `contentmodel.yaml` en één commando zet het in Craft, inclusief de startinhoud. Redacteuren beheren daarna alles zelf.

Een prototype groeit zonder herbouw door naar een Craft-site, want beide gebruiken dezelfde templates, CSS, contentmodel en seed.

> **De starter is een vertrekpunt, geen grens.** Vraagt het ontwerp om iets wat hier niet in zit, zoals een ander bloktype, meertaligheid, zoeken of een koppeling, dan bouw je dat gewoon voor het project. Noteer de afwijking in `docs/afwijkingen.md`. Is de oplossing herbruikbaar, stel dan voor om de starter uit te breiden (zie [docs/uitbreiden.md](docs/uitbreiden.md)).

Deze starter hoort bij de Claude-skill **figma-naar-website**, die het hele traject begeleidt: intake, stackkeuze, Figma uitlezen, bouwen, en na elke fase een akkoord.

## Wat zit erin

| Onderdeel | Waar |
| --- | --- |
| Contentmodel (velden, blokken, secties, globals) | `contentmodel.yaml` |
| Craft-module: setupcommando, schema, seeder, `vite()` | `modules/site/` |
| Templates: layout, header, footer, hero, 13 blokken | `templates/` |
| Design tokens (kleuren, fonts, tekststijlen) | `src/css/app.css` |
| JavaScript (menu, video, formulier, reveal), zonder libraries | `src/js/` |
| Startinhoud en placeholderbeelden | `seed/` |
| Statisch prototype, screenshots en linkcontrole | `prototype/` |
| Werkwijze en naslag | `docs/` |

De 13 blokken zijn: tekst, tekst met beeld, afbeelding, video, citaat, knop, stappen, kaarten, accordeon, call-to-action, uitgelichte artikelen, artikeloverzicht en contactformulier. Je vindt ze allemaal op de pagina **Alle blokken** in het prototype. Velden en gedrag staan in [docs/blokken.md](docs/blokken.md).

## Snel starten

Een nieuw project begin je met een kopie van deze repo. Zet in die kopie eerst de projectnaam:

```bash
bash scripts/nieuw-project.sh projectnaam
```

**Prototype** (Node 22):

```bash
npm install
npm run prototype          # bouwt CSS/JS en rendert alle pagina's naar prototype/dist
npm run prototype:serve    # bekijk op http://localhost:4400
npm run screenshots        # screenshots op 390, 1024 en 1512 px (eenmalig: npx playwright-core install chromium)
```

Netlify: sleep `prototype/dist` naar app.netlify.com/drop, of koppel de repo (de instellingen staan in `netlify.toml`).

**Craft** (Docker Desktop of OrbStack, DDEV en mkcert via Homebrew):

```bash
ddev start
ddev composer install
ddev craft setup/security-key
ddev craft install
ddev craft site/setup                  # contentmodel + startinhoud
ddev npm install && ddev npm run build
ddev launch
```

Tijdens het bouwen houd je `ddev npm run dev` open in een eigen venster.

## Belangrijke commando's

| Commando | Wat het doet |
| --- | --- |
| `ddev craft site/setup/controleer` | Controleert `contentmodel.yaml` en past niets aan |
| `ddev craft site/setup/schema` | Maakt het contentmodel aan of vult het aan. Bestaande content blijft staan |
| `ddev craft site/setup/seed` | Zet de startinhoud terug. **Overschrijft** de demo-entries en globals |
| `ddev craft up` | Op andere omgevingen: project config en migraties toepassen |
| `npm run prototype` | Statisch prototype opnieuw renderen, inclusief linkcontrole |

## Afspraken

- Teksten uit Figma neem je letterlijk over, ook als het dummytekst is. Je verzint niets.
- Fonts, foto's, stock, illustraties en AI-beeld: controleer de commerciële gebruiksrechten voordat iets live of naar de klant gaat.
- Versies: V0.x tijdens het concept, V1.0 is de eerste interne definitieve versie, V2.0 de definitieve versie richting klant.
- Werk richting klant gaat eerst langs de creatief eindverantwoordelijke (beeld en ontwerp) en de eindredactie (copy).
- Klantinhoud komt nooit in deze starter-repo.

## Verder lezen

- [docs/nieuw-project.md](docs/nieuw-project.md): van Figma naar prototype of Craft-site, stap voor stap
- [docs/contentmodel.md](docs/contentmodel.md): de notatie van `contentmodel.yaml` en veilig wijzigen
- [docs/blokken.md](docs/blokken.md): alle blokken met hun velden
- [docs/uitbreiden.md](docs/uitbreiden.md): een blok toevoegen, afwijkingen bijhouden, de starter uitbreiden
- [docs/prototype.md](docs/prototype.md): hoe het prototype werkt en waar de grenzen liggen
- [docs/livegang.md](docs/livegang.md): hosting, licenties, e-mail, privacy en SEO

---

## Dit project: PZP-toolbox regio Twente

Gebouwd vanuit De Bron V1.0. Het contentmodel staat in `contentmodel.yaml`, de beslissingen in de blokkenspecificatie (Claude Doc) en de afwijkingen in `docs/afwijkingen.md`.

- `python3 scripts/prototype-naar-seed.py` zet de inhoud van prototype V0.10 (`archief/prototype-v0.10`) om naar `seed/content.json`.
- Templates en frontend volgen in fase B3.
