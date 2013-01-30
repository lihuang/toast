<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Lavg Monitor
 * 
 * @package application.models.moniter
 */
class LavgMonitor extends MachineMonitor
{
    public $group = 'load';
    public $gmetrics = array(
        'one_min' => 'one_min',
        'five_min' => 'five_min',
        'fifteen_min' => 'fifteen_min'
    );
    public $colors = array(
        'one_min' => 'f6090f',
        'five_min' => '4f9af5',
        'fifteen_min' => '55a255',
    );
    public $types = array(
        'one_min'  => 'LINE1',
        'five_min'  => 'LINE1',
        'fifteen_min' => 'LINE1'
    );
    public $vertical = '\'Load Average_processe number, N\'';
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
?>