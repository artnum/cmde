<?php namespace CMDE;
require_once('request.php');

interface Response {
    function __construct(Request $request);
    function headers ();
    function print ($json_object);
    function done ();
    function ok();
    function error($message);
}

class GenericResponse implements Response {
    protected $started;
    protected $object_count;
    protected $request;

    function __construct(Request $request) {
        $this->request = $request;
        $this->object_count = 0;
        $this->started = false;
        $this->done = false;
        $this->ok_started = 0;
        ob_start();
    }

    function __destruct() {
        if (!$this->done) { $this->done(); }
    }

    function headers () {
        header('Content-Type: application/json', true);
        header('Allow: GET, POST, PUT, DELETE, OPTIONS', true);
        header('Access-Control-Allow-Methods: ' . $this->request->method(), true);
        header('Access-Control-Allow-Origin: ' . $this->request->server(), true);
        header('Access-Control-Max-Age: 3600', true);
        header('Access-Control-Allow-Credentials: true', true);
        header('Access-Control-Allow-Headers: ' . $this->request->request_headers(), true);
    }

    function print ($json_object) {
        if (!$this->started) {
            echo '{"data":[';
            $this->started = true;
        } else {
            if ($this->ok_started === 1) {
                $this->ok_started = 2;
            } else {
                echo ',';
            }
        }
        if ($json_object !== null) {
            echo json_encode($json_object);
            $this->object_count++;
        }
        if ($this->started) { flush(); }
    }

    function done () {
        if ($this->started) {
            $this->done = true;
            printf('], "length": %d, "message": "Ok", "success": true, "was": "%s"}', $this->object_count, $this->request->id());
        } else {
            $this->error('Unknown error');
        }
        
    }

    function ok() {
        if (!$this->started) {
            echo '{"data":[';
            $this->ok_started = 1;
        }
        $this->started = true;
        http_response_code(200);
        $this->headers();
        ob_end_flush();
        flush();
    }

    function error($message = "error") {
        $this->done = true;
        if ($this->started) { ob_end_clean(); }
        http_response_code(400);
        $this->headers();
        echo json_encode([
            'data' => [],
            'length' => 0,
            'message' => $message,
            'success' => false,
            'was' => $this->request->id()
        ]);
        flush();
    }
}