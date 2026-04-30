<?php
require_once 'site.php';

$settings = getSettings();
$error = '';
$success = '';
// Admin file path and first-run detection
$adminFile = CONTENT_DIR . '/admin.json';
$isFirstRun = true;
if (file_exists($adminFile)) {
    $adm = json_decode(file_get_contents($adminFile), true);
    if (!empty($adm) && !empty($adm['password'])) $isFirstRun = false;
}

// CSRF token for sensitive forms (setup / password change)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

/* ----------------------------------------
   UPLOAD VALIDATION CONSTANTS
---------------------------------------- */
// Size limits are intentionally not enforced here; server may still impose limits.
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);      // legacy default (not enforced)
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024);     // legacy default (not enforced)
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

function validateUpload($file, $isVideo = false) {
    // Only validate MIME type here. Size is not restricted by the application layer.
    $allowedTypes = $isVideo ? ALLOWED_VIDEO_TYPES : ALLOWED_IMAGE_TYPES;
    if (!in_array($file['type'], $allowedTypes)) {
        return 'Dateiformat nicht erlaubt';
    }
    return null;
}

/* ----------------------------------------
   LOGIN HANDLING
---------------------------------------- */
// First-time setup: create admin user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Ungültiges Formular.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';
        if ($username === '' || strlen($password) < 8) {
            $error = 'Bitte gültigen Benutzernamen und mindestens 8-stelliges Passwort wählen.';
        } elseif ($password !== $password2) {
            $error = 'Passwörter stimmen nicht überein.';
        } else {
            if (!is_dir(CONTENT_DIR)) mkdir(CONTENT_DIR, 0755, true);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $data = ['username' => $username, 'password' => $hash];
            file_put_contents($adminFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['logged_in'] = true;
            header('Location: ' . SITE_URL . '/admin.php?page=projects');
            exit;
        }
    }
}

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
<!--
 __  __       ____  
|  \/  |     / ___| 
| \  / |    | | __  
| |\/| |    | |(  | 
| |  | |  _ | |_) |  _ 
(_)  (_) (_) \____| (_)

