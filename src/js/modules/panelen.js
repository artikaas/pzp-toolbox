/**
 * Panelen (dialog): openen via [data-open], sluiten via [data-close], de achtergrond of Esc.
 * Tabbladen ([role="tab"], ook met pijltjestoetsen), [data-tab] kiest het tabblad bij openen,
 * [data-anker] scrolt naar een onderdeel. In panelen met [data-sluit-bij-link] sluit een ankerlink het paneel.
 */
export function initPanelen() {
  if (!document.querySelector('dialog.paneel')) return;
  let terugFocus = null;

  const kiesTab = (d, naam, focus) => {
    const tabs = [...d.querySelectorAll('[role="tab"]')];
    if (!tabs.length) return;
    const doel = tabs.find((t) => t.dataset.tab === naam) || tabs[0];
    tabs.forEach((t) => {
      const aan = t === doel;
      t.setAttribute('aria-selected', aan);
      t.tabIndex = aan ? 0 : -1;
      document.getElementById(t.getAttribute('aria-controls')).hidden = !aan;
    });
    if (focus) doel.focus();
    d.scrollTop = 0;
  };

  const open = (id) => {
    const d = document.getElementById(id);
    if (!d) return null;
    const huidig = document.querySelector('dialog[open]');
    if (huidig && huidig !== d) huidig.close();
    else terugFocus = document.activeElement;
    d.showModal();
    d.scrollTop = 0;
    return d;
  };

  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open]');
    if (opener) {
      e.preventDefault();
      const d = open(opener.dataset.open);
      if (!d) return;
      kiesTab(d, opener.dataset.tab || 'info');
      const anker = opener.dataset.anker && document.getElementById(opener.dataset.anker);
      if (anker) requestAnimationFrame(() => anker.scrollIntoView({ block: 'start' }));
      return;
    }
    const tab = e.target.closest('[role="tab"]');
    if (tab) { kiesTab(tab.closest('dialog'), tab.dataset.tab); return; }
    if (e.target.closest('[data-close]')) { e.target.closest('dialog').close(); return; }
    const link = e.target.closest('dialog[data-sluit-bij-link] a[href^="#"]');
    if (link) { link.closest('dialog').close(); return; }
    if (e.target.tagName === 'DIALOG') e.target.close();
  });

  document.addEventListener('keydown', (e) => {
    const tab = e.target.closest && e.target.closest('[role="tab"]');
    if (!tab || !['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
    const tabs = [...tab.parentElement.querySelectorAll('[role="tab"]')];
    let i = tabs.indexOf(tab);
    i = e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : (i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    e.preventDefault();
    kiesTab(tab.closest('dialog'), tabs[i].dataset.tab, true);
  });

  document.querySelectorAll('dialog.paneel').forEach((d) => d.addEventListener('close', () => {
    if (!document.querySelector('dialog[open]') && terugFocus) { terugFocus.focus({ preventScroll: true }); terugFocus = null; }
  }));
}
