<?php
$yiit   = dirname(__FILE__).'/../../lib/yiit.php';
$config = dirname(__FILE__).'/../config/config.debug.php';

require_once($yiit);
require_once(dirname(__FILE__).'/WebTestCase.php');

Yii::createWebApplication($config);