--> 
        <!DOCTYPE html>
        <html lang="de">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Admin Login</title>
        <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/favicon/favicon.png">
        <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/favicon/favicon.png">
        <link rel="stylesheet" href="assets/css/admin-panel.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        </head>
        <body class="login-page">
        <div class="container">
            <header class="header">
                <div class="header-left">
                    <a href="#" class="admin-link" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">admin -</a>
                    <a class="brand site-logo" href="/">Spade CMS</a>
                </div>
                <a class="view-site" href="/">Zur Seite</a>
            </header>

            <main class="content">
                <h2>Admin Login</h2>
                <p class="microcopy">Bitte melde dich an, um Inhalte zu verwalten.</p>
                <?php if ($error): ?><div class="error"><?php echo e($error); ?></div><?php endif; ?>
                <?php if ($isFirstRun): ?>
                <h3>Ersteinrichtung</h3>
                <p class="microcopy">Bitte einen Administrator-Benutzer anlegen.</p>
                <form method="POST" class="login-form" style="max-width:520px;">
                    <label for="username">Benutzername</label>
                    <input id="username" type="text" name="username" placeholder="Benutzername" required autofocus>

                    <label for="password">Passwort</label>
                    <input id="password" type="password" name="password" placeholder="Passwort (min. 8 Zeichen)" required>

                    <label for="password_confirm">Passwort wiederholen</label>
                    <input id="password_confirm" type="password" name="password_confirm" placeholder="Passwort wiederholen" required>

                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                    <button name="setup" class="btn">Einrichten und anmelden</button>
                </form>
                <?php else: ?>
                <form method="POST" class="login-form" style="max-width:520px;">
                    <label for="username">Benutzername</label>
                    <input id="username" type="text" name="username" placeholder="Benutzername" required autofocus>

                    <label for="password">Passwort</label>
                    <input id="password" type="password" name="password" placeholder="Passwort" required>

                    <button name="login" class="btn">Anmelden</button>
                </form>
                <?php endif; ?>
            </main>
        </div>
        <div class="site-copyright"><a href="https://piquedeux.de" target="_blank" rel="noopener">Click for help ©mg</a></div>
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
    /* CHANGE PASSWORD */
    if (isset($_POST['change_password'])) {
        if (!isLoggedIn()) {
            $error = 'Nicht angemeldet.';
        } elseif (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $error = 'Ungültiges Formular.';
        } else {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $new2 = $_POST['new_password_confirm'] ?? '';
            if (!file_exists($adminFile)) {
                $error = 'Admin-Daten nicht gefunden.';
            } else {
                $adm = json_decode(file_get_contents($adminFile), true);
                if (!password_verify($current, $adm['password'] ?? '')) {
                    $error = 'Aktuelles Passwort ist falsch.';
                } elseif (strlen($new) < 8) {
                    $error = 'Neues Passwort muss mindestens 8 Zeichen haben.';
                } elseif ($new !== $new2) {
                    $error = 'Neue Passwörter stimmen nicht überein.';
                } else {
                    $adm['password'] = password_hash($new, PASSWORD_DEFAULT);
                    file_put_contents($adminFile, json_encode($adm, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $success = 'Passwort geändert.';
                }
            }
        }
    }

    /* CHANGE USERNAME */
    if (isset($_POST['change_username'])) {
        if (!isLoggedIn()) {
            $error = 'Nicht angemeldet.';
        } elseif (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $error = 'Ungültiges Formular.';
        } else {
            $current = $_POST['current_password_username'] ?? '';
            $newname = trim($_POST['new_username'] ?? '');
            if ($newname === '') {
                $error = 'Benutzername darf nicht leer sein.';
            } else {
                if (!file_exists($adminFile)) {
                    $error = 'Admin-Daten nicht gefunden.';
                } else {
                    $adm = json_decode(file_get_contents($adminFile), true);
                    if (!password_verify($current, $adm['password'] ?? '')) {
                        $error = 'Aktuelles Passwort ist falsch.';
                    } else {
                        $adm['username'] = $newname;
                        file_put_contents($adminFile, json_encode($adm, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $success = 'Benutzername geändert.';
                    }
                }
            }
        }
    }

    /* DELETE PROJECT */
    if (isset($_POST['delete_project'])) {
        $del = (int)$_POST['delete_project'];
        $projects = getProjects();
        array_splice($projects, $del, 1);
        saveProjects($projects);
        header('Location: ' . SITE_URL . '?page=projects');
        exit;
    }

    /* MOVE PROJECT UP */
    if (isset($_POST['move_project_up'])) {
        $idx = (int)$_POST['move_project_up'];
        $projects = getProjects();
        if ($idx > 0 && $idx < count($projects)) {
            [$projects[$idx], $projects[$idx - 1]] = [$projects[$idx - 1], $projects[$idx]];
            saveProjects($projects);
        }
        header('Location: ' . SITE_URL . '/admin.php?page=projects&active=' . max(0, $idx - 1));
        exit;
    }

    /* MOVE PROJECT DOWN */
    if (isset($_POST['move_project_down'])) {
        $idx = (int)$_POST['move_project_down'];
        $projects = getProjects();
        if ($idx >= 0 && $idx < count($projects) - 1) {
            [$projects[$idx], $projects[$idx + 1]] = [$projects[$idx + 1], $projects[$idx]];
            saveProjects($projects);
        }
        header('Location: ' . SITE_URL . '/admin.php?page=projects&active=' . min(count($projects) - 1, $idx + 1));
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
        
        // favicon upload removed — favicon is served statically from assets/favicon/favicon.png
        
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

                // Build galleries: support multiple galleries each with multilingual title + images
                $galleriesOut = [];
                if (!empty($p['galleries']) && is_array($p['galleries'])) {
                    foreach ($p['galleries'] as $gIdx => $gal) {
                        $gal_title_de = trim($gal['title_de'] ?? ($gal['title']['de'] ?? ''));
                        $gal_title_en = trim($gal['title_en'] ?? ($gal['title']['en'] ?? ''));

                        $imgs = [];
                        // existing images and their captions (if provided)
                        $existingFiles = $gal['existing'] ?? [];
                        // normalize arrays so indices align even if inputs were sparse
                        if (is_array($existingFiles)) $existingFiles = array_values($existingFiles);
                        // support legacy per-language caption arrays or new single caption array
                        $existingCap = $gal['existing_caption'] ?? null;
                        $existingCapDe = $gal['existing_caption_de'] ?? null;
                        $existingCapEn = $gal['existing_caption_en'] ?? null;
                        if (is_array($existingCap)) $existingCap = array_values($existingCap);
                        if (is_array($existingCapDe)) $existingCapDe = array_values($existingCapDe);
                        if (is_array($existingCapEn)) $existingCapEn = array_values($existingCapEn);
                        if (!empty($existingFiles)) {
                            if (!is_array($existingFiles)) {
                                $existingFiles = array_filter(array_map('trim', explode(',', $existingFiles)));
                            }
                            foreach ($existingFiles as $k => $ef) {
                                $ef = trim($ef);
                                if ($ef === '') continue;
                                // prefer unified caption, fall back to language-specific arrays
                                $cap = '';
                                if (is_array($existingCap)) {
                                    $cap = trim($existingCap[$k] ?? '');
                                } elseif (is_array($existingCapDe) || is_array($existingCapEn)) {
                                    $cap = trim($existingCapDe[$k] ?? $existingCapEn[$k] ?? '');
                                } elseif (is_string($existingCap)) {
                                    $cap = trim($existingCap);
                                }
                                $imgs[] = [ 'file' => $ef, 'caption' => $cap ];
                            }
                        }

                                // handle newly uploaded files for this gallery
                        if (isset($_FILES['project_galleries']['name'][$index][$gIdx]) && is_array($_FILES['project_galleries']['name'][$index][$gIdx])) {
                            $uploadDir = ASSETS_DIR . '/projects/';
                            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                            foreach ($_FILES['project_galleries']['name'][$index][$gIdx] as $fileIdx => $fileName) {
                                if ($_FILES['project_galleries']['error'][$index][$gIdx][$fileIdx] === UPLOAD_ERR_OK) {
                                    $isVideo = in_array($_FILES['project_galleries']['type'][$index][$gIdx][$fileIdx], ALLOWED_VIDEO_TYPES);
                                    $uploadErr = validateUpload([
                                        'type' => $_FILES['project_galleries']['type'][$index][$gIdx][$fileIdx]
                                    ], $isVideo);
                                    if (!$uploadErr) {
                                        $filename = time() . '_' . uniqid() . '_' . pathinfo($fileName, PATHINFO_FILENAME) . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
                                        if (move_uploaded_file($_FILES['project_galleries']['tmp_name'][$index][$gIdx][$fileIdx], $uploadDir . $filename)) {
                                                    // capture captions for newly uploaded images if provided in the form
                                                    // accept unified new_caption or legacy per-language new_caption_de/new_caption_en
                                                    $new_cap = $p['galleries'][$gIdx]['new_caption'][$fileIdx] ?? $p['galleries'][$gIdx]['new_caption_de'][$fileIdx] ?? $p['galleries'][$gIdx]['new_caption_en'][$fileIdx] ?? '';
                                                    $imgs[] = [ 'file' => $filename, 'caption' => trim($new_cap) ];
                                        }
                                    }
                                } elseif ($_FILES['project_galleries']['error'][$index][$gIdx][$fileIdx] !== UPLOAD_ERR_NO_FILE) {
                                    $error .= ($error ? ' | ' : '') . 'Upload fehlgeschlagen oder Dateityp nicht erlaubt';
                                }
                            }
                        }

                        // detect and delete removed images compared with previously saved project
                        if (isset($oldProjects) && isset($oldProjects[$index]) && !empty($oldProjects[$index]['galleries']) && isset($oldProjects[$index]['galleries'][$gIdx])) {
                            $oldImgsRaw = $oldProjects[$index]['galleries'][$gIdx]['images'] ?? [];
                            $oldImgFiles = [];
                            if (!empty($oldImgsRaw)) {
                                foreach ($oldImgsRaw as $oi) {
                                    if (is_array($oi)) $oldImgFiles[] = $oi['file'] ?? reset($oi);
                                    else $oldImgFiles[] = $oi;
                                }
                            }
                            // current imgs may be objects; build filename list for comparison
                            $newImgFiles = array_map(function($it){ return is_array($it) ? ($it['file'] ?? reset($it)) : $it; }, $imgs);
                            foreach ($oldImgFiles as $oldImg) {
                                if (!in_array($oldImg, $newImgFiles)) {
                                    $path = ASSETS_DIR . '/projects/' . $oldImg;
                                    if (is_file($path)) @unlink($path);
                                }
                            }
                        }

                        $galleriesOut[] = [
                            'title' => ['de' => $gal_title_de, 'en' => $gal_title_en],
                            'type' => $gal['type'] ?? 'regular',
                            'images' => $imgs
                        ];
                    }
                } else {
                    // fallback: legacy single gallery field
                    $imgs = [];
                    if (!empty($p['gallery_existing'])) {
                        if (is_array($p['gallery_existing'])) {
                            foreach ($p['gallery_existing'] as $existingFile) {
                                $existingFile = trim($existingFile);
                                if ($existingFile !== '') $imgs[] = $existingFile;
                            }
                        } else {
                            $imgs = array_filter(array_map('trim', explode(',', $p['gallery_existing'])));
                        }
                    }

                    if (isset($_FILES['project_gallery']['name'][$index]) && is_array($_FILES['project_gallery']['name'][$index])) {
                        $uploadDir = ASSETS_DIR . '/projects/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        foreach ($_FILES['project_gallery']['name'][$index] as $gIdx => $gName) {
                            if ($_FILES['project_gallery']['error'][$index][$gIdx] === UPLOAD_ERR_OK) {
                                $isVideo = in_array($_FILES['project_gallery']['type'][$index][$gIdx], ALLOWED_VIDEO_TYPES);
                                $uploadErr = validateUpload([
                                    'type' => $_FILES['project_gallery']['type'][$index][$gIdx]
                                ], $isVideo);
                                if (!$uploadErr) {
                                    $filename = time() . '_' . uniqid() . '_' . pathinfo($gName, PATHINFO_FILENAME) . '.' . pathinfo($gName, PATHINFO_EXTENSION);
                                    if (move_uploaded_file($_FILES['project_gallery']['tmp_name'][$index][$gIdx], $uploadDir . $filename)) {
                                        $imgs[] = $filename;
                                    }
                                }
                            } elseif ($_FILES['project_gallery']['error'][$index][$gIdx] !== UPLOAD_ERR_NO_FILE) {
                                $error .= ($error ? ' | ' : '') . 'Upload fehlgeschlagen oder Dateityp nicht erlaubt';
                            }
                        }
                    }

                    if (!empty($imgs)) {
                        // delete removed images from legacy gallery
                        if (isset($oldProjects) && isset($oldProjects[$index])) {
                            $oldImgs = [];
                            if (!empty($oldProjects[$index]['galleries'][0]['images'])) $oldImgs = $oldProjects[$index]['galleries'][0]['images'];
                            elseif (!empty($oldProjects[$index]['gallery'])) $oldImgs = $oldProjects[$index]['gallery'];
                            foreach ($oldImgs as $oldImg) {
                                if (!in_array($oldImg, $imgs)) {
                                    $path = ASSETS_DIR . '/projects/' . $oldImg;
                                    if (is_file($path)) @unlink($path);
                                }
                            }
                        }

                        $galleriesOut[] = ['title' => ['de' => '', 'en' => ''], 'type' => 'regular', 'images' => $imgs];
                    }
                }

                
                // prepare credits structure (maintain backward compatibility with old `credits` field)
                $creditGroupsOut = [];
                if (!empty($p['credit_groups']) && is_array($p['credit_groups'])) {
                    $creditGroupsOut = $p['credit_groups'];
                } elseif (!empty($p['credits']) && is_array($p['credits'])) {
                    $creditGroupsOut = [[ 'title' => ['de' => '', 'en' => ''], 'items' => $p['credits'] ]];
                }

                // sanitize description fields (support legacy single description or per-language fields)
                $san_de = '';
                $san_en = '';
                if (isset($p['description_de']) || isset($p['description_en'])) {
                    $san_de = strip_tags($p['description_de'] ?? '');
                    $san_en = strip_tags($p['description_en'] ?? '');
                } elseif (isset($p['description'])) {
                    if (is_array($p['description'])) {
                        $san_de = strip_tags($p['description']['de'] ?? '');
                        $san_en = strip_tags($p['description']['en'] ?? '');
                    } else {
                        $san_de = strip_tags($p['description']);
                        $san_en = '';
                    }
                }

                // parse language-specific credits (one entry per line)
                $creditsDe = [];
                $creditsEn = [];
                if (isset($p['credits_de'])) {
                    if (is_array($p['credits_de'])) {
                        $creditsDe = array_values(array_filter(array_map('trim', $p['credits_de']), function($v){ return $v !== ''; }));
                    } else {
                        $lines = preg_split('/\r\n|\n|\r/', $p['credits_de']);
                        $creditsDe = array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
                    }
                } elseif (!empty($p['credits_text_de'])) {
                    $lines = preg_split('/\r\n|\n|\r/', $p['credits_text_de']);
                    $creditsDe = array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
                } elseif (isset($creditGroupsOut) && count($creditGroupsOut)) {
                    $creditsDe = $creditGroupsOut[0]['items'] ?? [];
                }

                if (isset($p['credits_en'])) {
                    if (is_array($p['credits_en'])) {
                        $creditsEn = array_values(array_filter(array_map('trim', $p['credits_en']), function($v){ return $v !== ''; }));
                    } else {
                        $lines = preg_split('/\r\n|\n|\r/', $p['credits_en']);
                        $creditsEn = array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
                    }
                } elseif (!empty($p['credits_text_en'])) {
                    $lines = preg_split('/\r\n|\n|\r/', $p['credits_text_en']);
                    $creditsEn = array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
                } elseif (isset($creditGroupsOut) && count($creditGroupsOut)) {
                    $creditsEn = $creditGroupsOut[0]['items'] ?? [];
                }

                $projects[] = [
                    'title' => [
                        'de' => $title_de,
                        'en' => $title_en
                    ],
                    'credits' => ['de' => $creditsDe, 'en' => $creditsEn],
                    'credit_groups' => [],
                    'description' => [
                        'de' => $san_de,
                        'en' => $san_en
                    ],
                    'year' => trim($p['year'] ?? ''),
                    'image' => $image,
                    'galleries' => $galleriesOut
                ];
            }
        }
        saveProjects($projects);
        $success = 'Projekte gespeichert.';
    }
}

    /* SAVE SINGLE PROJECT (merge textual fields into existing projects)
       This keeps the existing global saving logic intact and allows saving one project at a time
       for title/description/credits/year without changing gallery/upload behavior. */
    if (isset($_POST['save_project'])) {
        $idx = (int)$_POST['save_project'];
        $projects = getProjects();
        $p = $_POST['projects'][$idx] ?? null;
        if ($p !== null && isset($projects[$idx])) {
            $projects[$idx]['title']['de'] = trim($p['title_de'] ?? ($projects[$idx]['title']['de'] ?? ''));
            $projects[$idx]['title']['en'] = trim($p['title_en'] ?? ($projects[$idx]['title']['en'] ?? ''));
            if (isset($p['description_de'])) $projects[$idx]['description']['de'] = strip_tags($p['description_de']);
            if (isset($p['description_en'])) $projects[$idx]['description']['en'] = strip_tags($p['description_en']);

            $parseLines = function($s){
                if (is_array($s)) return array_values(array_filter(array_map('trim',$s), function($v){ return $v !== ''; }));
                $lines = preg_split('/\r\n|\n|\r/', $s ?? '');
                return array_values(array_filter(array_map('trim', $lines), function($v){ return $v !== ''; }));
            };

            if (isset($p['credits_de'])) $projects[$idx]['credits']['de'] = $parseLines($p['credits_de']);
            if (isset($p['credits_en'])) $projects[$idx]['credits']['en'] = $parseLines($p['credits_en']);

            $projects[$idx]['year'] = trim($p['year'] ?? ($projects[$idx]['year'] ?? ''));
            // Also persist gallery captions if provided for this single project
            if (!empty($p['galleries']) && is_array($p['galleries'])) {
                $gout = [];
                foreach ($p['galleries'] as $gIdx => $gal) {
                    $existingFiles = $gal['existing'] ?? [];
                    if (!is_array($existingFiles)) $existingFiles = array_filter(array_map('trim', explode(',', $existingFiles)));
                    $existingFiles = array_values($existingFiles);

                    $existingCap = $gal['existing_caption'] ?? null;
                    $existingCapDe = $gal['existing_caption_de'] ?? null;
                    $existingCapEn = $gal['existing_caption_en'] ?? null;
                    if (is_array($existingCap)) $existingCap = array_values($existingCap);
                    if (is_array($existingCapDe)) $existingCapDe = array_values($existingCapDe);
                    if (is_array($existingCapEn)) $existingCapEn = array_values($existingCapEn);

                    $imgs = [];
                    foreach ($existingFiles as $k => $ef) {
                        $ef = trim($ef);
                        if ($ef === '') continue;
                        // prefer unified caption, fall back to language-specific
                        $cap = '';
                        if (is_array($existingCap)) $cap = trim($existingCap[$k] ?? '');
                        elseif (is_array($existingCapDe) || is_array($existingCapEn)) $cap = trim($existingCapDe[$k] ?? $existingCapEn[$k] ?? '');
                        elseif (is_string($existingCap)) $cap = trim($existingCap);
                        $imgs[] = ['file' => $ef, 'caption' => $cap];
                    }

                    $gout[] = [
                        'title' => is_array($projects[$idx]['galleries'][$gIdx]['title'] ?? null) ? $projects[$idx]['galleries'][$gIdx]['title'] : ($projects[$idx]['galleries'][$gIdx]['title'] ?? ['de'=>'','en'=>'']),
                        'type' => $gal['type'] ?? ($projects[$idx]['galleries'][$gIdx]['type'] ?? 'regular'),
                        'images' => $imgs
                    ];
                }
                // merge or replace galleries while keeping other galleries if none submitted
                if (count($gout)) $projects[$idx]['galleries'] = $gout;
            }

            saveProjects($projects);
            $success = 'Projekt gespeichert.';
        } else {
            $error = 'Projektdaten fehlen.';
        }
    }

