<?php
// Simple contact log viewer helper
function getContactLog() {
    $file = CONTENT_DIR . '/contact-log.txt';
    $out = [];
    if (!file_exists($file)) return $out;

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return $out;

    // Show newest first
    $lines = array_reverse($lines);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Try to split by ' | ' parts
        $parts = array_map('trim', explode(' | ', $line));
        $entry = ['date' => $parts[0] ?? '', 'nameMail' => '', 'msg' => ''];

        // name/email usually appears as something like "Name <email>"
        // Find the part that contains '<' and '>' and treat it as nameMail
        foreach ($parts as $p) {
            if (strpos($p, '<') !== false && strpos($p, '>') !== false) {
                $entry['nameMail'] = $p;
                break;
            }
        }

        // If not found, fallback to the 3rd part if present
        if ($entry['nameMail'] === '' && isset($parts[2])) {
            $entry['nameMail'] = $parts[2];
        }

        // Message is typically the last part (may not exist)
        if (count($parts) >= 4) {
            // join everything after the third part to preserve pipe characters
            $entry['msg'] = implode(' | ', array_slice($parts, 3));
        } else {
            $entry['msg'] = '';
        }

        $out[] = $entry;
    }

    return $out;
}

?>
