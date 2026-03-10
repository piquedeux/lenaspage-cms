<?php
require_once __DIR__ . '/site.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'msg' => 'Nicht eingeloggt']);
    exit;
}
if (empty($_FILES['gallery_image']) || $_FILES['gallery_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'msg' => 'Keine Datei hochgeladen']);
    exit;
}
$file = $_FILES['gallery_image'];
// basic mime/type check
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedImages = ['image/jpeg','image/png','image/gif','image/webp'];
$allowedVideos = ['video/mp4','video/webm','video/quicktime','video/x-msvideo'];
if (!in_array($mime, array_merge($allowedImages, $allowedVideos))) {
    echo json_encode(['success' => false, 'msg' => 'Dateityp nicht erlaubt']);
    exit;
}
$uploadDir = ASSETS_DIR . '/projects/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$orig = pathinfo($file['name'], PATHINFO_FILENAME);
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9\-_]/', '', $orig) . '.' . $ext;
$target = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'msg' => 'Verschieben fehlgeschlagen']);
    exit;
}
// optionally: generate webp or thumbnails here
echo json_encode(['success' => true, 'filename' => $filename]);
exit;
