#!/usr/bin/env bash
# Maakt van een kopie van de starter een nieuw project.
#
#   bash scripts/nieuw-project.sh <projectnaam>      bijv. bash scripts/nieuw-project.sh bakkerij-jansen
#
# Past aan: DDEV-naam, CRAFT_APP_ID, lokale URL, pakketnamen en versie (0.1.0).
# Daarna: zie de stappen die het script laat zien, of docs/nieuw-project.md.
set -euo pipefail

naam="${1:-}"
if [[ ! "$naam" =~ ^[a-z0-9][a-z0-9-]*$ ]]; then
  echo "Gebruik: bash scripts/nieuw-project.sh <projectnaam>  (kleine letters, cijfers en streepjes, bijv. bakkerij-jansen)"
  exit 1
fi
cd "$(dirname "$0")/.."

perl -pi -e "s/^name: .*/name: $naam/" .ddev/config.yaml
perl -pi -e "s/^CRAFT_APP_ID=.*/CRAFT_APP_ID=$naam/; s#^PRIMARY_SITE_URL=https://.*\.ddev\.site#PRIMARY_SITE_URL=https://$naam.ddev.site#" .env.example.dev
perl -pi -e "s/^CRAFT_APP_ID=.*/CRAFT_APP_ID=$naam/" .env.example.production
perl -pi -e "s/\"name\": \"website-starter\"/\"name\": \"$naam\"/; s/\"version\": \"[^\"]*\"/\"version\": \"0.1.0\"/" package.json
perl -pi -e "s#\"name\": \"artica/website-starter\"#\"name\": \"artica/$naam\"#" composer.json
[[ -f .env ]] || cp .env.example.dev .env

# Starter-historie bewaren als referentie, eigen changelog voor het project
if [[ -f CHANGELOG.md && ! -f CHANGELOG-starter.md ]]; then
  mv CHANGELOG.md CHANGELOG-starter.md
  printf '# Changelog %s\n\n## V0.1\n- Project gestart vanuit de Artica website-starter (zie CHANGELOG-starter.md voor de versie).\n' "$naam" > CHANGELOG.md
fi
[[ -f docs/afwijkingen.md ]] || cp docs/afwijkingen-sjabloon.md docs/afwijkingen.md

cat <<MSG

Klaar: project '$naam' ingesteld.

Volgende stappen
  Prototype (alleen Node nodig):
    npm install
    npm run prototype
    npm run prototype:serve        -> http://localhost:4400

  Craft (Docker/OrbStack + DDEV nodig):
    ddev start
    ddev composer install
    ddev craft setup/security-key
    ddev craft install
    ddev craft site/setup
    ddev npm install && ddev npm run build
    ddev launch                    -> https://$naam.ddev.site

Zie docs/nieuw-project.md voor de volledige werkwijze.
MSG
