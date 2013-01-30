<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
return CMap::mergeArray(
    require(dirname(__FILE__) . '/config.php'),
    array(
        'components' => array(
            'urlManager' => array(
                'urlFormat' => 'path',
                'showScriptName' => true
            ),
            'fixture' => array(
                'class' => 'system.test.CDbFixtureManager',
            ),
            'db' => array(
                'enableProfiling' => true,
                'enableParamLogging' => true,
            ),
            'log' => array(
                'class' => 'CLogRouter',
                'routes' => array(
                    array(
                        'class' => 'application.extensions.yii-debug-toolbar.YiiDebugToolbarRoute',
                        'ipFilters' => array('*'),
                        'levels' => '',
                        'categories' => ''
                    )
                )
            )
        )
    )
);
?>