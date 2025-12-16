<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Starts the queue listener worker.
 */
class WorkerStart extends BaseCommand
{
    protected $group       = 'worker';
    protected $name        = 'worker:start';
    protected $description = 'Start the queue listener (runs app/Workers/RequestConsumer.php).';

    public function run(array $params)
    {
        // Run the standalone worker script
        $workerScript = ROOTPATH . 'app/Workers/RequestConsumer.php';
        if (!is_file($workerScript)) {
            CLI::error("Worker script not found: {$workerScript}");
            return 1;
        }

        $listenerCmd = PHP_BINARY . ' ' . $workerScript;

        CLI::write('Launching queue listener...', 'green');
        CLI::write($listenerCmd, 'yellow');
        CLI::newLine();

        passthru($listenerCmd, $exitCode);

        if ($exitCode !== 0) {
            CLI::error("Worker exited with code {$exitCode}");
        }

        return $exitCode;
    }
}
