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
    
    // Reihenfolge aus projects.json wird beibehalten (admin sortiert über ↑↓)
    ?>
    <div class="projects-grid">
        <?php foreach ($displayProjects as $project): ?>
            <?php
                // build flattened gallery image list for thumbnail cycling (backwards compatible)
                $flatGallery = [];
                if (!empty($project['galleries']) && is_array($project['galleries'])) {
                    foreach ($project['galleries'] as $g) {
                        if (!empty($g['images']) && is_array($g['images'])) {
                            foreach ($g['images'] as $img) {
                                $file = is_array($img) ? ($img['file'] ?? reset($img)) : $img;
                                $flatGallery[] = SITE_URL . '/assets/projects/' . $file;
                            }
                        }
                    }
                } elseif (!empty($project['gallery']) && is_array($project['gallery'])) {
                    foreach ($project['gallery'] as $img) {
                        $file = is_array($img) ? ($img['file'] ?? reset($img)) : $img;
                        $flatGallery[] = SITE_URL . '/assets/projects/' . $file;
                    }
                }
            ?>
            <article class="project-card" data-project-id="<?php echo $project['stable_id']; ?>" data-gallery='<?php echo json_encode($flatGallery); ?>'>
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
                                // Try to split by first sentence. If no clear sentence boundary exists,
                                // fall back to a short word-based excerpt to avoid showing the whole text.
                                $parts = preg_split('/(?<=[.?!])\s+/', $fullDescription, 2);
                                $first = $parts[0] ?? '';
                                if ($first === $fullDescription) {
                                    // fallback: first 20 words
                                    $words = preg_split('/\s+/', $fullDescription);
                                    if (count($words) > 20) {
                                        $excerpt = implode(' ', array_slice($words, 0, 20)) . '…';
                                    } else {
                                        $excerpt = $fullDescription;
                                    }
                                } else {
                                    $excerpt = $first;
                                }
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
        $showIntro = true;

        $projects = getProjects();
        $folderImages = [
            '42ba7c8e-aa2c-4e3c-b0e6-2d91fdb7ca4f.jpeg',
            'f08775fc-d349-4e54-8281-3026d635d0be.jpeg',
        ];
        $metaTitle = $settings['site_name'];
        $metaDescription = $settings['site_description'];

        // Render home content (project grid + optional intro)
        ob_start();
        if ($showIntro): ?>
            <div class="folder-overlay" id="folderOverlay">
                <div class="folder-overlay-inner">
                    <a class="folder-overlay-title" href="<?php echo SITE_URL; ?>/" data-overlay-home><?php echo strtoupper(e($settings['site_name'])); ?></a>

                    <?php if (!empty($folderImages)): ?>
                        <div class="folder-overlay-images">
                            <?php foreach ($folderImages as $folderImage): ?>
                                <?php if (!empty($folderImage)): ?>
                                    <img
                                        src="<?php echo SITE_URL; ?>/assets/folder/<?php echo e($folderImage); ?>"
                                        alt="<?php echo strtoupper(e($settings['site_name'])); ?>"
                                        loading="eager"
                                    >
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <nav class="folder-overlay-nav" aria-label="Intro navigation">
                        <a class="folder-overlay-link" href="<?php echo SITE_URL; ?>/about">info</a>
                        <a class="folder-overlay-link" href="<?php echo SITE_URL; ?>/contact"><?php echo getLanguage() === 'de' ? 'kontakt' : 'contact'; ?></a>
                    </nav>
                </div>
            </div>
        <?php endif;
        render_project_grid($projects);
        $content = ob_get_clean();
        break;

    case 'about':
        $pageData = getPage('about') ?: (getPage('home') ?: ['title' => 'About', 'content' => '']);
        $timeline = getTimeline();
        $metaTitle = 'About - ' . $settings['site_name'];
        $metaDescription = $settings['site_description'];
        ob_start();
        ?>
            <div class="about-left">
                    <?php echo nl2br(e(getTranslation($pageData['content'], 'content') ?? '')); ?>
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
        <?php
        $content = ob_get_clean();
        break;

    case 'contact':
        $pageData = getPage('contact') ?: ['title' => 'Contact', 'content' => ''];
        $metaTitle = (getLanguage() === 'de' ? 'Kontakt' : 'Contact') . ' - ' . $settings['site_name'];
        $metaDescription = $settings['site_description'];

        $contactSuccess = '';
        $contactError = '';
        // Basic sanitization to guard against header injection
        $rawName = $_POST['name'] ?? '';
        $rawEmail = $_POST['email'] ?? '';
        $name = trim(str_replace(["\r", "\n"], ' ', $rawName));
        $email = trim(str_replace(["\r", "\n"], ' ', $rawEmail));
        $message = trim($_POST['message'] ?? '');
        $honeypot = trim($_POST['website'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $logFile = CONTENT_DIR . '/contact-log.txt';

            // Honeypot: if filled, assume bot and pretend success
            if ($honeypot !== '') {
                $contactSuccess = getLanguage() === 'de'
                    ? 'Danke, deine Nachricht wurde verschickt.'
                    : 'Thank you, your message has been sent.';

                $logLine = date('c') . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . ' | ' .
                    'BOT honeypot | ' . $name . ' <' . $email . "\n";
                @file_put_contents($logFile, $logLine, FILE_APPEND);
            } else if ($name === '' || $email === '' || $message === '') {
                $contactError = getLanguage() === 'de'
                    ? 'Bitte alle erforderlichen Felder ausfüllen.'
                    : 'Please fill in all required fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contactError = getLanguage() === 'de'
                    ? 'Bitte eine gültige E-Mail-Adresse eingeben.'
                    : 'Please enter a valid email address.';
            } else {
                // Nur ins Log schreiben, keine Mail versenden
                $contactSuccess = getLanguage() === 'de'
                    ? 'Danke, deine Nachricht wurde verschickt.'
                    : 'Thank you, your message has been sent.';

                $logLine = date('c') . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . ' | ' .
                    $name . ' <' . $email . '> | status=logged | ' .
                    str_replace(["\r", "\n"], ' ', substr($message, 0, 500)) . "\n";
                @file_put_contents($logFile, $logLine, FILE_APPEND);
                // Clear form fields on success
                $name = '';
                $email = '';
                $message = '';
            }
        }

        ob_start();
        ?>
                <div class="contact-intro">
                    <h1 class="contact-title"><?php echo e(getTranslation($pageData['title'], 'title') ?: (getLanguage() === 'de' ? 'Kontakt' : 'Contact')); ?></h1>
                    <div class="contact-text">
                        <?php echo nl2br(e(getTranslation($pageData['content'], 'content') ?? '')); ?>
                    </div>
                </div>
                <div class="contact-form-wrapper">
                    <?php if ($contactSuccess): ?>
                        <div class="contact-message contact-message--success"><?php echo e($contactSuccess); ?></div>
                    <?php endif; ?>
                    <?php if ($contactError): ?>
                        <div class="contact-message contact-message--error"><?php echo e($contactError); ?></div>
                    <?php endif; ?>

                    <form class="contact-form" method="post" action="<?php echo SITE_URL; ?>/contact">
                        <div class="contact-hp">
                            <label for="contact-website">Website</label>
                            <input
                                type="text"
                                id="contact-website"
                                name="website"
                                autocomplete="off"
                                tabindex="-1"
                            >
                        </div>
                        <div class="form-field">
                            <label for="contact-name"><?php echo getLanguage() === 'de' ? 'Name' : 'Name'; ?></label>
                            <input
                                type="text"
                                id="contact-name"
                                name="name"
                                required
                                value="<?php echo e($name); ?>"
                                autocomplete="name"
                            >
                        </div>

                        <div class="form-field">
                            <label for="contact-email"><?php echo getLanguage() === 'de' ? 'E-Mail' : 'Email'; ?></label>
                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                required
                                value="<?php echo e($email); ?>"
                                autocomplete="email"
                            >
                        </div>

                        <div class="form-field">
                            <label for="contact-message"><?php echo getLanguage() === 'de' ? 'Nachricht' : 'Message'; ?></label>
                            <textarea
                                id="contact-message"
                                name="message"
                                rows="5"
                                required
                            ><?php echo e($message); ?></textarea>
                        </div>

                        <button type="submit" class="contact-submit">
                            <?php echo getLanguage() === 'de' ? 'Nachricht senden' : 'Send message'; ?>
                        </button>
                        </form>

                    <?php // removed mobile imprint link per request ?>

                    </div>
        <?php
        $content = ob_get_clean();
        break;

    case 'timeline':
        // Keep old /timeline URLs working, but show content on the info page
        header('Location: ' . SITE_URL . '/about');
        exit;

    case 'imprint':
        $metaTitle = (getLanguage() === 'de' ? 'Impressum' : 'Imprint') . ' - ' . $settings['site_name'];
        ob_start();
        ?>
            <?php echo $settings['imprint']; ?>

        <?php
        $content = ob_get_clean();
        break;

    default:
        // Project Detail Routing
        $projectKey = null;
        if (strpos($currentPage, 'project/') === 0) {
            $projectKey = substr($currentPage, 8); // slug or numeric ID
        } elseif ($currentPage === 'projects') {
            header('Location: ' . SITE_URL . '/');
            exit;
        } elseif (strpos($currentPage, 'projects/') === 0) {
            // support legacy plural routes such as /projects/slug or /projects/project/slug
            $projectKey = substr($currentPage, 9);
            if (strpos($projectKey, 'project/') === 0) {
                $projectKey = substr($projectKey, 8);
            }
        }

        if ($projectKey !== null && $projectKey !== '') {
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

                        <div class="project-detail-header">
                            <?php if (!empty($project['year'])): ?>
                                <p class="project-year"><?php echo e($project['year']); ?></p>
                            <?php endif; ?>
                        </div>

                    <?php if (!empty($project['image'])): ?>
                        <div class="project-detail-image">
                            <img src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($project['image']); ?>" alt="<?php echo e($projectTitle); ?>">
                        </div>
                    <?php endif; ?>

                        <?php if (!empty($project['description'])): ?>
                            <div class="project-description"><?php echo getTranslation($project['description'], 'description'); ?></div>
                        <?php endif; ?>

                        <?php
                            // Render credits as plain text (line breaks) immediately below description
                            $lang = getLanguage();
                            $credits_lines = [];
                            // helper to extract credits for a given language into $credits_lines
                            $extractFor = function($lng) use (&$credits_lines, $project) {
                                // credit_groups (legacy)
                                if (!empty($project['credit_groups']) && is_array($project['credit_groups'])) {
                                    $items = $project['credit_groups'][0]['items'] ?? [];
                                    foreach ($items as $cred) {
                                        $name = is_array($cred['name'] ?? null) ? ($cred['name'][$lng] ?? reset($cred['name'])) : ($cred['name'] ?? '');
                                        if (trim($name) !== '') $credits_lines[] = $name;
                                    }
                                    return;
                                }
                                // new per-language credits
                                if (!empty($project['credits']) && is_array($project['credits']) && array_key_exists($lng, $project['credits'])) {
                                    $list = $project['credits'][$lng] ?? [];
                                    if (is_array($list)) {
                                        foreach ($list as $cred) {
                                            if (is_string($cred) && trim($cred) !== '') $credits_lines[] = $cred;
                                            elseif (is_array($cred)) {
                                                $name = is_array($cred['name'] ?? null) ? ($cred['name'][$lng] ?? reset($cred['name'])) : ($cred['name'] ?? '');
                                                if (trim($name) !== '') $credits_lines[] = $name;
                                            }
                                        }
                                    }
                                    return;
                                }
                                // fallback flat credits array
                                if (!empty($project['credits']) && is_array($project['credits'])) {
                                    foreach ($project['credits'] as $cred) {
                                        if (is_string($cred) && trim($cred) !== '') $credits_lines[] = $cred;
                                        else {
                                            $name = is_array($cred['name'] ?? null) ? ($cred['name'][$lng] ?? reset($cred['name'])) : ($cred['name'] ?? '');
                                            if (trim($name) !== '') $credits_lines[] = $name;
                                        }
                                    }
                                }
                            };

                            // try current language first
                            $extractFor($lang);
                            // if nothing found, try the other language to avoid empty space
                            if (empty($credits_lines)) {
                                $other = $lang === 'de' ? 'en' : 'de';
                                $extractFor($other);
                            }

                            if (!empty($credits_lines)) {
                                echo '<div class="project-credits-frontend"><div class="credits-text">' . nl2br(e(implode("\n", $credits_lines))) . '</div></div>';
                            }
                        ?>

                    <?php
                        // render multiple galleries if present (backwards compatible)
                        $garr = [];
                        if (!empty($project['galleries']) && is_array($project['galleries'])) $garr = $project['galleries'];
                        elseif (!empty($project['gallery']) && is_array($project['gallery'])) $garr = [['title' => '', 'images' => $project['gallery']]];
                    ?>
                    <?php if (!empty($garr)): ?>
                        <?php foreach ($garr as $gal): ?>
                            <?php $galleryImages = is_array($gal['images'] ?? null) ? $gal['images'] : []; ?>
                            <?php if (empty($galleryImages)) continue; ?>
                            <div class="gallery-section">
                                <?php $gtitle = getTranslation($gal['title'] ?? '', 'title'); if (!empty($gtitle)): ?>
                                    <p class="gallery-title"><?php echo e($gtitle); ?></p>
                                <?php endif; ?>
                                <?php $gtype = $gal['type'] ?? 'regular'; $gclass = $gtype === 'scrollable' ? 'gallery-scroll' : 'gallery-grid'; ?>
                                <div class="project-gallery <?php echo $gclass; ?>">
                                    <?php foreach ($galleryImages as $img):
                                        $imgFile = is_array($img) ? ($img['file'] ?? reset($img)) : $img;
                                        $caption = is_array($img) && !empty($img['caption']) ? $img['caption'] : null;
                                        $captionText = $caption ? getTranslation($caption, 'caption') : '';
                                    ?>
                                        <div class="project-gallery-item">
                                            <a href="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($imgFile); ?>" class="lightbox-link" <?php if(!empty($captionText)): ?> data-caption="<?php echo e($captionText); ?>" <?php endif; ?> >
                                                <img src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($imgFile); ?>" data-full-src="<?php echo SITE_URL; ?>/assets/projects/<?php echo e($imgFile); ?>" loading="lazy" class="gallery-image">
                                            </a>
                                            <?php if (!empty($captionText)): ?>
                                                <p class="gallery-caption"><?php echo nl2br(e($captionText)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
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
    'metaDescription' => $metaDescription ?? $settings['site_description'],
    'projectTitle' => ($projectTitle ?? '')
]);
