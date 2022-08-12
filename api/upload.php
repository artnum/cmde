<?php

define('KFU_MAX_ALLOWED_SIZE', 1073741824); // 1GB
define('KFU_UPLOAD_PATH', '/tmp/');

require_once('../conf/server.php');
require_once('../lib/files.php');

function kfu_upload_done ($meta) {
    global $CMDEConf;
    return upload_file(
        KFU_UPLOAD_PATH . '/' . $meta['token'] . '/temp.bin',
        $CMDEConf['STORAGE']['path'], 
        $meta['hash'],
        $meta['filetype']
    );
}

function kfu_upload_failed($meta) {
    error_log(var_export($meta, true));
}

include('../app/js/vendor/kfileupload/server/kfileupload.php');