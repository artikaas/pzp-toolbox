// PZP-toolbox V0.1: panelen openen en sluiten, checklist stap 5. Er wordt niets opgeslagen.
(() => {
  const nav = document.querySelector('.nav');
  const gescrold = () => nav && nav.toggleAttribute('data-gescrold', window.scrollY > 8);
  addEventListener('scroll', gescrold, { passive: true }); gescrold();

  // Het wiel (cirkel + kaarten) past altijd in één keer in beeld: schalen op schermhoogte en -breedte.
  const schaalWiel = () => {
    const c = document.querySelector('.cirkel--5');
    if (!c) return;
    const hero = c.closest('.hero');
    if (innerWidth <= 760) { c.style.zoom = ''; c.style.margin = ''; hero.style.minHeight = ''; return; }
    c.style.zoom = 1; c.style.margin = '0 auto';
    const r = c.getBoundingClientRect();
    const delen = [r, ...[...c.querySelectorAll('.ballon, .kaart .tag')].map((e) => e.getBoundingClientRect())];
    const top = Math.min(...delen.map((d) => d.top)), onderkant = Math.max(...delen.map((d) => d.bottom));
    const links = Math.min(...delen.map((d) => d.left)), rechts = Math.max(...delen.map((d) => d.right));
    const ruimte = 48;
    const boven = r.top - top + ruimte, onder = onderkant - r.bottom + ruimte;
    const zij = Math.max(r.left - links, rechts - r.right) + ruimte;
    const hoogte = innerHeight - (nav ? nav.offsetHeight : 0);
    const z = Math.min(hoogte / (boven + r.height + onder), innerWidth / (r.width + 2 * zij), 1.15);
    c.style.margin = `${boven}px auto ${onder}px`;
    c.style.zoom = z;
    hero.style.minHeight = hoogte + 'px';
  };
  let wielFrame;
  const plan = () => { cancelAnimationFrame(wielFrame); wielFrame = requestAnimationFrame(schaalWiel); };
  addEventListener('resize', plan);
  schaalWiel();
  if (document.fonts) document.fonts.ready.then(plan);
  let terugFocus = null;
  const open = (id) => {
    const d = document.getElementById(id);
    if (!d) return;
    const huidig = document.querySelector('dialog[open]');
    if (huidig && huidig !== d) huidig.close(); else terugFocus = document.activeElement;
    d.showModal();
    d.scrollTop = 0;
  };
  const kiesTab = (d, naam, focus) => {
    const tabs = [...d.querySelectorAll('[role="tab"]')];
    if (!tabs.length) return;
    const doel = tabs.find((t) => t.dataset.tab === naam) || tabs[0];
    tabs.forEach((t) => {
      const aan = t === doel;
      t.setAttribute('aria-selected', aan); t.tabIndex = aan ? 0 : -1;
      document.getElementById(t.getAttribute('aria-controls')).hidden = !aan;
    });
    if (focus) doel.focus();
    d.scrollTop = 0;
  };
  const naarAnker = (opener) => {
    const doel = opener.dataset.anker && document.getElementById(opener.dataset.anker);
    if (doel) requestAnimationFrame(() => doel.scrollIntoView({ block: 'start' }));
  };
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open]');
    if (opener) { e.preventDefault(); open(opener.dataset.open); kiesTab(document.getElementById(opener.dataset.open), opener.dataset.tab || 'info'); naarAnker(opener); return; }
    const tab = e.target.closest('[role="tab"]');
    if (tab) { kiesTab(tab.closest('dialog'), tab.dataset.tab); return; }
    const link = e.target.closest('dialog[data-sluit-bij-link] a[href^="#"]');
    if (link) { link.closest('dialog').close(); return; }
    if (e.target.closest('[data-close]')) { e.target.closest('dialog').close(); return; }
    if (e.target.tagName === 'DIALOG') e.target.close();
  });
  document.addEventListener('keydown', (e) => {
    const tab = e.target.closest && e.target.closest('[role="tab"]');
    if (!tab || !['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
    const tabs = [...tab.parentElement.querySelectorAll('[role="tab"]')];
    let i = tabs.indexOf(tab);
    i = e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : (i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    e.preventDefault(); kiesTab(tab.closest('dialog'), tabs[i].dataset.tab, true);
  });
  document.querySelectorAll('dialog').forEach((d) => d.addEventListener('close', () => {
    if (!document.querySelector('dialog[open]') && terugFocus) { terugFocus.focus({ preventScroll: true }); terugFocus = null; }
  }));
  document.querySelectorAll('[data-checklist]').forEach((c) => {
    const update = () => {
      const keuzes = [...c.querySelectorAll('input:checked')].map((i) => i.value);
      const rijen = c.querySelectorAll('fieldset').length;
      const anders = keuzes.some((v) => v !== '1');
      c.querySelector('[data-uitkomst="anders"]').classList.toggle('is-actief', anders);
      c.querySelector('[data-uitkomst="nee"]').classList.toggle('is-actief', !anders && keuzes.length === rijen);
    };
    c.addEventListener('change', update);
    c.querySelector('[data-wis]').addEventListener('click', () => { c.querySelectorAll('input').forEach((i) => (i.checked = false)); update(); });
  });
})();
