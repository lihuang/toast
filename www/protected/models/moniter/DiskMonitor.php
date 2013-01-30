<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Disk Monitor
 *
 * @package application.models.moniter
 */
class DiskMonitor extends MachineMonitor
{
    public $group = 'disk';
    public $gmetrics = array(
        'read' => 'read',
        'write' => 'write'
    );
    public $colors = array(
        'read' => 'f6090f',
        'write' => '4f9af5',
    );
    public $types = array(
        'read' => 'LINE1',
        'write' => 'LINE1',
    );
    public $vertical = '\'data transfer speed, kB/sec\'';
    
    /**
     * 获取CpuMonitor静态实例
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
?>