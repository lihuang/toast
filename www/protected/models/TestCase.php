<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Test Case Model.
 * 
 * @package application.models
 */
class TestCase extends Model
{
    /**
     * The case id.
     * @var integer 
     */
    public $id;
    /**
     * The case name.
     * @var string
     */
    public $name;
    /**
     * The source code url.
     * @var string
     */
    public $code_url;
    /**
     * The test case's func name.
     * @var string
     */
    public $func_name;
    /**
     * The case run in which framework
     * @var integer
     */
    public $framework;
    /**
     * The information of case.
     * @var string
     */
    public $info;
    /**
     * The project id of case.
     * @var integer
     */
    public $project_id;
    /**
     * The created user id.
     * @var integer
     */
    public $created_by;
    /**
     * The updated user id.
     * @var integer
     */
    public $updated_by;
    /**
     * The created datetime.
     * @var string 
     */
    public $create_time;
    /**
     * The updated datetime.
     * @var string
     */
    public $update_time;
    /**
     * The status of test case.
     * @var integer
     */
    public $status;
    
    /**
     * DISABLE 
     */
    const STATUS_DISABLE = 0;
    /**
     * AVALIABLE 
     */ 
   const STATUS_AVAILABLE = 1;

    /**
     * Get a instance.
     * Just implement parent model function.
     *  
     * @param string $className
     * @return TestCase $model
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Get this model's table name in database. 
     * 
     * @return string $tableName
     */
    public function tableName()
    {
        return 'test_case';
    }

    /**
     * Get the validation rule array.
     * 
     * @return array $rules 
     */
    public function rules()
    {
        return array(
            array('name, project_id, code_url, framework', 'required'),
            array('name, code_url, func_name', 'filter', 'filter' => 'trim'),
            array('code_url', 'url'),
            array('name, code_url, func_name, project_Id, info, status', 'safe'),
            array('project_id, status', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => '255', 'min' => '3')
        );
    }

    /**
     * Set action user and datetime before save.
     * 
     * @return boolean $flag 
     */
    public function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
            $this->created_by = $this->updated_by = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
            $this->updated_by = Yii::app()->user->id;
        }

        return parent::beforeSave();
    }

    /**
     * Get the relation array.
     * 
     * @return array $relations 
     */
    public function relations()
    {
        return array(
            'project' => array(self::BELONGS_TO, 'Project', 'project_id'),
            'parser' => array(self::BELONGS_TO, 'Parser', 'framework'),
            'jobs' => array(self::MANY_MANY, 'Job', 'job_test_case(job_id, test_case_id)',
                'on' => '`jobs_jobs`.`status` = :status',
                'params' => array(':status' => Job::STATUS_AVAILABLE),
            ),
        );
    }

    /**
     * Get attribute labels
     * @return array $label
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('TestCase', 'ID'),
            'name' => Yii::t('TestCase', 'Name'),
            'code_url' => Yii::t('TestCase', 'Code URL'),
            'func_name' => Yii::t('TestCase', 'Function Name'),
            'framework' => Yii::t('TestCase', 'Framework'),
            'project_id' => Yii::t('TestCase', 'Project Name'),
            'info' => Yii::t('TestCase', 'Inforamtion'),
            'created_by' => Yii::t('TestCase', 'Created By'),
            'updated_by' => Yii::t('TestCase', 'Updated By'),
            'create_time' => Yii::t('TestCase', 'Create Time'),
            'update_time' => Yii::t('TestCase', 'Update Time'),
        );
    }

    public function getFrameworkOpts()
    {
        $condition = new CDbCriteria();
        $condition->compare('name', 'JUnit-mvn');
        $parsers = Parser::model()->findAll($condition);
        $opts = array();
        foreach($parsers as $parser)
        {
            $opts[$parser->id] = $parser->name;
        }
        return $opts;
    }

    public function getFrameworkText()
    {
        $text = '';
        switch($this->parser->name)
        {
            case 'JUnit-mvn': {
                $text = 'mvn';
                break;
            }
            default: {
                break;
            }
        }
        return $text;
    }

    public function addResult($case_result, $created_by = null,
            $case_info = null, $command_run_id = null)
    {
        CaseResult::model()->updateAll(array('is_last' => false),
                'test_case_id = :test_case_id', array(':test_case_id' => $this->id));

        $result = new CaseResult();
        $result->test_case_id = $this->id;
        $result->case_name = $this->name;
        $result->case_result = $case_result;
        $result->case_info = $case_info;
        $result->command_run_id = $command_run_id;
        $result->created_by = $created_by;
        $result->is_last = true;

        return $result->save();
    }

    /**
     * Delete case, set case status as STATUS_DISABLE
     * @return boolean $flag 
     */
    public function delete()
    {
        $flag = false;
        if(!$this->isNewRecord)
        {
            $this->status = self::STATUS_DISABLE;
            $flag = $this->save();
        }

        return $flag;
    }
    
    /**
     * Return navigator items.
     * @return array Items
     */
    public function getNavItems()
    {
        $items = array();
        if($this->isNewRecord)
        {
            $items[] = array('label' => Yii::t('TestCase', 'New Case'));
        }
        else
        {
            $items[] = array('label' => '#' . $this->id . ' ' . $this->name, 'url' => array('/case/view/id/' . $this->id));
            $items[] = array('label' => Yii::t('TestCase', 'Update Case'));
        }
        return $items;
    }
    
    public function getCommand()
    {
        $command = '/home/ads/runcase/run_case -t ' . strtolower($this->getFrameworkText())
                          . ' -u ' . $this->code_url . ' -c ' . $this->id;
        if(!empty($this->func_name))
        {
            $command .= ' -f ' . $this->func_name;
        }
        return $command;
    }
}
?>