<?php

$source1 = '/home/rubysho1/domains/devbot.rubyshop.co.th/nodeapp/uploads/';
$source2 = '/home/rubysho1/domains/dev.rubyshop.co.th/nodeapp/uploads/';
$destination = '/home/rubysho1/domains/rubyshop.co.th/public_html/dojob/files/timeline_files/';
$logFile = '/home/rubysho1/domains/rubyshop.co.th/public_html/dojob/files/sync_log.txt';

function copyFilesOnly($src, $dst, &$log, &$copiedFiles) {
    if (!is_dir($src)) {
        $log[] = "Source does not exist: $src";
        return;
    }

    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $srcFile = $src . $file;
        $dstFile = $dst . $file;

        if (is_file($srcFile)) {
            if (!file_exists($dstFile) || filemtime($srcFile) > filemtime($dstFile)) {
                if (copy($srcFile, $dstFile)) {
                    $log[] = "Copied from $src: $file";
                    $copiedFiles[] = $srcFile;
                } else {
                    $log[] = "Failed to copy from $src: $file";
                }
            }
        }
    }
}

function deleteFiles($files, &$log) {
    foreach ($files as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $log[] = "Deleted: $file";
            } else {
                $log[] = "Failed to delete: $file";
            }
        }
    }
}

// Start sync
$log = ["--- Sync started at " . date('Y-m-d H:i:s') . " ---"];
$copiedFiles = [];

copyFilesOnly($source1, $destination, $log, $copiedFiles);
copyFilesOnly($source2, $destination, $log, $copiedFiles);

$log[] = "--- Waiting 2 minutes before deletion ---";
file_put_contents($logFile, implode("\n", $log), FILE_APPEND);

// Delay for 2 minutes
sleep(120);

// Clear log variable and start fresh for deletion log
$log = ["--- Deletion started at " . date('Y-m-d H:i:s') . " ---"];
deleteFiles($copiedFiles, $log);
$log[] = "--- Sync ended at " . date('Y-m-d H:i:s') . " ---\n";

// Append final log
file_put_contents($logFile, implode("\n", $log), FILE_APPEND);
