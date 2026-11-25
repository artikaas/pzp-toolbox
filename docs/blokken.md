# Blokken in de starter

Elk blok heeft een definitie in `contentmodel.yaml` (onder `blokken`) en een template in `templates/_blokken/<handle>.twig`. Een verplicht veld staat in **vet**. Op de pagina **Alle blokken** in het prototype zie je ze allemaal, ook in hun varianten.

| Blok | Handle | Velden | Gedrag |
| --- | --- | --- | --- |
| Tekst | `tekstblok` | kop, inleiding, **tekst** | Smalle tekstkolom. De inleiding staat in een grotere letter |
| Tekst met beeld | `tekstMetBeeld` | **kop**, **tekst**, **afbeelding**, beeld links of rechts, knop, achtergrond | Twee kolommen. Op mobiel staat het beeld boven de tekst. Achtergrond: wit, licht vlak of primaire kleur |
| Afbeelding | `afbeelding` | **afbeelding**, onderschrift, bronvermelding | Behoudt de verhouding van het beeld. In artikelen iets smaller |
| Video | `video` | YouTube- of Vimeo-link, videobestand, stilstaand beeld, onderschrift | Een embed laadt pas na een klik (privacyvriendelijk, geen cookies vooraf). Bij een eigen bestand: `<video>` met poster |
| Citaat | `citaat` | **citaat**, naam, functie | Groot citaat met aanhalingsteken in de accentkleur |
| Knop | `knop` | **link** | Losse knop. Externe links openen in een nieuw tabblad, met een melding voor schermlezers |
| Stappen | `stappen` | kop, **stappen** (titel, tekst, beeld) | Genummerd, 2 tot 4 kolommen. De nummering is ook voor schermlezers duidelijk |
| Kaarten | `kaarten` | kop, intro, **kaarten** (titel, beeld, tekst, link) | Raster van 2 of 3 kolommen. Een kaart met een link is helemaal klikbaar |
| Accordeon | `accordeon` | kop, intro, **items** (titel, inhoud) | Native `<details>`: werkt zonder JavaScript en met het toetsenbord |
| Call-to-action | `cta` | **kop**, tekst, **knop**, achtergrond | Zelfde opmaak als de CTA in de footer. Eindigt een pagina met dit blok, dan vervalt de CTA in de footer |
| Uitgelichte artikelen | `uitgelichteArtikelen` | kop, artikelen, knop naar het overzicht | Toont de gekozen artikelen, of anders de 3 nieuwste |
| Artikeloverzicht | `artikelOverzicht` | kop, aantal per pagina | Alle artikelen, nieuwste eerst, met paginering (`?pagina=2`) |
| Contactformulier | `contactformulier` | kop, intro | Via de plugin Contact Form, met honeypot en validatie. Contactgegevens komen uit de global Contact. In het prototype via Netlify Forms |

## Overal geldig

- In elke kop zet je woorden tussen `*sterretjes*` om ze de accentkleur te geven, bijvoorbeeld `Samen *verder*`.
- De ruimte tussen blokken komt van de wrapper (`.blokken` in `app.css`), niet van het blok zelf. Blokken met een gekleurde achtergrond sluiten op elkaar en op de rand van de pagina aan.
- Elk blok krijgt `context` mee (`home`, `pagina` of `artikel`). Gebruik dat als een blok er per paginasoort anders uit moet zien.
- Bestaat er voor een blok geen template, dan toont `_onbekend.twig` dat in devMode en in het prototype.

## Globals

| Global | Velden |
| --- | --- |
| Algemeen | sitetitel, logo, standaardomschrijving, deelbeeld, social media |
| Navigatie | hoofdmenu, knop rechts in het menu, footerkolommen met links |
| Footer | CTA-kop, -tekst en -knop, regel onderaan |
| Contact | e-mailadres, telefoon, adres, ontvanger(s) van het formulier, bedankpagina |
