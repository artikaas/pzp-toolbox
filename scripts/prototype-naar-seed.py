"""Zet de inhoud van prototype V0.10 (archief/prototype-v0.10/seed/content.json) om naar het seedformaat
van De Bron (seed/content.json). Teksten blijven letterlijk; alleen de verpakking verandert.

    python3 scripts/prototype-naar-seed.py
"""
import json, re, pathlib
root = pathlib.Path(__file__).resolve().parent.parent
bron = json.loads((root / 'archief/prototype-v0.10/seed/content.json').read_text())
g = bron['globals']

def p(html):          # losse tekst als alinea (ook als die met <strong> of <em> begint)
    if not html: return ''
    return html if re.match(r'\s*<(p|ul|ol|figure|table)[\s>]', html) else f'<p>{html}</p>'
def ul(items):
    return '<ul>' + ''.join(f'<li>{i}</li>' for i in items) + '</ul>' if items else ''
def plat(html):
    return re.sub(r'<[^>]+>', '', html or '').strip()

rol_slug = {'alle-zorgprofessionals': 'alle-zorgprofessionals', 'regiebehandelaar': 'regiebehandelaar'}
rollen = [{'sectie': 'rollen', 'type': 'rol', 'slug': h, 'titel': r['label'], 'velden': {'kleur': r['kleur']}}
          for h, r in g['rollen'].items()]

def groep(kop, tekst='', items=(), kolom=None):
    v = {'tekst': p(tekst) + ul(list(items))}
    if kolom: v['kolom'] = kolom
    return {'type': 'groep', 'titel': kop, 'velden': v}

def hulpmiddel(l):
    v = {}
    if plat(l.get('omschrijving')): v['omschrijving'] = plat(l['omschrijving'])
    if l.get('url'): v['hulpmiddelLink'] = {'type': 'url', 'value': l['url']}
    return {'type': 'hulpmiddel', 'titel': l['titel'], 'velden': v}

stappen = []
for s in bron['stappen']:
    m = s['meer']
    groepen = []
    for i, kolom in enumerate(s['kolommen']):
        for gr in kolom:
            groepen.append(groep(gr['kop'], gr.get('tekst', ''), gr.get('items', []), 'links' if i == 0 else 'rechts'))
    v = {
        'rollen': [f'@entry:rollen/{rol_slug[r]}' for r in s['rollen']],
        'groepen': groepen,
        'tips': ''.join(f'<p>{t}</p>' for t in s['tips']),
        'doel': p(s['doel']),
        'achtergrond': [groep(a['kop'], items=a['items']) for a in m['achtergrond']],
        'rolverdeling': ul(m['rolverdeling']),
        'werkwijzen': [hulpmiddel(w) for w in m['werkwijzen']],
        'hulpmiddelgroepen': [{'type': 'hulpmiddelgroep', 'velden': {'kop': hg['kop'], 'hulpmiddelen': [hulpmiddel(l) for l in hg['links']]}}
                              for hg in m['hulpmiddelen']],
    }
    if s['kaarttitel'] != s['titel']: v['kaarttitel'] = s['kaarttitel']
    c = m.get('checklist')
    if c:
        u = c['uitkomst']
        v['checklist'] = [{'type': 'checklist', 'titel': c['titel'], 'velden': {
            'instructie': p(c['instructie']),
            'signalen': [{'signaal': plat(x)} for x in c['signalen']],
            'conclusie': plat(c['conclusie']),
            'uitkomstKop': u['kop'],
            'uitkomstNee': f"<p><strong>Alle vragen 'Nee':</strong> {u['nee']}</p>",
            'uitkomstAnders': f"<p><strong>{u['anders_kop']}:</strong></p>" + ul(u['anders']),
        }}]
    stappen.append({'type': 'toolboxStap', 'titel': s['titel'], 'velden': v})

def inhoud(blokken):
    """Tussenkoppen los; tekst, lijsten en tabellen ertussen samen in één tekstblok."""
    uit, buf = [], []
    def flush():
        if buf: uit.append({'type': 'tekstInhoud', 'velden': {'tekst': ''.join(buf)}}); buf.clear()
    for b in blokken:
        if b['type'] == 'kop':
            flush(); uit.append({'type': 'tussenkop', 'titel': b['tekst']})
        elif b['type'] == 'tekst': buf.append(p(b['html']))
        elif b['type'] == 'lijst': buf.append(ul(b['items']))
        elif b['type'] == 'tabel':
            kop = ''.join(f'<th>{k}</th>' for k in b['kop'])
            rijen = ''.join('<tr>' + ''.join(f'<td>{c}</td>' for c in r) + '</tr>' for r in b['rijen'])
            buf.append(f'<figure class="table"><table><thead><tr>{kop}</tr></thead><tbody>{rijen}</tbody></table></figure>')
    flush()
    return uit

vjb = bron['voordatJeBegint']; ref = bron['referenties']
seed = {
    'assets': [{'key': 'zzsd-logo', 'volume': 'media', 'file': 'zzsd-logo.svg', 'alt': 'Zelf zorgen, samen doen'}],
    'entries': rollen + [
        {'sectie': 'toolbox', 'titel': 'Toolbox', 'velden': {'toolboxStappen': stappen}},
        {'sectie': 'voordat', 'titel': vjb['titel'], 'velden': {
            'intro': g['documentOndertitel'],
            'onderdelen': [{'type': 'onderdeel', 'titel': s['titel'], 'velden': {'inhoud': inhoud(s['blokken'])}} for s in vjb['secties']],
            'kop': ref['titel'], 'tekst': ul(ref['items'])}},
    ],
    'globals': {
        'algemeen': {'siteTitel': g['documentTitel']},
        'toolboxAlgemeen': {'naamDeel1': g['toolnaam'][0], 'naamDeel2': g['toolnaam'][1], 'kop': g['heroTitel'],
                            'heroSubtitel': g['heroSubtitel'], 'documentTitel': g['documentTitel']},
        'labels': {'leesMeer': g['leesMeer'], 'toolboxLabel': g['toolboxLabel'], 'menuLabel': g['menuLabel'],
                   'voordatLabel': g['voordatLabel'], 'tabInformatie': g['tabInformatie'], 'tabHulpmiddelen': g['tabHulpmiddelen'],
                   'tabWerkwijze': g['tabWerkwijze'], 'tipsKop': g['tipsKop'], 'leegTekst': g['leegTekst'],
                   **dict(zip(['antwoordNee', 'antwoordTwijfel', 'antwoordJa'], bron['stappen'][4]['meer']['checklist']['kolommen']))},
        'footer': {'logo': '@asset:zzsd-logo', 'onderschrift': g['footerTekst']},
    },
}
(root / 'seed/content.json').write_text(json.dumps(seed, ensure_ascii=False, indent=2) + '\n')
print(f"{len(stappen)} stappen, {len(rollen)} rollen, {len(vjb['secties'])} onderdelen, {len(ref['items'])} referenties")
