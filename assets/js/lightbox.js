// Lightbox: extracted and isolated from gallery logic

document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('lightboxModal');
  const lightboxImage = document.getElementById('lightboxImage');
  const closeBtn = document.querySelector('.lightbox-close');
  const prevBtn = document.querySelector('.lightbox-prev');
  const nextBtn = document.querySelector('.lightbox-next');
  const captionEl = document.getElementById('lightboxCaption');

  let galleryLinks = [];
  let currentIndex = 0;

  function collectGallery(link) {
    const gallery = link.closest('.project-gallery') || document;
    return Array.from(gallery.querySelectorAll('.lightbox-link'));
  }

  function open(index) {
    if (!galleryLinks || index < 0 || index >= galleryLinks.length) return;
    const link = galleryLinks[index];
    const img = link.querySelector('img');
    const src = (img && img.dataset && img.dataset.fullSrc) || img?.src || link.href;

    currentIndex = index;
    lightboxImage.classList.remove('loaded');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    lightboxImage.onload = function () {
      lightboxImage.classList.add('loaded');
    };
    lightboxImage.src = src;
    // set caption (from data-caption attribute on link)
    const rawCaption = link.dataset && link.dataset.caption ? link.dataset.caption : link.getAttribute('data-caption') || '';
    if (captionEl) {
      captionEl.textContent = rawCaption || '';
      captionEl.style.display = rawCaption ? 'block' : 'none';
    }

    updateNav();
  }

  function close() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    lightboxImage.src = '';
    currentIndex = 0;
    if (captionEl) {
      captionEl.textContent = '';
      captionEl.style.display = 'none';
    }
  }

  function updateNav() {
    if (!prevBtn || !nextBtn) return;
    prevBtn.style.display = currentIndex <= 0 ? 'none' : 'flex';
    nextBtn.style.display = currentIndex >= galleryLinks.length - 1 ? 'none' : 'flex';
  }

  // Attach click handlers to all lightbox links and keep gallery scoping
  document.querySelectorAll('.lightbox-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      galleryLinks = collectGallery(link);
      const idx = galleryLinks.indexOf(link);
      if (idx === -1) return;
      open(idx);
    });
  });

  // Close handlers
  if (closeBtn) closeBtn.addEventListener('click', close);
  if (modal) {
    modal.addEventListener('click', function (e) {
      // Ignore clicks on navigation buttons or the explicit close button
      if (e.target.closest && (e.target.closest('.lightbox-nav') || e.target.closest('.lightbox-close'))) return;
      // If click is inside the image container or the image itself, do nothing
      if (e.target.closest && (e.target.closest('.lightbox-image') || e.target.closest('.lightbox-image-container'))) return;
      // Otherwise (clicked overlay or any area outside the image) close
      close();
    });
  }

  // Prev/Next
  if (prevBtn) prevBtn.addEventListener('click', function (e) {
    e.preventDefault();
    if (currentIndex > 0) open(currentIndex - 1);
  });
  if (nextBtn) nextBtn.addEventListener('click', function (e) {
    e.preventDefault();
    if (currentIndex < galleryLinks.length - 1) open(currentIndex + 1);
  });

  // Keyboard
  document.addEventListener('keydown', function (e) {
    if (!modal.classList.contains('active')) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft') prevBtn && prevBtn.click();
    else if (e.key === 'ArrowRight') nextBtn && nextBtn.click();
  });

  // Simple touch swipe for gallery navigation
  let touchStartX = 0;
  let touchStartY = 0;
  const container = document.querySelector('.lightbox-image-container');
  if (container) {
    container.addEventListener('touchstart', function (e) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
    });
    container.addEventListener('touchend', function (e) {
      const dx = touchStartX - e.changedTouches[0].clientX;
      const dy = touchStartY - e.changedTouches[0].clientY;
      if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) {
        if (dx > 0) nextBtn && nextBtn.click();
        else prevBtn && prevBtn.click();
      }
    });
  }
});
