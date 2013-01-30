<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * TaskRun Model
 * 
 * @package application.models
 */
class TaskRun extends Model
{
    public $id;
    public $name;
    public $task_id;
    public $result;
    public $status;
    public $build;
    public $svn_url;
    public $report_to;
    public $all_case_amount;
    public $dev_log;
    public $created_by;
    public $updated_by;
    public $start_time;
    public $stop_time;
    public $create_time;
    public $update_time;

//    const STATUS_WAITING = 0;
//    const STATUS_RUNNING = 1;
//    const STATUS_COMPLETED = 2;
//    const STATUS_CANCELED = 3;
//    const STATUS_TIMEOUT = 4;
//    const STATUS_CANCELING = 10;

//    const RESULT_PASSED = 0;
//    const RESULT_FAILED = 1;
//    const RESULT_NONE = 2;
//    const RESULT_NULL = 9;

    const ACTION_CREATE = 'Create';
    const ACTION_CANCEL = 'CancelRun';
    
    const BUILD_PARAM = '$BUILD';
    const CHANGE_FILE = '$CHANGE_FILE';
    const ACTION_WITH_CHANGE_FILE = '$ACTION_WITH_CHANGE_FILE';
    const CHECK_IN_AUTHOR = '$CHECK_IN_AUTHOR';
    const CHECK_IN_COMMENT = '$CHECK_IN_COMMENT';
    const CHECK_IN_TIME = '$CHECK_IN_TIME';
    const TASK_RUN_ID_PARAM = '$TASK_RUN_ID';

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'task_run';
    }

    public function rules()
    {
        return array(
            array('name, task_id, status, result', 'required'),
            array('task_id, status, result', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 128)
        );
    }

    public function relations()
    {
        return array(
            'task' => array(self::BELONGS_TO, 'Task', 'task_id'),
            'commandruns' => array(self::HAS_MANY, 'CommandRun', 'task_run_id'),
            'reports' => array(self::HAS_MANY, 'Report', 'task_run_id')
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Run', 'Id'),
            'name' => Yii::t('Run', 'Name'),
            'task_id' => Yii::t('Run', 'Task Id'),
            'status' => Yii::t('Run', 'Status'),
            'start_time' => Yii::t('Run', 'Start Time'),
            'stop_time' => Yii::t('Run', 'Stop Time'),
            'result' => Yii::t('Run', 'Result'),
            'dev_log' => Yii::t('Run', 'Dev Log'),
            'created_by' => Yii::t('Run', 'Created By'),
            'updated_by' => Yii::t('Run', 'Updated By'),
            'create_time' => Yii::t('Run','Create Time'),
            'update_time' => Yii::t('Run','Update Time'),
            'all_case_amount' => Yii::t('Run', 'Case Total Amount')
        );
    }

    protected function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = date(Yii::app()->params->dateFormat);
            $this->created_by  = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
            if(empty($this->updated_by))
                $this->updated_by  = Yii::app()->user->id;
        }

        return parent::beforeSave();
    }

    public function getStatusOptions()
    {
        return CommandRun::model()->getStatusOptions();
    }

    public function getResultOptions()
    {
        return CommandRun::model()->getResultOptions();
    }

    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status])
               ? $status[$this->status]
               : Yii::t('TaskRun', 'Unknown status({status})', array('{status}' => $this->status));
    }

    /**
     *
     * @param type $stage_num 
     */
    public function getStageStatus($stage_num)
    {
        $commandRuns = $this->getStageCommands($stage_num);
        $isCompleted = true;
        foreach ($commandRuns as $commandRun)
        {
            $isCompleted = $isCompleted && $commandRun->hasCompleted();
        }
        if ($isCompleted)
            return CommandRun::STATUS_COMPLETED;
        else
            return CommandRun::STATUS_RUNNING;
    }
    
    /**
     * get stage's result
     * @param type $stage_num
     * @param type $ignoreNotCrucialJob ignore the job's result which is not crucial
     * @return type 
     */
    public function getStageResult($stage_num, $ignoreNotCrucialJob = FALSE)
    {
        $commandRuns = $this->getStageCommands($stage_num);
        $stageResult = CommandRun::RESULT_PASSED;
        foreach ($commandRuns as $commandRun)
        {
            if ($ignoreNotCrucialJob && !$commandRun->job->isCrucial())
                continue;
            
            if ($commandRun->result != CommandRun::RESULT_PASSED)
                $stageResult = CommandRun::RESULT_NULL;
            if ($commandRun->result == CommandRun::RESULT_FAILED)
                $stageResult = CommandRun::RESULT_FAILED;
        }
        return $stageResult;
    }
    
    public function getResultText()
    {
        $results = $this->getResultOptions();
        return isset($results[$this->result])
               ? $results[$this->result]
               : Yii::t('TaskRun', 'Unknown result({result})', array('{result}' => $this->result));
    }

    public function getStatusStyle()
    {
        $style = '';
        switch ($this->status) {
            case CommandRun::STATUS_RUNNING :
            case CommandRun::STATUS_WAITING:
            case CommandRun::STATUS_CANCELING: {
                $style = 'running';
                break;
            }
            default : {
                break;
            }
        }
        return $style;
    }

    public function getResultStyle()
    {
        $style = '';
        switch ($this->result) {
            case CommandRun::RESULT_PASSED : {
                $style = 'passed';
                break;
            }
            case CommandRun::RESULT_FAILED : {
                $style = 'failed';
                break;
            }
            case CommandRun::RESULT_NULL : {
                $style = 'null';
                break;
            }
            default : {
                break;
            }
        }
        return $style;
    }

    public function getStageCommands($stage_num = 0)
    {
        return CommandRun::model()->findAllByAttributes(array('task_run_id' => $this->id, 'stage_num' => $stage_num));
    }

    public function hasCompleted()
    {
        $completed = false;
        if($this->status >= CommandRun::STATUS_COMPLETED)
        {
            $completed = true;
        }
        return $completed;
    }

    public function hasCanceled()
    {
        $canceled = false;
        if($this->hasCompleted() || $this->status == CommandRun::STATUS_CANCELING)
        {
            $canceled = true;
        }
        return $canceled;
    }

    public function sendAction($action, $stage_num, $params = '')
    {
        $config = array();
        $config['RunID'] = $this->id;
        $config['Commands'] = array();
        $config['TestType'] = ($action == self::ACTION_CREATE)
                ? $this->task->getProtocolName(Task::TYPE_REGRESS) : self::ACTION_CANCEL;
        $commandRuns = $this->getStageCommands($stage_num);
        
        foreach ($commandRuns as $commandRun)
        {
            $error = false;
            if ($action == self::ACTION_CANCEL && $commandRun->hasCompleted())
                continue;
            
            $arr = array();
            $arr['CommandID'] = $commandRun->id;
            $arr['TestBox'] = trim($commandRun->machine->ip);
            $arr['TestCommand'] = str_replace("\r", '', $commandRun->getCommand());
            $devLogArr = $this->parserDevLog();
            foreach($devLogArr as $key => $val)
            {
                $arr['TestCommand'] = str_replace($key, '"' . addslashes(join('&&', $val)) . '"', $arr['TestCommand']);
            }
            $arr['TestCommand'] = str_replace(self::BUILD_PARAM, $params,  $arr['TestCommand']);
            $arr['TestCommand'] = str_replace(self::TASK_RUN_ID_PARAM, $this->id,  $arr['TestCommand']);
            $arr['Timeout'] = $commandRun->timeout;
            $arr['Sudoer'] = $commandRun->sudoer;
            
            if($commandRun->machine->status == Machine::STATUS_IDLE || !($this->task->wait_machine))
            {
                $config['Commands'][] = $arr;
            }
        }

        if(count($config['Commands']) > 0)
        {
            $content = trim(CJSON::encode($config));
            if(empty($content))
                Yii::log('TaskRun ini file is empty: ' . var_export($config, true), 'error', 'toast.TaskRun.sendAction');
            else
                Yii::log($content, 'trace', 'toast.TaskRun.sendAction');

            $timestamp = time();
            $iniFile = Yii::app()->params['runFilePath'] . "/Run_{$action}_{$this->id}_{$stage_num}_{$timestamp}.ini";
            TLocal::touch($iniFile, $content, 0022, TRUE);
        }
    }

    public function update($attributes = null)
    {
        switch($this->status)
        {
            case CommandRun::STATUS_WAITING: {
                $this->update_time = date(Yii::app()->params->dateFormat);
                $this->result = CommandRun::RESULT_NULL;
                $msg = 'TaskRun #' . $this->id . ' is waiting';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                break;
            }
            case CommandRun::STATUS_RUNNING: {
                $this->update_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $this->result = CommandRun::RESULT_NULL;
                $msg = 'TaskRun #' . $this->id . ' is running';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                break;
            }
            case CommandRun::STATUS_CANCELING: {
                $this->update_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $msg = 'TaskRun #' . $this->id . ' is canceling';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                break;
            }
            case CommandRun::STATUS_CANCELED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                $this->result = CommandRun::RESULT_FAILED;
                $msg = 'TaskRun #' . $this->id . ' is canceled';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                break;
            }
            case CommandRun::STATUS_TIMEOUT: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                $this->result = CommandRun::RESULT_FAILED;
                $msg = 'TaskRun #' . $this->id . ' is timeout';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                break;
            }
            case CommandRun::STATUS_COMPLETED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                $taskResult = CommandRun::RESULT_PASSED;
                $maxStageNum = Job::model()->find(array('select' => 'MAX(stage_num) AS maxStageNum', 
                    'condition' => 'task_id=' . $this->task->id . 
                    ' AND status=' . Job::STATUS_AVAILABLE))->maxStageNum;
                for ($index = 0; $index <= $maxStageNum; $index++)
                {
                    if (CommandRun::RESULT_FAILED == $this->getStageResult($index))
                    {
                        $taskResult = CommandRun::RESULT_FAILED;
                        break;
                    }
                }
                $this->result = $taskResult;
                
                $msg = 'TaskRun #' . $this->id . ' is complete';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                
                // Add case amount info into the report table
                $report = Report::model()->findByAttributes(array('task_run_id' => $this->id));
                if (null !== $report)
                {
                    $report->case_total_amount = 0;
                    $report->case_pass_amount = 0;
                    $report->case_fail_amount = 0;
                    $commandRuns = CommandRun::model()->findAllByAttributes(array('task_run_id' => $this->id));
                    foreach ($commandRuns as $commandRun)
                    {
                        $report->case_total_amount += $commandRun->case_total_amount;
                        $report->case_pass_amount += $commandRun->case_pass_amount;
                        $report->case_fail_amount += $commandRun->case_fail_amount;
                    }

                    $report->update();
                }
                break;
            }
            case CommandRun::STATUS_AGENTDOWN:
            case CommandRun::STATUS_ABORTED:
            case CommandRun::STATUS_BUILD_FAILED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                $this->result = CommandRun::RESULT_FAILED;
                
                $msg = 'TaskRun #' . $this->id . ' is aborted';
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                break;
            }
            default : {
                $msg = 'TaskRun #' . $this->id . ' update failed with unkonw status ' . $this->status;
                Yii::log($msg, 'trace', 'toast.TaskRun.update');
                return false;
            }
        }
           
        return parent::update($attributes);
    }
    
    /**
     * Add receiver to report_to for api call.
     * Unique the receivers and save by this function.
     * 
     * @param string $receiver
     * @param string $domain
     * @param string $split 
     */
    public function addReportTo($receiver, $domain = 'taobao.com', $split = ',')
    {
        $reportToArr = explode($split, $this->report_to);
        $receiverArr = array();
        foreach($reportToArr as $reportTo)
        {
            $user = User::model()->findByAttributes(array('realname' => $reportTo));
            if(null === $user)
            {
                $receiverArr[] = $reportTo;
            }
            else
            {
                $receiverArr[] = $user->email;
            }
        }
        
        $receiveUser = User::model()->findByAttributes(array('username' => $receiver));
        if(null === $receiveUser)
        {
            $receiver .= '@' . $domain;
        }
        else
        {
            $receiver = $receiveUser->email;
        }
        
        if(!in_array($receiver, $receiverArr))
        {
            $this->report_to .= $split . $receiver;
            $this->save();
        }
    }
    
    public function cancelRun()
    {
        $commandRuns = $this->commandruns;
        $stage_num = 0;
        $res = array();
        $status = CommandRun::STATUS_WAITING;
        foreach ($commandRuns as $commandRun)
        {
            if(!$commandRun->hasCanceled())
            {
                if($commandRun->status == CommandRun::STATUS_RUNNING)
                {
                    $commandRun->status = CommandRun::STATUS_CANCELING;
                    $status = CommandRun::STATUS_RUNNING;
                }
                else
                    $commandRun->status = CommandRun::STATUS_CANCELED;
                $commandRun->update();
                $stage_num = $commandRun->stage_num;

                $res['id'] = $commandRun->id;
                $res['status'] = $commandRun->getStatusText();

                $msg = 'Cancel Run #' . $commandRun->id . ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.TaskRun.cancelRun');
            }
        }
        if(!$this->hasCanceled())
        {
            if($status == CommandRun::STATUS_RUNNING && $this->status == CommandRun::STATUS_RUNNING)
            {
                $this->status = CommandRun::STATUS_CANCELING;
                $this->update();
                $this->sendAction(TaskRun::ACTION_CANCEL, $stage_num);
            }
            else
            {
                $this->status = CommandRun::STATUS_CANCELED;
                $this->update();
                
                if($this->task->exclusive)
                {
                    $runningRun = TaskRun::model()->findByAttributes(array('task_id' => $this->task_id, 'status' => CommandRun::STATUS_RUNNING));
                    if($runningRun == NULL)
                    {
                        //check and run other TaskRun
                        $nextRun = TaskRun::model()->findByAttributes(array('task_id' => $this->task_id, 'status' => CommandRun::STATUS_WAITING));
                        if($nextRun)
                            $nextRun->sendAction(TaskRun::ACTION_CREATE, 0, $nextRun->build);
                    }
                }
            }
            Report::model()->deleteAllByAttributes(array('task_run_id' => $this->id));
        }
        return $res;
    }

    public function checkNextStage($stageNum)
    {
        $complete = false;
        
        // lock
        $filePath = Yii::app()->params['stageLockFile'] . $this->id;
        $lockFile = fopen($filePath, 'w+');
        flock($lockFile, LOCK_EX);

        $stageStatus = $this->getStageStatus($stageNum);
        $stageResult = $this->getStageResult($stageNum, TRUE);
        Yii::log('TASK ID: ' . $this->id . ' status: ' . $stageStatus . ' result: ' . $stageResult, 
                'trace', 'toast.TaskRun.checkNextStage');
        if ($stageStatus == CommandRun::STATUS_COMPLETED)
        {
            $maxStageNum = Job::model()->find(array('select' => 'MAX(stage_num) AS maxStageNum', 
                'condition' => 'task_id=' . $this->task->id . 
                ' AND status=' . Job::STATUS_AVAILABLE))->maxStageNum;

            if ($stageNum < $maxStageNum && $stageResult == CommandRun::RESULT_PASSED)
            {
                $complete = false;
                $stageRun = CommandRun::model()->findAllByAttributes(
                        array('task_run_id' => $this->id, 
                            'stage_num' => $stageNum + 1));
                if (empty($stageRun))
                {
                    $jobs = $this->task->jobs(array('condition' => 'stage_num=' . ($stageNum + 1)));
                    // Run Next Stage
                    $this->task->createRun($jobs, $this);
                }
            }
            else
            {
                // Task completed
                $complete = true;
                foreach($this->commandruns as $commandRun)
                {
                    // server error status has a higher priority 
                    if($this->status < CommandRun::STATUS_COMPLETED ||
                            ($commandRun->status >= CommandRun::STATUS_TIMEOUT
                            && $this->status < CommandRun::STATUS_TIMEOUT)
                            || ($commandRun->status >= CommandRun::STATUS_ABORTED 
                            && $this->status < CommandRun::STATUS_ABORTED))
                    {
                        $this->status = $commandRun->status;
                        $this->result = $commandRun->result;
                    }
                }
                $this->save();
            }
        }

        //unlock
        flock($lockFile, LOCK_UN);
        fclose($lockFile);
        
        return $complete;
    }
    
    /**
     * Parser develop log.
     * @return array Develop log array.
     */
    public function parserDevLog()
    {
        $arr = array(
            self::CHANGE_FILE => array(),
            self::ACTION_WITH_CHANGE_FILE => array(),
            self::CHECK_IN_AUTHOR => array(),
            self::CHECK_IN_COMMENT => array(),
            self::CHECK_IN_TIME => array(),
        );
        
        if(!empty($this->dev_log) && $logArr = CJSON::decode($this->dev_log))
        {
            foreach($logArr as $log)
            {
                $arr[self::CHECK_IN_AUTHOR][] = $log['author'];
                $arr[self::CHECK_IN_TIME][] = date('Y-m-d H:i:s', strtotime($log['date']));
                $arr[self::CHECK_IN_COMMENT][] = $log['comment'];
                $changeFile = array();
                $changeFileWithAction = array();
                foreach($log['lists'] as $list)
                {
                    $changeFile[] = $list['file'];
                    $changeFileWithAction[] = $list['action'] . '|' . $list['file'];
                }
                $arr[self::CHANGE_FILE][] = join(',', $changeFile);
                $arr[self::ACTION_WITH_CHANGE_FILE][] = join(',', $changeFileWithAction); 
           }
        }
        
        return $arr;
    }
}
?>