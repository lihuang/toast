<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Machine Monitor 
 * 
 * @package application.models
 */
class MachineMonitor extends Machine
{
    public $group;
    public $gmetrics;
    public $start;
    public $end;
    public $colors;
    public $vertical;
    public $types;
    public $size = '-w 700 -h 200';
    
    public static $opts = array(
        'cpu' => 'cpu',
        'disk' => 'disk',
        'load' => 'load',
        'memory' => 'memory',
        'network' => 'network',
    );
    
    public static $classNames = array(
        'cpu' => 'CpuMonitor',
        'disk' => 'DiskMonitor',
        'load' => 'LavgMonitor',
        'memory' => 'MemMonitor',
        'network' => 'NetworkMonitor',
    );
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function rules()
    {
        return array(
            array('group, gmetrics, start, end, size', 'safe')
        );
    }    
    
    public function getRRDImg()
    {
        $start = date('Y-m-d H:i:s', $this->start); 
        $end = date('Y-m-d H:i:s', $this->end);
        $title = "-t '{$this->group}: {$start} - {$end}'";

        $cmd = "/usr/bin/rrdtool graph  - --start {$this->start} --end {$this->end} {$this->size} {$title} -v {$this->vertical} --font TITLE:12: "
              ."--font UNIT:9: COMMENT:'                  Current           Avg           Max           Min\\n'";
        
        foreach ($this->gmetrics as $i => $metric)
        {
            $len = 20-strlen($metric);
            $rraPath = Yii::app()->param['rraPath'];
            $cmd .= " DEF:{$metric}={$rraPath}{$this->name}.{$this->group}.rrd:{$metric}:AVERAGE"
                   ." {$this->types[$metric]}:{$metric}#{$this->colors[$metric]}:{$metric}"
                   ." GPRINT:{$metric}:LAST:'%{$len}.2lf'"
                   ." GPRINT:{$metric}:AVERAGE:'%12.2lf'"
                   ." GPRINT:{$metric}:MAX:'%12.2lf'"
                   ." GPRINT:{$metric}:MIN:'%12.2lf\\n'";
        }
        passthru($cmd);
    }
    
    public function getDetailObj()
    {
        $clazz = MachineMonitor::getDetailClass($this->group);
        $monitor = new $clazz();
        $monitor->id = $this->id;
        $monitor->name = $this->name;
        $monitor->start = $this->start;
        $monitor->end = $this->end;
        $monitor->group = $this->group;
        if(!empty($this->gmetrics))
        {
            $monitor->gmetrics = $this->gmetrics;
        }
        $monitor->size = $this->size;
        return $monitor;
    }
    
    public static function getDetailClass($group)
    {
        $clazz = 'ProcessMonitor';
        if(array_key_exists($group, MachineMonitor::$classNames))
        {
            $clazz = MachineMonitor::$classNames[$group];
        }
        return $clazz;
    }
}
?>