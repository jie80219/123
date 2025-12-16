<?php

namespace App\Controllers\v1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class HeartBeat extends BaseController
{
    /**
     * method for Get
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        $session = session();
        $session->set('eg_test', 'ok');
        return $this->response
            ->setStatusCode(200)
            ->setBody(view('heartbeat', [
                'msg'  => 'EventGateway is Online.',
                'time' => date('Y-m-d H:i:s'),
                'env'  => ENVIRONMENT,
            ]))
            ->setContentType('text/html');
    }
    
}
