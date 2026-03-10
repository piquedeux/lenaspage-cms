document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.folder-overlay');
  const folder = document.querySelector('.folder');

  if (!overlay) return;

  // Use sessionStorage so the overlay only appears once
  // per browser session (will show again after closing
  // the browser or tab), instead of a persistent cookie.
  const hasSeenThisSession = sessionStorage.getItem('hasSeenFolder') === '1';

  if (hasSeenThisSession) {
    document.body.classList.add('folder-opened');
    overlay.classList.add('finished');
    return;
  }

  overlay.classList.add('is-active');

  // Overlay is visible for this session: lock scrolling behind it
  document.body.classList.add('folder-overlay-active');

  let didClose = false;
  const closeOverlay = () => {
    if (didClose) return;
    didClose = true;
    sessionStorage.setItem('hasSeenFolder', '1');
    overlay.classList.add('fade-out');

    setTimeout(() => {
      document.body.classList.add('folder-opened');
      document.body.classList.remove('folder-overlay-active');
      overlay.classList.remove('is-active');
      overlay.classList.add('finished');
    }, 400);
  };

  // Close when clicking anywhere in the overlay.
  overlay.addEventListener('click', closeOverlay);

  // Keep compatibility with old markup that used a .folder element.
  if (folder) {
    folder.addEventListener('click', (event) => {
      event.stopPropagation();
      closeOverlay();
    });
  }
});
