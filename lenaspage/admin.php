<?php
require_once 'site.php';

$settings = getSettings();
$error = '';
$success = '';

/* ----------------------------------------
   UPLOAD VALIDATION CONSTANTS
---------------------------------------- */
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);      // 10MB for images
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024);     // 500MB for videos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

function validateUpload($file, $isVideo = false) {
    $maxSize = $isVideo ? MAX_VIDEO_SIZE : MAX_IMAGE_SIZE;
    $allowedTypes = $isVideo ? ALLOWED_VIDEO_TYPES : ALLOWED_IMAGE_TYPES;
    
    if ($file['size'] > $maxSize) {
        $maxMB = $maxSize / 1024 / 1024;
        return "Datei zu groß (max. {$maxMB}MB)";
    }
    if (!in_array($file['type'], $allowedTypes)) {
        return 'Dateiformat nicht erlaubt';
    }
    return null;
}

/* ----------------------------------------
   LOGIN HANDLING
---------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $adminFile = CONTENT_DIR . '/admin.json';
    if (file_exists($adminFile)) {
        $admin = json_decode(file_get_contents($adminFile), true);
        if ($username === ($admin['username'] ?? '') && password_verify($password, $admin['password'] ?? '')) {
            $_SESSION['logged_in'] = true;
            header('Location: ' . SITE_URL . '/admin.php?page=projects');
            exit;
        }
    }
    $error = 'Benutzername oder Passwort ist falsch';
}

/* ----------------------------------------
   LOGOUT
---------------------------------------- */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . SITE_URL . '/admin.php');
    exit;
}

