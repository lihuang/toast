<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Process Monitor
 *
 * @package application.models.moniter
 */
class ProcessMonitor extends MachineMonitor
{
    public $gmetrics = array(
        'cpu' => 'cpu',
        'mem' => 'mem',
    );
    public $colors = array(
        'cpu' => 'f6090f',
        'mem' => '4f9af5',
    );
    public $types = array(
        'cpu' => 'LINE1',
        'mem' => 'LINE1',
    );
    public $vertical = '\'Process utilization, percentage\'';

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
?>