<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class TaskController extends Controller
{
    public function filters()
    {
        return array(
            array(
                'application.filters.APIAccessFilter + run'
            ),
            'accessControl',
            array(
                'application.filters.ProductAccessFilter + index'
            )
        );
    }

    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('getAllRuntime', 'getAllURL'),
                'users' => array('*')
            ),
            array(
                'allow',
                'actions' => array('index', 'createdbyme', 'responsiblebyme', 'recentrun', 'getHistory',
                    'view', 'create', 'update', 'copy', 'delete', 'createRun', 'getJobView', 'getJobForm', 'run',
                    'validatejobdata', 'editconfig'),
                'users' => array('@')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    protected function beforeAction($action)
    {
        $cookie = new CHttpCookie(WebUser::CONTROLLER, $this->getId());
        $cookie->expire = time() + 60 * 60 * 24 * 30;
        Yii::app()->request->cookies[WebUser::CONTROLLER] = $cookie;
        if (isset($_GET['VTask']['parent_id']))
        {
            Yii::app()->request->cookies[WebUser::CURRENT_PROJECT_ID] = new CHttpCookie(WebUser::CURRENT_PROJECT_ID, $_GET['VTask']['parent_id']);
        }
        else if($action->getId() != 'create')
        {
            $cookies = Yii::app()->request->getCookies();
            unset($cookies[WebUser::CURRENT_PROJECT_ID]);
        }
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $vTask = $this->listModels('VTask');
        $condition = null;
        if(isset($_GET['q']))
            $condition = $this->getCondition($_GET['q']);
        
        $this->render('index',array(
            'vTaskProvider' => $vTask->search(Yii::app()->user->getPageSize(), $condition),
            'vTask' => $vTask,
        ));
    }
    
    public function actionCreatedByMe()
    {
        $vTask = $this->listModels('VTask');

        $vTask->created_by = Yii::app()->user->id;
        $this->render('index',array(
            'vTaskProvider' => $vTask->search(Yii::app()->user->getPageSize()),
            'vTask' => $vTask,
        ));
    }
    
    public function actionResponsibleByMe()
    {
        $vTask = $this->listModels('VTask');

        $vTask->responsible = Yii::app()->user->id;
        $this->render('index',array(
            'vTaskProvider' => $vTask->search(Yii::app()->user->getPageSize()),
            'vTask' => $vTask,
        ));
    }
    
    public function actionView()
    {
        $vTask = $this->loadModel('VTask');
        $options = array('order' => 'create_time DESC', 'limit' => 1);
        if(isset($_REQUEST['runid']))
        {
            $options['condition'] = 'id=' . $_REQUEST['runid'];
        }
        if(isset($_REQUEST['runID']))
        {
            $options['condition'] = 'id=' . $_REQUEST['runID'];
        }
        $vTaskRun = current($vTask->vtaskruns($options));
        
        $perPage = 50;
        if($vTaskRun == FALSE)
        {
            $vTaskRun = NULL;
        }
        else
        {
            if(!isset($_REQUEST['VTaskRun_page']))
            {
                $sort = 'id DESC';
                if(isset($_REQUEST['VTaskRun_sort']))
                    $sort = preg_replace('/\./', ' ', $_REQUEST['VTaskRun_sort']);
                $page = Yii::app()->db->createCommand('SELECT row_number FROM 
                    (SELECT task_run.id, @row_num := @row_num + 1 AS row_number FROM task_run, (SELECT @row_num := 0) AS r 
                        WHERE task_run.task_id = ' . $vTask->id . ' ORDER BY ' . $sort . ')  AS f 
                        WHERE f.id = ' . $vTaskRun->id)->queryColumn();
                if(isset($page[0]))
                    $_REQUEST['VTaskRun_page'] = $_GET['VTaskRun_page'] = (string)ceil((int)$page[0]/$perPage);
            }
        }
        
//        if(!isset($_REQUEST['runID']) && !empty($vTaskRun))
//            $this->redirect(array('view', 'id' => $vTask->id, 'runID' => $vTaskRun->id));
        
        $vRunProvider = new CActiveDataProvider('VTaskRun', array(
            'criteria' => array(
                'condition' => 'task_id=' . $vTask->id,
                'with' => array('task'),
            ),
            'pagination' => array(
                'pageSize' => $perPage,
            ),
            'sort' => array(
                'defaultOrder' => "t.id DESC"
            ),
        ));

        $this->render('view', array(
            'vTask' => $vTask, 
            'vTaskRun' => $vTaskRun, 
            'vRunProvider' => $vRunProvider,
        ));
    }

    public function actionCreate()
    {
        $task = new Task();
        $cookies = Yii::app()->request->getCookies();
        if(isset($cookies[WebUser::CURRENT_PROJECT_ID]))
            $task->project_id = $cookies[WebUser::CURRENT_PROJECT_ID]->value;
        $task->responsible = Yii::app()->user->id;
        $jobs = array();
        if(isset($_POST['Task']))
        {
            $task->attributes = $_POST['Task'];
            $valid = $task->validate();
            if(isset($_POST['Jobs']))
            {
                foreach($_POST['Jobs'] as $key => $val)
                {
                    $job = new Job();
                    $job->attributes = $val;
                    $jobs[] = $job;
                }
            }
            if($valid)
            {
                if ($task->save())
                {
                    foreach($jobs as $key => $job)
                    {
                        $job->task_id = $task->id;
                        if($job->save())
                        {
                            if(Job::TYPE_CASE == $job->type && !empty($job->test_case_ids))
                            {
                                foreach($job->test_case_ids as $test_case_id)
                                {
                                    $job_test_case = new JobTestCase();
                                    $job_test_case->test_case_id = $test_case_id;
                                    $job_test_case->job_id = $job->id;
                                    $job_test_case->save();
                                }
                            }
                        }
                    }
                }

                $task->sendAction(Task::ACTION_ADD, Task::TIMER_ACTION_TYPE);
                $task->sendAction(Task::ACTION_ADD, Task::MONITOR_ACTION_TYPE);
                $msg = 'Create Task #' . $task->id . ' ' . $task->name. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.TaskController.actionCreate');
                if(isset($_POST['saverun']))
                {
                    $jobs = $task->jobs(array('condition' => 'stage_num=0'));
                    $run = $task->createRun($jobs);
                    $msg = 'Create Run #' . $run->id . ' By ' . Yii::app()->user->name;
                    Yii::log($msg, 'trace', 'toast.TaskController.actionCreate');
                    $this->redirect(array('view', 'id' => $task->id, 'runID' => $run->id));
                }
                else
                {
                    $this->redirect(array('view', 'id' => $task->id));
                }
            }
        }
        $responsible = User::model()->findByPk($task->responsible);
        if(null !== $responsible)
        {
            $task->responsible_realname = $responsible->realname;
        }

        $this->render('create', array('task' => $task, 'jobs' => $jobs));
    }

    public function actionUpdate()
    {
        $task = $this->loadModel('Task');
        $task->isNewRecord = false;
        $jobs = $task->jobs;

        if(isset($_POST['Task']))
        {
            $jobs = array();
            foreach($_POST['Jobs'] as $key => $val)
            {
                if(is_numeric($val['id']))
                    $job = Job::model()->findByPk($val['id']);
                else
                    $job = new Job();
                $job->attributes = $val;
                $job->status = Job::STATUS_AVAILABLE;
                $jobs[] = $job;
            }

            $oldTime = $task->cron_time;
            $oldUrl = $task->svn_url;
            $task->attributes = $_POST['Task'];
            $valid = $task->validate();
            
            if($valid)
            {
                $oldJobs = array();
                $newJobs = array();
                
                /// clear old stage and jobs
                foreach ($task->jobs as $job)
                {
                    $job->status = Job::STATUS_DISABLE;
                    if(!$job->save())
                    {
                        var_dump($job->getErrors());
                        exit;
                    }
                    $oldJobs[] = $job->id;
                }
                
                if ($task->save())
                {
                    // Save Task modify diff
                    $diffAction = NULL;            
                    $taskDiffs = $task->getDiff();
                    if(!empty($taskDiffs))
                    {
                        $diffAction = new DiffAction();
                        $diffAction->model_name = get_class($task);
                        $diffAction->model_id = $task->id;
                        $diffAction->save();
                        foreach($taskDiffs as $diff)
                        {
                            $diffAttr = new DiffAttribute();
                            $diffAttr->attributes = $diff;
                            $diffAttr->diff_action_id = $diffAction->id;
                            $diffAttr->save();
                        }
                    }
                    
                    foreach($jobs as $job)
                    {
                        $job->task_id = $task->id;
                        if($job->save())
                        {
                            if(Job::TYPE_CASE == $job->type && !empty($job->test_case_ids))
                            {
                                JobTestCase::model()->deleteAllByAttributes(array('job_id' => $job->id));
                                $displayOrder = 0;
                                foreach($job->test_case_ids as $test_case_id)
                                {
                                    $job_test_case = new JobTestCase();
                                    $job_test_case->test_case_id = $test_case_id;
                                    $job_test_case->job_id = $job->id;
                                    $job_test_case->display_order = $displayOrder++;
                                    $job_test_case->save();
                                }
                            }
                        }
                        $newJobs[] = $job->id;
                        
                        // Save Job modify diff
                        $jobDiffs = $job->getDiff();
                        if(!empty($jobDiffs))
                        {
                            if($diffAction == NULL)
                            {
                                $diffAction = new DiffAction();
                                $diffAction->model_name = get_class($task);
                                $diffAction->model_id = $task->id;
                                $diffAction->save();
                            }
                            foreach($jobDiffs as $diff)
                            {
                                $diffAttr = new DiffAttribute();
                                $diffAttr->attributes = $diff;
                                $diffAttr->diff_action_id = $diffAction->id;
                                $diffAttr->save();
                            }
                        }                        
                    }
                    foreach(array_diff($oldJobs, $newJobs) as $delJobID)
                    {
                        $delJob = Job::model()->findByPk($delJobID);
                        if($delJob)
                        {
                            if($diffAction == NULL)
                            {
                                $diffAction = new DiffAction();
                                $diffAction->model_name = get_class($task);
                                $diffAction->model_id = $task->id;
                                $diffAction->save();
                            }
                            $diffAttr = new DiffAttribute();
                            $diffAttr->model_name = get_class($delJob);
                            $diffAttr->model_id = $delJob->id;
                            $diffAttr->attribute = 'status';
                            $diffAttr->old = Job::STATUS_AVAILABLE;
                            $diffAttr->new = Job::STATUS_DISABLE;
                            $diffAttr->diff_action_id = $diffAction->id;
                            $diffAttr->save();
                        }
                    }
                }
                
                // if don't change cron_time, do not send actions to toast be contorller
                if($oldTime != $task->cron_time)
                {
                    $actions[] = Task::ACTION_DEL;
                    $actions[] = Task::ACTION_ADD;
                    $task->sendAction($actions, Task::TIMER_ACTION_TYPE);
                }

                if($oldUrl != $task->svn_url)
                {
                    $task->sendAction(Task::ACTION_UPDATE, Task::MONITOR_ACTION_TYPE);
                }
                
                $msg = 'Update Task #' . $task->id . ' ' . $task->name. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.TaskController.actionUpdate');
                if(isset($_POST['saverun']))
                {
                    $jobs = $task->jobs(array('condition' => 'stage_num=0'));
                    $run = $task->createRun($jobs);
                    $msg = 'Create Run #' . $run->id . ' By ' . Yii::app()->user->name;
                    Yii::log($msg, 'trace', 'toast.TaskController.actionUpdate');
                    $this->redirect(array('/task/view', 'id' => $task->id));
                }
                else
                {
                    $this->redirect(array('view', 'id' => $task->id));
                }
            }
        }
        
        $responsible = User::model()->findByPk($task->responsible);
        if(null !== $responsible)
        {
            $task->responsible_realname = $responsible->realname;
        }
        
        $this->render('update', array('task' => $task, 'jobs' => $jobs));
    }
    
    /**
     * copy task 
     */
    public function actionCopy()
    {
        $task = $this->loadModel('Task');
        $copyTask = new Task();
        $copyTask->attributes = $task->attributes;
        $copyTask->name = $task->name . ' [Copy]';

        $copyJobs = array();
        foreach($task->jobs as $job)
        {
            $copyCommand = new Command();
            $copyCommand->attributes = $job->command->attributes;
            $copyCommand->name = $job->command->name . ' [Copy]';
            $copyCommand->save();
            
            $copyJob = new Job();
            $copyJob->attributes = $job->attributes;
            $copyJob->type = $job->type;
            if(Job::TYPE_COMMAND == $copyJob->type)
            {
                $copyJob->command_id = $copyCommand->id;
            }
            else
            {
                $copyJob->vtestcases = $job->vtestcases;
            }
            $copyJobs[] = $copyJob;
        }
        $this->render('create', array('task' => $copyTask, 'jobs' => $copyJobs));
    }
    
    public function actionDelete()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $task = $this->loadModel('Task');
            $task->status = Task::STATUS_DISABLE;
            $task->cron_time = '';
            $flag = false;
            if ($task->update())
            {
                $taskDiffs = $task->getDiff();
                if(!empty($taskDiffs))
                {
                    $diffAction = new DiffAction();
                    $diffAction->model_name = get_class($task);
                    $diffAction->model_id = $task->id;
                    $diffAction->save();
                    foreach($taskDiffs as $diff)
                    {
                        $diffAttr = new DiffAttribute();
                        $diffAttr->attributes = $diff;
                        $diffAttr->diff_action_id = $diffAction->id;
                        $diffAttr->save();
                    }
                }

                $flag = true;
                $action = Task::ACTION_DEL;
                $task->sendAction($action, Task::TIMER_ACTION_TYPE);
                $task->sendAction($action, Task::MONITOR_ACTION_TYPE);
                
                // Cancel Waiting TaskRuns
                $waitingRuns = $task->taskruns(array('condition' => 'status=' . CommandRun::STATUS_WAITING));
                foreach($waitingRuns as $run)
                {
                    $commandRuns = $run->commandruns;
                    foreach($commandRuns as $commandRun)
                    {
                        $commandRun->status = CommandRun::STATUS_CANCELED;
                        $commandRun->save();
                    }
                }
                
                // Cancel Running TaskRuns
                $runningRuns = $task->taskruns(array('condition' => 'status=' . CommandRun::STATUS_RUNNING));
                foreach($runningRuns as $run)
                {
                    $run->cancelRun();
                }
            }
            
            echo CJSON::encode(array('flag' => $flag));
        }
    }
    
    public function actionCreateRun()
    {
        $task = $this->loadModel('Task');
        
        // cancel runs by API
        if($task->exclusive)
        {
            $toast = User::model()->findByAttributes(array('username' => 'TOAST'));
            $createBy = array();
            if($toast)
                $createBy[] = $toast->id;
            if($createBy)
            {
                $apiRuns = $task->taskruns(array('condition' => 'status IN(' . CommandRun::STATUS_WAITING . ',' 
                    . CommandRun::STATUS_RUNNING . ") AND created_by IN(" . join(',', $createBy) . ")",
                    'order' => 'id DESC'));
                foreach ($apiRuns as $apiRun)
                {
                    $apiRun->cancelRun();
                }
            }
        }
        
        if($run = TaskUtility::run($task, null, null, null, null, true))
        {
            $msg = 'Task #' . $task->id . ' run, task run #' . $run->id . ' just created.' ;
            Yii::log($msg, 'trace', 'toast.TaskController.actionCreateRun');
        }
        else
        {
            $msg = 'Create Run Error !!! By ' . Yii::app()->user->name;
            Yii::log($msg, 'error', 'toast.TaskController.actionCreateRun');
        }
        $this->redirect(array('/task/view', 'id' => $task->id));
    }
    
    /**
     *
     * @param type $jobNum 
     */
    public function actionGetJobView($jobNum)
    {
        $this->layout = false;
        $job = new Job();
        if (isset($_REQUEST['Job']))
        {
            $job->attributes = $_REQUEST['Job'];
            $job->id = $_REQUEST['Job']['id'];
            if(isset($_REQUEST['testcases']))
            {
                $condition = new CDbCriteria();
                $condition->addInCondition('id', $_REQUEST['testcases']);
                $condition->order = 'FIELD(id, \'' . join("','", $_REQUEST['testcases']) . '\')';
                $testcases = VTestCase::model()->findAll($condition);
                $job->vtestcases = $testcases;
            }
        }
        $this->render('jobView', array('job' => $job, 'jobNum' => $jobNum));
    }
    
    
    /**
     * Echo all task runtime as JSON.
     */
    public function actionGetAllRuntime()
    {
        $tasks = Task::model()->findAll();
        $arr = array();
        foreach($tasks as $task)
        {
            if(!empty($task->cron_time))
            {
                $arr[$task->id] = $task->cron_time;
            }
        }
        echo json_encode($arr);
    }
    
    /**
     * Echo all task svn moniter url as JSON.
     */
    public function actionGetAllURL()
    {
        $tasks = Task::model()->findAll();
        $arr = array();
        foreach($tasks as $task)
        {
            if(!empty($task->svn_url))
            {
                $arr[$task->id] = $task->svn_url;
            }
        }
        echo json_encode($arr);
    }
    
    /**
     * 
     */
    public function actionGetJobForm()
    {
        $this->layout = false;
        $job = new Job();
        if (isset($_POST['Job']))
        {
            $job->attributes = $_POST['Job'];
            $job->id = $_POST['Job']['id'];
        }
        $this->render('jobForm', array('job' => $job));
    }
    
    /**
     * 
     */
    public function actionValidateJobData(array $Job, array $Command, array $testcases = null)
    {
        $taskUtility = new TaskUtility();
        $res = array();
        list($newCmdFlag, $cmdValid, $command) = 
                $taskUtility->saveCommand($Job['type'], $Job['command_id'], $Command);
        if($cmdValid && null !== $command)
        {
            $Job['command_id'] = $command->id;
        }
        else
        {
            $Job['test_case_ids'] = $testcases;
        }
        
        list($jobValid, $job) = $taskUtility->validateJob($Job);

        if($cmdValid && null !== $command)
        {
            $res['command_code'] = 0;
            $res['command']['id'] = $command->id;
            $res['command']['name'] = $command->name;
            $res['command']['newone'] = $newCmdFlag;
        }
        else
        {
            $res['command_code'] = 1;
        }
        
        if($cmdValid && $jobValid)
        {
            $res['job_code'] = 0;
            $res['job']['machine_id'] = $job->machine_id;
        }
        else
        {
            $res['errors'] = $job->getErrors();
            if(null !== $command)
            {
                $res['errors'] += $command->getErrors();
            }
            if(isset($res['errors']['command_id']))
            {
                unset($res['errors']['command_id']);
            }
            $res['job_code'] = 1;
        }
        echo CJSON::encode($res);
    }
    
    public function actionGetHistory($id)
    {
        $this->layout = false;
        $historyProvider = new CActiveDataProvider('DiffAction', array(
            'criteria' => array(
                'condition' => 'model_name="Task" AND model_id=' . $id
            ),
            'pagination' => array(
                'pageSize' => Yii::app()->params['pageSize']
            ),
            'sort' => array(
                'defaultOrder' => 'id DESC'
            ),
        ));
        $this->render('/layouts/history', array('history' => $historyProvider));
    }
    
    public function actionRun()
    {
        $id = Yii::app()->request->getParam('id', null);
        $build = Yii::app()->request->getParam('build', null);
        $param = Yii::app()->request->getParam('param', null);
        list($res, $tasks) = TaskUtility::getTasks($id, $build);
        if(self::STATUS_SUCCESS == $res['status'])
        {
            $dev_log = Yii::app()->request->getParam('dev_log');
            $mail_to = Yii::app()->request->getParam('mail_to');
            $runIds = array();
            foreach($tasks as $task)
            {
                if($run = TaskUtility::run($task, $build, $dev_log, $mail_to, $param))
                {
                    $runIds[] = $run->id;
                    $res['msg'] .= 'Task #' . $task->id . ' run, task run #' . $run->id . ' just created.' ;
                }
                else
                {
                    $res['status'] = self::STATUS_FAILURE;
                    $res['msg'] .= 'Task #' . $task->id . ' run failed.';
                }
            }
            $res['runids'] = $runIds;
        }
        
        header('Content-type: application/json');
        $this->layout = false;
        echo CJSON::encode($res);
        Yii::app()->end();
    }
    
    public function actionEditConfig()
    {
        $content = '';
        if(isset($_GET['name']))
            $name = $_GET['name'];
        else
        {
            $name = date("YmdHis") . '_' . rand(10000, 99999) . '.conf';
            $content = @file_get_contents(Yii::app()->params['ciConfigSample']);
        }
        $configFile = Yii::app()->params['uploadPath'] . $name;
        if(isset($_REQUEST['config-content']))
        {
            $content = $_REQUEST['config-content'];
            $handle = @fopen($configFile, 'w');
            if(!$handle)
            {
                $msg = 'Cannot create file: ' . $configFile;
                Yii::log($msg, 'error', 'toast.TaskController.actionEditConfig');
                echo CJSON::encode(array('code' => 1, 'msg' => $msg));
                Yii::app()->end();
            }
            @fwrite($handle, $content);
            @fclose($handle);
            $file_url = Yii::app()->request->getBaseUrl(true) . '/upload/attachments/' . $name;
            echo CJSON::encode(array('code' => 0, 'msg' => 'success', 'url' => $file_url, 'name' => $name, 'newname' => $name));
            Yii::app()->end();
        }
        else
        {
            if(@file_exists($configFile))
            {
                $content = @file_get_contents($configFile);
            }
            $this->layout = false;
            $this->render('editor', array('content' => $content));
        }
    }
}
?>