/* ----------------------------------------
   REQUIRE LOGIN
---------------------------------------- */
if (!isLoggedIn()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
    body {background:#f5f5f5;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;font-family:sans-serif;}
    .box {background:white;padding:40px;border-radius:8px;width:320px;box-shadow:0 2px 10px rgba(0,0,0,0.1);max-width:95vw;box-sizing:border-box;}
    input, button {width:100%;padding:12px;margin-top:10px;font-size:1rem;box-sizing:border-box;}
    @media (max-width:500px){body{padding:8px;height:auto;} .box{padding:18px 8px;width:100%;max-width:100vw;border-radius:6px;font-size:1em;} input,button{padding:10px;font-size:1em;} h2{font-size:1.3em;}}
    .error{color:#b00020;margin-top:8px;}
    </style>
    </head>
    <body>
    <div class="box">
        <h2>Admin Login</h2>
        <p class="microcopy">Bitte melde dich an, um Inhalte zu verwalten.</p>
        <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Benutzername" required autofocus>
            <input type="password" name="password" placeholder="Passwort" required>
            <button name="login">Anmelden</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

/* ----------------------------------------
   MAIN PAGE LOGIC
---------------------------------------- */
$page = $_GET['page'] ?? 'projects';
$projects = getProjects();

/* ----------------------------------------
   FORM PROCESSING
---------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* DELETE PROJECT */
    if (isset($_POST['delete_project'])) {
        $del = (int)$_POST['delete_project'];
        $projects = getProjects();
        array_splice($projects, $del, 1);
        saveProjects($projects);
        header('Location: ' . SITE_URL . '?page=projects');
        exit;
    }

    /* SAVE SETTINGS */
    if (isset($_POST['save_settings']) && isset($_POST['settings'])) {
        $settingsFile = CONTENT_DIR . '/settings.json';
        $settings = json_decode(file_get_contents($settingsFile), true);
        foreach ($_POST['settings'] as $key => $val) {
            $settings[$key] = $val;
        }
        if (isset($_POST['social_links'])) {
            $settings['social_links'] = array_filter(array_map('trim', explode(',', $_POST['social_links'])));
        }
        
        /* Handle favicon upload */
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['favicon'];
            $allowed = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];
            if (in_array($file['type'], $allowed) && $file['size'] <= 100000) {
                $uploadDir = dirname(__DIR__) . '/assets/favicon/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'favicon.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
                $settings['favicon'] = 'favicon.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            }
        }
        
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = 'Einstellungen gespeichert.';
    }

    /* SAVE ABOUT PAGE */
    if (isset($_POST['save_page']) && ($_POST['slug'] ?? '') === 'about') {
        $data = [
            'title' => [
                'de' => $_POST['title_de'] ?? 'About',
                'en' => $_POST['title_en'] ?? 'About'
            ],
            'content' => [
                'de' => strip_tags($_POST['content_de'] ?? ''),
                'en' => strip_tags($_POST['content_en'] ?? '')
            ]
        ];
        if (savePage('about', $data)) {
            $success = 'About gespeichert.';
        }
    }

    /* SAVE CONTACT PAGE */
    if (isset($_POST['save_page']) && ($_POST['slug'] ?? '') === 'contact') {
        $data = [
            'title' => [
                'de' => $_POST['title_de'] ?? 'Kontakt',
                'en' => $_POST['title_en'] ?? 'Contact'
            ],
            'content' => [
                'de' => trim($_POST['content_de'] ?? ''),
                'en' => trim($_POST['content_en'] ?? '')
            ]
        ];
        if (savePage('contact', $data)) {
            $success = 'Contact gespeichert.';
        }
    }

    /* SAVE TIMELINE */
    if (isset($_POST['save_timeline'])) {
        $timeline = [];
        if (!empty($_POST['timeline'])) {
            foreach ($_POST['timeline'] as $entry) {
                $title_de = trim($entry['title_de'] ?? '');
                $title_en = trim($entry['title_en'] ?? '');
                if ($title_de === '' && $title_en === '') continue;

                $timeline[] = [
                    'title' => [
                        'de' => $title_de,
                        'en' => $title_en
                    ],
                    'date_start' => trim($entry['date_start'] ?? ''),
                    'date_end' => trim($entry['date_end'] ?? ''),
                    'ongoing' => isset($entry['ongoing']) && $entry['ongoing'] === 'yes'
                ];
            }
        }
        saveTimeline($timeline);
        $success = 'Timeline gespeichert.';
    }

    /* ADD TIMELINE ENTRY */
    if (isset($_POST['add_timeline'])) {
        $timeline = getTimeline();
        array_unshift($timeline, [
            'title' => ['de' => '', 'en' => ''],
            'date_start' => '',
            'date_end' => '',
            'ongoing' => false
        ]);
        saveTimeline($timeline);
        header('Location: ' . SITE_URL . '/admin.php?page=about');
        exit;
    }

    /* DELETE TIMELINE ENTRY */
    if (isset($_POST['delete_timeline'])) {
        $del = (int)$_POST['delete_timeline'];
        $timeline = getTimeline();
        array_splice($timeline, $del, 1);
        saveTimeline($timeline);
        header('Location: ' . SITE_URL . '/admin.php?page=about');
        exit;
    }

    /* ADD PROJECT */
    if (isset($_POST['add_project'])) {
        $projects = getProjects();
        array_unshift($projects, [
            'title' => ['de' => '', 'en' => ''],
            'client' => ['de' => '', 'en' => ''],
            'description' => ['de' => '', 'en' => ''],
            'year' => '',
            'image' => '',
            'gallery' => []
        ]);
        saveProjects($projects);
        header('Location: ' . SITE_URL . '/admin.php?page=projects');
        exit;
    }

    /* SAVE PROJECTS */
    if (isset($_POST['save_projects'])) {
        $projects = [];
        if (!empty($_POST['projects'])) {
            foreach ($_POST['projects'] as $index => $p) {
                $title_de = trim($p['title_de'] ?? '');
                $title_en = trim($p['title_en'] ?? '');
                if ($title_de === '' && $title_en === '') continue;

                $image = $p['image'] ?? '';
                if (isset($_FILES['project_thumbnails']['error'][$index])) {
                    $err = $_FILES['project_thumbnails']['error'][$index];
                    if ($err === UPLOAD_ERR_OK) {
                        $file = $_FILES['project_thumbnails'];
                        $uploadErr = validateUpload([
                            'size' => $file['size'][$index],
                            'type' => $file['type'][$index]
                        ], false);
                        if ($uploadErr) {
                            $error = "Thumbnail Fehler: $uploadErr";
                        } else {
                            $uploadDir = ASSETS_DIR . '/projects/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            $filename = time() . '_' . pathinfo($file['name'][$index], PATHINFO_FILENAME) . '.' . pathinfo($file['name'][$index], PATHINFO_EXTENSION);
                            if (move_uploaded_file($file['tmp_name'][$index], $uploadDir . $filename)) {
                                $image = $filename;
                            }
                        }
                    } elseif ($err !== UPLOAD_ERR_NO_FILE) {
                        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                            $error = 'Datei zu groß - siehe Nginx/PHP-Konfiguration';
                        } else {
                            $error = "Upload-Fehler: $err";
                        }
                    }
                }

                $gallery = [];
                if (!empty($p['gallery_existing'])) {
                    if (is_array($p['gallery_existing'])) {
                        foreach ($p['gallery_existing'] as $existingFile) {
                            $existingFile = trim($existingFile);
                            if ($existingFile !== '') {
                                $gallery[] = $existingFile;
                            }
                        }
                    } else {
                        $gallery = array_filter(array_map('trim', explode(',', $p['gallery_existing'])));
                    }
                }
                if (isset($_FILES['project_gallery']['name'][$index])) {
                    $uploadDir = ASSETS_DIR . '/projects/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    foreach ($_FILES['project_gallery']['name'][$index] as $gIdx => $gName) {
                        if ($_FILES['project_gallery']['error'][$index][$gIdx] === UPLOAD_ERR_OK) {
                            $isVideo = in_array($_FILES['project_gallery']['type'][$index][$gIdx], ALLOWED_VIDEO_TYPES);
                            $uploadErr = validateUpload([
                                'size' => $_FILES['project_gallery']['size'][$index][$gIdx],
                                'type' => $_FILES['project_gallery']['type'][$index][$gIdx]
                            ], $isVideo);
                            if (!$uploadErr) {
                                $filename = time() . '_' . uniqid() . '_' . pathinfo($gName, PATHINFO_FILENAME) . '.' . pathinfo($gName, PATHINFO_EXTENSION);
                                if (move_uploaded_file($_FILES['project_gallery']['tmp_name'][$index][$gIdx], $uploadDir . $filename)) {
                                    $gallery[] = $filename;
                                }
                            }
                        } elseif ($_FILES['project_gallery']['error'][$index][$gIdx] !== UPLOAD_ERR_NO_FILE) {
                            if ($_FILES['project_gallery']['error'][$index][$gIdx] === UPLOAD_ERR_INI_SIZE || $_FILES['project_gallery']['error'][$index][$gIdx] === UPLOAD_ERR_FORM_SIZE) {
                                $error .= ($error ? ' | ' : '') . 'Datei zu groß';
                            }
                        }
                    }
                }

                $projects[] = [
                    'title' => [
                        'de' => $title_de,
                        'en' => $title_en
                    ],
                    'client' => [
                        'de' => trim($p['client_de'] ?? ''),
                        'en' => trim($p['client_en'] ?? '')
                    ],
                    'description' => [
                        'de' => strip_tags($p['description_de'] ?? ''),
                        'en' => strip_tags($p['description_en'] ?? '')
                    ],
                    'year' => trim($p['year'] ?? ''),
                    'image' => $image,
                    'gallery' => $gallery
                ];
            }
        }
        saveProjects($projects);
        $success = 'Projekte gespeichert.';
    }
}

