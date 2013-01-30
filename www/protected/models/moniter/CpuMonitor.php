<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Cpu Monitor
 *
 * @package application.models.moniter
 */
class CpuMonitor extends MachineMonitor
{
    public $group = 'cpu';
    public $gmetrics = array(
        'user' => 'user',
        'system' => 'system',
        'idle' => 'idle',
//        'nice' => 'nice'
    );
    public $colors = array(
        'user' => 'f6090f',
        'system' => '4f9af5',
        'idle' => '55a255',
//        'nice' => 'ffa500',
    );
    public $types = array(
        'user' => 'LINE1',
        'system' => 'LINE1',
        'idle' => 'LINE1',
//        'nice' => 'LINE1',
    );
    public $vertical = '\'cpu utilization, percentage\'';
    
    /**
     * 获取CpuMonitor静态实例
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
?>