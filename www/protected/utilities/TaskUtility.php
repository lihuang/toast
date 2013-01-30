<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class TaskUtility extends Utility
{
    public function saveCommand($type, $command_id, $data)
    {
        $newFlag = false;
        $validFlag = true;
        $command = null;
        if(Job::TYPE_COMMAND == $type)
        {
            $command = Command::model()->findByAttributes(array('id' => $command_id, 'created_by' => Yii::app()->user->id));
            if(null === $command)
            {
                $command = new Command();
                $newFlag = true;
            }
            if(isset($data['parser_id']))
            {
                $data['parser_id'] = join(',', $data['parser_id']);
            }
            else
            {
                $data['parser_id'] = '';
            }
            $command->attributes = $data;
            if($command->save())
            {
                $validFlag = true;
                $msg = 'Save Command #' . $command->id . ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'info', 'toast.TaskUtility.saveCommand');
            }
            else
            {
                $validFlag = false;
            }
        }

        return array($newFlag, $validFlag, $command);
    }
    
    public function validateJob($data)
    {
        $job = new Job();
        $job->attributes = $data;
        $validFlag = $job->validate();
        return array($validFlag, $job);
    }
    
    public static function getTasks($id, $build)
    {
        $res = array(
            'status' => Controller::STATUS_FAILURE,
            'msg' => 'no task found.',
        );
        $tasks = array();
        
        if(isset($id))
        {
            $task = Task::model()->findByAttributes(array(
                'id' => $id,
                'status' => Task::STATUS_AVAILABLE
            ));
            if(null !== $task)
            {
                $res['status'] = Controller::STATUS_SUCCESS;
                $res['msg'] = '';
                $tasks[] = $task;
            }
        }
        else if(isset($build))
        {
            if($infos =self::parseBuildInfo($build))
            {
                $condition = new CDbCriteria();
                foreach($infos as $info)
                {
                    $condition->compare('build', ',' . $info['package_name'] . ',', 'true', 'OR');
                }
                $condition->compare('status', Task::STATUS_AVAILABLE);
                $tasks = Task::model()->findAll($condition);
                $res['status'] = Controller::STATUS_SUCCESS;
                $res['msg'] = '';
            }
        }
        return array($res, $tasks);
    }
    
    public static function run($task, $build = null, $dev_log = null, $mail_to = null, $param = null, $force = false)
    {
        if(!$force && !$task->isRunable())
        {
            return false;
        }
        $jobs = $task->jobs(array('condition' => 'stage_num=0'));
        $buildArr = array();
        if($infos =self::parseBuildInfo($build))
        {
            foreach($infos as $info)
            {
                $buildArr[] = $info['package'];
            }
        }
        if(!empty($buildArr))
        {
            $param =  '"' . join(' ', $buildArr) . '" ' . $param;
        }
        if(empty($param))
        {
            $param = '';
        }
        $run = $task->createRun($jobs, null, 1, $param);
        if($run)
         {
             $run->dev_log = $dev_log;
             $run->report_to .= ',' . $mail_to;
             $run->save();
             return $run;
         }
         return false;
    }
    
    public static function parseBuildInfo($build)
    {
        if(!empty($build) && ($infos = CJSON::decode($build)) && is_array($infos))
        {
            $default = array(
                'svn_url' => '',
                'svn_version' => '',
                'package' => '',
                'package_name' => '',
                'package_branch' => '',
                'rpm_url' => '',
            );
            foreach($infos as $idx => $info)
           {
                $info = array_merge($default, $info);
                $info['package'] = addslashes(trim($info['package'], '.rpm'));
                $infos[$idx] = $info;
           }
           
           return $infos;
        }
        
        return false;
    }
}
?>