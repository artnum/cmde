<?php namespace CMDE;

interface MiddleWare {
    function __construct (Request $request, Response $response, Backend $backend);
    function run ();
    function single ($backendReturn);
    function multiple ($backendReturn);
}

class GenericMiddleWare implements MiddleWare {
    function __construct (Request $request, Response $response, Backend $backend) {
        $this->request = $request;
        $this->response = $response;
        $this->backend = $backend;
    }

    /* output single result */
    function single ($backendReturn) {
        $this->response->print($backendReturn);
        $this->response->ok();
    }

    /* output multiple result */
    function multiple ($backendReturn) {
        $this->response->ok();
        foreach ($backendReturn as $element) {
            $this->response->print($element);
        }
    }

    function run () {
        switch ($this->request->action()) {
            case 'create':
                $this->single($this->backend->create($this->request->body()));
                break;
            case 'update':
                $this->single($this->backend->update($this->request->next(), $this->request->body()));
                break;
            case 'get':
                $this->single($this->backend->get($this->request->next()));
                break;
            case 'query':
                $this->multiple($this->backend->query($this->request->body()));
                $this->response->ok();
                break;
            case 'delete':
                $this->single($this->backend->delete($this->request->next()));
                break;
            }
    }
}