// Refresh data after changes
$settings = getSettings();
$projects = getProjects();
// Determine which project tab should be active after a save/submit
$activeProject = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_project'])) {
        $activeProject = (int)$_POST['save_project'];
    } elseif (isset($_POST['active_project'])) {
        $activeProject = (int)$_POST['active_project'];
    }
} elseif (isset($_GET['active'])) {
    $activeProject = (int)$_GET['active'];
}
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
<!--
 __  __       ____  
|  \/  |     / ___| 
| \  / |    | | __  
| |\/| |    | |(  | 
| |  | |  _ | |_) |  _ 
(_)  (_) (_) \____| (_)

--> 
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin</title>
<link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/favicon/favicon.png">
<link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/favicon/favicon.png">
<link rel="stylesheet" href="assets/css/admin-panel.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-left">
            <a href="#" class="admin-link" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">admin -</a>
            <a class="brand site-logo" href="<?php echo SITE_URL; ?>/" target="_blank" rel="noopener noreferrer">
                <?php echo strtoupper(e($settings['site_name'] ?? '')); ?> &#8599;
            </a>
        </div>
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
            <!-- Project tabs navigation -->
            <div class="projects-tabs" id="projectsTabs">
                <?php foreach ($projects as $ti => $tp): ?>
                    <?php $tabTitle = is_array($tp['title'] ?? '') ? ($tp['title']['de'] ?? 'Projekt '.($ti+1)) : ($tp['title'] ?? 'Projekt '.($ti+1)); ?>
                    <button type="button" class="projects-tab<?php echo ($ti === ($activeProject ?? 0)) ? ' active' : ''; ?>" onclick="showProjectTab(<?php echo $ti; ?>)"><?php echo e($tabTitle); ?></button>
                <?php endforeach; ?>
            </div>

            <form id="projectsForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="projects_submission_marker" value="1">
                <?php foreach ($projects as $i => $p): ?>
                <div class="project project-pane<?php echo ($i === ($activeProject ?? 0)) ? ' active' : ''; ?>" data-index="<?php echo $i; ?>">
                    <!-- removed unused "Galerien bearbeiten" button -->
                    <h3>Projekt <?php echo $i+1; ?></h3>
                    <div class="project-actions">
                        <button type="submit" name="delete_project" value="<?php echo $i; ?>" class="btn-danger"><span class="material-icons" style="vertical-align:middle;">delete</span> Löschen</button>
                        <button type="submit" name="save_project" value="<?php echo $i; ?>" class="btn" style="margin-left:8px;">Dieses Projekt speichern</button>
                        <?php if ($i > 0): ?>
                            <button type="submit" name="move_project_up" value="<?php echo $i; ?>" class="btn" style="margin-left:8px;" title="Projekt nach oben">↑</button>
                        <?php endif; ?>
                        <?php if ($i < count($projects) - 1): ?>
                            <button type="submit" name="move_project_down" value="<?php echo $i; ?>" class="btn" style="margin-left:4px;" title="Projekt nach unten">↓</button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="lang-toggle">
                        <button type="button" class="lang-btn active" data-lang="de" onclick="showLang(this, <?php echo $i; ?>)">Deutsch</button>
                        <button type="button" class="lang-btn" data-lang="en" onclick="showLang(this, <?php echo $i; ?>)">English</button>
                    </div>
                    
                    <div class="lang-content" data-lang-content="de-<?php echo $i; ?>">
                        <label>Titel (Deutsch)</label>
                        <input type="text" name="projects[<?php echo $i; ?>][title_de]" value="<?php echo is_array($p['title']) ? e($p['title']['de'] ?? '') : e($p['title']); ?>">
                        <label>Beschreibung (Deutsch, nur Text)</label>
                        <textarea name="projects[<?php echo $i; ?>][description_de]" style="height:100px;"><?php $desc = is_array($p['description']) ? ($p['description']['de'] ?? '') : ($p['description'] ?? ''); echo htmlspecialchars(strip_tags($desc)); ?></textarea>
                        <label>Credits (Deutsch — eine Zeile pro Eintrag)</label>
                        <textarea name="projects[<?php echo $i; ?>][credits_de]" style="width:100%;height:80px;"><?php
                            $cde = '';
                            if (!empty($p['credits'])) {
                                if (is_array($p['credits'])) {
                                    $cde = isset($p['credits']['de']) ? implode("\n", $p['credits']['de']) : implode("\n", $p['credits']);
                                } else {
                                    $cde = $p['credits'];
                                }
                            } elseif (!empty($p['credits_text_de'])) { $cde = $p['credits_text_de']; }
                            echo htmlspecialchars($cde);
                        ?></textarea>
                    </div>
                    
                    <div class="lang-content" data-lang-content="en-<?php echo $i; ?>" style="display:none;">
                        <label>Title (English)</label>
                        <input type="text" name="projects[<?php echo $i; ?>][title_en]" value="<?php echo is_array($p['title']) ? e($p['title']['en'] ?? '') : ''; ?>">
                        
                        <label>Description (English, text only)</label>
                        <textarea name="projects[<?php echo $i; ?>][description_en]" style="height:100px;"><?php $desc_en = is_array($p['description']) ? ($p['description']['en'] ?? '') : ''; echo htmlspecialchars(strip_tags($desc_en)); ?></textarea>
                        <label>Credits (English — one entry per line)</label>
                        <textarea name="projects[<?php echo $i; ?>][credits_en]" style="width:100%;height:80px;"><?php
                            $cen = '';
                            if (!empty($p['credits'])) {
                                if (is_array($p['credits'])) {
                                    $cen = isset($p['credits']['en']) ? implode("\n", $p['credits']['en']) : implode("\n", $p['credits']);
                                } else {
                                    $cen = $p['credits'];
                                }
                            } elseif (!empty($p['credits_text_en'])) { $cen = $p['credits_text_en']; }
                            echo htmlspecialchars($cen);
                        ?></textarea>
                    </div>
                    
                    <label>Jahr</label>
                    <input type="text" name="projects[<?php echo $i; ?>][year]" value="<?php echo e($p['year'] ?? ''); ?>">
                    
                    <label>Hauptbild (max. 10MB)</label>
                    <?php if (!empty($p['image'])): ?>
                        <p>Aktuell: <?php echo e($p['image']); ?></p>
                        <img src="<?php echo 'assets/projects/' . e($p['image']); ?>" style="max-width:120px;max-height:80px;" />
                        <input type="hidden" name="projects[<?php echo $i; ?>][image]" value="<?php echo e($p['image']); ?>">
                        <!-- main image caption removed per user request -->
                    <?php endif; ?>
                    <input type="file" name="project_thumbnails[<?php echo $i; ?>]" accept="image/*">
                    <h4>Galerien</h4>
                    <?php
                        // Normalize existing project galleries for rendering
                        $projGalleries = [];
                        if (!empty($p['galleries']) && is_array($p['galleries'])) {
                            $projGalleries = $p['galleries'];
                        } elseif (!empty($p['gallery']) && is_array($p['gallery'])) {
                            // migrate legacy single gallery into new structure
                            $projGalleries = [['title' => '', 'images' => $p['gallery']]];
                        }
                    ?>
                    <div class="project-galleries" data-project-index="<?php echo $i; ?>">
                        <?php foreach ($projGalleries as $gIdx => $gal): ?>
                            <div class="project-gallery-block" data-gal-index="<?php echo $gIdx; ?>" style="border:1px solid #eee;padding:10px;margin-bottom:8px;">
                                <label>Galerie Titel (Deutsch)</label>
                                <input type="text" name="projects[<?php echo $i; ?>][galleries][<?php echo $gIdx; ?>][title_de]" value="<?php echo e(is_array($gal['title'] ?? null) ? ($gal['title']['de'] ?? '') : ($gal['title'] ?? '')); ?>">
                                <label>Gallery Title (English)</label>
                                <input type="text" name="projects[<?php echo $i; ?>][galleries][<?php echo $gIdx; ?>][title_en]" value="<?php echo e(is_array($gal['title'] ?? null) ? ($gal['title']['en'] ?? '') : ''); ?>">
                                <label>Galerie-Typ</label>
                                <select name="projects[<?php echo $i; ?>][galleries][<?php echo $gIdx; ?>][type]">
                                    <?php $gtype = $gal['type'] ?? 'regular'; ?>
                                    <option value="regular" <?php echo $gtype === 'regular' ? 'selected' : ''; ?>>Regulär (Gitter)</option>
                                    <option value="scrollable" <?php echo $gtype === 'scrollable' ? 'selected' : ''; ?>>Horizontal scrollbar</option>
                                </select>
                                <div class="gallery-images" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                    <?php foreach (($gal['images'] ?? []) as $imgIdx => $img):
                                        $imgFile = is_array($img) ? ($img['file'] ?? reset($img)) : $img;
                                        // support unified caption string or legacy per-language caption object
                                        $cap = '';
                                        if (is_array($img) && !empty($img['caption'])) {
                                            if (is_string($img['caption'])) {
                                                $cap = $img['caption'];
                                            } elseif (is_array($img['caption'])) {
                                                $cap = $img['caption']['de'] ?? $img['caption']['en'] ?? '';
                                            }
                                        }
                                    ?>
                                        <div class="gallery-thumb" style="display:inline-block;text-align:center;margin-bottom:6px;">
                                            <img src="<?php echo 'assets/projects/'; ?><?php echo e($imgFile); ?>" style="width:90px;height:60px;object-fit:cover;border:1px solid #ddd;border-radius:4px;">
                                            <input type="hidden" name="projects[<?php echo $i; ?>][galleries][<?php echo $gIdx; ?>][existing][]" value="<?php echo e($imgFile); ?>">
                                            <div style="margin-top:6px;">
                                                <label style="font-size:0.9em;display:block;">Unterschrift</label>
                                                <input type="text" name="projects[<?php echo $i; ?>][galleries][<?php echo $gIdx; ?>][existing_caption][]" value="<?php echo e($cap); ?>" style="width:160px;">
                                            </div>
                                            <div style="margin-top:4px;display:flex;gap:4px;justify-content:center;">
                                                <button type="button" onclick="moveGalleryItem(this, -1)"><span class="material-icons">arrow_upward</span></button>
                                                <button type="button" onclick="moveGalleryItem(this, 1)"><span class="material-icons">arrow_downward</span></button>
                                                <button type="button" class="btn-danger" onclick="deleteGalleryItem(this)"><span class="material-icons">delete</span></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top:8px;">
                                    <label style="display:inline-block;margin-right:8px;">
                                        <input type="file" name="project_galleries[<?php echo $i; ?>][<?php echo $gIdx; ?>][]" accept="image/*,video/*" multiple style="display:none;" class="proj-add-file-input">
                                        <button type="button" class="add-img-btn" data-project="<?php echo $i; ?>" data-gal="<?php echo $gIdx; ?>"><span class="material-icons">photo_library</span> Bilder/Videos hinzufügen</button>
                                    </label>
                                    <button type="button" class="btn-danger btn-delete-gallery" onclick="deleteGalleryBlock(this)"><span class="material-icons">delete_forever</span> Galerie löschen</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-gallery-btn" data-project="<?php echo $i; ?>">+ Neue Galerie hinzufügen</button>

                    <!-- credits are now per-language within each language panel -->
                </div>
                <?php endforeach; ?>
                <button name="save_projects">Projekte speichern</button>
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
                <!-- Favicon upload removed; favicon served statically from assets/favicon/favicon.png -->
                <button name="save_settings">Einstellungen speichern</button>
            </form>

            <hr>
            <h3>Benutzername ändern</h3>
            <p class="microcopy">Ändere hier deinen Administrator-Benutzernamen (Bestätigung durch aktuelles Passwort erforderlich).</p>
            <form method="POST" style="max-width:520px;margin-bottom:18px;">
                <label for="current_password_username">Aktuelles Passwort</label>
                <input id="current_password_username" type="password" name="current_password_username" required>

                <label for="new_username">Neuer Benutzername</label>
                <input id="new_username" type="text" name="new_username" required>

                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <button name="change_username">Benutzername ändern</button>
            </form>

            <h3>Passwort ändern</h3>
            <p class="microcopy">Hier kannst du dein Administrator-Passwort ändern.</p>
            <form method="POST" style="max-width:520px;"> 
                <label for="current_password">Aktuelles Passwort</label>
                <input id="current_password" type="password" name="current_password" required>

                <label for="new_password">Neues Passwort</label>
                <input id="new_password" type="password" name="new_password" placeholder="Min. 8 Zeichen" required>

                <label for="new_password_confirm">Neues Passwort wiederholen</label>
                <input id="new_password_confirm" type="password" name="new_password_confirm" required>

                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <button name="change_password">Passwort ändern</button>
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
                    fetch('upload-gallery-image.php', {method:'POST',body:formData, credentials: 'same-origin'})
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

        // Ensure descriptions are submitted even when PHP's max_input_vars would truncate large forms.
        (function(){
            const form = document.getElementById('projectsForm');
            if (!form) return;
            form.addEventListener('submit', function(e){
                // For each project, find the description textareas and pack into a single hidden field
                <?php foreach ($projects as $i => $p): ?>
                    (function(idx){
                        const deEl = document.querySelector('textarea[name="projects['+idx+'][description_de]"]');
                        const enEl = document.querySelector('textarea[name="projects['+idx+'][description_en]"]');
                        const de = deEl ? deEl.value : '';
                        const en = enEl ? enEl.value : '';
                        const blob = JSON.stringify({de: de, en: en});
                        // create or update hidden input
                        let hid = document.querySelector('input[name="projects['+idx+'][description_blob]"]');
                        if (!hid) {
                            hid = document.createElement('input');
                            hid.type = 'hidden';
                            hid.name = 'projects['+idx+'][description_blob]';
                            form.appendChild(hid);
                        }
                        hid.value = blob;
                    })(<?php echo $i; ?>);
                <?php endforeach; ?>
                // include currently visible project index so page stays on that tab after reload
                (function(){
                    let activePane = document.querySelector('.project-pane.active');
                    let activeIdx = activePane ? activePane.getAttribute('data-index') : '0';
                    let hid = document.querySelector('input[name="active_project"]');
                    if (!hid) {
                        hid = document.createElement('input');
                        hid.type = 'hidden';
                        hid.name = 'active_project';
                        form.appendChild(hid);
                    }
                    hid.value = activeIdx;
                })();
            });
        })();

        function initGalleryOverlayHandlers() {
            // (Removed: deprecated .btn-edit-gallery toggle — button removed from markup)

            // Add gallery inline
            document.querySelectorAll('.add-gallery-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const proj = parseInt(btn.dataset.project, 10);
                    const container = document.querySelector('.project-galleries[data-project-index="'+proj+'"]');
                    if (!container) return;
                    const gIdx = container.querySelectorAll('.project-gallery-block').length;
                    const block = document.createElement('div');
                    block.className = 'project-gallery-block';
                    block.dataset.galIndex = gIdx;
                    block.style = 'border:1px solid #eee;padding:10px;margin-bottom:8px;';
                    block.innerHTML = `
                        <label>Galerie Titel (Deutsch)</label>
                        <input type="text" name="projects[${proj}][galleries][${gIdx}][title_de]" value="">
                        <label>Gallery Title (English)</label>
                        <input type="text" name="projects[${proj}][galleries][${gIdx}][title_en]" value="">
                        <label>Galerie-Typ</label>
                        <select name="projects[${proj}][galleries][${gIdx}][type]">
                            <option value="regular" selected>Regulär (Gitter)</option>
                            <option value="scrollable">Horizontal scrollbar</option>
                        </select>
                        <div class="gallery-images" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;"></div>
                        <div style="margin-top:8px;">
                            <label style="display:inline-block;margin-right:8px;">
                                <input type="file" name="project_galleries[${proj}][${gIdx}][]" accept="image/*,video/*" multiple style="display:none;" class="proj-add-file-input">
                                <button type="button" class="add-img-btn" data-project="${proj}" data-gal="${gIdx}">+ Bilder/Videos hinzufügen</button>
                            </label>
                        </div>
                    `;
                    container.appendChild(block);
                    attachGalleryBlockHandlers(block, proj);
                });
            });
        }
        function closeGalleryModal(){
            document.getElementById('galleryModal').style.display = 'none';
        }
        document.addEventListener('DOMContentLoaded', function() {
            initGalleryOverlayHandlers();
            // Attach handlers for existing inline gallery blocks
            document.querySelectorAll('.project-galleries').forEach(function(container){
                const proj = container.dataset.projectIndex;
                container.querySelectorAll('.project-gallery-block').forEach(function(block){
                    attachGalleryBlockHandlers(block, proj);
                });
            });

            // Description AJAX removed — descriptions are saved with the main form now.
        });

        function attachGalleryBlockHandlers(block, proj) {
            // Add image button -> click hidden file input
            block.querySelectorAll('.add-img-btn').forEach(btn => {
                btn.addEventListener('click', function(){
                    const g = btn.dataset.gal;
                    const input = block.querySelector('.proj-add-file-input') || block.querySelector('input[type=file]');
                    if (input) input.click();
                });
            });
            // File input change -> upload immediately via AJAX and insert as existing image
            block.querySelectorAll('.proj-add-file-input').forEach(inp => {
                inp.addEventListener('change', function(e){
                    const files = Array.from(inp.files || []);
                    const imgContainer = block.querySelector('.gallery-images');
                    const gIdx = block.dataset.galIndex || '0';
                    if (!files.length) return;
                    files.forEach(f => {
                        const placeholder = document.createElement('div');
                        placeholder.className = 'gallery-thumb';
                        placeholder.style = 'display:inline-block;text-align:center;margin-bottom:6px;opacity:0.6;';
                        placeholder.innerHTML = `<div style="width:90px;height:60px;display:flex;align-items:center;justify-content:center;border:1px solid #ddd;border-radius:4px;background:#fafafa;color:#999;">Uploading...</div>`;
                        imgContainer.appendChild(placeholder);

                        const formData = new FormData();
                        formData.append('gallery_image', f);
                        formData.append('project', proj);
                        formData.append('gallery', gIdx);

                        fetch('upload-gallery-image.php', {method:'POST', body: formData, credentials: 'same-origin'})
                            .then(r => r.json())
                            .then(res => {
                                if (res && res.success && res.filename) {
                                    // replace placeholder with real thumb and hidden input for existing file
                                    placeholder.innerHTML = `
                                        <img src="<?php echo SITE_URL; ?>/assets/projects/${res.filename}" style="width:90px;height:60px;object-fit:cover;border:1px solid #ddd;border-radius:4px;">
                                        <input type="hidden" name="projects[${proj}][galleries][${gIdx}][existing][]" value="${res.filename}">
                                        <div style="margin-top:6px;">
                                            <label style="font-size:0.9em;display:block;">Unterschrift</label>
                                            <input type="text" name="projects[${proj}][galleries][${gIdx}][existing_caption][]" value="" style="width:160px;">
                                        </div>
                                        <div style="margin-top:4px;display:flex;gap:4px;justify-content:center;">
                                            <button type="button" onclick="moveGalleryItem(this, -1)">&#8592;</button>
                                            <button type="button" onclick="moveGalleryItem(this, 1)">&#8594;</button>
                                            <button type="button" class="btn-danger" onclick="deleteGalleryItem(this)">&#10006;</button>
                                        </div>
                                    `;
                                    placeholder.style.opacity = '1';
                                } else {
                                    placeholder.remove();
                                    alert('Upload fehlgeschlagen');
                                }
                            }).catch(err => {
                                console.error(err);
                                placeholder.remove();
                                alert('Upload fehlgeschlagen');
                            });
                    });
                    // clear input so same file can be reselected later
                    inp.value = '';
                });
            });
            // delete gallery block handler (in case buttons added dynamically)
            const delBtn = block.querySelector('.btn-delete-gallery');
            if (delBtn) {
                delBtn.addEventListener('click', function(){
                    if (confirm('Galerie wirklich löschen?')) {
                        block.remove();
                    }
                });
            }
        }
        </script>
