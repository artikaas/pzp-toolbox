/**
 * Hoofdbundel. Elke module controleert zelf of zijn elementen op de pagina staan.
 * Gebouwd met Vite; Twig laadt de output via de functie vite('src/js/app.js').
 *
 * Bewust zonder externe libraries. Voeg GSAP, Swiper e.d. pas toe als het ontwerp erom vraagt
 * (en noteer dat als afwijking van de starter).
 */
import '../css/app.css';
import { initMenu } from './modules/menu.js';
import { initReveal } from './modules/reveal.js';
import { initVideo } from './modules/video.js';
import { initContactForm } from './modules/contact-form.js';

document.documentElement.classList.add('js');

const ready = (fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn());

ready(() => {
  initMenu();
  initVideo();
  initContactForm();
  initReveal();
});
