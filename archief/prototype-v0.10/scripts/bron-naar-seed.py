# V0.3: tips zonder cursief en referenties in de footer worden na deze stap gezet.
# Zet het brondocument (docx van Noortje) om naar seed/content.json. Teksten blijven letterlijk.
import docx, json, re, html as H
D = docx.Document('/mnt/user-data/uploads/20261109_Toolbox_PZP__Hoger_opgeleiden_.docx')
P = D.paragraphs

def runs_html(runs):
    out = ''
    for r in runs:
        t = H.escape(r.text, quote=False)
        if not t: continue
        if r.italic: t = f'<em>{t}</em>'
        if r.bold: t = f'<strong>{t}</strong>'
        out += t
    out = re.sub(r'</strong><strong>', '', out); out = re.sub(r'</em><em>', '', out)
    return out.replace('⚠️ ', '').replace('⚠️', '').strip()

def h(i): return runs_html(P[i].runs)
def plain(i): return P[i].text.replace('⚠️', '').strip()
def kop(i): return plain(i).rstrip(':').strip()
def na_label(i):
    t = h(i)
    m = re.match(r'^(<strong>|<em>)?[^<:]*:\s*(</strong>|</em>)\s*', t)
    return t[m.end():] if m else t.split(':', 1)[1].strip()
def label(i):
    return re.sub(r'<[^>]+>', '', h(i)).split(':', 1)[0].strip()
def lijst(ids): return [h(i) for i in ids]
def link(i, met_omschrijving=True):
    t = plain(i)
    if met_omschrijving and ':' in t:
        titel, oms = label(i), na_label(i)
        titel = re.sub(r'^Tool\s+', '', titel)
    else:
        titel, oms = t, ''
    return {"titel": titel, "omschrijving": oms, "url": "", "type": "Link volgt"}

def tabel(t):
    rows = []
    for r in t.rows:
        rows.append([runs_html([run for p in c.paragraphs for run in p.runs]) for c in r.cells])
    return rows

alle, regie = "alle-zorgprofessionals", "regiebehandelaar"
stappen = [
 {"nummer": 1, "titel": "Signaleren en markeren", "kaarttitel": "Signaleren en markeren",
  "rollen": [alle, regie], "doel": na_label(48),
  "kolommen": [[{"kop": kop(49), "items": lijst(range(50, 54))}], [{"kop": kop(54), "items": lijst(range(55, 60))}]],
  "tips": lijst([69, 70]),
  "meer": {"hulpmiddelen": [{"kop": "", "links": [link(i) for i in (61, 62, 63)]}],
           "werkwijzen": [], "rolverdeling": lijst([65, 66, 67]), "achtergrond": [], "checklist": None}},
 {"nummer": 2, "titel": "Exploreren van waarden en behoeften", "kaarttitel": "Exploreren van waarden en behoeften",
  "rollen": [alle], "doel": na_label(73),
  "kolommen": [[{"kop": kop(74), "items": lijst([75, 76])}, {"kop": label(88), "tekst": na_label(88)}],
               [{"kop": kop(89), "items": lijst([90, 91])}]],
  "tips": lijst([95, 96]),
  "meer": {"hulpmiddelen": [{"kop": kop(78), "links": [link(i, False) for i in range(79, 83)]},
                            {"kop": kop(83), "links": [link(i, False) for i in range(84, 88)]}],
           "werkwijzen": [], "rolverdeling": lijst([93]), "achtergrond": [], "checklist": None}},
 {"nummer": 3, "titel": "Gezamenlijke besluitvorming (Samen beslissen)", "kaarttitel": "Samen beslissen",
  "rollen": [regie], "doel": na_label(99),
  "kolommen": [[{"kop": kop(100), "items": lijst(range(101, 104))}], [{"kop": kop(104), "items": lijst([105])}]],
  "tips": lijst([109, 110]),
  "meer": {"hulpmiddelen": [], "werkwijzen": [], "rolverdeling": lijst([107]), "achtergrond": [], "checklist": None}},
 {"nummer": 4, "titel": "Gegevens vastleggen en delen", "kaarttitel": "Gegevens vastleggen en delen",
  "rollen": [alle, regie], "doel": na_label(113),
  "kolommen": [[{"kop": kop(114), "items": lijst([115])}, {"kop": kop(121), "items": lijst([122, 123])}],
               [{"kop": kop(116), "tekst": h(117), "items": lijst(range(118, 121))}]],
  "tips": lijst([129, 130]),
  "meer": {"hulpmiddelen": [{"kop": "", "links": [
                {"titel": "Informatiestandaard PZP", "omschrijving": "", "url": "", "type": "Link volgt"},
                {"titel": "Formulier Uniform Vastleggen PZP", "omschrijving": "", "url": "", "type": "Link volgt"},
                {"titel": "Zorgviewer / Versneld Verbinden", "omschrijving": "", "url": "", "type": "Link volgt"}]}],
           "werkwijzen": [],
           "rolverdeling": lijst([125, 126, 127]), "achtergrond": [], "checklist": None}},
 {"nummer": 5, "titel": "Evaluatie en borging", "kaarttitel": "Evaluatie en borging",
  "rollen": [alle, regie], "doel": na_label(133),
  "kolommen": [[{"kop": kop(134), "items": lijst(range(135, 139))}], [{"kop": kop(142), "items": lijst([143, 144])}]],
  "tips": lijst([149, 150, 151]),
  "meer": {"hulpmiddelen": [], "werkwijzen": [], "rolverdeling": lijst([146, 147]),
           "achtergrond": [{"kop": kop(139), "items": lijst([140, 141])}],
           "checklist": None}},
]
t2 = tabel(D.tables[1])
stappen[4]["meer"]["checklist"] = {
    "titel": re.sub(r'^Extra tool bij stap 5:\s*', '', plain(153)),
    "instructie": h(154),
    "kolommen": [re.sub(r'<[^>]+>', '', c) for c in t2[0][1:]],
    "signalen": [r[0] for r in t2[1:-1]],
    "conclusie": t2[-1][0],
    "uitkomst": {"kop": kop(155), "nee": na_label(156), "anders_kop": label(157), "anders": lijst([158, 159, 160])},
}

