<?php

class QueueTest extends Ibuildings_Mage_Test_PHPUnit_ControllerTestCase
{
    private $_queue;
    const BASE_URL = 'http://magento.development.local';
    
    public function setUp()
    {
        $this->mageBootstrap();
        $this->_queue = Mage::getModel('Ibuildings_Gearman_Model_Queue');
        $opts = array(
            'gearman' => array(
                'server' => '127.0.0.1',
                'port' => 4730
            )
        );
        $this->_queue->setGearmanClient($opts);

        $ps = explode(PHP_EOL, `ps ax | grep test_worker`);
        $found = false;
        foreach ($ps as $line) {
            if (preg_match('/test_worker\.php/', $line)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->fail(
                'You need to have started test_worker.php from the ' .
                'tests directory'
            );
        }
        $this->assertTrue($found);
    }

    public function getQueue($type = '')
    {
        $opts = array(
            'gearman' => array(
                'server' => '127.0.0.1',
                'port' => 4730,
                'type' => $type)
        );
        $this->_queue->setGearmanClient($opts);
    }
    
    public function getTask()
    {
        $t = array();
        $t['queue']    = 'test';
        $t['task']     = array(
            'id'       => uniqid(),
            'payload'  => 'This is a string!',
            'callback' => self::BASE_URL . '/index.php'
        );
        return $t;
    }
    
    public function testSubmitJobReturnsId()
    {
        $this->getQueue('ext');
        $this->assertType(
            'GearmanClient',
            $this->_queue->getGearmanClient()
        );
        $id = $this->_queue->dispatchTask($this->getTask());
        $this->assertTrue(
            preg_match('/[A-Z]+\:[A-z\-_0-9]+\:[0-9]+/', $id) > 0
        );
        $this->getQueue('net');
        $this->assertType(
            'Net_Gearman_Client',
            $this->_queue->getGearmanClient()
        );
        $id = $this->_queue->dispatchTask($this->getTask());
        $this->assertNull($id);
    }

    public function testCheckTaskCompleteReturnsTrueWhenDone()
    {
        $this->getQueue();
        $id = $this->_queue->dispatchTask($this->getTask());
        do {
            $ret = $this->_queue->checkTaskComplete($id);
            sleep(1);
        }
        while (!$ret);
        $this->assertTrue($ret);
        $this->getQueue('net');
        $id = $this->_queue->dispatchTask($this->getTask());
        $this->assertNull($id);
        $this->assertNull(
            $this->_queue->checkTaskComplete($id)
        );
    }

    public function testCheckJobStatusReturnsTheRightValues()
    {
        $this->getQueue();
        $id = $this->_queue->dispatchTask($this->getTask());
        do {
            $ret = $this->_queue->checkJobStatus($id);
            if ($ret !== 'done' && $ret !== 'queued' && $ret !== 'working') {
                $this->assertGreaterThanOrEqual(0, $ret);
                $this->assertLessThanOrEqual(100, $ret);
            }
            sleep(1);
        }
        while ($ret !== 'done');
        $this->assertTrue($ret === 'done');
        $this->getQueue('net');
        $id = $this->_queue->dispatchTask($this->getTask());
        $this->assertNull($id);
        $this->assertNull(
            $this->_queue->checkJobStatus($id)
        );
    }

    public function testBlockingTaskReturnsResults()
    {
        $this->getQueue();
        $ret = $this->_queue->blockingCall($this->getTask());
        $this->assertTrue($ret['payload'] == '!gnirts a si sihT');
        $this->getQueue('net');
        $ret = $this->_queue->blockingCall($this->getTask());
        $this->assertNull($ret);
    }
    
    public function testJobStatuses()
    {
        $array = array(true, false);
        $ret   = $this->_queue->getJobStatus($array);
        $this->assertEquals('queued', $ret);
        $array = array(true, true, 0, 0);
        $ret   = $this->_queue->getJobStatus($array);
        $this->assertEquals('working', $ret);
        $array = array(true, true, 1, 2);
        $ret   = $this->_queue->getJobStatus($array);
        $this->assertEquals('50', $ret);
        $array = array(false, false);
        $ret   = $this->_queue->getJobStatus($array);
        $this->assertEquals('done', $ret);
    }
    
    public function testSettingLowTimeoutCausesBlockingCallToReturn()
    {
        $this->getQueue();
        $ret = $this->_queue->blockingCall($this->getTask(), 0);
        $this->assertNull($ret);
    }
}