// Project Gallery - List/Grid Toggle, Lightbox, and Zoom Functionality

document.addEventListener('DOMContentLoaded', function() {
    const gallery = document.querySelector('.project-gallery');
    if (!gallery) return;

    const toggleButtons = document.querySelectorAll('.gallery-toggle');
    const lightboxModal = document.getElementById('lightboxModal');
    const galleryImages = document.querySelectorAll('.gallery-image');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxClose = document.querySelector('.lightbox-close');
    const lightboxPrev = document.querySelector('.lightbox-prev');
    const lightboxNext = document.querySelector('.lightbox-next');
    const lightboxImageContainer = document.querySelector('.lightbox-image-container');

    let currentZoom = 1;
    let currentImageIndex = 0;
    let isImageLoading = false;
    const zoomStep = 0.15;
    const maxZoom = 4;
    const minZoom = 1;

    // Load gallery view preference from localStorage
    const savedView = localStorage.getItem('galleryView') || 'grid';
    setGalleryView(savedView);

    // Toggle view buttons
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            setGalleryView(view);
            localStorage.setItem('galleryView', view);
        });
    });

    function setGalleryView(view) {
        gallery.setAttribute('data-gallery-view', view);
        document.querySelectorAll('.gallery-toggle').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');
    }

    // Lightbox functionality
    galleryImages.forEach((img, index) => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            currentImageIndex = index;
            openLightbox(index);
        });
    });

    function openLightbox(index) {
        if (index < 0 || index >= galleryImages.length) return;
        
        currentImageIndex = index;
        currentZoom = 1;
        isImageLoading = true;
        
        const img = galleryImages[index];
        lightboxImage.src = img.dataset.fullSrc;
        lightboxImage.alt = img.alt;
        
        lightboxModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Reset transform
        lightboxImage.style.transform = '';
        lightboxImage.classList.remove('loaded');
        
        // Add loaded class when image is ready
        lightboxImage.onload = function() {
            isImageLoading = false;
            lightboxImage.classList.add('loaded');
        };
        
        if (lightboxImage.complete) {
            isImageLoading = false;
            lightboxImage.classList.add('loaded');
        }
        
        // Update nav button states
        updateNavStates();
    }

    function closeLightbox() {
        lightboxModal.classList.remove('active');
        document.body.style.overflow = '';
        currentZoom = 1;
        lightboxImage.style.transform = '';
    }

    function updateNavStates() {
        if (currentImageIndex === 0) {
            lightboxPrev.style.display = 'none';
        } else {
            lightboxPrev.style.display = 'flex';
        }
        
        if (currentImageIndex === galleryImages.length - 1) {
            lightboxNext.style.display = 'none';
        } else {
            lightboxNext.style.display = 'flex';
        }
    }

    // Close lightbox
    lightboxClose.addEventListener('click', closeLightbox);

    // Close on background click
    lightboxModal.addEventListener('click', function(e) {
        if (e.target === lightboxModal || e.target === lightboxClose) {
            closeLightbox();
        }
    });

    // Navigation
    lightboxPrev.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (currentImageIndex > 0 && !isImageLoading) {
            openLightbox(currentImageIndex - 1);
        }
    });

    lightboxNext.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (currentImageIndex < galleryImages.length - 1 && !isImageLoading) {
            openLightbox(currentImageIndex + 1);
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (!lightboxModal.classList.contains('active')) return;
        
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            lightboxPrev.click();
        } else if (e.key === 'ArrowRight') {
            lightboxNext.click();
        }
    });

    function zoomIn() {
        if (currentZoom < maxZoom) {
            currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
            updateZoom();
        }
    }

    function zoomOut() {
        if (currentZoom > minZoom) {
            currentZoom = Math.max(currentZoom - zoomStep, minZoom);
            updateZoom();
        }
    }

    function updateZoom() {
        lightboxImage.style.transform = `scale(${currentZoom.toFixed(2)})`;
    }

    // Mouse wheel zoom only
    lightboxImageContainer.addEventListener('wheel', function(e) {
        if (lightboxModal.classList.contains('active')) {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        }
    }, { passive: false });

    // Touch support for mobile
    let touchStartX = 0;
    let touchStartY = 0;
    
    lightboxImageContainer.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    });

    lightboxImageContainer.addEventListener('touchend', function(e) {
        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;
        const diffX = touchStartX - touchEndX;
        const diffY = touchStartY - touchEndY;

        // Horizontal swipe
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0) {
                // Swiped left, show next image
                lightboxNext.click();
            } else {
                // Swiped right, show previous image
                lightboxPrev.click();
            }
        }
    });
});
