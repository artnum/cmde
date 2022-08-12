<?php

function upload_file ($path, $dest, $hash = null, $type = null) {
    if (!is_file($path) || !is_writable($path)) { return false; }
    if ($hash === null) {
        $hash = hash_file('sha256', $path);
    }
    $dirpath = sprintf('%s/%s/%s/', $dest, substr($hash, 0, 2), substr($hash, 2, 2));
    mkdir($dirpath, 0755, true);
    $filepath = sprintf('%s/%s', $dirpath, $hash);
    $result = rename($path, $filepath);
    if ($result && $type) {
        if (is_callable('xattr_set')) { xattr_set($filepath, 'kfu-filetype', $type, XATTR_CREATE); }
    }
    return $result;
}