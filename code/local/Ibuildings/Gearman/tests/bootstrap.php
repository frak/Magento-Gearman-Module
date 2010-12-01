<?php
$app_path = realpath(
    dirname(__FILE__) . '/../../../../../../app'
);
if (!$app_path) {
    $app_path = '/mnt/hgfs/Sites/magento.development.local/public/app';
}

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', $app_path);
    
defined('LOG_PATH')
    || define('LOG_PATH', '/mnt/hgfs/Sites/magento.development.local/public/app/code/local/Ibuildings/Gearman/tests/');
    
defined('TEST')
    || define('TEST', true);

    
require_once APPLICATION_PATH.'/Mage.php';
