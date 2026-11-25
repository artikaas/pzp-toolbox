/**
 * Statisch prototype: rendert alle pagina's uit seed/content.json met de Craft-templates (Twing + mock)
 * naar prototype/dist. Die map kan zo naar Netlify (drag-and-drop of via Git, zie netlify.toml).
 *
 *   npm run prototype            bouwt de CSS/JS (Vite) en rendert alle pagina's
 *   npm run prototype:serve      bekijk het resultaat op http://localhost:4400
 *
 * Variabelen: PROTOTYPE_URL (voor canonical/og, standaard http://localhost:4400), PROTOTYPE_BASE (standaard /).
 * Controleert na het renderen of alle interne links naar een bestaande pagina wijzen.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createEnvironment, createFilesystemLoader, createFunction, createFilter, createTest, createMarkup } from 'twing';
import { ROOT, BASE, ORIGIN, entries, globals, assets, craftGlobal, sjabloonVoor, arr } from './mock.mjs';

const OUT = path.join(ROOT, 'prototype/dist');
const TPL = path.join(ROOT, 'templates');

// Loader: sjabloonnamen zonder extensie zoals in Craft ('_components/image' -> .twig)
const fsZonderExtensie = {
  stat(p, cb) {
    fs.stat(p, (err, st) => {
      if (!err && st.isFile()) return cb(null, st);
      fs.stat(p + '.twig', (e2, st2) => cb(null, e2 ? null : st2));
    });
  },
  readFile(p, cb) {
    fs.readFile(p, (err, d) => (err ? fs.readFile(p + '.twig', cb) : cb(null, d)));
  },
};
const loader = createFilesystemLoader(fsZonderExtensie);
loader.addPath(TPL);

const manifestPad = path.join(ROOT, 'web/dist/.vite/manifest.json');
if (!fs.existsSync(manifestPad)) {
  console.error('Geen web/dist/.vite/manifest.json. Draai eerst `npm run build` (npm run prototype doet dat zelf).');
  process.exit(1);
}
const manifest = JSON.parse(fs.readFileSync(manifestPad, 'utf8'));
const leeg = createMarkup('', 'UTF-8');

function omgeving(request) {
  const env = createEnvironment(loader, {
    globals: {
      craft: craftGlobal(request),
      siteUrl: BASE,
      siteName: 'Prototype',
      prototype: true,
      now: new Date(),
      currentSite: { language: 'nl', handle: 'default' },
      ...globals,
    },
  });
  const fn = (name, args, impl) => env.addFunction(createFunction(name, async (_ctx, ...a) => impl(...a), args.map((n) => ({ name: n }))));
  // Craft: |filter zonder arrow haalt lege waarden weg
  env.addFilter(createFilter('filter', async (_ctx, lijst, arrow) => {
    const items = lijst instanceof Map ? [...lijst.values()] : Array.isArray(lijst) ? lijst : lijst && typeof lijst[Symbol.iterator] === 'function' ? [...lijst] : Object.values(lijst ?? {});
    if (!arrow) return items.filter((x) => x !== null && x !== undefined && x !== '' && x !== false && !(Array.isArray(x) && !x.length));
    const out = [];
    for (const x of items) if (await arrow(x)) out.push(x);
    return out;
  }, [{ name: 'arrow', defaultValue: null }]));
  // Craft-tests die Twig zelf niet kent
  env.addTest(createTest('string', async (_ctx, v) => typeof v === 'string', []));
  env.addTest(createTest('array', async (_ctx, v) => Array.isArray(v) || v instanceof Map, []));
  fn('vite', ['entry'], (entry) => {
    const c = manifest[entry];
    return createMarkup([...(c.css ?? []).map((x) => `<link rel="stylesheet" href="${BASE}dist/${x}">`), `<script type="module" src="${BASE}dist/${c.file}"></script>`].join('\n  '), 'UTF-8');
  });
  fn('url', ['p'], (p) => `${BASE}${String(p).replace(/^\//, '')}`);
  fn('csrfInput', [], () => leeg);
  fn('actionInput', ['a'], () => leeg);
  fn('redirectInput', ['u'], () => leeg);
  return env;
}

const request = (uri, query = {}) => ({
  pathInfo: uri === '__home__' ? '' : uri,
  absoluteUrl: `${ORIGIN}${BASE}${uri === '__home__' ? '' : uri + '/'}`,
  getQueryParam: (k) => query[k] ?? null,
  getParam: (k) => query[k] ?? null,
});

async function render(sjabloon, uri, entry, bestand, query = {}) {
  const html = await omgeving(request(uri, query)).render(sjabloon, { entry }, { strict: true });
  const doel = path.join(OUT, bestand);
  fs.mkdirSync(path.dirname(doel), { recursive: true });
  fs.writeFileSync(doel, html);
  return html;
}

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

const fouten = [];
const routes = [];
for (const e of entries) {
  const sjabloon = sjabloonVoor(e);
  if (!e.uri || !sjabloon) continue;
  const map = e.uri === '__home__' ? '' : `${e.uri}/`;
  routes.push({ sjabloon, uri: e.uri, entry: e, bestand: `${map}index.html` });
  // Paginering in het overzicht: in het prototype als /<pagina>/pagina-2/
  for (const blok of arr(e.fields.blokken?.all?.() ?? [])) {
    if (blok.type.handle !== 'artikelOverzicht') continue;
    const per = blok.fields.aantalPerPagina || 9;
    const totaal = entries.filter((x) => x.section.handle === 'artikelen').length;
    for (let p = 2; p <= Math.ceil(totaal / per); p++) routes.push({ sjabloon, uri: e.uri, entry: e, bestand: `${map}pagina-${p}/index.html`, query: { pagina: String(p) } });
  }
}
routes.push({ sjabloon: '404', uri: '404', entry: null, bestand: '404.html' });

for (const r of routes) {
  try {
    const html = await render(r.sjabloon, r.uri, r.entry, r.bestand, r.query);
    console.log('ok ', r.bestand, `${Math.round(html.length / 1024)} kB`);
  } catch (err) {
    fouten.push(r.bestand);
    console.log('FOUT', r.bestand, '\n    ', err.message, err.source?.name ? `(${err.source.name}${err.location ? ':' + err.location.line : ''})` : '');
  }
}

// Statische bestanden
const kopieer = (van, naar) => fs.existsSync(van) && fs.cpSync(van, naar, { recursive: true });
kopieer(path.join(ROOT, 'web/dist'), path.join(OUT, 'dist'));
kopieer(path.join(ROOT, 'web/fonts'), path.join(OUT, 'fonts'));
kopieer(path.join(ROOT, 'web/favicon.svg'), path.join(OUT, 'favicon.svg'));
fs.writeFileSync(path.join(OUT, 'robots.txt'), 'User-agent: *\nDisallow: /\n');
for (const a of Object.values(assets)) kopieer(a._bron, path.join(OUT, a.url.replace(BASE, '')));

// Interne links controleren
const kapot = new Set();
const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((x) => (x.isDirectory() ? walk(path.join(d, x.name)) : [path.join(d, x.name)]));
for (const f of walk(OUT).filter((f) => f.endsWith('.html'))) {
  const html = fs.readFileSync(f, 'utf8');
  for (const [, href] of html.matchAll(/(?:href|src)="([^"#?]*)/g)) {
    if (!href.startsWith(BASE) || href.startsWith('//')) continue;
    const rel = decodeURIComponent(href.slice(BASE.length));
    const doel = path.join(OUT, rel);
    if (!fs.existsSync(doel) && !fs.existsSync(path.join(doel, 'index.html'))) kapot.add(`${href} (in ${path.relative(OUT, f)})`);
  }
}

if (kapot.size) console.log(`\nKapotte interne links:\n  ${[...kapot].join('\n  ')}`);
console.log(fouten.length ? `\nFOUTEN in: ${fouten.join(', ')}` : `\nAlle ${routes.length} pagina's gerenderd naar prototype/dist.`);
process.exit(fouten.length || kapot.size ? 1 : 0);
