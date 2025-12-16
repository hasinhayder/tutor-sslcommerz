<?php

$src = __DIR__;
$dst = __DIR__ . '/tspay';

// Define files and directories to ignore during copy
$ignoreFiles = ['build.php'];
$ignoreDirs = ['tspay'];

function recurse_copy($src, $dst) {
    global $ignoreFiles, $ignoreDirs;
    if (!is_dir($dst)) {
        mkdir($dst);
    }

    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        // Skip ignored files
        if (in_array($file, $ignoreFiles)) {
            continue;
        }
        // Skip ignored directories
        if (in_array($file, $ignoreDirs)) {
            continue;
        }
        // Skip hidden files and directories (starting with .)
        if (strpos($file, '.') === 0) {
            continue;
        }
        if (is_dir($src . '/' . $file)) {
            recurse_copy($src . '/' . $file, $dst . '/' . $file);
        } else {
            copy($src . '/' . $file, $dst . '/' . $file);
        }
    }
    closedir($dir);
}

// Clean up existing dist folder if it exists
if (is_dir($dst)) {
    echo "Cleaning up existing distribution folder...\n";
    // Using shell command for recursive delete for simplicity and robustness on Mac/Linux
    exec('rm -rf ' . escapeshellarg($dst));
}

echo "Starting build...\n";
recurse_copy($src, $dst);
echo "Build complete. Distribution located at: $dst\n";

