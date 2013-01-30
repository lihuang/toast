<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Job Model
 * 
 * @package application.models
 */
class Job extends Model
{
    public $id;
    public $task_id;
    public $command_id;
    public $machine_id;
    public $stage_num;
    public $timeout = 1440; //24*60 24hours
    public $sudoer = 'root';
    public $status = self::STATUS_AVAILABLE;
    public $maxStageNum;
    public $failed_repeat = 0;
    public $test_case_ids = array();
    
    /**
     * The type of job, means what the job contained.
     * @var integer 
     */
    public $type = self::TYPE_COMMAND;
    
    /*
     * @var bool if this jobs is crucial, default to true
     */
    public $crucial = 1;

    const STATUS_AVAILABLE = 1;
    const STATUS_DISABLE = 0;

    const TYPE_COMMAND = 0;
    const TYPE_CASE = 1;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'job';
    }

    /**
     * Get the validation rules.
     * 
     * @return array 
     */
    public function rules()
    {
        return array(
            array('machine_id, stage_num, sudoer, timeout, crucial', 'required'),
            array('command_id, task_id, stage_num, timeout, crucial, failed_repeat',
                'numerical', 'integerOnly' => true),
            array('type, machine_id, command_id, task_id, stage_num, timeout, 
                sudoer, crucial, failed_repeat, test_case_ids', 'safe'),
            array('command_id', 'typeValidator'),
        );
    }

    public function typeValidator($attribute, $params)
    {
        if((self::TYPE_COMMAND == $this->type && empty($this->command_id)))
        {
            $this->addError($attribute, Yii::t('Job', '{label} should not be empty',
                    array('{label}' => $this->getAttributeLabel($attribute))));
        }
        else if((self::TYPE_CASE == $this->type) && empty($this->test_case_ids))
        {
            if(empty($this->testcases))
            {
                $this->addError('test_case_ids', Yii::t('Job', 'Test case should not be empty'));                
            }
        }
    }

    /**
     * Get the relations rules.
     * 
     * @return array
     */
    public function relations()
    {
        return array(
            'task' => array(self::BELONGS_TO, 'Task', 'task_id'),
            'machine' => array(self::BELONGS_TO, 'Machine', 'machine_id'),
            'vmachine' => array(self::BELONGS_TO, 'VMachine', 'machine_id'),
            'command' => array(self::BELONGS_TO, 'Command', 'command_id'),
            'vcommand' => array(self::BELONGS_TO, 'VCommand', 'command_id'),
            'testcases' => array(self::MANY_MANY, 'TestCase', 'job_test_case(job_id, test_case_id)', 'order' => 'display_order'),
            'vtestcases' => array(self::MANY_MANY, 'VTestCase', 'job_test_case(job_id, test_case_id)', 'order' => 'display_order'),
        );
    }

    /**
     * Get the attribute labels.
     * 
     * @return array
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Command', 'Id'),
            'command_id' => Yii::t('VRun', 'Command Name'),
            'machine_id' => Yii::t('Command', 'Machine'),
            'timeout' => Yii::t('Command', 'Timeout'),
            'sudoer' => Yii::t('Command', 'Sudoer'),
            'crucial' => Yii::t('Command', 'Crucial'),
            'failed_repeat' => Yii::t('Command', 'Failed Repeat')
        );
    }

    /**
     * Set created_by, updated_by, create_time, update_time.
     * 
     * @return boolean 
     */
    protected function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
            $this->created_by  = $this->updated_by  = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
            $this->updated_by  = Yii::app()->user->id;
        }
        
        return parent::beforeSave();
    }
    
    public function getDiffIgnoreAttributes()
    {
        return array_merge(parent::getDiffIgnoreAttributes(), array('task_id'));
    }
    
    /**
     * get the task ids which command run on the machine
     */
    public function getTaskIdsByMachine($machineId)
    {
        $sql = 'SELECT id FROM `' . Task::model()->tableName()
                . '` WHERE `status` = ' . Task::STATUS_AVAILABLE
                . ' AND `id` IN (SELECT DISTINCT task_id FROM `' . Job::model()->tableName()
                . '` WHERE `status` = ' . Job::STATUS_AVAILABLE . ' AND `machine_id` = ' . $machineId . ')';
        $taskIds = Yii::app()->db->createCommand($sql)->queryColumn();
        return $taskIds;
    }

    /**
     * get the task ids which command run on the machine
     */
    public function getTaskIdsByCommand($commandId)
    {
        $sql = 'SELECT id FROM `' . Task::model()->tableName()
                . '` WHERE `status` = ' . Task::STATUS_AVAILABLE
                . ' AND `id` IN (SELECT DISTINCT task_id FROM `' . Job::model()->tableName()
                . '` WHERE `status` = ' . Job::STATUS_AVAILABLE . ' AND `command_id` = ' . $commandId . ')';
        $taskIds = Yii::app()->db->createCommand($sql)->queryColumn();
        return $taskIds;
    }
    
    /**
     * check if this job is a crucial job.
     * @return type bool
     */
    public function isCrucial()
    {
        return $this->crucial;
    }
    
    /**
     * get the options of crucial
     * @return type 
     */
    public function getCrucialOptions()
    {
        return array(
            Yii::t('Command', 'Noncrucial Command'),
            Yii::t('Command', 'Crucial Command'),
        );
    }
    
    /**
     *
     * @return type 
     */
    public function getCrucialText()
    {
        $crucialOptions = $this->getCrucialOptions();
        return isset($crucialOptions[$this->crucial]) ? $crucialOptions[$this->crucial] : '';
    }
    
    public function getMakeToolOpt()
    {
        return array(
            '' => 'C/C++',
            '-M' => 'Java',
            '--python' => 'Python',
            '--php' => 'PHP',
            '--perl' => 'Perl',
            '--shell' => 'Shell',
        );
    }
    
    public function getComparisonToolOpt()
    {
        return array(
            '/home/a/bin/frontcompare' => 'frontcompare',
            '/home/algoqa/diff/algo_superdiff/algo_superdiff.pl' => 'algo_superdiff',
            '/home/algoqa/diff/hdfs_diff/bin/run.sh' => 'hdfsdiff',
        );
    }
}
?>