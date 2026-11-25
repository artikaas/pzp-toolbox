/** Navbar: zachte schaduw zodra er gescrold is. */
export function initNavbar() {
  const nav = document.querySelector('.nav');
  if (!nav) return;
  const gescrold = () => nav.toggleAttribute('data-gescrold', window.scrollY > 8);
  addEventListener('scroll', gescrold, { passive: true });
  gescrold();
}
