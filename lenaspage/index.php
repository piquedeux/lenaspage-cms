<?php
require_once 'site.php';

$currentPage = getCurrentPage();
$settings = getSettings();

/**
 * Renders the project grid with stable IDs
 */
function render_project_grid($projects) {
    if (empty($projects)) {
        echo '<div class="no-projects"><p>No projects available yet.</p></div>';
        return;
    }
    
    // Wir indizieren die Projekte fest, bevor wir sie mischen
    $displayProjects = [];
    foreach ($projects as $originalId => $project) {
        $project['stable_id'] = $originalId;
        // Precompute slug so we can link to name-based URLs
        $project['slug'] = getProjectSlug($project, $originalId);
        $displayProjects[] = $project;
    }
    
    shuffle($displayProjects);
    ?>
    <div class="projects-grid">
        <?php foreach ($displayProjects as $project): ?>
            <article class="project-card" data-project-id="<?php echo $project['stable_id']; ?>">
                <a href="<?php echo SITE_URL; ?>/project/<?php echo e($project['slug']); ?>" class="project-link">
                    <?php if (!empty($project['image'])): ?>
                        <div class="project-image">
                            <img src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($project['image']); ?>" 
                                 alt="<?php echo e(getTranslation($project['title'], 'title')); ?>" 
                                 class="project-thumbnail" 
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="project-info">
                        <h2 class="project-title"><?php echo e(getTranslation($project['title'], 'title')); ?></h2>
                        <?php
                        $excerpt = '';
                        if (!empty($project['description'])) {
                            $fullDescription = getTranslation($project['description'], 'description');
                            $fullDescription = trim(strip_tags($fullDescription));
                            if ($fullDescription !== '') {
                                $parts = preg_split('/(?<=[.?!])\s+/', $fullDescription, 2);
                                $excerpt = $parts[0] ?? '';
                            }
                        }
                        if ($excerpt !== ''): ?>
                            <p class="project-excerpt"><?php echo e($excerpt); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($project['year'])): ?>
                            <p class="project-year"><?php echo e($project['year']); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

// --- ROUTING LOGIC ---

