<?php
// Render minimal footer variations according to current page
$isProjectDetail = (!empty($currentPage) && strpos($currentPage, 'project/') === 0);
$isHome = (empty($currentPage) || $currentPage === 'home');
$isInfoOrContact = in_array($currentPage, ['about', 'contact', 'imprint'], true);

// Only show footer on: home, about, contact, imprint and project detail pages
if ($isProjectDetail) :
    // Project detail: show a left-aligned back button
    $backLabel = getLanguage() === 'de' ? 'zurück' : 'back';
?>
<footer class="site-footer site-footer--project">
  <div class="footer-left">
    <a class="footer-back" href="javascript:window.history.back();" aria-label="<?php echo e($backLabel); ?>"><?php echo e($backLabel); ?></a>
  </div>
</footer>
<?php
elseif ($isHome || $isInfoOrContact) :
    // Home / Info / Contact: show left-aligned imprint link (home shows only link without title)
    $imprintLabel = getLanguage() === 'de' ? 'impressum' : 'imprint';
?>
<footer class="site-footer">
  <div class="footer-left">
    <a class="footer-imprint" href="<?php echo SITE_URL; ?>/imprint"><?php echo e($imprintLabel); ?></a>
  </div>
</footer>
<?php
else :
    // For all other sub pages: do not render footer
endif;
?>
