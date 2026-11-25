# contentmodel.yaml

Eén bestand beschrijft het hele contentmodel. Craft leest het met `site/setup/schema`, het prototype gebruikt het om lege velden goed te vullen.

## Opbouw

```yaml
plugins: [ckeditor, contact-form]
ckeditor: { naam: Standaard, toolbar: [bold, italic, '|', link] }
volumes:  { beelden: Beelden }
velden:   { kop: { soort: tekst, naam: Kop } }
typen:    { pagina: { naam: Pagina, tabs: { Header: [title, kop], Blokken: [blokken] } } }
blokken:  { tekstblok: { naam: Tekst, velden: [kop, tekst!] } }
secties:  { paginas: { naam: "Pagina's", soort: structure, typen: [pagina], uri: '{parent.uri}/{slug}', template: _entries/pagina } }
globals:  { footer: { naam: Footer, tabs: { CTA: [kop] } } }
```

- **typen** zijn entry types voor pagina's, artikelen en items in lijsten (zoals een kaart of een menu-item).
- **blokken** zijn entry types zonder titel. Samen vormen ze automatisch het Matrix-veld `blokken`, in de volgorde van de YAML. Elk blok heeft een template in `templates/_blokken/<handle>.twig`.
- Een type of blok met `velden: [...]` krijgt één tab, Inhoud. Met `tabs:` bepaal je zelf de tabs.

## Velden in een layout

| Notatie | Betekenis |
| --- | --- |
| `kop` | het veld `kop` |
| `kop!` | verplicht |
| `'kop \| Kop van de kaart'` | met een eigen label (aanhalingstekens nodig vanwege de `\|`) |
| `'kop! \| Kop van de kaart'` | verplicht, met een eigen label |
| `'title \| Naam'` | het titelveld met een eigen label. Staat `title` er niet in, dan heeft het type geen titel |

Je kunt hetzelfde veld in meerdere typen en blokken gebruiken, steeds met een ander label. Dat houdt het aantal velden klein.

## Soorten velden

| soort | Craft-veld | Opties |
| --- | --- | --- |
| `tekst` | Plain Text | `regels` (vanaf 2 meerdere regels), `max` (aantal tekens) |
| `rijketekst` | CKEditor | gebruikt de config uit `ckeditor` |
| `beeld` | Assets | `volume` (verplicht), `bestanden` (standaard `[image]`, bijv. `[video]`), `max` (standaard 1) |
| `link` | Link | `soorten` (standaard `[entry, url, email]`), `label: false` voor een link zonder labelveld |
| `keuze` | Dropdown | `opties: { waarde: Label }`, `standaard` (anders de eerste) |
| `schakelaar` | Lightswitch | `standaard` |
| `getal` | Number | `min`, `max`, `standaard`, `decimalen` |
| `email` | Email | |
| `tabel` | Table | `kolommen: { handle: Kop }`, `knop` |
| `entries` | Entries | `bronnen` (secties), `max` |
| `matrix` | Matrix | `typen` (verplicht), `min`, `max`, `knop` |

Elk veld kan `naam` en `uitleg` hebben. De uitleg ziet de redacteur onder het veld staan.

## Veilig wijzigen op een bestaande site

`site/setup/schema` mag je zo vaak draaien als je wilt. Dit kun je veilig doen:

- velden, typen, blokken, secties en globals **toevoegen**
- een veld aan een layout toevoegen: het komt direct na het veld dat er in de YAML voor staat
- labels, uitleg, namen en verplicht aanpassen
- keuzes aan een keuzelijst en typen aan een Matrix-veld toevoegen

Dit doet het schema bewust **niet**, omdat er anders content verloren gaat:

- velden, typen of blokken verwijderen
- een handle hernoemen
- een layout opnieuw opbouwen of de soort van een veld wijzigen

Zulke wijzigingen doe je in het Craft-CP, zodat Craft de content meeneemt. Pas daarna de YAML aan zodat die weer klopt. Achtergrond: in Craft 5 hangt opgeslagen content aan de UID van het element in de veldlayout. Vervang je een layout, dan raakt de content los.

`link` is in Craft een gereserveerde handle. Gebruik bijvoorbeeld `linkDoel`.

## Controleren

`ddev craft site/setup/controleer` leest de YAML en meldt verwijzingen naar velden, typen, volumes en secties die niet bestaan. Er verandert niets.