// Refresh data after changes
$settings = getSettings();
$projects = getProjects();
$aboutPage = getPage('about') ?: (getPage('home') ?: ['title' => 'About', 'content' => '']);
$contactPage = getPage('contact') ?: [
    'title' => [
        'de' => 'Kontakt',
        'en' => 'Contact'
    ],
    'content' => [
        'de' => '',
        'en' => 'Get in touch. Let\'s work together on your next project.'
    ]
];
$timeline = getTimeline();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin</title>
<link rel="stylesheet" href="assets/css/admin-panel.css">
</head>
<body>

<div class="container">
    <div class="header">
        <a href="/" class="brand">Admin · <?php echo e($settings['site_name'] ?? 'Site'); ?></a>
        <a href="/" target="_blank" class="view-site">Zur Seite</a>
        <div class="nav">
            <a href="?page=projects" class="<?php echo $page==='projects'?'active':''; ?>">Projekte</a>
            <a href="?page=about" class="<?php echo $page==='about'?'active':''; ?>">About Me</a>
            <a href="?page=contact" class="<?php echo $page==='contact'?'active':''; ?>">Contact</a>
            <a href="?page=settings" class="<?php echo $page==='settings'?'active':''; ?>">Einstellungen</a>
            <a href="?logout">Logout</a>
        </div>
    </div>

    <div class="content">
        <?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>

        <?php if ($page === 'projects'): ?>
            <h2>Projekte verwalten</h2>
            <p class="microcopy">Reihenfolge per Pfeile anpassen, Bilder hochladen, Beschreibungen pflegen.</p>
            <form method="POST"><button name="add_project">➕ Projekt hinzufügen</button></form>
            <br>
            <form method="POST" enctype="multipart/form-data">
                <button name="save_projects">Alle Projekte speichern</button>
                <?php foreach ($projects as $i => $p): ?>
                <div class="project">
                    <button type="button" class="btn-edit-gallery" data-project="<?php echo $i; ?>" style="margin-bottom:8px;float:right;">Galerien bearbeiten</button>
                    <h3>Projekt <?php echo $i+1; ?></h3>
                    <button type="submit" name="delete_project" value="<?php echo $i; ?>" class="btn-danger">🗑️ Löschen</button>
                    <button type="button" onclick="moveProject(<?php echo $i; ?>, -1)">⬆️</button>
                    <button type="button" onclick="moveProject(<?php echo $i; ?>, 1)">⬇️</button>
                    
                    <div class="lang-toggle">
                        <button type="button" class="lang-btn active" data-lang="de" onclick="showLang(this, <?php echo $i; ?>)">Deutsch</button>
                        <button type="button" class="lang-btn" data-lang="en" onclick="showLang(this, <?php echo $i; ?>)">English</button>
                    </div>
                    
                    <div class="lang-content" data-lang-content="de-<?php echo $i; ?>">
                        <label>Titel (Deutsch)</label>
                        <input type="text" name="projects[<?php echo $i; ?>][title_de]" value="<?php echo is_array($p['title']) ? e($p['title']['de'] ?? '') : e($p['title']); ?>">
                        <label>Kunde (Deutsch)</label>
                        <input type="text" name="projects[<?php echo $i; ?>][client_de]" value="<?php echo is_array($p['client']) ? e($p['client']['de'] ?? '') : e($p['client'] ?? ''); ?>">
                        <label>Beschreibung (Deutsch, nur Text)</label>
                        <textarea name="projects[<?php echo $i; ?>][description_de]" style="height:100px;"><?php $desc = is_array($p['description']) ? ($p['description']['de'] ?? '') : ($p['description'] ?? ''); echo htmlspecialchars(strip_tags($desc)); ?></textarea>
                    </div>
                    
                    <div class="lang-content" data-lang-content="en-<?php echo $i; ?>" style="display:none;">
                        <label>Title (English)</label>
                        <input type="text" name="projects[<?php echo $i; ?>][title_en]" value="<?php echo is_array($p['title']) ? e($p['title']['en'] ?? '') : ''; ?>">
                        <label>Client (English)</label>
                        <input type="text" name="projects[<?php echo $i; ?>][client_en]" value="<?php echo is_array($p['client']) ? e($p['client']['en'] ?? '') : ''; ?>">
                        <label>Description (English, text only)</label>
                        <textarea name="projects[<?php echo $i; ?>][description_en]" style="height:100px;"><?php $desc_en = is_array($p['description']) ? ($p['description']['en'] ?? '') : ''; echo htmlspecialchars(strip_tags($desc_en)); ?></textarea>
                    </div>
                    
                    <label>Jahr</label>
                    <input type="text" name="projects[<?php echo $i; ?>][year]" value="<?php echo e($p['year'] ?? ''); ?>">
                    
                    <label>Hauptbild (max. 10MB)</label>
                    <?php if (!empty($p['image'])): ?>
                        <p>Aktuell: <?php echo e($p['image']); ?></p>
                        <img src="<?php echo 'assets/projects/' . e($p['image']); ?>" style="max-width:120px;max-height:80px;" />
                        <input type="hidden" name="projects[<?php echo $i; ?>][image]" value="<?php echo e($p['image']); ?>">
                    <?php endif; ?>
                    <input type="file" name="project_thumbnails[<?php echo $i; ?>]" accept="image/*">
                    <h4>Galerie-Bilder</h4>
                    <?php $gallery = isset($p['gallery']) && is_array($p['gallery']) ? $p['gallery'] : []; ?>
                    <?php if (!empty($gallery)): ?>
                        <div class="gallery-list" data-project-index="<?php echo $i; ?>">
                        <?php foreach ($gallery as $g): ?>
                            <div class="gallery-item">
                                <img src="<?php echo 'assets/projects/' . e($g); ?>" style="max-width:120px;max-height:80px;" />
                                <input type="hidden" name="projects[<?php echo $i; ?>][gallery_existing][]" value="<?php echo e($g); ?>">
                                <div class="gallery-item-controls">
                                    <button type="button" onclick="moveGalleryItem(this, -1)">⬆️</button>
                                    <button type="button" onclick="moveGalleryItem(this, 1)">⬇️</button>
                                    <button type="button" class="btn-danger" onclick="deleteGalleryItem(this)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <label>Galerie & Videos (Bilder max. 10MB, Videos max. 500MB)</label>
                    <input type="file" name="project_gallery[<?php echo $i; ?>][]" accept="image/*,video/*" multiple>
                </div>
                <?php endforeach; ?>
                <button name="save_projects">Alle Projekte speichern</button>
            </form>

        <?php elseif ($page === 'about'): ?>
            <h2>About Me</h2>
            <p class="microcopy">Pflege hier deinen About-Text.</p>
            <form method="POST">
                <input type="hidden" name="slug" value="about">
                
                <div class="lang-toggle">
                    <button type="button" class="lang-btn active" data-lang="de" onclick="showLangAbout('de')">Deutsch</button>
                    <button type="button" class="lang-btn" data-lang="en" onclick="showLangAbout('en')">English</button>
                </div>
                
                <div class="lang-content" data-lang-content="de-about">
                    <label>Titel (Deutsch)</label>
                    <input type="text" name="title_de" value="<?php echo is_array($aboutPage['title']) ? e($aboutPage['title']['de'] ?? 'About') : e($aboutPage['title']); ?>">
                    <label>Inhalt (Deutsch)</label>
                    <textarea name="content_de" style="width:100%;height:160px;"><?php $content = is_array($aboutPage['content']) ? ($aboutPage['content']['de'] ?? '') : ($aboutPage['content'] ?? ''); echo htmlspecialchars($content); ?></textarea>
                </div>
                
                <div class="lang-content" data-lang-content="en-about" style="display:none;">
                    <label>Title (English)</label>
                    <input type="text" name="title_en" value="<?php echo is_array($aboutPage['title']) ? e($aboutPage['title']['en'] ?? '') : ''; ?>">
                    <label>Content (English)</label>
                    <textarea name="content_en" style="width:100%;height:160px;"><?php $content_en = is_array($aboutPage['content']) ? ($aboutPage['content']['en'] ?? '') : ''; echo htmlspecialchars($content_en); ?></textarea>
                </div>
                
                <button name="save_page">About speichern</button>
            </form>

            <h2>Timeline</h2>
            <p class="microcopy">Pflege vergangene, laufende und kommende Projekte oder Stationen.</p>
            <form method="POST"><button name="add_timeline">➕ Eintrag hinzufügen</button></form>
            <br>
            <form method="POST">
                <button name="save_timeline">Alle Einträge speichern</button>
                <?php foreach ($timeline as $i => $entry): ?>
                <div class="project">
                    <h3>Eintrag <?php echo $i+1; ?></h3>
                    <button type="submit" name="delete_timeline" value="<?php echo $i; ?>" class="btn-danger">🗑️ Löschen</button>
                    
                    <div class="lang-toggle">
                        <button type="button" class="lang-btn active" data-lang="de" onclick="showLangCV(this, <?php echo $i; ?>)">Deutsch</button>
                        <button type="button" class="lang-btn" data-lang="en" onclick="showLangCV(this, <?php echo $i; ?>)">English</button>
                    </div>
                    
                    <div class="lang-content" data-lang-content="de-cv-<?php echo $i; ?>">
                        <label>Titel / Position (Deutsch)</label>
                        <input type="text" name="timeline[<?php echo $i; ?>][title_de]" value="<?php echo e($entry['title']['de'] ?? ''); ?>" placeholder="z.B. Projekt, Rolle, Station">
                    </div>
                    
                    <div class="lang-content" data-lang-content="en-cv-<?php echo $i; ?>" style="display:none;">
                        <label>Title / Position (English)</label>
                        <input type="text" name="timeline[<?php echo $i; ?>][title_en]" value="<?php echo e($entry['title']['en'] ?? ''); ?>" placeholder="e.g. Project, role, station">
                    </div>
                    
                    <label>Startdatum</label>
                    <input type="date" name="timeline[<?php echo $i; ?>][date_start]" value="<?php echo e($entry['date_start'] ?? ''); ?>">
                    
                    <label>Enddatum</label>
                    <input type="date" name="timeline[<?php echo $i; ?>][date_end]" value="<?php echo e($entry['date_end'] ?? ''); ?>" <?php echo ($entry['ongoing'] ?? false) ? 'disabled' : ''; ?>>
                    
                    <label>
                        <input type="checkbox" name="timeline[<?php echo $i; ?>][ongoing]" value="yes" <?php echo ($entry['ongoing'] ?? false) ? 'checked' : ''; ?> onchange="toggleEndDate(this, <?php echo $i; ?>)">
                        Aktuell / Laufend
                    </label>
                </div>
                <?php endforeach; ?>
                <button name="save_timeline">Alle Einträge speichern</button>
            </form>


        <?php elseif ($page === 'contact'): ?>
            <h2>Contact</h2>
            <p class="microcopy">Intro-Text für die Kontaktseite in beiden Sprachen.</p>
            <form method="POST">
                <input type="hidden" name="slug" value="contact">

                <div class="lang-toggle">
                    <button type="button" class="lang-btn active" data-lang="de" onclick="showLangContact('de')">Deutsch</button>
                    <button type="button" class="lang-btn" data-lang="en" onclick="showLangContact('en')">English</button>
                </div>

                <div class="lang-content" data-lang-content="de-contact">
                    <label>Titel (Deutsch)</label>
                    <input type="text" name="title_de" value="<?php echo is_array($contactPage['title']) ? e($contactPage['title']['de'] ?? 'Kontakt') : e($contactPage['title']); ?>">
                    <label>Einleitung / Text (Deutsch)</label>
                    <textarea name="content_de" style="width:100%;height:140px;"><?php $c = is_array($contactPage['content']) ? ($contactPage['content']['de'] ?? '') : ($contactPage['content'] ?? ''); echo htmlspecialchars($c); ?></textarea>
                </div>

                <div class="lang-content" data-lang-content="en-contact" style="display:none;">
                    <label>Title (English)</label>
                    <input type="text" name="title_en" value="<?php echo is_array($contactPage['title']) ? e($contactPage['title']['en'] ?? 'Contact') : ''; ?>">
                    <label>Intro / Text (English)</label>
                    <textarea name="content_en" style="width:100%;height:140px;"><?php $c_en = is_array($contactPage['content']) ? ($contactPage['content']['en'] ?? '') : ''; echo htmlspecialchars($c_en); ?></textarea>
                </div>

                <button name="save_page">Contact speichern</button>
            </form>

            <h2 style="margin-top:40px;">Kontaktaufnahmen</h2>
            <p class="microcopy">Hier siehst du alle Kontaktanfragen. Du kannst direkt antworten.</p>
            <?php require_once __DIR__ . '/contact-log-viewer.php'; $log = getContactLog(); ?>
            <table style="width:100%;border-collapse:separate;border-spacing:0 10px;">
                <tr style="background:#eee;"><th>Datum</th><th>Name/E-Mail</th><th style="width:40%">Nachricht</th><th>Antwort</th></tr>
                <?php foreach ($log as $entry): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="font-size:0.9em;white-space:nowrap;vertical-align:top;padding:8px 4px;"><?php echo htmlspecialchars($entry['date']); ?></td>
                    <td style="vertical-align:top;padding:8px 4px;"><?php echo htmlspecialchars($entry['nameMail']); ?></td>
                    <td style="vertical-align:top;padding:8px 4px;max-width:500px;word-break:break-word;"><div style="min-height:48px;white-space:pre-line;"><?php echo htmlspecialchars($entry['msg']); ?></div></td>
                    <td style="vertical-align:top;padding:8px 4px;">
                        <?php
                        if (preg_match('/<([^>]+)>/', $entry['nameMail'], $m)) {
                            $mailto = 'mailto:' . htmlspecialchars($m[1]);
                            echo '<a href="' . $mailto . '" class="btn-mailto" style="padding:4px 10px;background:#333;color:#fff;border-radius:4px;text-decoration:none;">Mail senden</a>';
                        } else {
                            echo '<span style="color:#aaa;">Keine E-Mail</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

        <?php elseif ($page === 'settings'): ?>
            <h2>Einstellungen</h2>
            <p class="microcopy">Basisinfos, die auf der Seite angezeigt werden.</p>
            <form method="POST" enctype="multipart/form-data">
                <label>Seitenname</label>
                <input type="text" name="settings[site_name]" value="<?php echo e($settings['site_name'] ?? ''); ?>">
                <label>Meta Description (für Suchmaschinen)</label>
                <input type="text" name="settings[site_description]" value="<?php echo e($settings['site_description'] ?? ''); ?>" placeholder="Kurze Beschreibung der Seite (max. 160 Zeichen)">
                <label>Email</label>
                <input type="text" name="settings[email]" value="<?php echo e($settings['email'] ?? ''); ?>">
                <label>Impressum</label>
                <textarea name="settings[imprint]" rows="8" style="width:100%" placeholder="Impressum einfügen..."><?php echo isset($settings['imprint']) ? htmlspecialchars($settings['imprint']) : ''; ?></textarea>
                <label>Social Links (mit Komma trennen)</label>
                <input type="text" name="social_links" value="<?php echo isset($settings['social_links']) ? htmlspecialchars(implode(', ', $settings['social_links'])) : ''; ?>" placeholder="z.B. https://instagram.com/... , https://behance.net/...">
                <label>Favicon (Icon für Browsertab)</label>
                <input type="file" name="favicon" accept=".ico,.png" style="padding:8px;">
                <p class="microcopy">Format: .ico oder .png | Größe: max. 100KB | Empfohlen: 32x32px oder 64x64px</p>
                <button name="save_settings">Einstellungen speichern</button>
            </form>

            <h2 style="margin-top:40px;">Kontaktaufnahmen</h2>
            <p class="microcopy">Hier siehst du alle Kontaktanfragen. Du kannst direkt antworten.</p>
            <?php require_once __DIR__ . '/contact-log-viewer.php'; $log = getContactLog(); ?>
            <table style="width:100%;border-collapse:separate;border-spacing:0 10px;">
                <tr style="background:#eee;"><th>Datum</th><th>Name/E-Mail</th><th style="width:40%">Nachricht</th><th>Antwort</th></tr>
                <?php foreach ($log as $entry): ?>
                <tr style="border-bottom:1px solid #ddd;">
                    <td style="font-size:0.9em;white-space:nowrap;vertical-align:top;padding:8px 4px;"><?php echo htmlspecialchars($entry['date']); ?></td>
                    <td style="vertical-align:top;padding:8px 4px;"><?php echo htmlspecialchars($entry['nameMail']); ?></td>
                    <td style="vertical-align:top;padding:8px 4px;max-width:500px;word-break:break-word;"><div style="min-height:48px;white-space:pre-line;"><?php echo htmlspecialchars($entry['msg']); ?></div></td>
                    <td style="vertical-align:top;padding:8px 4px;">
                        <?php
                        if (preg_match('/<([^>]+)>/', $entry['nameMail'], $m)) {
                            $mailto = 'mailto:' . htmlspecialchars($m[1]);
                            echo '<a href="' . $mailto . '" class="btn-mailto" style="padding:4px 10px;background:#333;color:#fff;border-radius:4px;text-decoration:none;">Mail senden</a>';
                        } else {
                            echo '<span style="color:#aaa;">Keine E-Mail</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif ($page === 'settings'): ?>
            <h2>Einstellungen</h2>
            <p class="microcopy">Basisinfos, die auf der Seite angezeigt werden.</p>
            <form method="POST" enctype="multipart/form-data">
                <label>Seitenname</label>
                <input type="text" name="settings[site_name]" value="<?php echo e($settings['site_name'] ?? ''); ?>">
                <label>Meta Description (für Suchmaschinen)</label>
                <input type="text" name="settings[site_description]" value="<?php echo e($settings['site_description'] ?? ''); ?>" placeholder="Kurze Beschreibung der Seite (max. 160 Zeichen)">
                <label>Email</label>
                <input type="text" name="settings[email]" value="<?php echo e($settings['email'] ?? ''); ?>">
                <label>Impressum</label>
                <textarea name="settings[imprint]" rows="8" style="width:100%" placeholder="Impressum einfügen..."><?php echo isset($settings['imprint']) ? htmlspecialchars($settings['imprint']) : ''; ?></textarea>
                <label>Social Links (mit Komma trennen)</label>
                <input type="text" name="social_links" value="<?php echo isset($settings['social_links']) ? htmlspecialchars(implode(', ', $settings['social_links'])) : ''; ?>" placeholder="z.B. https://instagram.com/... , https://behance.net/...">
                <label>Favicon (Icon für Browsertab)</label>
                <input type="file" name="favicon" accept=".ico,.png" style="padding:8px;">
                <p class="microcopy">Format: .ico oder .png | Größe: max. 100KB | Empfohlen: 32x32px oder 64x64px</p>
                <button name="save_settings">Einstellungen speichern</button>
            </form>
        <?php endif; ?>
        </div>

        <!-- Galerie-Overlay (Modal) -->
        <div id="galleryModal" class="gallery-modal" style="display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;">
            <div class="gallery-modal-content" style="background:#fff;padding:32px 24px;max-width:600px;width:96vw;max-height:90vh;overflow:auto;position:relative;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
                <button id="closeGalleryModal" style="position:absolute;top:12px;right:12px;font-size:22px;background:none;border:none;cursor:pointer;">&times;</button>
                <h2>Galerien bearbeiten</h2>
                <div id="galleryModalBody">
                    <p>Hier kannst du mehrere Galerien (z.B. Prozess, fertig) anlegen, Titel vergeben und Bilder verwalten.</p>
                    <div id="galleryList"></div>
                    <button id="addGalleryBtn" type="button" style="margin-top:18px;">+ Galerie hinzufügen</button>
                </div>
            </div>
        </div>

        <script>
        // --- Galerie-Overlay-Logik ---
        const projectsData = <?php echo json_encode($projects); ?>;
        let currentProjectIndex = null;
        let galleryState = [];

        function renderGalleryList() {
            const list = document.getElementById('galleryList');
            list.innerHTML = '';
            galleryState.forEach((gal, idx) => {
                const galDiv = document.createElement('div');
                galDiv.className = 'gallery-edit-block';
                galDiv.style = 'margin-bottom:22px;padding:12px 0;border-bottom:1px solid #eee;';
                galDiv.innerHTML = `
                    <label>Galerie-Titel: <input type="text" value="${gal.title || ''}" data-idx="${idx}" class="gallery-title-input" style="margin-left:8px;width:180px;"></label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 0 0;align-items:center;">
                        ${
                            (gal.images||[]).map((img, imgIdx) => `
                                <span style="display:inline-flex;flex-direction:column;align-items:center;">
                                    <img src="<?php echo SITE_URL; ?>/assets/projects/${img}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
                                    <div style="display:flex;gap:2px;margin-top:2px;">
                                      <button type="button" class="move-img-left" data-gal="${idx}" data-img="${imgIdx}">&#8592;</button>
                                      <button type="button" class="move-img-right" data-gal="${idx}" data-img="${imgIdx}">&#8594;</button>
                                      <button type="button" class="remove-img" data-gal="${idx}" data-img="${imgIdx}">&#10006;</button>
                                    </div>
                                </span>
                            `).join('') || '<span style="color:#aaa;">(Keine Bilder)</span>'
                        }
                        <label style="margin-left:12px;"><input type="file" class="add-img-input" data-gal="${idx}" style="display:none;"><button type="button" class="add-img-btn" data-gal="${idx}">+ Bild</button></label>
                    </div>
                `;
                list.appendChild(galDiv);
            });
            // Titel-Änderungen übernehmen
            list.querySelectorAll('.gallery-title-input').forEach(inp => {
                inp.addEventListener('input', e => {
                    const idx = +inp.dataset.idx;
                    galleryState[idx].title = inp.value;
                });
            });
            // Bild entfernen
            list.querySelectorAll('.remove-img').forEach(btn => {
                btn.addEventListener('click', e => {
                    const gal = +btn.dataset.gal, img = +btn.dataset.img;
                    galleryState[gal].images.splice(img, 1);
                    renderGalleryList();
                });
            });
            // Bild verschieben
            list.querySelectorAll('.move-img-left').forEach(btn => {
                btn.addEventListener('click', e => {
                    const gal = +btn.dataset.gal, img = +btn.dataset.img;
                    if (img > 0) {
                        const arr = galleryState[gal].images;
                        [arr[img-1], arr[img]] = [arr[img], arr[img-1]];
                        renderGalleryList();
                    }
                });
            });
            list.querySelectorAll('.move-img-right').forEach(btn => {
                btn.addEventListener('click', e => {
                    const gal = +btn.dataset.gal, img = +btn.dataset.img;
                    const arr = galleryState[gal].images;
                    if (img < arr.length-1) {
                        [arr[img+1], arr[img]] = [arr[img], arr[img+1]];
                        renderGalleryList();
                    }
                });
            });
            // Bild hinzufügen (Dateiupload)
            list.querySelectorAll('.add-img-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    const gal = +btn.dataset.gal;
                    list.querySelector('.add-img-input[data-gal="'+gal+'"]')?.click();
                });
            });
            list.querySelectorAll('.add-img-input').forEach(inp => {
                inp.addEventListener('change', e => {
                    const gal = +inp.dataset.gal;
                    const file = inp.files[0];
                    if (!file) return;
                    // Upload per AJAX
                    const formData = new FormData();
                    formData.append('gallery_image', file);
                    fetch('upload-gallery-image.php', {method:'POST',body:formData})
                        .then(r=>r.json()).then(res=>{
                            if(res.success && res.filename){
                                galleryState[gal].images.push(res.filename);
                                renderGalleryList();
                            }else{
                                alert('Fehler beim Upload');
                            }
                        });
                });
            });
        }

        function initGalleryOverlayHandlers() {
            document.querySelectorAll('.btn-edit-gallery').forEach(btn => {
                btn.onclick = function() {
                    currentProjectIndex = parseInt(btn.dataset.project, 10);
                    const proj = projectsData[currentProjectIndex];
                    // Migration: Falls noch keine Galerien-Struktur, initialisiere mit einer Standardgalerie
                    if (!proj.galleries) {
                        proj.galleries = [{title: 'Galerie', images: proj.gallery || []}];
                    }
                    galleryState = JSON.parse(JSON.stringify(proj.galleries));
                    renderGalleryList();
                    const modal = document.getElementById('galleryModal');
                    modal.style.display = 'flex';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                };
            });
            document.getElementById('closeGalleryModal').onclick = function() {
                closeGalleryModal();
            };
            document.getElementById('galleryModal').addEventListener('click', function(e){
                if(e.target === this) closeGalleryModal();
            });
            document.addEventListener('keydown', function(e){
                if(document.getElementById('galleryModal').style.display==='flex' && e.key==='Escape') closeGalleryModal();
            });
            document.getElementById('addGalleryBtn').onclick = function() {
                galleryState.push({title: '', images: []});
                renderGalleryList();
            };
        }
        function closeGalleryModal(){
            document.getElementById('galleryModal').style.display = 'none';
        }
        document.addEventListener('DOMContentLoaded', function() {
            initGalleryOverlayHandlers();
        });
        </script>