</div>

<script>
// Project reordering removed — frontend shuffles projects; admin reorder controls intentionally disabled.

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
    // support both legacy .gallery-item and inline .gallery-thumb
    let item = button.closest('.gallery-item');
    if (!item) item = button.closest('.gallery-thumb');
    if (!item) return;
    const list = item.parentElement;
    const siblings = Array.from(list.children);
    const index = siblings.indexOf(item);
    if (direction === -1 && index > 0) {
        list.insertBefore(item, siblings[index - 1]);
    } else if (direction === 1 && index < siblings.length - 1) {
        list.insertBefore(siblings[index + 1], item);
    }
    // try to persist reorder immediately if all items are existing files
    tryPersistGalleryReorder(item);
}

function deleteGalleryItem(button) {
    let item = button.closest('.gallery-item');
    if (!item) item = button.closest('.gallery-thumb');
    if (item) item.remove();
    // if this was an existing image on disk, request immediate deletion
    tryPersistGalleryDelete(button);
}

function tryPersistGalleryReorder(anyElementInGallery) {
    const block = anyElementInGallery.closest('.project-gallery-block');
    if (!block) return;
    const container = block.closest('.project-galleries');
    if (!container) return;
    const proj = parseInt(container.dataset.projectIndex, 10);
    const gIdx = parseInt(block.dataset.galIndex || block.dataset.galIndex === 0 ? block.dataset.galIndex : -1, 10);
    if (isNaN(proj) || isNaN(gIdx) || gIdx < 0) return;

    // collect existing filenames in current DOM order; if any thumb is a new upload (no hidden input), abort
    const thumbs = Array.from(block.querySelectorAll('.gallery-thumb'));
    const filenames = [];
    for (const t of thumbs) {
        const hidden = t.querySelector('input[type="hidden"][name*="[existing]"]');
        if (!hidden) {
            // contains an not-yet-uploaded file, skip immediate reorder
            return;
        }
        filenames.push(hidden.value);
    }

    // send reorder to server
    const fd = new FormData();
    fd.append('action', 'reorder');
    fd.append('project', proj);
    fd.append('gallery', gIdx);
    fd.append('images', JSON.stringify(filenames));
    fetch('ajax-gallery.php', {method: 'POST', body: fd, credentials: 'same-origin'}).then(r=>r.json()).then(res=>{
        if (!res.success) console.warn('Reorder failed', res);
    }).catch(err=>console.error(err));
}

