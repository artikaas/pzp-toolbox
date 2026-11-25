/**
 * Nabootsing van de Craft-API op basis van contentmodel.yaml en seed/content.json.
 * Hiermee renderen dezelfde Twig-templates als in Craft, zonder Craft, PHP of database.
 *
 * Dekt wat de starter gebruikt: entries met velden, Matrix, assets, Link-velden, keuzes, globals,
 * craft.entries() met section/slug/type/id/relatedTo/orderBy/limit/offset/count en entry.ancestors.
 * Gebruik je in een project iets wat hier ontbreekt (bijv. search of eigen Craft-functies), breid dit bestand uit.
 */
import fs from 'node:fs';
import path from 'node:path';
import YAML from 'yaml';
import { createMarkup } from 'twing';

export const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
export const BASE = process.env.PROTOTYPE_BASE ?? '/';
export const ORIGIN = (process.env.PROTOTYPE_URL ?? 'http://localhost:4400').replace(/\/$/, '');

const model = YAML.parse(fs.readFileSync(path.join(ROOT, 'contentmodel.yaml'), 'utf8'));
const seed = JSON.parse(fs.readFileSync(path.join(ROOT, 'seed/content.json'), 'utf8'));

// ------------------------------------------------------------------ Contentmodel (zelfde notatie als Contentmodel.php)
const element = (el) => {
  if (typeof el === 'object') return { handle: el.veld, verplicht: !!el.verplicht, label: el.label ?? null };
  const [h, label] = String(el).split('|').map((s) => s.trim());
  return { handle: h.replace(/!$/, ''), verplicht: h.endsWith('!'), label: label || null };
};
const layoutVelden = (def) => Object.values(def.tabs ?? { Inhoud: def.velden ?? [] }).flat().map(element).map((e) => e.handle).filter((h) => h !== 'title');
const velden = { ...(model.velden ?? {}) };
const typen = {};
for (const [h, def] of Object.entries(model.typen ?? {})) typen[h] = { naam: def.naam ?? h, velden: layoutVelden(def) };
for (const [h, def] of Object.entries(model.blokken ?? {})) typen[h] = { naam: def.naam ?? h, velden: layoutVelden(def) };
if (model.blokken && !velden.blokken) velden.blokken = { soort: 'matrix', typen: Object.keys(model.blokken) };
const secties = model.secties ?? {};

// Twing geeft Twig-arrays/hashes door als Map: omzetten naar JS
export const arr = (x) => (x instanceof Map ? [...x.values()] : Array.isArray(x) ? x : x == null ? [] : [x]);
const obj = (x) => (x instanceof Map ? Object.fromEntries(x) : x ?? {});

let nextId = 1;
const all = [];

// ------------------------------------------------------------------ Query (craft.entries(), relatievelden, Matrix)
export class Query {
  constructor(items, opts = {}) { this.items = items; this._limit = opts.limit ?? null; this._offset = opts.offset ?? 0; this._order = opts.order ?? null; }
  _f(fn) { return new Query(this.items.filter(fn), { limit: this._limit, offset: this._offset, order: this._order }); }
  section(h) { if (h == null) return this; const hs = arr(h); return this._f((e) => hs.includes(e.section.handle)); }
  type(h) { const hs = arr(h); return this._f((e) => hs.includes(e.type.handle)); }
  slug(s) { const ss = arr(s); return this._f((e) => ss.includes(e.slug)); }
  uri(u) { return this._f((e) => e.uri === u); }
  id(v) {
    if (v == null) return this;
    v = arr(v);
    if (v[0] === 'not') { const ex = v.slice(1).flatMap(arr); return this._f((e) => !ex.includes(e.id)); }
    return this._f((e) => v.includes(e.id));
  }
  level(l) { return this._f((e) => e.level === l); }
  relatedTo(c) {
    c = obj(c);
    const ids = (x) => arr(x).map((y) => (typeof y === 'object' ? y.id : y));
    if (c.targetElement) { const t = ids(c.targetElement); return this._f((e) => Object.values(e.fields).some((v) => v instanceof Query && v.items.some((i) => t.includes(i.id)))); }
    if (c.sourceElement) { const s = ids(c.sourceElement); const doel = new Set(all.filter((e) => s.includes(e.id)).flatMap((e) => Object.values(e.fields).filter((v) => v instanceof Query).flatMap((v) => v.items.map((i) => i.id)))); return this._f((e) => doel.has(e.id)); }
    return this;
  }
  status() { return this; }
  site() { return this; }
  siteId() { return this; }
  with() { return this; }
  eagerly() { return this; }
  limit(n) { return new Query(this.items, { limit: n, offset: this._offset, order: this._order }); }
  offset(n) { return new Query(this.items, { limit: this._limit, offset: n, order: this._order }); }
  orderBy(o) { return new Query(this.items, { limit: this._limit, offset: this._offset, order: o }); }
  _resolved() {
    let items = [...this.items];
    const o = String(this._order ?? '');
    if (/postDate\s+DESC/i.test(o)) items.sort((a, b) => b.postDate - a.postDate);
    else if (/postDate/i.test(o)) items.sort((a, b) => a.postDate - b.postDate);
    else if (/^title/i.test(o)) items.sort((a, b) => a.title.localeCompare(b.title, 'nl'));
    return items.slice(this._offset, this._limit != null ? this._offset + this._limit : undefined);
  }
  all() { return this._resolved(); }
  one() { return this._resolved()[0] ?? null; }
  ids() { return this._resolved().map((e) => e.id); }
  count() { return this.items.length; }
  exists() { return this.items.length > 0; }
  get length() { return this._resolved().length; }
  [Symbol.iterator]() { return this._resolved()[Symbol.iterator](); }
}