</div>

<script>
function moveProject(index, direction) {
    var projects = Array.from(document.querySelectorAll('.project'));
    if ((index + direction) < 0 || (index + direction) >= projects.length) return;
    var parent = projects[index].parentNode;
    if (direction === -1) parent.insertBefore(projects[index], projects[index-1]);
    else parent.insertBefore(projects[index+1], projects[index]);
}

function showLang(button, index) {
    var lang = button.getAttribute('data-lang');
    var parent = button.closest('.project');
    
    // Update button states
    parent.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    
    // Show/hide content
    parent.querySelectorAll('[data-lang-content]').forEach(el => {
        el.style.display = 'none';
    });
    var content = parent.querySelector('[data-lang-content="' + lang + '-' + index + '"]');
    if (content) content.style.display = 'block';
}

function showLangAbout(lang) {
    // Update button states
    document.querySelectorAll('[data-lang]').forEach(btn => {
        if (btn.getAttribute('data-lang') === lang) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Show/hide content
    document.querySelectorAll('[data-lang-content]').forEach(el => {
        el.style.display = 'none';
    });
    var content = document.querySelector('[data-lang-content="' + lang + '-about"]');
    if (content) content.style.display = 'block';
}

function showLangContact(lang) {
    document.querySelectorAll('[data-lang="de"], [data-lang="en"]').forEach(btn => {
        if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes('showLangContact')) {
            if (btn.getAttribute('data-lang') === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });

    document.querySelectorAll('[data-lang-content="de-contact"], [data-lang-content="en-contact"]').forEach(el => {
        el.style.display = 'none';
    });
    var content = document.querySelector('[data-lang-content="' + lang + '-contact"]');
    if (content) content.style.display = 'block';
}

function showLangCV(button, index) {
    var lang = button.getAttribute('data-lang');
    var parent = button.closest('.project');
    
    // Update button states
    parent.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    
    // Show/hide content
    parent.querySelectorAll('[data-lang-content]').forEach(el => {
        el.style.display = 'none';
    });
    var content = parent.querySelector('[data-lang-content="' + lang + '-cv-' + index + '"]');
    if (content) content.style.display = 'block';
}

function toggleEndDate(checkbox, index) {
    var parent = checkbox.closest('.project');
    var endDateInput = parent.querySelector('input[name="cv[' + index + '][date_end]"]');
    if (endDateInput) {
        endDateInput.disabled = checkbox.checked;
        if (checkbox.checked) {
            endDateInput.value = '';
        }
    }
}

function moveGalleryItem(button, direction) {
    const item = button.closest('.gallery-item');
    if (!item) return;
    const list = item.parentElement;
    const siblings = Array.from(list.children);
    const index = siblings.indexOf(item);
    if (direction === -1 && index > 0) {
        list.insertBefore(item, siblings[index - 1]);
    } else if (direction === 1 && index < siblings.length - 1) {
        list.insertBefore(siblings[index + 1], item);
    }
}

function deleteGalleryItem(button) {
    const item = button.closest('.gallery-item');
    if (item) {
        item.remove();
    }
}
</script>

<style>
.lang-toggle {
    margin: 15px 0;
    display: flex;
    gap: 8px;
}

.lang-btn {
    padding: 8px 16px;
    background: #eee;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    font-weight: normal;
    transition: all 0.2s;
}

.lang-btn:hover {
    background: #ddd;
}

.lang-btn.active {
    background: #333;
    color: white;
    border-color: #333;
}

.lang-content {
    padding: 0;
    border-left: 3px solid #ddd;
    padding-left: 12px;
}

.lang-content label {
    margin-top: 12px;
    display: block;
}
</style>

</body>
</html>