<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Command Run Model 
 * 
 * @package application.models
 */
class CommandRun extends Model
{
    public $id;
    public $name;
    public $command_id;
    public $machine_id;
    public $task_run_id;
    public $job_id;
    public $stage_num;
    public $result;
    public $status;
    public $timeout = 1440; //24*60 24hours
    public $sudoer = 'root';    
    public $desc_info;
    public $return_code;
    public $build;
    public $created_by;
    public $start_time;
    public $stop_time;
    public $create_time;
    public $update_time;
    public $run_times = 1;
    
    public $case_total_amount;
    public $case_pass_amount;
    public $case_fail_amount;
    public $case_skip_amount;
    public $case_block_amount;
    public $cc_result;
    public $cc_line_hit;
    public $cc_line_total;
    public $cc_branch_hit;
    public $cc_branch_total;

    const STATUS_WAITING = 100;
    const STATUS_RUNNING = 101;
    const STATUS_CANCELING = 102;
    
    const STATUS_COMPLETED = 200;
    const STATUS_CANCELED = 201;
    
    const STATUS_TIMEOUT = 300;
    const STATUS_BUILD_FAILED = 301;
    
    const STATUS_ABORTED = 400;
    const STATUS_AGENTDOWN = 401;
    
    // hard code for toast controller
    const RETURN_VALUE_BUILD_FAILED = 100;

    const RESULT_PASSED = 0;
    const RESULT_FAILED = 1;
    const RESULT_NONE = 2;
    const RESULT_NULL = 9;

