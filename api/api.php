<?php namespace CMDE;

use artnum\JStore\Generic;
use Exception;

require_once('../conf/server.php');
require_once('../lib/dbs.php');

require_once('frontend/request.php');
require_once('backend/sql.php');
require_once('backend/commande.php');
require_once('middleware/middle.php');

$request = new GenericRequest();
$response = new GenericResponse($request);

try {
    $pdo = init_pdo($CMDEConf['DB']['pdo-string'], $CMDEConf['DB']['user'], $CMDEConf['DB']['pass']);

    $collection = $request->next();

    switch ($collection) {
        default: 
            $back = new SQLBackend($pdo, $collection);
            $middle = new GenericMiddleWare($request, $response, $back);
            return $middle->run();
            
        case 'commande':
            $back = new CommandeBackend($pdo, $collection);
            $middle = new GenericMiddleWare($request, $response, $back);
            switch ($request->http_method()) {
                case 'POST':
                    switch($request->last()) {
                        case 'progress':
                            return $middle->single($back->progress($request->next(), $request->body()));
                    }
                    break;
                case 'GET':
                    switch($request->last()) {
                        case 'opened':
                            return $middle->multiple($back->opened());
                        case 'closed':
                            return $middle->multiple($back->closed());
                            break;
                        case 'deleted':
                            return $middle->multiple($back->deleted());
                            break;
                    }
                    break;

            }
            return $middle->run();
    }
} catch (\Exception $e) {
    $response->error($e->getMessage());
}