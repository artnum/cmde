<?php namespace CMDE;
require_once('response.php');

interface Request {
    function next();
    function previous();
    function action();
    function last();
    function first();
    function http_method();
    function body();
    function id();
    function server();
    function method();
    function request_headers();
}

class GenericRequest implements Request {
    protected $path;
    protected $action;
    protected $body;
    protected $request_id;
    protected $error;
    private $path_pos;

    function __construct() {
        $this->action = null;
        $this->path = null;
        $this->error = null;
        $this->http_method = $_SERVER['REQUEST_METHOD'];
        $this->path_pos = -1;

        $this->path = explode('/', $_SERVER['PATH_INFO']);
        $this->path = array_values(array_filter(array_map(function ($i) { return strtolower(trim($i)); }, $this->path), function ($i) { return !empty($i); }));
        $body = true;
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET': $this->action = 'get'; $body = false; break;
            case 'POST':
                if (count($this->path) > 1 && $this->path[1] === '_query') {
                    $this->action = 'query';
                } else {
                    $this->action = 'create';
                }
                break;
            case 'PUT':
            case 'PATCH':
                $this->action = 'update';
                break;
            case 'DELETE':
                $this->action = 'delete';
                break;
        }
        if ($body) {
            $content = file_get_contents('php://input');
            if (!empty($content)) { 
                try {
                    $this->body = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                } catch (\Exception $e) {
                    $this->body = [];
                    $this->error = $e->getMessage();
                }
            } else {
                $this->body = [];
            }
        } else {
            $this->body = [];
        }

        if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            $this->id = $_SERVER['HTTP_X_REQUEST_ID'];
        } else {
            $this->id = '';
        }

        $this->server = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $this->method = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] : '';
        $this->request_headers = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] : '';
  
        if (empty($this->method)) { $this->method = 'GET, POST, PUT, DELETE, HEAD, OPTIONS'; }
        if (empty($this->server)) { $this->server = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME']; }
        if (empty($this->request_headers)) { $this->request_headers = 'x-requested-with,content-type,x-request-id,authorization'; }
    }

    function can_run (Response $response) {
        if ($this->error) {
            $response->error($this->error);
            exit(0);
        }
    }

    function http_method() {
        return $this->http_method;
    }

    function next() {
        if ($this->path_pos >= count($this->path)) { return null; }
        $this->path_pos++;
        return $this->path[$this->path_pos];
    }

    function previous() {
        if ($this->path_pos < 0) { return null; }
        $this->path_pos--;
        return $this->path[$this->path_pos];
    }

    function first () {
        return $this->path[0];
    }

    function last() {
        return $this->path[count($this->path) - 1];
    }

    function rewind() {
        $this->path_pos = 0;
    }

    function action() {
        return $this->action;
    }

    function body() {
        return $this->body;
    }

    function id() {
        return $this->id;
    }

    function server() {
        return $this->server;
    }

    function method() {
        return $this->method;
    }

    function request_headers() {
        return $this->request_headers;
    }
}