switch ($currentPage) {
    case 'home':
        // Always render the folder overlay on the home route;
        // visibility is controlled per-browser-session in JS
        $showIntro = true;

        $projects = getProjects();
        $peekProjects = [];
        if (is_array($projects)) {
            foreach ($projects as $project) {
                if (!empty($project['image'])) {
                    $peekProjects[] = $project;
                    if (count($peekProjects) === 3) {
                        break;
                    }
                }
            }
        }
        $metaTitle = $settings['site_name'];
        $metaDescription = $settings['site_description'];

        ob_start(); 
        if ($showIntro): ?>
            <div class="folder-overlay" id="folderOverlay">
                <div class="folder">
                    <div class="back-cover"></div>
                    <div class="front-cover">
                        <div class="folder-cover-image folder-cover-image--main"></div>
                        <div class="folder-cover-image folder-cover-image--secondary"></div>
                        <div class="folder-cover-image folder-cover-image--tertiary"></div>
                        <div class="folder-cover-label">
                            <span class="folder-cover-label-main">portfolio</span>
                            <span class="folder-cover-label-sub">lena rickenstorf</span>
                        </div>
                    </div>
                    <?php if (!empty($peekProjects)): ?>
                        <?php $peekIndex = 0; ?>
                        <?php foreach ($peekProjects as $peekProject): ?>
                            <?php
                                $peekIndex++;
                                $peekClass = 'folder-peek-image--' . $peekIndex;
                            ?>
                            <div class="folder-peek-image <?php echo $peekClass; ?>">
                                <img src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($peekProject['image']); ?>" alt="" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php render_project_grid($projects); ?>
        <?php
        $content = ob_get_clean();
        break;

    case 'about':
        $pageData = getPage('about') ?: (getPage('home') ?: ['title' => 'About', 'content' => '']);
        $metaTitle = 'About - ' . $settings['site_name'];
        $metaDescription = $settings['site_description'];
        ob_start();
        ?>
        <div class="folder-page folder-page--info">
        <div class="about-page-wrapper">
            <div class="about-left">
                <div class="about-content">
                    <?php echo nl2br(e(getTranslation($pageData['content'], 'content') ?? '')); ?>
                </div>
            </div>
            <div class="about-right">
                <?php if (!empty($settings['social_links'])): ?>
                    <div class="social-links">
                        <h3 class="social-links-label">Links</h3>
                        <ul class="social-links-list">
                            <?php foreach ($settings['social_links'] as $link): 
                                $domain = parse_url($link, PHP_URL_HOST);
                                $label = ucfirst(explode('.', str_replace('www.', '', $domain))[0]);
                            ?>
                                <li><a href="<?php echo e($link); ?>" target="_blank" class="social-link link-perforated"><?php echo e($label); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
        <?php
        $content = ob_get_clean();
        break;

    case 'timeline':
        $timeline = getTimeline();
        $metaTitle = 'Timeline - ' . $settings['site_name'];
        $metaDescription = $settings['site_description'];
        ob_start();
        ?>
        <div class="folder-page folder-page--info">
            <?php if (!empty($timeline)): ?>
                <div class="cv-section">
                    <div class="cv-timeline">
                        <?php foreach ($timeline as $entry): 
                            $dateStart = !empty($entry['date_start']) ? date('d.m.Y', strtotime($entry['date_start'])) : '';
                            $dateEnd = !empty($entry['date_end']) ? date('d.m.Y', strtotime($entry['date_end'])) : '';
                            $isOngoing = $entry['ongoing'] ?? false;
                            $ongoingLabel = getLanguage() === 'de' ? 'laufend' : 'ongoing';

                            $dateRange = '';
                            if ($dateStart && $dateEnd) {
                                $dateRange = $dateStart . '–' . $dateEnd;
                            } elseif ($dateStart && $isOngoing) {
                                $dateRange = $dateStart . '–' . $ongoingLabel;
                            } elseif ($dateEnd) {
                                $dateRange = $dateEnd;
                            } elseif ($dateStart) {
                                $dateRange = $dateStart;
                            } elseif ($isOngoing) {
                                $dateRange = $ongoingLabel;
                            }
                        ?>
                            <div class="cv-entry">
                                <div class="cv-entry-title"><?php echo e(getTranslation($entry['title'], 'title')); ?></div>
                                <?php if ($dateRange): ?>
                                    <div class="cv-entry-date"><?php echo e($dateRange); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p><?php echo getLanguage() === 'de' ? 'Noch keine Einträge in der Timeline.' : 'No timeline entries yet.'; ?></p>
            <?php endif; ?>
        </div>
        <?php
        $content = ob_get_clean();
        break;

    case 'imprint':
        $metaTitle = (getLanguage() === 'de' ? 'Impressum' : 'Imprint') . ' - ' . $settings['site_name'];
        ob_start();
        ?>
        <div class="folder-page folder-page--info">
            <div class="imprint-content"><?php echo $settings['imprint']; ?></div>
        </div>
        <?php
        $content = ob_get_clean();
        break;

    default:
        // Project Detail Routing
        if (strpos($currentPage, 'project/') === 0) {
            $projectKey = substr($currentPage, 8); // slug or numeric ID
            $projects = getProjects();

            $project = null;
            $projectId = null;

            // First try to match by slug based on project title
            foreach ($projects as $id => $p) {
                if (getProjectSlug($p, $id) === $projectKey) {
                    $project = $p;
                    $projectId = $id;
                    break;
                }
            }

            // Fallback: support old numeric ID URLs
            if ($project === null && ctype_digit($projectKey)) {
                $numericId = (int) $projectKey;
                if (isset($projects[$numericId])) {
                    $project = $projects[$numericId];
                    $projectId = $numericId;
                }
            }

            if ($project !== null) {
                $projectTitle = getTranslation($project['title'], 'title');
                $metaTitle = e($projectTitle) . ' - ' . $settings['site_name'];
                
                ob_start();
                ?>
                <div class="folder-page folder-page--project-detail">
                <div class="project-detail-page">
                    <a href="<?php echo SITE_URL; ?>/" class="back-link">← <?php echo getLanguage() === 'de' ? 'zurück' : 'back'; ?></a>

                    <div class="project-detail-header">
                        <h1><?php echo e($projectTitle); ?></h1>
                        <?php if (!empty($project['year'])): ?>
                            <p class="project-year"><?php echo e($project['year']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($project['image'])): ?>
                        <div class="project-detail-image">
                            <img src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($project['image']); ?>" alt="<?php echo e($projectTitle); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="project-detail-content">
                        <?php if (!empty($project['description'])): ?>
                            <div class="project-description"><?php echo getTranslation($project['description'], 'description'); ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($project['gallery'])): ?>
                        <div class="gallery-section">
                            <div class="gallery-controls">
                                <button class="gallery-toggle active" data-view="grid">grid</button>
                                <button class="gallery-toggle" data-view="list">list</button>
                            </div>
                            <div class="project-gallery gallery-grid">
                                <?php foreach ($project['gallery'] as $img): ?>
                                    <div class="project-gallery-item">
                                        <a href="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($img); ?>" class="lightbox-link">
                                            <img src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($img); ?>" data-full-src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($img); ?>" loading="lazy" class="gallery-image">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
                <?php
                $content = ob_get_clean();
            } else {
                header("Location: " . SITE_URL); exit;
            }
        } else {
            header("Location: " . SITE_URL); exit;
        }
        break;
}

snippet('layout', [
    'content' => $content,
    'currentPage' => $currentPage,
    'settings' => $settings,
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription ?? $settings['site_description']
]);
