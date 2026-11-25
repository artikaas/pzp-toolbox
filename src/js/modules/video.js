/**
 * Video met toestemming-vriendelijke embed: YouTube/Vimeo wordt pas geladen na een klik
 * (geen cookies of tracking vooraf). Knop: data-video-embed="<embed-url>".
 */
export function initVideo() {
  document.querySelectorAll('[data-video-embed]').forEach((knop) =>
    knop.addEventListener('click', () => {
      const iframe = document.createElement('iframe');
      iframe.src = knop.dataset.videoEmbed;
      iframe.title = knop.dataset.videoTitel || 'Video';
      iframe.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
      iframe.allowFullscreen = true;
      iframe.className = 'absolute inset-0 h-full w-full';
      knop.replaceWith(iframe);
      iframe.focus();
    })
  );
}
