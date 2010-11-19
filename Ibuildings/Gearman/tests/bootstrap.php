<?php

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(
        dirname(__FILE__) . '/../../../../../../app'
    )
);
    
require_once APPLICATION_PATH.'/Mage.php';