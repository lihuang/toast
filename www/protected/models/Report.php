<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Report Model
 * 
 * @package application.models
 */
class Report extends Model
{
    public $id;
    public $task_run_id;
    public $year;
    public $month;
    public $week;
    public $date;
    public $case_total_amount = 0;
    public $case_pass_amount = 0;
    public $case_fail_amount = 0;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function tableName()
    {
        return 'report';
    }

    public function rules()
    {
        return array(
            array('task_run_id, date', 'required'),
        );
    }

    public function relations()
    {
        return array(
            'taskrun' => array(self::BELONGS_TO, 'TaskRun', 'task_run_id'),
        );
    }
    
    protected function beforeSave()
    {
        $this->syncDate();
        return parent::beforeSave();
    }

    public function syncDate()
    {
        $this->year = date('Y', strtotime($this->date));
        $this->month = date('m', strtotime($this->date));
        $this->week = date('W', strtotime($this->date));
    }
}
?>