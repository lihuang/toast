<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Netin Monitor
 *
 * @package application.models.moniter
 */
class NetworkMonitor extends MachineMonitor
{
    public $group = 'netin';
    public $gmetrics = array(
        'inbytes' => 'inbytes',
        'outbytes' => 'outbytes',
        'inpackets' => 'inpackets',
        'outpackets' => 'outpackets',
    );
    public $colors = array(
        'inbytes' => 'f6090f',
        'outbytes' => 'f6090f',
        'inpackets' => '4f9af5',
        'outpackets' => '4f9af5'
    );
    public $types = array(
        'inbytes' => 'LINE1',
        'outbytes' => 'LINE1',
        'inpackets' => 'LINE1',
        'outpackets' => 'LINE1'
    );
    public $vertical = '\'B/sec\'';
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
?>