// ------------------------------------------------------------------ Assets
const imageSize = (file) => {
  // Breedte en hoogte uit de JPEG- of PNG-header, zonder extra pakketten.
  const b = fs.readFileSync(file);
  if (b[0] === 0x89 && b.toString('ascii', 1, 4) === 'PNG') return [b.readUInt32BE(16), b.readUInt32BE(20)];
  if (b[0] === 0xff && b[1] === 0xd8) {
    let i = 2;
    while (i < b.length) {
      if (b[i] !== 0xff) { i++; continue; }
      const m = b[i + 1];
      if (m >= 0xc0 && m <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(m)) return [b.readUInt16BE(i + 7), b.readUInt16BE(i + 5)];
      i += 2 + b.readUInt16BE(i + 2);
    }
  }
  const svg = b.toString('utf8', 0, 2000).match(/viewBox="[\d.\s-]+?\s([\d.]+)\s([\d.]+)"/);
  return svg ? [Number(svg[1]), Number(svg[2])] : [0, 0];
};

export const assets = {};
for (const a of seed.assets ?? []) {
  const bron = path.join(ROOT, 'seed/bestanden', a.file);
  if (!fs.existsSync(bron)) { console.warn(`Let op: bestand ontbreekt: seed/bestanden/${a.file}`); continue; }
  const filename = a.filename ?? path.basename(a.file);
  const [w, h] = imageSize(bron);
  const url = `${BASE}uploads/${a.volume}/${filename}`;
  const ext = filename.split('.').pop().toLowerCase();
  assets[a.key] = {
    id: nextId++, kind: ['mp4', 'webm', 'mov'].includes(ext) ? 'video' : 'image', extension: ext, filename, url, width: w, height: h,
    alt: a.alt ?? '', title: filename, mimeType: ext === 'mp4' ? 'video/mp4' : `image/${ext === 'jpg' ? 'jpeg' : ext}`,
    _bron: bron, _focal: a.focal ?? [0.5, 0.5],
    // Geen transforms in het prototype: altijd het origineel.
    getUrl() { return url; },
    getSrcset(sizes) { return arr(sizes).map((s) => `${url} ${s}`).join(', '); },
    getFocalPoint(css) { return css ? `${this._focal[0] * 100}% ${this._focal[1] * 100}%` : { x: this._focal[0], y: this._focal[1] }; },
    toString() { return url; },
  };
}

// ------------------------------------------------------------------ Entries
const refs = {};
const ref = (s) => (s === 'home' ? refs.home : refs[s]) ?? null;

function proxy(e) {
  // Velden direct bereikbaar als entry.<handle>, zoals in Craft
  return new Proxy(e, {
    get(t, p) {
      if (p === 'children') return new Query(t.children.map(proxy));
      if (p in t) return t[p];
      if (typeof p === 'string' && p in t.fields) return t.fields[p];
      return undefined;
    },
    has(t, p) { return p in t || (typeof p === 'string' && p in t.fields); },
  });
}

function maakEntry(sectie, type, titel, slug, parent) {
  const e = {
    id: nextId++, title: titel ?? typen[type]?.naam ?? type, slug: slug ?? null, section: { handle: sectie }, type: { handle: type, name: typen[type]?.naam ?? type },
    fields: {}, parent, level: parent ? parent.level + 1 : 1, children: [], postDate: new Date('2026-01-01T10:00:00'), uri: null, url: null, enabled: true,
  };
  e.ancestors = { all: () => { const a = []; let p = e.parent; while (p) { a.unshift(proxy(p)); p = p.parent; } return a; } };
  e.getParent = () => (e.parent ? proxy(e.parent) : null);
  if (parent) parent.children.push(e);
  return e;
}

