<?php

require_once './bootstrap.php';

@unlink(LOG_PATH . 'gearman_testing.log');

$worker = new GearmanWorker();
$worker->addServer('127.0.0.1', 4730);
$worker->addFunction('test', 'test_fn');

echo "Waiting for work...\n";
while ($worker->work()) {
    if ($worker->returnCode() !== GEARMAN_SUCCESS) {
        echo 'Ooops: ' . $worker->returnCode() . PHP_EOL;
        break;
    }
    echo "\tWork done!\n";
}

function test_fn($job)
{
    $job->sendStatus(0, 0);
    $stuff = unserialize($job->workload());
    file_put_contents(
        LOG_PATH . 'gearman_testing.log',
        $stuff['id'] . ' - ' . $stuff['payload'] . PHP_EOL,
        FILE_APPEND
    );
    sleep(1);
    $job->sendStatus(0, 2);
    $stuff['payload'] = strrev($stuff['payload']);
    sleep(1);
    $job->sendStatus(1, 2);
    if (isset($stuff['callback'])) {
        $stuff['result'] = file_get_contents($stuff['callback']);
    }
    $job->sendStatus(2, 2);
    sleep(1);
    return serialize($stuff);
}

function quick_test_fn($job)
{
    $stuff = unserialize($job->workload());
    file_put_contents(
        LOG_PATH . 'gearman_testing.log',
        $stuff['id'] . ' - ' . $stuff['payload'] . PHP_EOL,
        FILE_APPEND
    );
    $stuff['payload'] = strrev($stuff['payload']);
    return serialize($stuff);
}