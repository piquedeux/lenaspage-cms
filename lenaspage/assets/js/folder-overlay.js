document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.folder-overlay');
  const folder = document.querySelector('.folder');

  if (!folder || !overlay) return;

  // Use sessionStorage so the overlay only appears once
  // per browser session (will show again after closing
  // the browser or tab), instead of a persistent cookie.
  const hasSeenThisSession = sessionStorage.getItem('hasSeenFolder') === '1';

  if (hasSeenThisSession) {
    document.body.classList.add('folder-opened');
    overlay.classList.add('finished');
    return;
  }

  // Overlay is visible for this session: lock scrolling behind it
  document.body.classList.add('folder-overlay-active');

  folder.addEventListener('click', () => {
    sessionStorage.setItem('hasSeenFolder', '1');
    overlay.classList.add('fade-out');

    setTimeout(() => {
      document.body.classList.add('folder-opened');
      document.body.classList.remove('folder-overlay-active');
      overlay.classList.add('finished');
    }, 400);
  });
});
