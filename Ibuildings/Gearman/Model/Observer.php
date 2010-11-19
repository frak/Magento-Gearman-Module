<?php
/**
 * Ibuildings Gearman Magento Model Observer
 *
 * @copyright (c) 2010 Ibuildings UK Ltd.
 * @author Michael Davey
 * @version 0.1.0
 * @package Ibuildings
 * @subpackage Gearman
 */
class Ibuildings_Gearman_Model_Observer
{
    /**
     * Send the job to the model
     *
     * Takes the event and send it through to the model object
     * for processing
     *
     * @param array $event Array containing the 'queue' name and the job 'workload'
     */
    public function dispatchTask($event)
    {
        $queue = Mage::getModel('Ibuildings_Gearman_Model_Queue');
        $queue->dispatchTask($event);
    }
}