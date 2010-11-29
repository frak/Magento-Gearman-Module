<?php

class ObserverTest extends Ibuildings_Mage_Test_PHPUnit_ControllerTestCase
{
    public function testDispatchTask()
    {
        $ps = explode(PHP_EOL, `ps ax | grep test_worker`);
        $found = false;
        foreach ($ps as $line) {
            if (preg_match('/php test_worker\.php/', $line)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $id         = uniqid();
        $data       = 'This is a string!';
        $e          = array();
        $e['queue'] = 'test';
        $e['task']  = array(
            'id' => $id,
            'payload' => $data,
            'callback' => 'http://magento.development.local/index.php'
        );
        Mage::dispatchEvent('gearman_do_async_task', $e);
        sleep(1);
        $log = explode(PHP_EOL, file_get_contents('./testing.log'));
        $res = preg_match(
            '/' . $id . ' \- ' . $data . '/',
            $log[count($log) - 2]
        );
        $this->assertEquals(1, $res);
    }
}