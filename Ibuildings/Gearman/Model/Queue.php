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
class Ibuildings_Gearman_Model_Queue extends Mage_Core_Model_Abstract
{
    /**
     * Reference to the queue object for sending jobs and fetching status
     * @var GearmanClient
     */
    private $_client;

    /**
     * Constructor
     *
     * Reads the server details from config and creates the GearmanClient
     * object and connects to the queue server
     */
    public function __construct()
    {
        $opts = Mage::getStoreConfig('gearman_options');
        $this->_client = new GearmanClient();
        $this->_client->addServer(
            $opts['gearman']['server'],
            $opts['gearman']['port']
        );
    }

    /**
     * Send the job to the queue specified
     *
     * @param array $task Array containing the 'queue' name and the job 'workload'
     * @return string The Gearman ID for the submitted task
     */
    public function dispatchTask($task)
    {
        return $this->_client->doBackground(
            $task['queue'],
            serialize($task['task'])
        );
    }
    
    /**
     * Check the status of a previously submitted job
     *
     * @param string $id The unique Gearman job ID
     * @return boolean Whether task is complete or not
     */
    public function checkTaskComplete($id)
    {
        $status = $this->_client->jobStatus($id);
        return !$status[0];
    }
}