function tryPersistGalleryDelete(button) {
    let item = button.closest('.gallery-item');
    if (!item) item = button.closest('.gallery-thumb');
    if (!item) return;
    const hidden = item.querySelector('input[type="hidden"][name*="[existing]"]');
    if (!hidden) return; // nothing to delete on server yet
    const filename = hidden.value;
    const block = item.closest('.project-gallery-block');
    const container = block ? block.closest('.project-galleries') : null;
    if (!container || !block) return;
    const proj = parseInt(container.dataset.projectIndex, 10);
    const gIdx = parseInt(block.dataset.galIndex || block.dataset.galIndex === 0 ? block.dataset.galIndex : -1, 10);
    if (isNaN(proj) || isNaN(gIdx) || gIdx < 0) return;

    if (!confirm('Bild wirklich vom Server löschen?')) return;

    const fd = new FormData();
    fd.append('action', 'delete_image');
    fd.append('project', proj);
    fd.append('gallery', gIdx);
    fd.append('filename', filename);
    fetch('ajax-gallery.php', {method: 'POST', body: fd, credentials: 'same-origin'}).then(r=>r.json()).then(res=>{
        if (!res.success) alert('Löschen fehlgeschlagen');
    }).catch(err=>{ console.error(err); alert('Löschen fehlgeschlagen'); });
}

