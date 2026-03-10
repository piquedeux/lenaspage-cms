<?php
require_once __DIR__ . '/site.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    @file_put_contents(CONTENT_DIR . '/ajax-debug.log', date('c') . " not logged in ajax request\n", FILE_APPEND);
    echo json_encode(['success' => false, 'msg' => 'Nicht eingeloggt']);
    exit;
}
$action = $_POST['action'] ?? '';
$projects = getProjects();
$changed = false;

if ($action === 'delete_image') {
    $proj = isset($_POST['project']) ? (int)$_POST['project'] : null;
    $gal = isset($_POST['gallery']) ? (int)$_POST['gallery'] : null;
    $filename = $_POST['filename'] ?? '';
    if ($proj === null || $gal === null || $filename === '') {
        echo json_encode(['success' => false, 'msg' => 'Ungültige Parameter']);
        exit;
    }
    if (!isset($projects[$proj]['galleries'][$gal])) {
        echo json_encode(['success' => false, 'msg' => 'Galerie nicht gefunden']);
        exit;
    }
    $images = $projects[$proj]['galleries'][$gal]['images'] ?? [];
    $new = [];
    foreach ($images as $it) {
        $file = is_array($it) ? ($it['file'] ?? reset($it)) : $it;
        if ($file === $filename) continue; // remove
        $new[] = $it;
    }
    $projects[$proj]['galleries'][$gal]['images'] = $new;
    // delete file from disk
    $path = ASSETS_DIR . '/projects/' . $filename;
    if (is_file($path)) @unlink($path);
    $changed = true;
    if ($changed) saveProjects($projects);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'reorder') {
    $proj = isset($_POST['project']) ? (int)$_POST['project'] : null;
    $gal = isset($_POST['gallery']) ? (int)$_POST['gallery'] : null;
    $imagesRaw = $_POST['images'] ?? '[]';
    $imagesArr = json_decode($imagesRaw, true);
    if ($proj === null || $gal === null || !is_array($imagesArr)) {
        echo json_encode(['success' => false, 'msg' => 'Ungültige Parameter']);
        exit;
    }
    if (!isset($projects[$proj]['galleries'][$gal])) {
        echo json_encode(['success' => false, 'msg' => 'Galerie nicht gefunden']);
        exit;
    }
    $oldImgs = $projects[$proj]['galleries'][$gal]['images'] ?? [];
    // Build map filename -> original item (preserve captions)
    $map = [];
    foreach ($oldImgs as $it) {
        if (is_array($it)) {
            $fname = $it['file'] ?? reset($it);
            // normalize caption: support legacy object or string
            $cap = '';
            if (isset($it['caption'])) {
                if (is_string($it['caption'])) $cap = $it['caption'];
                elseif (is_array($it['caption'])) $cap = $it['caption']['de'] ?? $it['caption']['en'] ?? '';
            }
            $map[$fname] = ['file' => $fname, 'caption' => $cap];
        } else {
            $map[$it] = ['file' => $it, 'caption' => ''];
        }
    }
    $new = [];
    foreach ($imagesArr as $fname) {
        if (isset($map[$fname])) $new[] = $map[$fname];
        else $new[] = ['file' => $fname, 'caption' => ''];
    }
    $projects[$proj]['galleries'][$gal]['images'] = $new;
    $changed = true;
    if ($changed) saveProjects($projects);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_title') {
    $proj = isset($_POST['project']) ? (int)$_POST['project'] : null;
    $gal = isset($_POST['gallery']) ? (int)$_POST['gallery'] : null;
    $title_de = $_POST['title_de'] ?? '';
    $title_en = $_POST['title_en'] ?? '';
    if ($proj === null || $gal === null) {
        echo json_encode(['success' => false, 'msg' => 'Ungültige Parameter']);
        exit;
    }
    if (!isset($projects[$proj]['galleries'][$gal])) {
        echo json_encode(['success' => false, 'msg' => 'Galerie nicht gefunden']);
        exit;
    }
    $projects[$proj]['galleries'][$gal]['title'] = ['de' => $title_de, 'en' => $title_en];
    saveProjects($projects);
    echo json_encode(['success' => true]);
    exit;
}

// Note: description updates are handled via the main admin form; per-user request
// the AJAX per-field description handler was removed to avoid conflicts.

echo json_encode(['success' => false, 'msg' => 'Unbekannte Aktion']);
