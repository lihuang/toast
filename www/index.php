<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

$yii    = dirname(__FILE__) . '/lib/yii.php';
$config = dirname(__FILE__) . '/protected/config/config.php';

require_once($yii);
Yii::createWebApplication($config)->run();
?>
