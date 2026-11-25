# Afwijkingen V0.10

## Van De Bron
- De starter (artikaas/deBron) was in deze sessie niet bereikbaar. Gebouwd volgens de patronen uit de skill, in dezelfde mapindeling. Bij route B (Craft) overzetten naar De Bron: templates, seed en CSS.
- Tailwind v4 (theme en utilities, zonder preflight) met de ZZSD-tokens in `@theme`; de componentstijlen staan in `@layer components`. Sluit aan op De Bron, maar preflight staat bewust uit.
- Renderer: twig.js in plaats van de prototype-renderer van De Bron.
- Herbruikbaar? Nee voor De Bron (projectspecifiek). Wel binnen de ZZSD-familie: schuin label, tag, ballon, rolchips.

## Van Figma (PZP Tool v1)
- Vijf stappen volgens het brondocument. Op verzoek van de klant is de cirkel evenredig verdeeld: stap 1 tot en met 4 gelijk (69,7 graden), stap 5 zichtbaar iets kleiner en onder stap 1 en de staart geschoven, gegenereerd in de stijl van Figma 174:427 (zelfde maten, kleuren, lipverbindingen, staart en schaduw; `scripts/cirkel-evenredig.py`).
- Kaarten zijn verankerd op de positie van de nummertag uit Figma: de tag staat vast, de ballon groeit ervan af. Kaart 5 is nieuw, linksonder bij het lichtroze segment.
- De staart loopt horizontaal door tot buiten beeld (in Figma eindigt hij bij de paginarand van 1440 px; op bredere schermen zou de ronde kop anders zichtbaar worden).
- Ster staat iets verschoven, linksonder in de witte cirkel, omdat kaart 5 nu bij de oude plek staat.
- Het wiel schaalt als geheel mee met de schermhoogte en -breedte, zodat het altijd in één keer in beeld is (`schaalWiel` in app.js).
- cirkel-4.svg en cirkel-6.svg worden met hetzelfde script gemaakt, voor als het aantal stappen verandert (kaartposities moeten dan opnieuw).
- Rollegenda: "Alle zorgprofessionals" en "Regiebehandelaar" (document) in plaats van "Verpleging + Verzorging" en "Medisch behandelaar".
- Legenda per stap toont alleen de rollen die bij die stap horen.
- Kaarttitel stap 3: "Samen beslissen" (de korte naam uit het document).
- Tips staan alleen op de pagina, rolverdeling alleen in het paneel (geen dubbele inhoud).
- De ster ("Voordat je begint") staat niet meer in het wiel maar zweeft rechtsonder in beeld, met een korte 'bop' om de aandacht te trekken (uit bij 'minder beweging'). Referenties staan als laatste onderdeel in dat paneel, en als subitem onder 'Voordat je begint' in het menu.
- Nieuw (niet in Figma): menuknop rechtsboven met menupaneel, hover-effect op de cirkelsegmenten. Ter controle door designer.
- Navbar lager dan in Figma (logo 32 px in plaats van 50 px), op verzoek.
- Panelen zijn 84% van de schermbreedte (op mobiel schermvullend).
- Mobiel en tablet: eigen invulling, er is geen mobiel ontwerp.
- Knop "Lees meer": Campton SemiBold in plaats van Medium (Medium niet aangeleverd).

## Wijzigingen V0.2
- Hero opnieuw opgebouwd vanuit Figma: exacte cirkel, maten (696 x 696), paddings (216 / 264) en kaartposities, kaarten 1 en 2 met ballon boven de tag zoals in het ontwerp.
- Fontnaam Railroad Gothic in de CSS aangevuld met "atf-railroad-gothic" (de naam bij Adobe Fonts).

## Wijzigingen V0.3
- Railroad Gothic ATF Black zelf gehost (aangeleverd bestand); Adobe Fonts-kit niet meer nodig en verwijderd.
- Panelen bijna schermvullend (84%), hulpmiddelen in twee kolommen, tekst begrensd op leesbare regellengte.
- Menuknop rechtsboven: Voordat je begint (en de onderdelen daarvan), de vijf stappen en de referenties.
- Referenties en onderbouwende kaders uit het brondocument in de footer.
- Navbar verticaal ongeveer gehalveerd.
- Puntjes van de ballonnen gedraaid zodat ze naar de tag wijzen, zoals in Figma.
- Segmenten lichten op en komen iets omhoog bij aanwijzen; dat gebeurt ook als je de kaart van die stap aanwijst, en andersom licht de kaart op.

