<?php

require __DIR__ . '/ContentType.php';

/**
 * @throws Exception
 */
function get_files(string $dirs, bool $flag = true): array
{
    if ($flag) {
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
            $tmp_array['file_path'] = $fileObj->getPath();
            $file_array[] = $tmp_array;
        }

        return $file_array;
    } else {
        $fileObj = new SplFileInfo($dirs);
        $gmt_time = date('D, d M Y H:i:s', $fileObj->getMTime()) . ' GMT';
        $tmp_array = [];
        $tmp_array['lastModified'] = $gmt_time;
        $tmp_array['contentLength'] = $fileObj->getSize();
        $tmp_array['is_dir'] = $fileObj->isDir();
        $tmp_array['filename'] = $fileObj->getFilename();
        $tmp_array['file_path'] = $fileObj->getPath();
        return $tmp_array;
    }

}

function get_current_uri(string $uri): string
{
    $uri = str_replace('/index.php', '', $uri);
    if ($uri === '') {
        $uri = '/';
    }

    return $uri;
}

function get_gmt_time(string $timestamp = null): string
{
    if (isset($timestamp)) {
        return date('D, d M Y H:i:s', intval($timestamp)) . ' GMT';
    }

    return date('D, d M Y H:i:s') . ' GMT';
}

/** 传入路径， 根据路径获取文件后缀名
 *  根据后缀名设置 Content_type
 * @param string $path
 * @return void
 */
function set_content_type_header_by_path(string $path): void
{
    global $content_type_array;
    $suffix = substr($path, strrpos($path, '.') + 1);
    $content_type = $content_type_array[$suffix] ?? 'application/octet-stream';
    header('Content-type: ' . $content_type);
}

function get_content_type_by_path(string $path): string
{
    global $content_type_array;
    $suffix = substr($path, strrpos($path, '.') + 1);
    return $content_type_array[$suffix] ?? 'application/octet-stream';
}

/** 通过对文件名和文件修改时间进行 sha1 计算得出 etag
 * @param string $path
 * @return string
 */
function get_etag_by_path(string $path): string
{
    $fileInfoObj = new SplFileInfo($path);
    return '"' . sha1(strval($fileInfoObj->getMTime()) . $fileInfoObj->getFilename()) . '"';
}


/** 根据用户传递过来的文件数组, 生成 response DOM 树
 * [
 *      'lastModified' => 'xxx',
 *      'contentLength' => 'xxx',
 *      'is_dir' => true|false,
 *      'filename' => xxx,
 *      'file_path' => 'xxxx'
 * ]
 * @param array $files
 * @param DOMDocument $dom
 * @param DOMElement $start_node
 * @param string $uri
 * @return void
 * @throws DOMException
 */
function set_response_node(array $files, DOMDocument &$dom, DOMElement &$start_node, string $uri, bool $is_first_node = false): void
{
    $response_node = $dom->createElement('D:response');
    $href_node = $dom->createElement('D:href');
    if ($is_first_node) {
        if ($uri[-1] === '/') {
            $href_node->textContent = substr($uri, 0, strlen($uri) - 1);
        } else {
            $href_node->textContent = $uri;
        }
    } elseif ($uri === '/') {
        $href_node->textContent = $uri . $files['filename'];
    } elseif ($uri[-1] == '/') {
        $href_node->textContent = $uri . $files['filename'];
    }
    else {
        $href_node->textContent = $uri . '/' . $files['filename'];
    }

    $response_node->appendChild($href_node);

    $prostate_node = $dom->createElement('D:prostate');
    $status_node = $dom->createElement('D:status');
    $status_node->textContent = 'HTTP/1.1 200 OK';

    $prop_node = $dom->createElement('D:prop');
    $getLastModified_node = $dom->createElement('D:getlastmodified');
    $getLastModified_node->textContent = $files['lastModified'];
    $prop_node->appendChild($getLastModified_node);

    if (!$files['is_dir']) {
        $content_length_node = $dom->createElement('D:getcontentlength');
        $content_length_node->textContent = $files['contentLength'];
        $prop_node->appendChild($content_length_node);
    }

    $displayname_node = $dom->createElement('D:displayname');
    $displayname_node->textContent = $files['filename'];

    $resource_type_node = $dom->createElement('D:resourcetype');

    if ($files['is_dir']) {
        $collection_node = $dom->createElement('D:collection');
        $collection_node->setAttribute('xmlns:D', 'DAV:');
        $resource_type_node->appendChild($collection_node);
    }
    $prop_node->appendChild($resource_type_node);

    if (!$files['is_dir']) {
        $content_type_node = $dom->createElement('D:getcontenttype');
        $content_type_node->textContent = get_content_type_by_path($files['file_path'] . DIRECTORY_SEPARATOR . $files['filename']);
        $prop_node->appendChild($content_type_node);
    }

    if (!$files['is_dir']) {
        $etag_node = $dom->createElement('D:getetag');
        $etag_node->textContent = get_etag_by_path($files['file_path'] . DIRECTORY_SEPARATOR . $files['filename']);
        $prop_node->appendChild($etag_node);
    }

    $prop_node->append($displayname_node);

    $prostate_node->appendChild($prop_node);
    $prostate_node->appendChild($status_node);
    $response_node->appendChild($prostate_node);

    $start_node->appendChild($response_node);
}