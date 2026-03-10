(function initFooterBehavior(){
  function setup() {
    // Only enable this behavior on project detail pages
    if (!document.querySelector('.project-detail-image')) return;
    const footer = document.querySelector('.site-footer');
    if (!footer) return;
    function isAtBottom() {
      const scrollBottom = window.innerHeight + window.scrollY;
      const docHeight = document.documentElement.scrollHeight;
      return scrollBottom >= docHeight - 1; // -1 to account for rounding errors
    }

    // New behaviour:
    // - Footer is NOT fixed initially.
    // - Once the user has reached the page bottom at least once, we enter "activated" mode.
    // - While activated, when the user scrolls up the footer becomes sticky (fixed at bottom).
    // - When the user scrolls down back to the bottom, the footer returns to normal (not fixed).
    let hasVisitedBottom = false;
    let lastY = window.scrollY;
    let ticking = false;

    function onFrame() {
      const y = window.scrollY;
      const atBottom = isAtBottom();
      const docHeight = document.documentElement.scrollHeight;
      const halfPoint = docHeight * 0.5;

      if (atBottom) hasVisitedBottom = true;

      if (hasVisitedBottom) {
        if (y < lastY) {
          // scrolling up -> show sticky footer, unless we've scrolled up past half the document
          if (y < halfPoint) {
            footer.classList.remove('footer-fixed');
          } else {
            footer.classList.add('footer-fixed');
          }
        } else if (atBottom && y >= lastY) {
          // landed at bottom or scrolling down into bottom -> remove sticky
          footer.classList.remove('footer-fixed');
        }
      }

      lastY = y;
      ticking = false;
    }

    // initial check (in case page loads already at bottom)
    if (isAtBottom()) hasVisitedBottom = true;

    window.addEventListener('scroll', function() {
      if (!ticking) {
        window.requestAnimationFrame(onFrame);
        ticking = true;
      }
    }, { passive: true });

    window.addEventListener('resize', function() {
      // recalc bottom status on resize
      if (!ticking) {
        window.requestAnimationFrame(onFrame);
        ticking = true;
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    // DOM already ready
    setup();
  }
})();
