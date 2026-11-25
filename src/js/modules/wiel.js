/**
 * Het wiel (cirkel + kaarten) past altijd in één keer in beeld: schalen op schermhoogte en -breedte.
 * Op mobiel (<= 760 px) staan de stappen als lijst onder een kleine cirkel en wordt er niet geschaald.
 */
export function initWiel() {
  const c = document.querySelector('.cirkel--5');
  if (!c) return;
  const hero = c.closest('.hero');
  const nav = document.querySelector('.nav');

  const schaal = () => {
    if (innerWidth <= 760) { c.style.zoom = ''; c.style.margin = ''; hero.style.minHeight = ''; return; }
    c.style.zoom = 1;
    c.style.margin = '0 auto';
    const r = c.getBoundingClientRect();
    const delen = [r, ...[...c.querySelectorAll('.ballon, .kaart .tag')].map((e) => e.getBoundingClientRect())];
    const top = Math.min(...delen.map((d) => d.top));
    const onderkant = Math.max(...delen.map((d) => d.bottom));
    const links = Math.min(...delen.map((d) => d.left));
    const rechts = Math.max(...delen.map((d) => d.right));
    const ruimte = 48;
    const boven = r.top - top + ruimte;
    const onder = onderkant - r.bottom + ruimte;
    const zij = Math.max(r.left - links, rechts - r.right) + ruimte;
    const hoogte = innerHeight - (nav ? nav.offsetHeight : 0);
    const z = Math.min(hoogte / (boven + r.height + onder), innerWidth / (r.width + 2 * zij), 1.15);
    c.style.margin = `${boven}px auto ${onder}px`;
    c.style.zoom = z;
    hero.style.minHeight = `${hoogte}px`;
  };

  let frame;
  const plan = () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(schaal); };
  addEventListener('resize', plan);
  schaal();
  if (document.fonts) document.fonts.ready.then(plan);
}
