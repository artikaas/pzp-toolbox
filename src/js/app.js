/**
 * Hoofdbundel. Elke module controleert zelf of zijn elementen op de pagina staan.
 * Gebouwd met Vite; Twig laadt de output via de functie vite('src/js/app.js').
 */
import '../css/app.css';
import { initPanelen } from './modules/panelen.js';
import { initWiel } from './modules/wiel.js';
import { initChecklist } from './modules/checklist.js';
import { initNavbar } from './modules/navbar.js';

document.documentElement.classList.add('js');

const ready = (fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn());

ready(() => {
  initNavbar();
  initPanelen();
  initWiel();
  initChecklist();
});
