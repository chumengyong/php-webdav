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
        $this->uri = get_current_uri(urldecode($_SERVER['REQUEST_URI']));
        $this->path = $this->scope . DIRECTORY_SEPARATOR . $this->uri;
    }

    /** 查找文件用
     * @return void
     * @throws DOMException
     */
    public function propfind(): void
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $start_node = $dom->createElement('D:multistatus');
        $start_node->setAttribute('xmlns:D', 'DAV:');
        $dom->appendChild($start_node);

        if (isset($_SERVER['HTTP_DEPTH']) && $_SERVER['HTTP_DEPTH'] == 0) {
            $f = get_files($this->path, false);
            set_response_node($f, $dom, $start_node, $this->uri);
        } else {
            $self_path_file = get_files($this->path, false);
            set_response_node($self_path_file, $dom, $start_node, $this->uri, true);

            foreach (get_files($this->path) as $file) {
                set_response_node($file, $dom, $start_node, $this->uri);
            }
        }

        header('HTTP/1.1 207 Multi-Status');
        header('Content-Type: text/xml');
        echo $dom->saveXML();
    }

    /** 下载文件用
     * @return void
     */
    public function get()
    {
        if (is_file($this->path)) {
            $fileObj = new SplFileObject($this->path, 'r');
            // 如果用户执行了请求范围, 则分次读取, 否则全部返回
            if (isset($_SERVER['HTTP_RANGE'])) {
                $exploded = explode('-', $_SERVER['HTTP_RANGE'], 2);
                $start = substr($exploded[0], 6);
                $end = $exploded[1];
                $start = intval($start);
                $end = intval($end);
                $r = $end - $start;
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges: bytes');
                header('Content-Length: ' . $r);
                header("Content-Range: bytes $start-$end");
                header('Last-Modified: ' . get_gmt_time($fileObj->getMTime()));
                header('Etag: ' . get_etag_by_path($this->path));
                set_content_type_header_by_path($this->path);
                // 如果分片过大
                $fileObj->fseek($start);
                echo $fileObj->fread($r);
            } else {
                set_content_type_header_by_path($this->path);
                while (!$fileObj->eof()) {
                    echo $fileObj->fgets();
                }
            }
        }
    }

    /** 当客户端访问我们的服务器时
     *  我们得返回所有支持的方法
     * @return void
     */
    public function options(): void
    {
        header('Allow: OPTIONS, GET, PUT, PROPFIND, PROPPATCH');
    }

}


$config = Yaml::parseFile('config.yaml');

$webdav = new WebDav($config['prefix'], $config['scope']);
$request_method = strtolower($_SERVER['REQUEST_METHOD']);
$webdav->$request_method();