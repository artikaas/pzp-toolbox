// Screenshots op 390, 1024 en 1512 px, plus een open paneel. Meldt horizontale scroll.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const exe = process.env.CHROMIUM_PATH || fs.readdirSync('/opt/pw-browsers').filter((d) => d.startsWith('chromium-')).map((d) => `/opt/pw-browsers/${d}/chrome-linux64/chrome`)[0];
const out = path.join(root, 'prototype/screenshots'); fs.mkdirSync(out, { recursive: true });
const browser = await chromium.launch({ executablePath: exe });
const url = 'file://' + path.join(root, 'prototype/dist/index.html');
for (const w of [390, 1024, 1512]) {
  const p = await browser.newPage({ viewport: { width: w, height: 900 } });
  await p.goto(url); await p.waitForTimeout(2200);
  const sw = await p.evaluate(() => document.documentElement.scrollWidth);
  console.log(`${w}px: ${sw > w ? 'HORIZONTALE SCROLL (' + sw + ')' : 'geen horizontale scroll'}`);
  await p.screenshot({ path: `${out}/pagina-${w}.png`, fullPage: true });
  await p.click('[data-open="meer-4"]'); await p.waitForTimeout(500);
  await p.screenshot({ path: `${out}/paneel-stap4-${w}.png` });
  await p.close();
}
const p = await browser.newPage({ viewport: { width: 1512, height: 1100 } });
await p.goto(url); await p.waitForTimeout(2200); await p.click('[data-open="meer-5"]'); await p.waitForTimeout(400);
await p.evaluate(() => { document.querySelector('#meer-5 input[name="cl5-3"][value="2"]').click(); document.querySelector('#meer-5 .checklist').scrollIntoView(); });
await p.waitForTimeout(200); await p.screenshot({ path: `${out}/paneel-stap5-checklist-1512.png` });
await p.goto(url); await p.waitForTimeout(2200); await p.click('.ster--zwevend'); await p.waitForTimeout(400); await p.screenshot({ path: `${out}/paneel-voordat-1512.png` });
await browser.close();
