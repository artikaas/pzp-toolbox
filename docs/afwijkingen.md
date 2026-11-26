# Afwijkingen van de starter

Alles waarin dit project afwijkt van De Bron (Artica website-starter). Starterversie: zie CHANGELOG-starter.md (V1.0).

| Datum | Onderdeel | Wat is er anders en waarom | Herbruikbaar? | Naar starter? |
| --- | --- | --- | --- | --- |
| 2026-11-25 | Contentmodel | Geen blokken, pagina's en artikelen. De toolbox is één vaste pagina | nee | nee |
| 2026-11-25 | Contentmodel | Stappen als losse entries in een structure-sectie Stappen (één niveau), ingeladen op de toolbox; de template toont de eerste vijf | nee | nee |
| 2026-11-25 | Contentmodel | Sectie Rollen (label en kleur) als relatie bij elke stap | nee | nee |
| 2026-11-25 | Contentmodel | Volume Documenten en linkvelden met soort `asset` voor hulpmiddelen | ja | voorgesteld (seed en mock ondersteunen nog geen asset-links) |
| 2026-11-25 | CKEditor | Knop Tabel invoegen in de standaardconfiguratie (matrix rollen en verantwoordelijkheden als rijke tekst) | misschien | nee |
| 2026-11-25 | Plugins | Contact Form niet geïnstalleerd; het pakket blijft in composer.json omdat de module ernaar verwijst. Opruimen bij B4 met PHP-review | nee | nee |
| 2026-11-25 | Templates | Geen blokken; vaste templates voor wiel, stapblok, panelen, menu en checklist | nee | nee |
| 2026-11-25 | Component | Paneel (dialog met tabbladen, ankers en in- en uitschuiven via Tailwind `starting:`) | ja | voorgesteld |
| 2026-11-25 | Component | Linkrij met automatisch label en omschrijving bij aanwijzen | ja | voorgesteld |
| 2026-11-25 | Component | Interactieve checklist (alleen in de browser) | misschien | nee |
| 2026-11-25 | Component | Zwevende actieknop (de ster) met aandachtsanimatie | misschien | nee |
| 2026-11-25 | CSS | Basisregel h1 tot h4 zonder font-kop en font-bold: koppen krijgen hun lettertype per component | nee | nee |
| 2026-11-25 | JavaScript | Modules menu, reveal, video en contactformulier vervangen door panelen, wiel, checklist en navbar | nee | nee |
| 2026-11-25 | Seed | De Seeder herkent een Matrix-lijst alleen als het eerste item `velden` heeft; in de seed opgelost, in de starter beter de Seeder aanpassen | ja | voorgesteld (PHP, met review) |