    const ACTION_CREATE = 'Regress'; //set Regress to cater for the backend.
    const ACTION_CANCEL = 'CancelRun';
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'command_run';
    }
    public function rules()
    {
        return array(
            array('machine_id, sudoer, timeout, status, result', 'required'),
            array('command_id, machine_id, task_run_id, job_id, stage_num, status, result, 
                created_by, run_times ', 'numerical', 'integerOnly' => true),
            array('name, command_id, machine_id, task_run_id, job_id, stage_num, 
                sudoer, timeout, status, result, callback, return_code, desc_info, 
                failure_cause, trigger, build, case_total_amount, case_pass_amount, 
                case_fail_amount, case_skip_amount, case_block_amount, 
                created_by, create_time, update_time, start_time, stop_time, run_times', 'safe'),
        );
    }

    public function relations()
    {
        return array(
            'command' => array(self::BELONGS_TO, 'Command', 'command_id'),
            'machine' => array(self::BELONGS_TO, 'Machine', 'machine_id'),
            'caseresults' => array(self::HAS_MANY, 'CaseResult', 'command_run_id'),
            'job' => array(self::BELONGS_TO, 'Job', 'job_id'),
            'taskrun' => array(self::BELONGS_TO, 'TaskRun', 'task_run_id'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Run', 'Id'),
            'command_id' => Yii::t('Run', 'Command Id'),
            'machine_id' => Yii::t('Run', 'Machine Id'),
            'status' => Yii::t('Run', 'Status'),
            'start_time' => Yii::t('Run', 'Start Time'),
            'stop_time' => Yii::t('Run', 'Stop Time'),
            'sudoer' => Yii::t('Run', 'Sudoer'),
            'timeout' => Yii::t('Run', 'Timeout'),
            'trigger' => Yii::t('Run', 'Trigger'),
            'result' => Yii::t('Run', 'Result'),
            'build' => Yii::t('Run', 'Build'),
            'desc_info' => Yii::t('Run', 'Describe Info'),
            'append_info' => Yii::t('Run', 'Agent Output'),
            'failure_cause' => Yii::t('Run', 'Failure Cause'),
            'created_by' => Yii::t('Run', 'Created By'),
            'create_time' => Yii::t('Run','Create Time'),
            'update_time' => Yii::t('Run','Update Time'),
            'cc_result' => Yii::t('Run', 'Coverage Detail'),
            'run_times' => Yii::t('Run', 'Run Time'),
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
        }

        return parent::beforeSave();
    }

    public function getStatusOptions()
    {
        return array(
            self::STATUS_WAITING => Yii::t('Run', 'Status Waiting'),
            self::STATUS_RUNNING => Yii::t('Run', 'Status Running'),
            self::STATUS_COMPLETED => Yii::t('Run', 'Status Completed'),
            self::STATUS_CANCELED => Yii::t('Run', 'Status Canceled'),
            self::STATUS_TIMEOUT => Yii::t('Run', 'Status Timeout'),
            self::STATUS_ABORTED => Yii::t('Run', 'Status Aborted'),
            self::STATUS_AGENTDOWN => Yii::t('Run', 'Status AgentDown'),
            self::STATUS_CANCELING => Yii::t('Run', 'Status Canceling'),
            self::STATUS_BUILD_FAILED => Yii::t('Run', 'Status Build Failed')
        );
    }

    public function getResultOptions()
    {
        return array(
            self::RESULT_PASSED => Yii::t('Run', 'Result Passed'),
            self::RESULT_FAILED => Yii::t('Run', 'Result Failed'),
            self::RESULT_NONE => '',
            self::RESULT_NULL => Yii::t('Run', 'Result Null'),
        );
    }

    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status])
               ? $status[$this->status]
               : Yii::t('Run', 'Unknown status({status})', array('{status}' => $this->status));
    }

    public function getResultText()
    {
        $results = $this->getResultOptions();
        return isset($results[$this->result])
               ? $results[$this->result]
               : Yii::t('Run', 'Unknown result({result})', array('{result}' => $this->result));
    }

    public function getStatusStyle()
    {
        $style = '';
        switch ($this->status) {
            case self::STATUS_RUNNING :
            case self::STATUS_WAITING:
            case self::STATUS_CANCELING: {
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
            case self::RESULT_PASSED : {
                $style = 'passed';
                break;
            }
            case self::RESULT_FAILED : {
                $style = 'failed';
                break;
            }
            case self::RESULT_NULL : {
                $style = 'null';
                break;
            }
            default : {
                break;
            }
        }
        return $style;
    }

    public function hasCompleted()
    {
        $completed = false;
        if($this->status >= self::STATUS_COMPLETED)
        {
            $completed = true;
        }
        return $completed;
    }

    public function hasCanceled()
    {
        $canceled = false;
        if($this->hasCompleted() || $this->status == self::STATUS_CANCELING)
        {
            $canceled = true;
        }
        return $canceled;
    }

    public function getFailedResults()
    {
        return CaseResult::model()->findAllByAttributes(array('command_run_id' => $this->id,
            'case_result' => CaseResult::RESULT_FAILED));
    }

    public function sendAction($action)
    {
        $config = array();
        $config['RunID'] = $this->id;
        $config['TestType'] = $action;
        
        $command = $this->command;
        $arr = array();
        $arr['CommandID'] = $this->id;
        $arr['TestBox'] = trim($this->machine->ip);

        $arr['TestCommand'] = str_replace("\r", '', $command->command);
        $arr['Timeout'] = $this->timeout;
        $arr['Sudoer'] = $this->sudoer;
        
        $config['Commands'] = array($arr);
        
        Yii::log(CJSON::encode($config), 'trace', 'toast.TaskRun.sendAction');

        $timestamp = time();
        $iniFile = Yii::app()->params['runFilePath'] . "/CommandRun_{$action}_{$this->id}_{$timestamp}.ini";
        TLocal::touch($iniFile, CJSON::encode($config), 0022, TRUE);
    }
    
    public function update($attributes = null)
    {
        switch($this->status)
        {
            case self::STATUS_WAITING: {
                $this->update_time = date(Yii::app()->params->dateFormat);
                $this->result = self::RESULT_NULL;

                if (($taskRun = $this->taskrun) !== NULL) 
                {
                    $taskRun->status = self::STATUS_WAITING;
                    $taskRun->update();
                }

                $msg = 'CommandRun #' . $this->id . ' is waiting';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_RUNNING: {
                $this->update_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $this->result = self::RESULT_NULL;
                
                if (($taskRun = $this->taskrun) !== NULL) 
                {
                    $taskRun->status = self::STATUS_RUNNING;
                    $taskRun->update();
                }

                $msg = 'CommandRun #' . $this->id . ' is running';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_CANCELING: {
                $this->update_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                
                if (($taskRun = $this->taskrun) !== NULL) 
                {
                    $taskRun->status = self::STATUS_CANCELING;
                    $taskRun->update();
                }

                $msg = 'CommandRun #' . $this->id . ' is canceling';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_CANCELED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $this->result = self::RESULT_FAILED;
                
                if (($taskRun = $this->taskrun) !== NULL)
                {
                    $allCompleted = true;
                    foreach ($taskRun->commandruns as $commandRun)
                    {
                        if($commandRun->id === $this->id)
                            continue;
                        $allCompleted = $allCompleted && $commandRun->hasCompleted();
                    }
                    if($allCompleted)
                    {
                        $taskRun->status = self::STATUS_CANCELED;
                        $taskRun->update();
                    }
                }
                
                $msg = 'CommandRun #' . $this->id . ' is canceled';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_TIMEOUT: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                
                $this->result = self::RESULT_FAILED;
                $msg = 'CommandRun #' . $this->id . ' is timeout';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_COMPLETED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                
                if(isset($this->job) && Job::TYPE_CASE == $this->job->type)
                {
                    $parsers = array();
                    foreach($this->job->testcases as $testcase)
                    {
                        if(isset($testcase->parser))
                        {
                            $parsers[$testcase->parser->id] = $testcase->parser;
                        }
                    }
                    foreach($parsers as $parser)
                    {
                        $parser->parse($this);
                    }
                    $this->result = self::RESULT_PASSED;
                    if($this->case_fail_amount > 0 || $this->case_total_amount <= 0)
                    {
                        $this->result = self::RESULT_FAILED;
                    }
                }
                else if(isset($this->command) && ($parsers = $this->command->getParsers()))
                {
                    foreach($parsers as $parser)
                    {
                        $parser->parse($this);
                    }

                    $this->result = self::RESULT_PASSED;
                    if($this->case_fail_amount > 0 || $this->case_total_amount <= 0)
                    {
                        $this->result = self::RESULT_FAILED;
                    }
                    
                    //Deploy just parse build
                    if(count($parsers) == 1 && $parsers[0]->name == 'Deploy')
                        $this->result = ($this->return_code == 0)?self::RESULT_PASSED:self::RESULT_FAILED;
                }
                else
                {
                    $this->result = ($this->return_code == 0)?self::RESULT_PASSED:self::RESULT_FAILED;
                }
               
                $msg = 'CommandRun #' . $this->id . ' is complete';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_ABORTED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $this->result = self::RESULT_FAILED;
                
                if (($taskRun = $this->taskrun) !== NULL) 
                {
                    $taskRun->status = self::STATUS_ABORTED;
                    $taskRun->update();
                }

                $msg = 'CommandRun #' . $this->id . ' is aborted';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_AGENTDOWN: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $this->result = self::RESULT_FAILED;
                $msg = 'CommandRun #' . $this->id . ' is failed because this agent is down';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            case self::STATUS_BUILD_FAILED: {
                $this->update_time = $this->stop_time = date(Yii::app()->params->dateFormat);
                if(!isset($this->start_time))
                {
                    $this->start_time = date(Yii::app()->params->dateFormat);
                }
                $this->result = self::RESULT_FAILED;
                $msg = 'CommandRun #' . $this->id . ' is failed because build failed';
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                break;
            }
            default : {
                $msg = 'CommandRun #' . $this->id . ' update failed with unkonw status ' . $this->status;
                Yii::log($msg, 'trace', 'toast.CommandRun.update');
                return false;
            }
        }
        
        return parent::update($attributes);
    }
    
    public function updateRun($attributes = NULL)
    {
        $complete = false;
        $this->attributes = $attributes;
        if(CommandRun::RETURN_VALUE_BUILD_FAILED == $this->return_code)
        {
            $this->status = CommandRun::STATUS_BUILD_FAILED;
        }
        if($this->validate() && $this->update())
        {
            $msg = 'Update command run #' . $this->id . ' success. source is ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'trace', 'toast.CommandRun.updateRun');
        }
        else
        {
            $errorMsg = '';
            foreach($this->attributes as $attr => $val)
            {
                $errorMsg .= $this->getError($attr);
            }
            $msg = 'Receive failed update run command because of ' . $errorMsg 
                    . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'trace', 'toast.RunController.actionUpdateRun');
            echo 'update run failed, because ' . $errorMsg;
        }

        //check if set repeat run
        if($this->job != null && $this->taskrun != null
                && in_array($this->status, array(self::STATUS_COMPLETED, self::STATUS_TIMEOUT, self::STATUS_BUILD_FAILED))
                && $this->result == CommandRun::RESULT_FAILED
                && $this->job->failed_repeat >= $this->run_times)
        {
            $this->taskrun->task->createRun(array($this->job), $this->taskrun, $this->run_times + 1);
            unset($this->job_id);
            unset($this->task_run_id);
            $this->update();
            Yii::log("CommandRun $this->id failed, will run again, count: " . ($this->run_times + 1), 'info', 'toast.CommandRun.updateRun');
            return $complete;
        }

        if($this->taskrun != NULL)
        {
            // if command run done and not throw server error and not cancel the task, run next stage
            if($this->status >= CommandRun::STATUS_COMPLETED
                    && $this->status != CommandRun::STATUS_ABORTED
                    && $this->status != CommandRun::STATUS_CANCELED)
            {
                $complete = $this->taskrun->checkNextStage($this->stage_num);
            }
            
            if($complete || $this->status == CommandRun::STATUS_CANCELED || $this->status == CommandRun::STATUS_ABORTED)
            {
                //unlink the lock file
                $filePath = Yii::app()->params['stageLockFile'] . $this->taskrun->id;
                if(file_exists($filePath))
                    unlink($filePath);
                
                $release = !(($this->taskrun->status == CommandRun::STATUS_COMPLETED && $this->taskrun->result == CommandRun::RESULT_FAILED) || 
                    in_array($this->taskrun->status, array(CommandRun::STATUS_CANCELED, CommandRun::STATUS_TIMEOUT, CommandRun::STATUS_BUILD_FAILED)));
                $task = $this->taskrun->task;
                
                if($this->taskrun->task->exclusive)
                {
                    //check and run other TaskRun
                    $nextRun = TaskRun::model()->findByAttributes(array('task_id' => $this->taskrun->task_id, 'status' => CommandRun::STATUS_WAITING));
                    if($nextRun)
                        $nextRun->sendAction(TaskRun::ACTION_CREATE, 0, $nextRun->build);
                }
            }
        }
        else
        {
            $complete = true;
        }
        return $complete;
    }
    
    public function getCommand()
    {
        $command = '';
        if(isset($this->job))
        {
            if(Job::TYPE_CASE == $this->job->type)
            {
                foreach($this->job->testcases as $testcase)
                {
                    $command .= $testcase->getCommand() . ';';
                }
            }
            else if(isset($this->command))
            {
                $command = $this->command->command;
            }
        }
        else if(isset($this->command))
        {
            $command = $this->command->command;
        }
        return $command;
    }
    
    public function getCaseView()
    {
        $view = 'case';
        return $view;
    }
}
?>