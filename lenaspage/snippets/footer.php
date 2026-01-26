<?php
// Derive human-readable page label with language support
$pageLabel = getLanguage() === 'de' ? 'startseite' : 'home';
if (!empty($currentPage)) {
    if (strpos($currentPage, 'project/') === 0) {
        $pageLabel = getLanguage() === 'de' ? 'projekt' : 'project';
    } elseif ($currentPage === 'projects') {
        $pageLabel = getLanguage() === 'de' ? 'projekte' : 'projects';
    } elseif ($currentPage === 'about') {
        $pageLabel = 'info';
    } elseif ($currentPage === 'imprint') {
        $pageLabel = getLanguage() === 'de' ? 'impressum' : 'imprint';
    } else {
        $pageLabel = $currentPage;
    }
}
?>
<footer class="site-footer">
  <div class="footer-left">
    <a href="<?php echo SITE_URL; ?>/imprint"><?php echo getLanguage() === 'de' ? 'impressum' : 'imprint'; ?></a>
  </div>

  <div class="footer-center"><?php echo e($pageLabel); ?></div>

  <div class="footer-right">
    <a class="email-link" href="mailto:<?php echo e($settings['email']); ?>"><?php echo e($settings['email']); ?></a>
  </div>
</footer>
