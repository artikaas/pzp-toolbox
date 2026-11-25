/**
 * Screenshots van alle pagina's van het prototype op 390, 1024 en 1512 px breed, naar prototype/screenshots.
 * Meldt pagina's met horizontale scroll. Vergelijk de beelden met Figma.
 *
 *   npm run prototype && npm run screenshots
 *   npm run screenshots -- over-ons contact     alleen deze pagina's
 *
 * Eenmalig nodig: `npx playwright-core install chromium` (of zet CHROMIUM_PATH naar een eigen Chrome/Chromium).
 */
import fs from 'node:fs';
import path from 'node:path';
import { start } from './serve.mjs';

const { chromium } = await import('playwright-core').catch(() => {
  console.error('playwright-core ontbreekt. Draai `npm install` en daarna `npx playwright-core install chromium`.');
  process.exit(1);
});
const DIST = path.resolve(path.dirname(new URL(import.meta.url).pathname), 'dist');
const OUT = path.resolve(path.dirname(new URL(import.meta.url).pathname), 'screenshots');
const BREEDTES = (process.env.BREEDTES ?? '390,1024,1512').split(',').map(Number);
const PORT = 4411;

const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).flatMap((x) => (x.isDirectory() ? walk(path.join(d, x.name)) : [path.join(d, x.name)]));
let paginas = walk(DIST).filter((f) => f.endsWith('index.html')).map((f) => path.relative(DIST, path.dirname(f)));
const filter = process.argv.slice(2);
if (filter.length) paginas = paginas.filter((p) => filter.some((f) => p === f || (f === 'home' && p === '')));

fs.mkdirSync(OUT, { recursive: true });
const server = await start(PORT);
const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
let problemen = 0;
for (const p of paginas) {
  for (const w of BREEDTES) {
    const page = await browser.newPage({ viewport: { width: w, height: 900 } });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto(`http://localhost:${PORT}/${p ? p + '/' : ''}`, { waitUntil: 'networkidle' });
    // Lazy-loaded beelden laden en reveal-animaties afronden
    await page.evaluate(async () => {
      document.querySelectorAll('img[loading=lazy]').forEach((i) => (i.loading = 'eager'));
      document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-zichtbaar'));
      // Door de pagina scrollen: sommige browsers laden lazy beelden pas als ze in beeld komen
      for (let y = 0; y < document.documentElement.scrollHeight; y += 600) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 40));
      }
      window.scrollTo(0, 0);
      await Promise.all([...document.images].map((i) => (i.complete && i.naturalWidth ? 1 : new Promise((r) => { i.onload = i.onerror = r; setTimeout(r, 3000); }))));
    });
    const sw = await page.evaluate(() => document.documentElement.scrollWidth);
    const naam = `${(p || 'home').replaceAll('/', '_')}-${w}.png`;
    await page.screenshot({ path: path.join(OUT, naam), fullPage: true });
    const melding = sw > w ? `  LET OP: horizontale scroll (${sw}px)` : '';
    if (melding) problemen++;
    console.log(`${naam}${melding}`);
    await page.close();
  }
}
await browser.close();
server.close();
console.log(problemen ? `\n${problemen} screenshot(s) met horizontale scroll.` : `\nKlaar: ${paginas.length * BREEDTES.length} screenshots in prototype/screenshots.`);
