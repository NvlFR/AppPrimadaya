<?php

$targetFolder = __DIR__.'/../storage/app/public';
$linkFolder = __DIR__.'/storage';

if (!file_exists($linkFolder)) {
    symlink($targetFolder, $linkFolder);
    echo "Symlink process successfully completed.";
} else {
    echo "Symlink already exists.";
}
