<?php

class ObserverTest extends Ibuildings_Mage_Test_PHPUnit_ControllerTestCase
{
    public function testDispatchTask()
    {
        $e = array();
        $e['queue'] = 'test';
        $e['task']  = array(
            'id' => 1234,
            'payload' => 'This is a string!',
            'callback' => 'http://magento.development.local/index.php'
        );
        Mage::dispatchEvent('gearman_do_async_task', $e);
    }
}