// project-thumbnail-gallery.js
// Animiert Thumbnails auf Desktop bei Hover und auf Mobile automatisch

document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    const projectCards = document.querySelectorAll('.project-card');

    if (isMobile) {
        // Mobile: alle Galerien nacheinander durchlaufen, leere überspringen
        const allGalleries = [];
        projectCards.forEach(card => {
            const gallery = card.dataset.gallery ? JSON.parse(card.dataset.gallery) : [];
            const thumbnail = card.querySelector('.project-thumbnail');
            if (!thumbnail || !gallery.length) return;
            allGalleries.push({gallery, thumbnail});
        });
        let galleryIdx = 0;
        let imageIdx = 0;
        function showNext() {
            if (!allGalleries.length) return;
            const {gallery, thumbnail} = allGalleries[galleryIdx];
            if (!gallery.length) {
                // Überspringen
                galleryIdx = (galleryIdx + 1) % allGalleries.length;
                imageIdx = 0;
                showNext();
                return;
            }
            thumbnail.src = gallery[imageIdx];
            imageIdx++;
            if (imageIdx >= gallery.length) {
                imageIdx = 0;
                galleryIdx = (galleryIdx + 1) % allGalleries.length;
            }
        }
        showNext();
        setInterval(showNext, 2000);
    } else {
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
    }
});