function standaard(handle) {
  const def = velden[handle];
  if (!def) return null;
  switch (def.soort) {
    case 'beeld': case 'entries': case 'matrix': return new Query([]);
    case 'keuze': { const opties = def.opties ?? {}; const v = def.standaard ?? Object.keys(opties)[0] ?? null; return keuze(v, opties); }
    case 'schakelaar': return !!def.standaard;
    case 'getal': return def.standaard ?? null;
    case 'tabel': return [];
    default: return null;
  }
}
const keuze = (v, opties) => ({ value: v, label: opties?.[v] ?? v, selected: v != null, toString: () => String(v ?? '') });

function linkData(v) {
  if (!v) return null;
  if (v.type === 'entry') {
    const doel = () => ref(v.entry);
    return { type: 'entry', get url() { return doel()?.url ?? ''; }, get value() { return doel()?.url ?? ''; }, get label() { return v.label ?? doel()?.title ?? ''; }, target: null, get element() { return doel(); }, toString() { return this.url; } };
  }
  const url = v.type === 'email' ? `mailto:${v.value}` : v.type === 'tel' ? `tel:${v.value}` : v.value;
  return { type: v.type, value: v.value, url, label: v.label ?? '', target: null, toString: () => url };
}

function waarde(handle, v) {
  const def = velden[handle] ?? {};
  if (v == null) return standaard(handle);
  switch (def.soort) {
    case 'rijketekst': return v ? createMarkup(String(v), 'UTF-8') : null;
    case 'beeld': return new Query(arr(v).map((x) => assets[String(x).replace('@asset:', '')]).filter(Boolean));
    case 'entries': return new Query(arr(v).map((x) => ref(String(x).replace('@entry:', ''))).filter(Boolean));
    case 'link': return linkData(v);
    case 'keuze': return keuze(v, def.opties);
    case 'matrix': return new Query(arr(v).map((item) => {
      const e = maakEntry('__genest', item.type, item.titel ?? null, null, null);
      vulVelden(e, item.velden ?? {});
      return proxy(e);
    }));
    default: return v;
  }
}

function vulVelden(e, gegeven) {
  for (const h of typen[e.type.handle]?.velden ?? Object.keys(gegeven)) e.fields[h] = standaard(h);
  for (const [h, v] of Object.entries(gegeven)) {
    if (!(h in velden)) console.warn(`Let op: veld '${h}' (in seed, type ${e.type.handle}) staat niet in contentmodel.yaml`);
    else if (typen[e.type.handle] && !typen[e.type.handle].velden.includes(h)) console.warn(`Let op: veld '${h}' staat niet in de layout van type ${e.type.handle}`);
    e.fields[h] = waarde(h, v);
  }
}

function uriVan(e) {
  const formaat = secties[e.section.handle]?.uri;
  if (!formaat) return null;
  if (formaat === '__home__') return '__home__';
  return formaat
    .replace('{parent.uri}', e.parent ? uriVan(e.parent) : '')
    .replace('{slug}', e.slug ?? '')
    .replace(/^\/+/, '');
}

// Opbouw in twee rondes, zoals de Seeder: eerst alle entries (voor verwijzingen), dan de velden.
const teVullen = [];
function bouw(def, parent) {
  const sectie = def.sectie;
  const type = def.type ?? secties[sectie]?.typen?.[0];
  const e = maakEntry(sectie, type, def.titel ?? (sectie === 'home' ? 'Home' : null), def.slug ?? (sectie === 'home' ? 'home' : null), parent);
  if (def.datum) e.postDate = new Date(def.datum);
  all.push(e);
  const p = proxy(e);
  if (sectie === 'home' || secties[sectie]?.soort === 'single') refs[sectie] = p;
  if (def.slug) refs[`${sectie}/${def.slug}`] = p;
  teVullen.push([e, def.velden ?? {}]);
  for (const kind of def.kinderen ?? []) bouw({ ...kind, sectie }, e);
}
(seed.entries ?? []).forEach((d) => bouw(d, null));
for (const e of all) {
  e.uri = uriVan(e);
  e.url = e.uri ? (e.uri === '__home__' ? BASE : `${BASE}${e.uri}/`) : null;
}
for (const [e, gegeven] of teVullen) vulVelden(e, gegeven);

// ------------------------------------------------------------------ Globals
export const globals = {};
for (const [handle, def] of Object.entries(model.globals ?? {})) {
  const g = { id: nextId++, handle, fields: {} };
  for (const h of Object.values(def.tabs ?? {}).flat().map(element).map((x) => x.handle)) g.fields[h] = standaard(h);
  for (const [h, v] of Object.entries(seed.globals?.[handle] ?? {})) g.fields[h] = waarde(h, v);
  globals[handle] = proxy(g);
}

// ------------------------------------------------------------------ Export
export const entries = all.map(proxy);
export const sjabloonVoor = (e) => secties[e.section.handle]?.template ?? null;
export function craftGlobal(request) {
  return {
    entries: () => new Query(entries),
    app: { request, config: { general: { devMode: true } } },
  };
}
