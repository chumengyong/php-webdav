<?php

require __DIR__ . '/utils.php';
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

class WebDav
{

    protected string $prefix;
    protected string $scope;
    protected string $uri;
    protected string $path;

    public function __construct(string $prefix, string $scope)
    {
        $this->prefix = $prefix;
        $this->scope = $scope;
        $this->uri = get_current_uri($_SERVER['REQUEST_URI']);
        $this->path = $this->scope . DIRECTORY_SEPARATOR . $this->uri;
    }

    /** 查找文件用
     * @return void
     * @throws DOMException
     */
    public function propfind()
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $start_node = $dom->createElement('D:multistatus');
        $start_node->setAttribute('xmlns:D', 'DAV:');
        $dom->appendChild($start_node);

        $files = get_files($this->path);

        // 生成 D:response
        foreach ($files as $file) {
            $response_node = $dom->createElement('D:response');
            // 给 response 加 href 属性
            $href_node = $dom->createElement('D:href');
            // 遍历出来的文件名
            $href_node->textContent = '/' . $file['filename'];
            $response_node->appendChild($href_node);

            // 文件属性
            $prostate_node = $dom->createElement('D:prostate');
            $status_node = $dom->createElement('D:status');
            $status_node->textContent = 'HTTP/1.1 200 OK';

            $prop_node = $dom->createElement('D:prop');
            $getLastModified_node = $dom->createElement('D:getlastmodified');

            $getLastModified_node->textContent = $file['lastModified'];
            $prop_node->appendChild($getLastModified_node);

            // 判断是否是文件夹, 如果是文件夹的话, 就不用显示大小了
            if (!$file['is_dir']) {
                $content_length_node = $dom->createElement('D:getcontentlength');
                $content_length_node->textContent = $file['contentLength'];
                $prop_node->appendChild($content_length_node);
            }

            // 显示文件名
            $displayname_node = $dom->createElement('D:displayname');
            $displayname_node->textContent = $file['filename'];

            // 判断是否为文件夹
            $resource_type_node = $dom->createElement('D:resourcetype');
            // 如果是文件夹, 那我们得再加一层
            if ($file['is_dir']) {
                $collection_node = $dom->createElement('D:collection');
                $collection_node->setAttribute('xmlns:D', 'DAV:');
                $resource_type_node->appendChild($collection_node);
            }
            $prop_node->appendChild($resource_type_node);


            $prop_node->appendChild($displayname_node);

            $prostate_node->appendChild($prop_node);
            $prostate_node->appendChild($status_node);
            $response_node->appendChild($prostate_node);

            $start_node->appendChild($response_node);
        }

        header('Content-Type: text/xml');
        echo $dom->saveXML();
    }

    /** 下载文件用
     * @return void
     */
    public function get()
    {
        if (is_file($this->path)) {
            $fh = fopen($this->path, 'r');
            $oh = fopen('php://output', 'w');
            stream_copy_to_stream($fh, $oh);
            fclose($fh);
            fclose($oh);
        }
    }

}


$config = Yaml::parseFile('config.yaml');

$webdav = new WebDav($config['prefix'], $config['scope']);
$request_method = strtolower($_SERVER['REQUEST_METHOD']);
$webdav->$request_method();