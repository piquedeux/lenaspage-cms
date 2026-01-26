<!--
 __  __       ____  
|  \/  |     / ___| 
| \  / |    | | __  
| |\/| |    | |(  | 
| |  | |  _ | |_) |  _ 
(_)  (_) (_) \____| (_)
© 2025
moritzgauss.com 
--> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($metaTitle); ?></title>
    <meta name="description" content="<?php echo strip_tags($metaDescription); ?>">
    <?php $favicon = ($GLOBALS['settings']['favicon'] ?? ''); if ($favicon): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/favicon/<?php echo e($favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/folder-overlay.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    // Favicon and touch icon
    if (!empty($settings['favicon'])) {
        $faviconPath = $settings['favicon'];
        // Remove leading slash if present
        if ($faviconPath[0] === '/') {
            $faviconPath = ltrim($faviconPath, '/');
        }
        // If path is already relative, use as is; if absolute, prepend SITE_URL
        $faviconUrl = (strpos($faviconPath, 'http') === 0) ? $faviconPath : (SITE_URL ? SITE_URL . '/' : '') . $faviconPath;
        echo '<link rel="icon" type="image/png" href="' . e($faviconUrl) . '" sizes="32x32">';
        echo '<link rel="apple-touch-icon" href="' . e($faviconUrl) . '" sizes="180x180">';
    }
    ?>
</head>
<body class="<?php echo ($currentPage === 'home') ? 'is-home' : ''; ?>">
    <div class="folder-shell">
        <div class="folder-shell-scroll">
            <?php snippet('header', ['currentPage' => $currentPage, 'settings' => $settings]); ?>
            
            <main>
                <?php echo $content; ?>
            </main>
            
            <?php snippet('footer', ['settings' => $settings, 'currentPage' => $currentPage]); ?>
            
            <?php snippet('cookie-banner'); ?>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightboxModal" class="lightbox-modal">
        <div class="lightbox-content">
            <div class="lightbox-image-container">
                <img id="lightboxImage" class="lightbox-image" alt="">
            </div>
            <button class="lightbox-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <button class="lightbox-nav lightbox-prev">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button class="lightbox-nav lightbox-next">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/project-gallery.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/project-thumbnail-gallery.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/folder-overlay.js"></script>
</body>
</html>
