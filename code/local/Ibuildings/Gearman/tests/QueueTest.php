<?php

class QueueTest extends Ibuildings_Mage_Test_PHPUnit_ControllerTestCase
{
    private $_queue;
    
    public function setUp()
    {
        $this->mageBootstrap();
        $this->_queue = Mage::getModel('Ibuildings_Gearman_Model_Queue');
    }
    
    public function getTask()
    {
        $t = array();
        $t['queue']    = 'test';
        $t['task']     = array(
            'id'       => 1234,
            'payload'  => 'This is a string!',
            'callback' => 'http://magento.development.local/index.php'
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
}