function addCredit(projectIndex) {
    const container = document.querySelector('.project-credits[data-project-index="' + projectIndex + '"]');
    if (!container) return;
    const nextIdx = container.querySelectorAll('.credit-entry').length;
    const entry = document.createElement('div');
    entry.className = 'credit-entry';
    entry.style = 'border:1px solid #eee;padding:8px;margin-bottom:8px;';
    entry.innerHTML = `
        <label>Name (Deutsch)</label>
        <input type="text" name="projects[${projectIndex}][credits][${nextIdx}][name_de]" value="">
        <label>Rolle (Deutsch)</label>
        <input type="text" name="projects[${projectIndex}][credits][${nextIdx}][role_de]" value="">
        <label>Name (English)</label>
        <input type="text" name="projects[${projectIndex}][credits][${nextIdx}][name_en]" value="">
        <label>Role (English)</label>
        <input type="text" name="projects[${projectIndex}][credits][${nextIdx}][role_en]" value="">
        <div style="margin-top:6px;"><button type="button" class="btn-danger" onclick="this.closest('.credit-entry').remove()">🗑️ Löschen</button></div>
    `;
    container.appendChild(entry);
}

function addCreditToGroup(projectIndex, groupIndex) {
    const selector = '.credit-items[data-project-index="' + projectIndex + '"][data-group-index="' + groupIndex + '"]';
    const container = document.querySelector(selector);
    if (!container) return;
    const nextIdx = container.querySelectorAll('.credit-entry').length;
    const entry = document.createElement('div');
    entry.className = 'credit-entry';
    entry.style = 'border:1px solid #eee;padding:8px;margin-bottom:8px;';
    entry.innerHTML = `
        <label>Name (Deutsch)</label>
        <input type="text" name="projects[${projectIndex}][credit_groups][${groupIndex}][items][${nextIdx}][name_de]" value="">
        <label>Rolle (Deutsch)</label>
        <input type="text" name="projects[${projectIndex}][credit_groups][${groupIndex}][items][${nextIdx}][role_de]" value="">
        <label>Name (English)</label>
        <input type="text" name="projects[${projectIndex}][credit_groups][${groupIndex}][items][${nextIdx}][name_en]" value="">
        <label>Role (English)</label>
        <input type="text" name="projects[${projectIndex}][credit_groups][${groupIndex}][items][${nextIdx}][role_en]" value="">
        <div style="margin-top:6px;"><button type="button" class="btn-danger" onclick="this.closest('.credit-entry').remove()">Löschen</button></div>
    `;
    container.appendChild(entry);
}

function deleteGalleryBlock(button) {
    const block = button.closest('.project-gallery-block');
    if (!block) return;
    if (confirm('Galerie wirklich löschen?')) {
        block.remove();
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



    <div class="site-copyright"><a href="https://piquedeux.de" target="_blank" rel="noopener" >Click for help ©mg</a></div>
</script>
<script>
function showProjectTab(idx) {
    document.querySelectorAll('.project-pane').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.projects-tab').forEach(function(t){ t.classList.remove('active'); });
    var pane = document.querySelector('.project-pane[data-index="'+idx+'"]');
    if (pane) pane.classList.add('active');
    var tab = document.querySelectorAll('.projects-tab')[idx];
    if (tab) tab.classList.add('active');
    window.scrollTo({ top: pane ? pane.offsetTop - 80 : 0, behavior: 'smooth' });
}
</script>
</body>
</html>