def blokken(ids):
    out = []
    lijstbuf = []
    def flush():
        if lijstbuf: out.append({"type": "lijst", "items": lijstbuf.copy()}); lijstbuf.clear()
    for i in ids:
        if not P[i].text.strip(): continue
        p = P[i]; pPr = p._p.pPr
        is_lijst = pPr is not None and pPr.numPr is not None
        runs = [r for r in p.runs if r.text.strip()]
        if is_lijst: lijstbuf.append(h(i)); continue
        flush()
        if runs and all(r.bold for r in runs): out.append({"type": "kop", "tekst": kop(i)})
        else: out.append({"type": "tekst", "html": h(i)})
    flush()
    return out

t1 = tabel(D.tables[0])
intro_s2 = blokken(range(29, 38))
intro_s2.append({"type": "tabel", "kop": t1[0], "rijen": t1[1:]})
intro_s2 += blokken(range(38, 43))
voordat = {
  "titel": "Voordat je begint",
  "secties": [
    {"titel": "Waarom en wat is PZP", "blokken": blokken(range(6, 21))},   # los kopje op verzoek van de klant
    {"titel": plain(3), "blokken": blokken(list(range(4, 6)) + list(range(21, 27)))},
    {"titel": plain(28), "blokken": intro_s2},
  ]}

content = {
  "globals": {
    "toolnaam": ["PZP", "toolbox"],
    "heroTitel": "Tijdig spreken over de *laatste levensfase*",
    "heroSubtitel": "Proactieve zorgplanning",
    "footerTekst": "De PZP-Tool is een product van Netwerk palliatieve Zorg Zuidoost Brabant • 2026",
    "leesMeer": "Lees meer",
    "tipsKop": "TIPS",
    "menuLabel": "Menu",
    "leegTekst": "Nog niet toegevoegd.",
    "tabInformatie": "Informatie", "tabWerkwijze": "Werkwijze per sector", "tabHulpmiddelen": "Hulpmiddelen en documenten", "toolboxLabel": "Toolbox",
    "werkwijzeDocument": {"titel": "Werkwijze per sector", "omschrijving": "", "url": "", "type": "Document volgt"},
    "rollen": {alle: {"label": "Alle zorgprofessionals", "kleur": "aubergine"},
               regie: {"label": "Regiebehandelaar", "kleur": "roze"}},
    "documentTitel": plain(0), "documentOndertitel": plain(1),
  },
  "stappen": stappen,
  "voordatJeBegint": voordat,
  "referenties": {"titel": "Referenties & onderbouwende kaders", "items": lijst(range(163, 171))},
}
# 'Patiënt' vetgedrukt zoals de andere begrippen (feedback focusgroep)
for s in voordat["secties"]:
    for blok in s["blokken"]:
        if blok["type"] == "lijst":
            blok["items"] = [i.replace("<em>Patiënt:</em>*", "<strong>Patiënt:*</strong>", 1) for i in blok["items"]]
json.dump(content, open('seed/content.json', 'w'), ensure_ascii=False, indent=1)
print(json.dumps(stappen[0], ensure_ascii=False, indent=1)[:2500])
print(json.dumps(stappen[4]["meer"]["checklist"], ensure_ascii=False, indent=1)[:1500])
print(json.dumps(voordat["secties"][1]["blokken"][:3], ensure_ascii=False)[:800])
