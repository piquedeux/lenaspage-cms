document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.folder-overlay');
  const folder = document.querySelector('.folder');
  const headerLogo = document.querySelector('.header-container .site-logo');
  const overlayHomeTrigger = document.querySelector('[data-overlay-home]');

  if (!overlay) return;

  const shouldSkipOverlay = sessionStorage.getItem('skipFolderOverlayOnce') === '1';

  if (shouldSkipOverlay) {
    sessionStorage.removeItem('skipFolderOverlayOnce');
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
    overlay.classList.add('fade-out');

    setTimeout(() => {
      document.body.classList.add('folder-opened');
      document.body.classList.remove('folder-overlay-active');
      overlay.classList.remove('is-active');
      overlay.classList.add('finished');
    }, 400);
  };

  if (headerLogo) {
    headerLogo.addEventListener('click', () => {
      sessionStorage.setItem('skipFolderOverlayOnce', '1');
    });
  }

  // Close when clicking on open overlay space, but let links/buttons handle their own action.
  overlay.addEventListener('click', (event) => {
    if (event.target.closest('a, button')) return;
    closeOverlay();
  });

  if (overlayHomeTrigger) {
    overlayHomeTrigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      closeOverlay();
    });
  }

  // Keep compatibility with old markup that used a .folder element.
  if (folder) {
    folder.addEventListener('click', (event) => {
      event.stopPropagation();
      closeOverlay();
    });
  }
});
