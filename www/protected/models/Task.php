<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Task Model
 * 
 * @package application.models
 */
class Task extends Model
{
    public $id;
    public $name;
    public $cron_time; // auto run every day at midnight
    public $type;
    public $responsible;
    public $report_filter = self::REPORT_ALL;
    public $report_to;
    public $exclusive = 1;
    public $wait_machine = 0;
    public $status;
    public $build;
    public $svn_url;
    public $createed_by;
    public $updated_by;
    public $create_time;
    public $update_time;
    public $project_id;
    public $responsible_realname;

    const STATUS_AVAILABLE = 1;
    const STATUS_DISABLE = 0;

    const ACTION_ADD = 'Add';
    const ACTION_DEL = 'Del';
    const ACTION_UPDATE = 'Update';
    
    const MONITOR_ACTION_TYPE = 'CI';
    const TIMER_ACTION_TYPE = 'TimerTask';
    
    const TYPE_UNIT = 1;
    const TYPE_BVT = 2;
    const TYPE_REGRESS = 3;
    const TYPE_SYSTEM = 4;
    const TYPE_CI = 6; //ContinuousIntegration
    
    const REPORT_NONE = 0;
    const REPORT_FAIL = 1;
    const REPORT_ALL = 2;
    
    const WAITING_SIZE = 5;

    public static $protocolName = array(
        self::TYPE_UNIT => 'Unit',
        self::TYPE_BVT => 'BVT',
        self::TYPE_REGRESS => 'Regress',
        self::TYPE_SYSTEM => 'System',
        self::TYPE_CI => 'ContinuousIntegration',
    );
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function tableName()
    {
        return 'task';
    }

