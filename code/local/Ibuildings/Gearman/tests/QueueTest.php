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
            'gearman' => array('server' => '127.0.0.1', 'port' => 4730)
        );
        $this->_queue->setGearmanClient($opts);
    }
    
    public function getTask()
    {
        $t = array();
        $t['queue']    = 'test';
        $t['task']     = array(
            'id'       => 1234,
            'payload'  => 'This is a string!',
            'callback' => self::BASE_URL . '/index.php'
        );
        return $t;
    }
    
    public function testSubmitJobReturnsId()
    {
        $id = $this->_queue->dispatchTask($this->getTask());
        // Kludge added for Net_Gearman case...
        if (!is_null($id)) {
            $this->assertTrue(preg_match('/[A-Z]+\:[A-z\-_0-9]+\:[0-9]+/', $id) > 0);
        }
    }

    public function testCheckTaskCompleteReturnsTrueWhenDone()
    {
        $id = $this->_queue->dispatchTask($this->getTask());
        if (!is_null($id)) {
            do {
                $ret = $this->_queue->checkTaskComplete($id);
                sleep(1);
            }
            while (!$ret);
            $this->assertTrue($ret);
        }
    }

    public function testCheckJobStatusReturnsTheRightValues()
    {
        $id = $this->_queue->dispatchTask($this->getTask());
        if (!is_null($id)) {
            do {
                $ret = $this->_queue->checkJobStatus($id);
                if ($ret !== 'done' && $ret !== 'queued') {
                    $this->assertGreaterThanOrEqual(0, $ret);
                    $this->assertLessThanOrEqual(100, $ret);
                }
                sleep(1);
            }
            while ($ret !== 'done');
            $this->assertTrue($ret === 'done');
        }
    }

    public function testBlockingTaskReturnsResults()
    {
        $ret = $this->_queue->blockingCall($this->getTask());
        if (!class_exists('GearmanClient')) {
            /*
            $this->markTestIncomplete(
                'This test only applies to the Gearman extension'
            );
            */
            $this->assertNull($ret);
        }
        else{
            print_r($ret);
            $this->assertTrue($ret['payload'] == '!gnirts a si sihT');
        }
    }
}