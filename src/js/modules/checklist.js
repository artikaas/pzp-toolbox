/** Checklist: toont de uitkomst bij de antwoorden. Er wordt niets opgeslagen. */
export function initChecklist() {
  document.querySelectorAll('[data-checklist]').forEach((c) => {
    const update = () => {
      const keuzes = [...c.querySelectorAll('input:checked')].map((i) => i.value);
      const rijen = c.querySelectorAll('fieldset').length;
      const anders = keuzes.some((v) => v !== '1');
      c.querySelector('[data-uitkomst="anders"]').classList.toggle('is-actief', anders);
      c.querySelector('[data-uitkomst="nee"]').classList.toggle('is-actief', !anders && keuzes.length === rijen);
    };
    c.addEventListener('change', update);
    c.querySelector('[data-wis]').addEventListener('click', () => {
      c.querySelectorAll('input').forEach((i) => { i.checked = false; });
      update();
    });
  });
}