    public function rules()
    {
        return array(
            array('name, project_id, type, responsible', 'required'),
            array('project_id, type, status, responsible', 'numerical', 'integerOnly' => true),
            array('id, name, project_id, type, report_filter, report_to, exclusive, wait_machine, responsible, cron_time, 
                created_by, updated_by, create_time, update_time, build, svn_url, responsible_realname', 'safe'),
            array('name', 'length', 'max' => 128),
            array('name, svn_url', 'filter', 'filter'=>'trim'),
            array('svn_url', 'url'),
            array('report_to', 'length', 'max' => 255),
            //array('cron_time', 'timeValidator')
        );
    }

    public function timeValidator($attribute,$params)
    {
        if(!empty($this->cron_time))
        {
            if(!(preg_match("/^[0-1][0-9]|2[0-3]:[0-5][0-9]:[0-5][0-9]/i", $this->cron_time)))
            {
                $this->addError('cron_time', Yii::t('Task', '{field} format error, should be {format}',
                        array('{field}' => $this->getAttributeLabel('cron_time'), '{format}' => 'hh:mm:ss')));
            }
        }
        else
        {
            unset($this->cron_time);
        }
    }

    public function relations()
    {
        return array(
            'taskruns' => array(self::HAS_MANY, 'TaskRun', 'task_id'),
            'jobs' => array(self::HAS_MANY, 'Job', 'task_id', 
                'order'=>'stage_num ASC',
                'condition' => 'status=' . Job::STATUS_AVAILABLE),
            'project' => array(self::BELONGS_TO, 'Project', 'project_id'),
            'vtask' => array(self::HAS_ONE, 'VTask', 'id')
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Task', 'Id'),
            'name' => Yii::t('Task','Name'),
            'project_id' => Yii::t('Task','Project Id'),
            'project_path' => Yii::t('Task', 'Project Path'),
            'report_filter' => Yii::t('Task', 'Report Filter'),
            'report_to' => Yii::t('Task','Report To'),
            'exclusive' => Yii::t('Task', 'Exclusive'),
            'wait_machine' => Yii::t('Task', 'Wait Machine'),
            'cron_time' => Yii::t('Task', 'Scheme Time'),
            'commands' => Yii::t('Task','Command'),
            'type' => Yii::t('Task','Type'),
            'build' => Yii::t('Task', 'Build'),
            'svn_url' => Yii::t('Task', 'SVN URL'),
            'responsible' => Yii::t('Task', 'Responsible'),
            'created_by' => Yii::t('Task', 'Created By'),
            'updated_by' => Yii::t('Task', 'Updated By'),
            'create_time' => Yii::t('Task','Create Time'),
            'update_time' => Yii::t('Task','Update Time')
        );
    }

    protected function beforeSave()
    {
        $this->report_to = TString::arrangeSplit($this->report_to);
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

    public function getTypeOptions($hasBlank = FALSE)
    {
        $opts = $hasBlank ? array('' => '') : array();
        $opts[self::TYPE_UNIT] = Yii::t('Task', 'Unit Test Task');
        $opts[self::TYPE_REGRESS] = Yii::t('Task', 'Regression Test Task');
        $opts[self::TYPE_CI] = Yii::t('Task', 'Continuous Integration');
        return $opts;
    }
    
    public function getTypeText()
    {
        $types= $this->getTypeOptions();
        return isset($types[$this->type])
               ? $types[$this->type]
               : Yii::t('Task', 'Unknown Type({type})', array('{type}' => $this->type));
    }

    public function getProtocolName($type = null)
    {
        if(isset($this->type) && empty($type))
        {
            $type = $this->type;
        }
        $protocol = '';
        if(isset(self::$protocolName[$type]))
        {
            $protocol = self::$protocolName[$type];
        }
        return $protocol;
    }
    
    public function sendAction($action, $type = Task::TIMER_ACTION_TYPE)
    {
        $config = array();
        $config['TestType'] = $type;
        $config['RunID']    = '0';

        $actions = array();
        if(is_array($action))
        {
            $actions = $action;
        }
        else
        {
            $actions[] = $action;
        }

        if(Task::TIMER_ACTION_TYPE == $type)
        {
            foreach($actions as $action)
            {
                // if task is not define timer, don't send config file
                if($action == Task::ACTION_ADD && empty($this->cron_time))
                {
                    continue;
                }

                $command = array();
                $command['TestCommand'] = $action;
                $appendInfo['TaskID'] = "{$this->id}";
                $appendInfo['Time']   = $this->cron_time;
                if(empty($appendInfo['Time']))
                {
                    $appendInfo['Time'] = '* * * * *';
                }
                $command['AppendInfo'] = json_encode($appendInfo);
                $config['Commands'][] = $command;
            }
        }
        else if(Task::MONITOR_ACTION_TYPE == $type)
        {
            foreach($actions as $action)
            {
                $command = array();
                $command['TestCommand'] = $action;
                if(empty($this->svn_url))
                {
                    $command['TestCommand'] = Task::ACTION_DEL;
                }
                $appendInfo['TaskID'] = "{$this->id}";
                $appendInfo['SVN']   = $this->svn_url;
                $command['AppendInfo'] = json_encode($appendInfo);
                $config['Commands'][] = $command;
            }
        }
        
        $timestamp = time();
        $iniFile = Yii::app()->params['runFilePath'] . "/Task_{$action}_{$type}_{$this->id}_{$timestamp}.ini";
        TLocal::touch($iniFile, CJSON::encode($config), 0022, TRUE);
    }

    
    /**
     *
     * @param type $stage_num 
     */
    public function createRun($jobs, $taskRun = NULL, $run_times = 1, $params = '')
    {
        if(count($jobs) <= 0)
            return $taskRun;
        
        if ($taskRun == NULL)
        {
            $taskRun = new TaskRun();
            $taskRun->name = Yii::t('Run', 'Run #{task_id} By {created_by} @ {current_time}', array(
                '{task_id}' => $this->id,
                '{created_by}' => Yii::app()->user->realname,
                '{current_time}' => date('Y-m-d H:i:s')
            ));
            $taskRun->task_id = $this->id;
            $taskRun->report_to = $this->report_to;
            $taskRun->svn_url = $this->svn_url;
            $taskRun->status = CommandRun::STATUS_WAITING;
            $taskRun->result = CommandRun::RESULT_NULL;
            
            if($taskRun->save())
            {
                $date = date('Y-m-d', strtotime($taskRun->create_time));
                $report = VReport::model()->findByAttributes(array(
                    'date' => $date, 'task_id' => $this->id));
                if(null === $report)
                {
                    $report = new Report();
                }
                else
                {
                    $report = Report::model()->findByPk($report->id);
                }
                $report->task_run_id = $taskRun->id;
                $report->date = $date;
                $report->save();
            }
        }
        
        foreach ($jobs as $job)
        {
            $commandRun = new CommandRun();
            if(Job::TYPE_COMMAND == $job->type)
            {
                $commandRun->command_id = $job->command_id;
            }
            $commandRun->machine_id = $job->machine_id;
            $commandRun->job_id = $job->id;
            $commandRun->task_run_id = $taskRun->id;
            $commandRun->stage_num = $job->stage_num;
            $commandRun->sudoer = $job->sudoer;
            $commandRun->timeout = $job->timeout;
            $commandRun->run_times = $run_times;
            $commandRun->status = CommandRun::STATUS_WAITING;
            $commandRun->result = CommandRun::RESULT_NULL;
            $commandRun->save();
        }
        
        //curl run/initTaskRun
        $url = Yii::app()->getBaseUrl(true);
        preg_match("#https?://([^/]*)(/.*)?#", $url, $matches);
        
        $host = "127.0.0.1";
        $path = "/run/inittaskrun/id/$taskRun->id/stage/$job->stage_num";
        if(isset($matches[2])) $path = $matches[2] . $path;

        $fp = fsockopen($host, 80, $errno, $errstr, 30);
        if (!$fp) {
            Yii::log("CURL run/initTaskRun : $errstr ($errno)", 'error', 'toast.Task.createRun');
            return false;
        }
        $out = "POST ".$path." HTTP/1.1\r\n";
        $out .= "Host: ".$host."\r\n";
        $out .= "Content-type: application/x-www-form-urlencoded\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Content-Length:7\r\n";
        $out .= "\r\n";
        fwrite($fp, $out);  
        fclose($fp);
        
        return $taskRun;
    }
    
    public function getLastRuns($size = 10)
    {
        return $this->taskruns(array('select' => 'id, name, status, result, start_time',
            'order' => 'create_time DESC', 'limit' => $size));
    }
    
    public function getCountRun()
    {
        return TaskRun::model()->countByAttributes(array('task_id' => $this->id));
    }
    
    public function findAll($condition = '', $params = array())
    {
        return parent::findAllByAttributes(array('status' => Task::STATUS_AVAILABLE), $condition, $params);
    }

    public function  findAllByAttributes($attributes, $condition = '', $params = array())
    {
        $attributes = array_merge(array('status' => Task::STATUS_AVAILABLE), $attributes);
        return parent::findAllByAttributes($attributes, $condition, $params);
    }
    
    /**
     * Return report filter options.
     * 
     * @return array, report filter options;
     */
    public function getReportFilterOptions()
    {
        return array(
            self::REPORT_NONE => Yii::t('Task', 'Report None'),
            self::REPORT_FAIL => Yii::t('Task', 'Report Fail'),
            self::REPORT_ALL => Yii::t('Task', 'Report All'),
        );
    }
    
    /**
     * get report filter text
     *
     * @return Stringreport filter text
     */
    public function getReportFilterText()
    {
        $reportFilters= $this->getReportFilterOptions();
        return $reportFilters[$this->report_filter];
    }
    
    public function getFormNavItems()
    {
        $items = array(array('label' => Yii::t('Task', 'New Task')));
        if(!$this->isNewRecord)
        {
            $items = array(
                array(
                    'label' => '#' . $this->id  . ' '. $this->name,
                    'url' => array('/task/view/id/' . $this->id)),
                array('label' => Yii::t('Task', 'Modify Task')));
        }
        return $items;
    }
    
    public function getBtnList()
    {
        $btn = array(
            CHtml::submitButton(Yii::t('Task', 'Save And Run'), array('class' => 'btn', 'name' => 'saverun')),
            CHtml::submitButton(Yii::t('Task', 'Save'), array('class' => 'btn', 'name' => 'save')),
            CHtml::button(Yii::t('Task', 'Return'), array('class' => 'btn return'))
        );
        return $btn;
    }
    
    public function isRunable()
    {
        $flag = true;
        
        // the count of waiting task run should less than WAITING_SIZE
        $count = TaskRun::model()->countByAttributes(array('task_id' => $this->id, 
            'status' => CommandRun::STATUS_WAITING));
        if($count >= self::WAITING_SIZE)
        {
            $flag = false;
        }
        
        return $flag;
    }
}
?>