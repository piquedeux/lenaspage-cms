// project-thumbnail-gallery.js
// Animiert Thumbnails auf Desktop bei Hover und auf Mobile automatisch

document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    // Disable thumbnail cycling entirely on mobile — causes scroll refresh issues.
    if (isMobile) return;

    const projectCards = document.querySelectorAll('.project-card');

    // Desktop: bei Hover durchlaufen
    projectCards.forEach(card => {
        const gallery = card.dataset.gallery ? JSON.parse(card.dataset.gallery) : [];
        const thumbnail = card.querySelector('.project-thumbnail');
        if (!thumbnail || !gallery.length) return;
        let current = 0;
        let interval = null;
        function showImage(idx) {
            if (!gallery[idx]) return;
            thumbnail.src = gallery[idx];
        }
        card.addEventListener('mouseenter', () => {
            current = 0;
            showImage(current);
            interval = setInterval(() => {
                current = (current + 1) % gallery.length;
                showImage(current);
            }, 700);
        });
        card.addEventListener('mouseleave', () => {
            clearInterval(interval);
            // Ursprungsbild wiederherstellen
            showImage(0);
        });
    });
});
