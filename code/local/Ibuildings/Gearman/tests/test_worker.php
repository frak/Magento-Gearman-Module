<?php

$worker = new GearmanWorker();
$worker->addServer('127.0.0.1', 4730);
$worker->addFunction('test', 'test_fn');

echo "Waiting for work...\n";
while ($worker->work()) {
    if ($worker->returnCode() !== GEARMAN_SUCCESS) {
        echo "Ooops: " . $worker->returnCode() . "\n";
        break;
    }
    echo "\tWork done!\n";
}

function test_fn($job)
{
    $job->sendStatus(0, 2);
    $stuff = unserialize($job->workload());
    $stuff['payload'] = strrev($stuff['payload']);
    sleep(1);
    $job->sendStatus(1, 2);
    if (isset($stuff['callback'])) {
        $stuff['result'] = file_get_contents($stuff['callback']);
        $job->sendStatus(2, 2);
        sleep(1);
    }
    return serialize($stuff);
}
