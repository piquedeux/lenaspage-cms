<?php
// Site Configuration
define('SITE_NAME', 'lena rickenstorf');
// Detect base path from script location - works in subdirectory or root
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptName);
if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
    define('SITE_URL', '');
} else {
    define('SITE_URL', rtrim($basePath, '/\\'));
}
define('CONTENT_DIR', __DIR__ . '/content');
define('ASSETS_DIR', __DIR__ . '/assets');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language handling
define('DEFAULT_LANGUAGE', 'de');

// Detect browser language on first visit
if (!isset($_SESSION['language'])) {
    // Check URL parameter first
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
        $_SESSION['language'] = $_GET['lang'];
    } else {
        // Try to detect from browser
        $browserLang = DEFAULT_LANGUAGE;
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            // Parse Accept-Language header
            if (stripos($acceptLanguage, 'en') !== false) {
                $browserLang = 'en';
            } elseif (stripos($acceptLanguage, 'de') !== false) {
                $browserLang = 'de';
            }
        }
        $_SESSION['language'] = $browserLang;
    }
} elseif (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
    // Allow URL parameter to override session
    $_SESSION['language'] = $_GET['lang'];
}

$currentLanguage = $_SESSION['language'];

// Helper Functions
function getLanguage() {
    return $_SESSION['language'] ?? DEFAULT_LANGUAGE;
}

function switchLanguage($language) {
    if (in_array($language, ['de', 'en'])) {
        $_SESSION['language'] = $language;
    }
}
function getSettings() {
    $file = CONTENT_DIR . '/settings.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [
        'site_name' => SITE_NAME,
        'site_description' => '',
        'email' => '',
        'imprint' => ''
    ];
}

function getTranslation($data, $field, $lang = null) {
    if ($lang === null) $lang = getLanguage();

    // If data is a string, return it (backward compatibility)
    if (is_string($data)) return $data;

    if (is_array($data)) {
        $current = $data[$lang] ?? '';
        if (is_string($current) && trim($current) !== '') {
            return $current;
        }

        // first try the configured default language as before
        $fallback = $data[DEFAULT_LANGUAGE] ?? '';
        if (is_string($fallback) && trim($fallback) !== '') {
            return $fallback;
        }

        // otherwise try any other language that contains content (prevent empty slots)
        $langs = ['de','en'];
        foreach ($langs as $alt) {
            if ($alt === $lang) continue;
            $val = $data[$alt] ?? '';
            if (is_string($val) && trim($val) !== '') return $val;
        }
    }

    return '';
}

/**
 * Sanitize HTML for project descriptions: allow only <a> tags with safe hrefs (http(s), mailto, or relative).
 */
function sanitize_html_allow_links($html) {
    if (!is_string($html) || trim($html) === '') return '';
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // load as fragment
    $htmlWrapped = '<div>' . $html . '</div>';
    // Avoid deprecated mb_convert_encoding for HTML entities. Prepend XML encoding
    // declaration so DOMDocument parses UTF-8 correctly.
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlWrapped);
    $container = $doc->getElementsByTagName('div')->item(0);

    // Remove all tags except <a>
    $allowed = ['a'];
    $nodes = [];
    foreach ($container->childNodes as $n) $nodes[] = $n;
    // recursive cleanup: walk and remove disallowed elements but keep text
    $xpath = new DOMXPath($doc);
    foreach ($xpath->query('//*') as $node) {
        if (!in_array($node->nodeName, $allowed)) {
            // unwrap: replace node with its children
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
        }
    }

    // sanitize anchors and normalize visible text for common cases
    foreach ($container->getElementsByTagName('a') as $a) {
        $href = $a->getAttribute('href');
        $href = trim($href);
        // allow only http(s), mailto or relative paths
        if (preg_match('#^(https?://|mailto:|/)#i', $href)) {
            // Build a human-friendly display text for common link types
            $display = $a->textContent;
            if (preg_match('#^https?://#i', $href)) {
                $parts = parse_url($href);
                $host = $parts['host'] ?? '';
                // remove www prefix for display
                $host = preg_replace('/^www\./i', '', $host);
                $path = $parts['path'] ?? '/';
                $path = trim($path, '/');
                $firstSeg = '';
                if ($path !== '') {
                    $segs = explode('/', $path);
                    $firstSeg = $segs[0];
                }
                $display = $host . ($firstSeg ? '/' . $firstSeg : '');
            } elseif (stripos($href, 'mailto:') === 0) {
                $email = preg_replace('/^mailto:/i', '', $href);
                $display = $email;
            } elseif (strpos($href, '/') === 0) {
                // relative URL: show trimmed path
                $display = ltrim($href, '/');
            }

            // normalize attributes: remove all, then set href and safe attributes
            while ($a->attributes->length) $a->removeAttributeNode($a->attributes->item(0));
            $a->setAttribute('href', $href);
            if (preg_match('#^https?://#i', $href)) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener noreferrer');
            }
            // replace anchor content with sanitized display text
            while ($a->firstChild) $a->removeChild($a->firstChild);
            $a->appendChild($doc->createTextNode($display));
        } else {
            // not allowed: replace anchor with its text content
            $text = $doc->createTextNode($a->textContent);
            $a->parentNode->replaceChild($text, $a);
        }
    }

    // get innerHTML of container
    $out = '';
    foreach ($container->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    libxml_clear_errors();
    return $out;
}

