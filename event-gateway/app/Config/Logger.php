<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Log\Handlers\FileHandler;
use CodeIgniter\Log\Handlers\HandlerInterface;

class Logger extends BaseConfig
{
    /**
     * Error Logging Threshold
     *
     * 0 = off, 1 = emergency, ..., 9 = all. Defaults to verbose in non-prod.
     *
     * @var int|list<int>
     */
    public $threshold = (ENVIRONMENT === 'production') ? 4 : 9;

    /**
     * Date format for log entries.
     */
    public string $dateFormat = 'Y-m-d H:i:s';

    /**
     * Log handlers executed in order.
     *
     * @var array<class-string<HandlerInterface>, array<string, int|list<string>|string>>
     */
    public array $handlers = [
        FileHandler::class => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],
            'fileExtension' => '',
            'filePermissions' => 0644,
            'path' => '',
        ],
    ];
}
