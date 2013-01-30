<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Mem Monitor
 *
 * @package application.models.moniter
 */
class MemMonitor extends MachineMonitor
{
    public $group = 'memory';
    public $gmetrics = array(
        'total' => 'total',
        'free' => 'free',
//        'swapfree' => 'swapfree',
//        'swaptotal' => 'swaptotal'
    );
    public $colors = array(
        'total' => '4f9af5',
        'free' => 'f6090f',
//        'swapfree' => '55a255',
//        'swaptotal' => 'ffa500',
    );
    public $types = array(
        'total' => 'LINE1',
        'free' => 'LINE1',
//        'swapfree' => 'LINE1',
//        'swaptotal' => 'LINE1',
    );
    public $vertical = '\'kB\'';
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
?>