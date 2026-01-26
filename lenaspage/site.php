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

        $fallback = $data[DEFAULT_LANGUAGE] ?? '';
        if (is_string($fallback) && trim($fallback) !== '') {
            return $fallback;
        }
    }

    return '';
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
