<?php

if (!class_exists('GearmanClient') || defined('TEST')) {
    require_once 'Net/Gearman/Client.php';
}

/**
 * Ibuildings Gearman Magento Model Observer
 *
 * @copyright (c) 2010 Ibuildings UK Ltd.
 * @author Michael Davey
 * @version 0.1.0
 * @package Ibuildings
 * @subpackage Gearman
 * @license https://github.com/ibuildings/Magento-Gearman-Module/blob/master/LICENCE
 */
class Ibuildings_Gearman_Model_Queue extends Mage_Core_Model_Abstract
{
    /**
     * Reference to the queue object for sending jobs and fetching status
     * @var GearmanClient|Net_Gearman_Client
     */
    private $_client;

    /**
     * Just calls setGearmanClient() for default instantiation
     *
     * @see setGearmanClient()
     */
    public function __construct()
    {
        $this->setGearmanClient();
    }

    /**
     * Sets the Gearman object according to config options
     *
     * Reads the server details from config and creates the GearmanClient
     * object and connects to the queue server
     * @param array $opts Configuration options
     */
    public function setGearmanClient($opts = null)
    {
        if (is_null($opts)) {
            $opts = Mage::getStoreConfig('gearman_options');
        }
        $servers = explode(',', $opts['gearman']['server']);
        $ports   = explode(',', $opts['gearman']['port']);
        $count   = count($servers);
        $onePort = (count($servers) !== count($ports)) ? true : false;
        for ($i = 0; $i < $count; ++$i) {
            $servers[$i] .= ':' . (($onePort) ? $ports[0] : $ports[$i]);
        }

        if (
            class_exists('Net_Gearman_Client') &&
            'net' === $opts['gearman']['type']) {

            $this->_client = new Net_Gearman_Client($servers);
        }
        else {
            $this->_client = new GearmanClient();
            $this->_client->addServers(
                implode(',', $servers)
            );
        }
    }

    /**
     * Returns the current client object used for dispatching messages
     *
     * @return GearmanClient|Net_Gearman_Client The client being used
     */
    public function getGearmanClient()
    {
        return $this->_client;
    }

    /**
     * Send the job to the queue specified
     * <code>
     * $queue = Mage::getModel('gearman/queue');
     * $id = $queue->dispatchTask($task);
     * </code>
     *
     * @param array $task Array containing the 'queue' name and the task
     * @return string|false The ID for the submitted task if the Gearman extension is used
     */
    public function dispatchTask($task)
    {
        if (get_class($this->_client) === 'Net_Gearman_Client') {
            $ngTask = new Net_Gearman_Task(
                $task['queue'],
                array($task['task'])
            );
            $this->_client->submitTask($ngTask);
            // There is no way to query a job status in Net_Gearman
            // presently, so no point in returning this...
            // return $ngTask->handle;
            return null;
        }
        else {
            return $this->_client->doBackground(
                $task['queue'],
                serialize($task['task'])
            );
        }
    }

    /**
     * Check whether a previously submitted job has completed
     * <code>
     * if ($queue->checkTaskComplete($id)) {
     *     // work has been done
     * }
     * </code>
     *
     * @param string $id The unique Gearman job ID
     * @return boolean Whether task is complete or not
     */
    public function checkTaskComplete($jobId)
    {
        if (get_class($this->_client) !== 'Net_Gearman_Client') {
            $status = $this->_client->jobStatus($jobId);
            return !$status[0];
        }
        else {
            return null;
        }
    }

    /**
     * Check the status of a previously submitted job
     * <code>
     * while (($status = $queue->checkJobStatus($id)) !== 'done') {
     *     echo "$status% complete\n";
     *     sleep(1);
     * }
     * </code>
     *
     * @param string $id The unique Gearman job ID
     * @return null|string
     */
     public function checkJobStatus($jobId)
     {
         if (get_class($this->_client) !== 'Net_Gearman_Client') {
             $status = $this->_client->jobStatus($jobId);
             return $this->getJobStatus($status);
         }
         else {
             return null;
         }
     }

     /**
      * Returns the current job status
      *
      * Turns the status array from Gearman into a meaningful status
      * to report back to the client
      *
      * @return string The current status
      */
     public function getJobStatus($status)
     {
         $out = '';
         if ($status[0] && !$status[1]) {
             $out = 'queued';
         }
         else if ($status[0] && $status[1]) {
             if ($status[2] === 0 && $status[3] === 0) {
                 $out = 'working';
             }
             else {
                 $out = ((int) $status[2] / $status[3]) * 100;
             }
         }
         else if (!$status[0] && !$status[1]) {
             $out = 'done';
         }
         return $out;
     }

    /**
     * Calls a Gearman task and waits for it's return value
     * <code>
     * $ret = $queue->blockingCall($task);
     * </code>
     *
     * @return array|null The results from the task
     */
    public function blockingCall($task)
    {
        if (get_class($this->_client) === 'GearmanClient') {
            do {
                $ret = $this->_client->do(
                    $task['queue'],
                    serialize($task['task'])
                );
                $code = $this->_client->returnCode();
            }
            while ($code !== GEARMAN_SUCCESS || $code === GEARMAN_FAILURE);
            return unserialize($ret);
        }
        else {
            return null;
        }
    }
}