/** Elementen met data-reveal verschijnen zodra ze in beeld komen (uit bij prefers-reduced-motion, zie app.css). */
export function initReveal() {
  const els = document.querySelectorAll('[data-reveal]');
  if (!els.length) return;
  if (!('IntersectionObserver' in window)) {
    els.forEach((el) => el.classList.add('is-zichtbaar'));
    return;
  }
  const io = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-zichtbaar');
          io.unobserve(entry.target);
        }
      }),
    { rootMargin: '0px 0px -10% 0px' }
  );
  els.forEach((el) => io.observe(el));
}