## Wijzigingen V0.4
- Nummertags (01 tot 05) op de Figma-posities gezet en de kaarten daaraan verankerd.
- Meer ruimte rond het wiel (boven 360 px, onder 400 px) en tussen de stappen (120 px).
- Navbar scrollt mee (sticky) en krijgt een zachte schaduw zodra je scrolt; ankers houden rekening met de navbar.
- Animaties via Tailwind: wiel en kaarten komen bij het laden rustig in beeld (kaarten na elkaar), panelen schuiven in en uit met een vloeiende curve en de achtergrond vervaagt mee, inhoud van een paneel komt iets later in beeld, knoppen en linkrijen tillen iets op, pijltjes schuiven mee, sluitknop draait. Alles staat uit bij 'minder beweging'.

## Wijzigingen V0.5 (feedback klant)
- Cirkel evenredig verdeeld (5 x 72 graden), kaarten en tags opnieuw geplaatst rond de segmenten.
- Panelen: stappenbalk en vorige/volgende-knoppen weg. In de kop staan nu 'Werkwijze per sector' en 'Hulpmiddelen en documenten' als sprongen naar die kopjes; beide kopjes staan in elk paneel, met 'Nog niet toegevoegd.' zolang er niets is.
- Omschrijving bij hulpmiddelen verschijnt pas bij aanwijzen (of toetsenbordfocus); op touchschermen blijft hij zichtbaar. Een 'i' geeft aan dat er een omschrijving is.
- Referenties blijven in de footer.

## Wijzigingen V0.6 (feedback klant, na check-in)
- 'Waarom en wat is PZP' is een eigen kopje bovenaan 'Voordat je begint' (de delen 'Wat is PZP' en 'Waarom PZP in Twente' uit sectie 1 van het document). In het menu is 'Voordat je begint' nu één item.
- Stappaneel met tabbladen: Informatie (doel, achtergrond, checklist, rolverdeling), Werkwijze per sector, Hulpmiddelen en documenten. Pijltjestoetsen werken tussen de tabbladen.
- Toolbox-knop naast 'Lees meer' bij elke stap: opent het paneel direct op het tabblad Hulpmiddelen en documenten. Tijdelijke invulling; de klant komt nog terug op 'toolbox visueel bij alle stappen'.

## Wijzigingen V0.7
- Werkwijze per sector is geen tabblad meer maar een kopje onder Informatie, met één link naar het losse werkwijzedocument (volgt). Per stap kan in het CMS ook een eigen lijst.
- Wiel past in één keer in beeld en schaalt mee met de viewer.
- Segment 5 iets kleiner en onder segment 1; segment 1 en de staart vormen weer één doorlopende vorm zoals in Figma.
- Nummertags weer zoals in Figma: midden op de ring, ballon naar buiten.

## Wijzigingen V0.8
- Referenties uit de footer gehaald; ze staan nu in 'Voordat je begint' en als subitem in het menu. De footer is weer zoals in Figma (logo en regel tekst).
- Ballonpuntje op Figma-maat (23,77 x 18,75) en de afstand tussen ballon en nummertag op 17 px, zodat de punt de tag net raakt in plaats van eronder door te lopen; vergeleken met de Figma-kaarten 174:456 en 174:464.

## Wijzigingen V0.9 (feedback focusgroep)
- Ballonnen breder en tekst groter: titel 46 px (was 32), 'Stap N' 24 px (was 16), rolicoontjes groter. Doordat het wiel in één keer in beeld past, is de titel op een laptop (1512 x 900) zo'n 25 px groot (was 20).
- Ballon van stap 2 en stap 5 gespiegeld rond het puntje, voor meer afwisseling rond het wiel.
- Navbar: PZP TOOLBOX in plaats van PZP TOOL.
- Ster zwevend rechtsonder met animatie.
- 'Patiënt' vetgedrukt in de begrippenlijst, zoals de andere begrippen (in de bron cursief).
- Toolbox als grotere illustratie (gereedschapskist) rechtsonder in elk stapblok, in de kleur van het segment van die stap; bij aanwijzen gaat het deksel open en komt het gereedschap omhoog. Vervangt het kleine icoon naast 'Lees meer'.
- Checklist van stap 5 staat nu onder Hulpmiddelen en documenten.

## Wijzigingen V0.10
- Puls achter de ster weggehaald; de 'bop' blijft.
- Gereedschapskist vervangen door de aangeleverde illustratie (gereedschapskist.svg), één keer als symbool in de pagina en per stap hergebruikt. De roze tinten zijn omgezet naar mengkleuren van de stapkleur, zodat elke kist de kleur van zijn segment volgt; de omlijning is aubergine. Bij aanwijzen tilt en kantelt de kist licht.

## Open
- Rechten/licentie van de aangeleverde gereedschapskist-illustratie controleren voor livegang.
- Poppetjes bij de items van stap 4: Kurt levert de verdeling per item aan.
- 'Toolbox visueel weergeven bij alle stappen': definitieve invulling volgt.
