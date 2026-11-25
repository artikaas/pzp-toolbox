/** Mobiel menu: openen en sluiten, Escape sluit, focus terug naar de knop. */
export function initMenu() {
  const knop = document.querySelector('[data-menu-toggle]');
  const menu = document.getElementById(knop?.getAttribute('aria-controls') ?? '');
  if (!knop || !menu) return;

  const zet = (open) => {
    knop.setAttribute('aria-expanded', String(open));
    menu.hidden = !open;
    knop.querySelector('[data-icoon-open]')?.toggleAttribute('hidden', open);
    knop.querySelector('[data-icoon-dicht]')?.toggleAttribute('hidden', !open);
    document.documentElement.classList.toggle('overflow-hidden', open);
  };

  knop.addEventListener('click', () => zet(knop.getAttribute('aria-expanded') !== 'true'));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && knop.getAttribute('aria-expanded') === 'true') {
      zet(false);
      knop.focus();
    }
  });
  // Bij groter scherm (desktopmenu zichtbaar) het mobiele menu sluiten
  window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => e.matches && zet(false));
}
