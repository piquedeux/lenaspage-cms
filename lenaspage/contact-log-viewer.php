<?php

function getContactLog() {
    $file = __DIR__ . '/content/contact-log.txt';
    $entries = [];
    if (!file_exists($file)) return $entries;

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if (is_array($data)) {
            $entries[] = $data;
        } else {
            // legacy: plain text line, show as raw
            $entries[] = ['date' => '', 'nameMail' => '', 'msg' => $line];
        }
    }
    // newest first
    return array_reverse($entries);
}
