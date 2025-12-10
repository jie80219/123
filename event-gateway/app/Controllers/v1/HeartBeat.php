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
        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status' => 200,
                'msg'    => 'AnserGateway is lived.',
            ]);
    }
}
