<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Job and TestCase related model.
 *
 * @package application.models
 */
class JobTestCase extends Model
{
    /**
     * @var integer Job Id. 
     */
    public $job_id;
    /**
     * @var integer Test case id. 
     */
    public $test_case_id;
    /**
     * @var integer Display order. 
     */
    public $display_order = 0;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function tableName()
    {
        return 'job_test_case';
    }
    
    public function rules()
    {
        return array(
            array('job_id, test_case_id', 'required'),
            array('display_order', 'numerical', 'integerOnly' => true),
        );
    }
    
    public function relations()
    {
        return array(
            'job' => array(self::HAS_ONE, 'Job', 'job_id'),
            'test_case' => array(self::HAS_ONE, 'TestCase', '')
        );
    }
}
?>