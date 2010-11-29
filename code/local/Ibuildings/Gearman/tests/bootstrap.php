<?php
$app_path = realpath(
    dirname(__FILE__) . '/../../../../../../app'
);
if (!$app_path) {
    $app_path = '/mnt/hgfs/Sites/magento.development.local/public/app';
}

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', $app_path);
    
require_once APPLICATION_PATH.'/Mage.php';
