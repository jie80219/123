<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * Extend this controller for application-wide helpers and setup.
 */
abstract class BaseController extends Controller
{
    /**
     * @var array<string>
     */
    protected $helpers = [];

    /**
     * @var IncomingRequest
     */
    protected $request;

    /**
     * Called on controller instantiation.
     *
     * @param IncomingRequest $request
     * @param ResponseInterface $response
     * @param LoggerInterface $logger
     */
    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
    }
}
