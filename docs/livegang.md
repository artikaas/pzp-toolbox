# Hosting, licenties en livegang

Prijzen, edities en plannen veranderen. Zoek ze op het moment van advies op en noem nooit bedragen uit je hoofd. Er is geen vaste Artica-hostingpartij: leg per project de opties voor.

## Craft-site

| Onderdeel | Aandachtspunt |
| --- | --- |
| Craft-editie | Het aantal redacteuren en de rechten bepalen of het Solo, Team of Pro wordt. Je koopt de licentie per project |
| Plugins | De starter gebruikt CKEditor en Contact Form (beide gratis, van Pixel & Tonic). Controleer bij elke extra plugin de licentie en het onderhoud |
| Hosting | PHP 8.2+, MySQL 8 of PostgreSQL. `storage/`, `web/uploads/` en `web/cpresources/` moeten beschrijfbaar zijn. Bijvoorbeeld Servd, Forge met een VPS, of eigen hosting. Niet Netlify of Vercel |
| Deploy | Git push, `composer install --no-dev`, `php craft up`, `npm ci && npm run build`. Pas op productie nooit velden met de hand aan (`CRAFT_ALLOW_ADMIN_CHANGES=false`) |
| E-mail | SMTP of een transactionele mailprovider, met SPF en DKIM op het domein. Zonder dat komen formulieren niet aan |
| Back-ups en updates | Spreek af wie de database en uploads back-upt en wie Craft en de plugins bijwerkt, en hoe vaak |

## Altijd

| Onderdeel | Aandachtspunt |
| --- | --- |
| Fonts | Een webfontlicentie voor het domein (soms per aantal pageviews). Zet nooit desktopfontbestanden online |
| Beelden | Commerciële gebruiksrechten voor foto's, stock, illustraties en AI-beeld. Vervang de placeholders uit de starter |
| Privacy | Een cookiebanner alleen als er tracking is, een privacyverklaring en een bewaartermijn voor formulierdata. Video-embeds laden pas na een klik |
| SEO | Titels en omschrijvingen per pagina, een sitemap en redirects van oude URL's. Zet de pagina **Alle blokken** uit |
| Toegankelijkheid | Koppenstructuur, alt-teksten, focus zichtbaar, contrast van de tokens |
| Domein en DNS | Wie beheert het domein, wie past de DNS aan, en wanneer |
| Goedkeuring | Beeld langs de creatief eindverantwoordelijke, copy langs de eindredactie. Versie V2.0 gaat naar de klant |
