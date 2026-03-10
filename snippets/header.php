<link rel="stylesheet" href="<?php echo e(asset_url('assets/css/folder-overlay.css')); ?>">

<header class="header-container">
  <div class="logo logo-fixed">
    <a class="site-logo" href="<?php echo SITE_URL; ?>/"><?php echo strtoupper(e($settings['site_name'])); ?></a>
    <?php if (!empty($projectTitle) && strpos($currentPage, 'project/') === 0): ?>
      <span class="project-title-header"><?php echo e($projectTitle); ?></span>
    <?php endif; ?>
  </div>
</header>

<nav class="right-fixed">
    <a href="<?php echo SITE_URL; ?>/about" class="<?php echo $currentPage === 'about' ? 'active' : ''; ?>">info</a>
    <a href="<?php echo SITE_URL; ?>/contact" class="<?php echo $currentPage === 'contact' ? 'active' : ''; ?>"><?php echo getLanguage() === 'de' ? 'kontakt' : 'contact'; ?></a>
    <div class="language-switcher">
      <?php 
        $currentUri = $_SERVER['REQUEST_URI'];
        $separator = (strpos($currentUri, '?') !== false) ? '&' : '?';
        $deLink = (strpos($currentUri, 'lang=') !== false) ? 
                  preg_replace('/lang=[a-z]+/', 'lang=de', $currentUri) : 
                  $currentUri . $separator . 'lang=de';
        $enLink = (strpos($currentUri, 'lang=') !== false) ? 
                  preg_replace('/lang=[a-z]+/', 'lang=en', $currentUri) : 
                  $currentUri . $separator . 'lang=en';
      ?>
      <a href="<?php echo $deLink; ?>" class="lang-link <?php echo getLanguage() === 'de' ? 'active' : ''; ?>" title="Deutsch">de</a>
      <span class="lang-divider">/</span>
      <a href="<?php echo $enLink; ?>" class="lang-link <?php echo getLanguage() === 'en' ? 'active' : ''; ?>" title="English">en</a>
    </div>
  </nav>
 

<script src="<?php echo e(asset_url('assets/js/folder-overlay.js')); ?>"></script>
