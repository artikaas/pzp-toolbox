/**
 * Contactformulier: validatie in de browser met nette foutmeldingen voordat het formulier verstuurd wordt.
 * Foutmelding per veld: een element met het id uit aria-describedby.
 */
export function initContactForm() {
  document.querySelectorAll('[data-contact-form]').forEach((form) => {
    const check = (el) => {
      const err = el.getAttribute('aria-describedby');
      const valid = el.checkValidity() && (!el.required || el.value.trim() !== '');
      el.setAttribute('aria-invalid', String(!valid));
      const msg = err && document.getElementById(err);
      if (msg) msg.hidden = valid;
      return valid;
    };
    const fields = Array.from(form.querySelectorAll('[required]'));
    fields.forEach((f) => f.addEventListener('blur', () => check(f)));
    form.addEventListener('submit', (e) => {
      const results = fields.map(check);
      if (results.includes(false)) {
        e.preventDefault();
        fields[results.indexOf(false)].focus();
      }
    });
  });
}