function getPage($slug) {
    $file = CONTENT_DIR . '/pages/' . $slug . '.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function getProjects() {
    $file = CONTENT_DIR . '/projects.json';
    if (file_exists($file)) {
        $projects = json_decode(file_get_contents($file), true);
        return is_array($projects) ? $projects : [];
    }
    return [];
}

function savePage($slug, $data) {
    $file = CONTENT_DIR . '/pages/' . $slug . '.json';
    if (!is_dir(CONTENT_DIR . '/pages')) {
        mkdir(CONTENT_DIR . '/pages', 0755, true);
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function saveProjects($projects) {
    $file = CONTENT_DIR . '/projects.json';
    return file_put_contents($file, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function saveSettings($settings) {
    $file = CONTENT_DIR . '/settings.json';
    return file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getTimeline() {
    $timelineFile = CONTENT_DIR . '/timeline.json';
    $legacyFile = CONTENT_DIR . '/cv.json';

    if (file_exists($timelineFile)) {
        $timeline = json_decode(file_get_contents($timelineFile), true);
        return is_array($timeline) ? $timeline : [];
    }

    if (file_exists($legacyFile)) {
        $legacy = json_decode(file_get_contents($legacyFile), true);
        return is_array($legacy) ? $legacy : [];
    }

    return [];
}

function saveTimeline($timeline) {
    $file = CONTENT_DIR . '/timeline.json';
    return file_put_contents($file, json_encode($timeline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Backwards compatibility
function getCV() {
    return getTimeline();
}

function saveCV($timeline) {
    return saveTimeline($timeline);
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin.php');
        exit;
    }
}

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function asset_url($relativePath) {
    $relativePath = ltrim((string)$relativePath, '/');
    $fullPath = __DIR__ . '/' . $relativePath;
    $url = SITE_URL . '/' . $relativePath;

    if (file_exists($fullPath)) {
        $version = filemtime($fullPath);
        return $url . '?v=' . $version;
    }

    return $url;
}

function getCurrentPage() {
    $uri = $_SERVER['REQUEST_URI'];
    $uri = str_replace(SITE_URL, '', $uri);
    $uri = trim($uri, '/');
    $uri = explode('?', $uri)[0];
    
    // Remove index.php if present
    $uri = str_replace('index.php', '', $uri);
    $uri = trim($uri, '/');
    
    if (empty($uri)) {
        return 'home';
    }
    
    return $uri;
}

function snippet($name, $data = []) {
    extract($data);
    $file = __DIR__ . '/snippets/' . $name . '.php';
    if (file_exists($file)) {
        include $file;
    }
}

/**
 * Generate a URL-friendly slug for a project based on its title.
 * Falls back to the numeric ID if no suitable title is available.
 */
function getProjectSlug(array $project, $id) {
    // Explicit slug field in JSON wins if present
    if (!empty($project['slug']) && is_string($project['slug'])) {
        return $project['slug'];
    }

    // Prefer the default language title so slugs stay stable
    $title = '';
    if (!empty($project['title'])) {
        $title = getTranslation($project['title'], 'title', DEFAULT_LANGUAGE);
    }

    if (!is_string($title) || trim($title) === '') {
        return 'project-' . $id;
    }

    $slug = mb_strtolower($title, 'UTF-8');
    // Replace non letters/digits by hyphens
    $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
    // Trim hyphens
    $slug = trim($slug, '-');
    // Remove unwanted characters
    $slug = preg_replace('~[^-a-z0-9]+~', '', $slug);

    if ($slug === '' || $slug === false) {
        $slug = 'project-' . $id;
    }

    return $slug;
}
