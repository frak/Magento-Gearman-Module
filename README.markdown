Ibuildings Gearman Module
=========================

Pre-requisites
---------------
To be able to use this module, you will need to install the Gearman library
and the PHP Gearman classes.  This is done as follows:

    $ sudo apt-get install libgearman-dev
    $ sudo pecl install gearman
    
Alternatively, and not as preferable, you can use the Net_Gearman classes
available on PEAR, however there is reduced functionality when using this. To
install the classes you will need only to run the following:

    $ sudo pear install Net_Gearman

You will also need to ensure that your PEAR directory is in your PHP
include_path.  

Furthermore, if you wish to have the gearman server running locally, you will
need to install the gearman job server:

    $ sudo apt-get install gearman-job-server

Using the module
-----------------
Once you have installed the module, you will need to configure it so that it
knows where how to connect to your job server(s).  This is done view the Configuration section of the Magento administration section.

There are two ways in which the module can be used, either by simply firing an
event (in which case you will not be able to track the job after it has been
sent), like so:

    $event = array();
    $event['queue'] = 'test';
    $event['task']  = array(
        'id'       => 1234,
        'payload'  => 'This is a string!',
        'callback' => 'http://some.server.com/stuff_was_done.php'
    );
    Mage::dispatchEvent('gearman_do_async_task', $event);

If, however, you wish to be able to query the server for the status of your
submitted task, you will need to instantiate the queue directly (please note
that this is not available if you used the Net_Gearman classes rather than the
Gearman extension):

    $queue = Mage::getModel('gearman/queue');
    $task = array();
    $task['queue']    = 'test';
    $task['task']     = array(
        'id'         => 1234,
        'payload'    => 'This is a string!'
    );
    $id = $queue->dispatchTask($task);
    
    // If you so desire you can halt execution until work is done,
    // or simply send the job ID to the client for Ajax polling, etc.
    do {
        $ret = $queue->checkTaskComplete($id);
        sleep(1);
    }
    while (!$ret);

For the event/task array, the 'queue' key is the name of the job queue
function that you wish to have process your task.  If you omit, or mis-spell
this, no work will be done - or worse, work will be done by the wrong queue!  The 'task' item is what will be sent to the worker at the other end of the
queue and may contain any arbitrary data.  In the  examples given above, there
is an ID, some data and an optional callback URI, however, this is entirely up
to the specifics of your implementation.

An example, if not very functional, worker is shown below as an demo of how
you might go about implementing a simple standalone worker based on using the Gearman extension and not the Net_Gearman classes:

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
    }

    function test_fn($job)
    {
        $task = unserialize($job->workload());
        $task['payload'] = strrev($task['payload']);
        if (isset($task['callback'])) {
            $task['result'] = file_get_contents($task['callback']);
        }
        return serialize($task);
    }

Should you prefer to call a function in an object, statically of course, then
you need to pass an array into the addFunction() method as follows:

    $worker->addFunction('test', array('MyStaticClass', 'workerMethod'));

Unit Tests
----------
In order to be able to run the Unit tests for this module, you will also need 
to have installed and setup the Ibuildings Mage_Test module, available on 
[GitHub](/ibuildings/Mage_Test).  Once you have this setup, you will need to
change the BASE_URL constant in QueueTest.php to reflect your development
server.