<?php

/**
 * @throws Exception
 */
function get_files(string $dirs): array
{
    $file_array = [];
    try {
        $directoryIterator = new DirectoryIterator($dirs);
    } catch (Exception $e) {
        die('404 Not Found');
    }

    foreach ($directoryIterator as $fileObj) {
        if ($fileObj->getFilename() === '.' || $fileObj->getFilename() === '..') {
            continue;
        }

        $gmt_time = date('D, d M Y H:i:s', $fileObj->getMTime()) . ' GMT';
        $tmp_array = [];
        $tmp_array['lastModified'] = $gmt_time;
        $tmp_array['contentLength'] = $fileObj->getSize();
        $tmp_array['is_dir'] = $fileObj->isDir();
        $tmp_array['filename'] = $fileObj->getFilename();
        $file_array[] = $tmp_array;
    }

    return $file_array;

}

function get_current_uri(string $uri): string
{
    $uri = str_replace('/index.php', '', $uri);
    if ($uri === '') {
        $uri = '/';
    }

    return $uri;
}