<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class ApiController extends Controller
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * filter configuration array
     */
    public function filters()
    {
        return array(
            'accessControl'
        );
    }

    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('upload', 'runTask', 'runTaskById',
                    'runTaskByBuild', 'getTwfInfo', 'getTaskRun', 'addTestCase'),
                'users' => array('*')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    /**
     * upload
     */
    public function actionUpload()
    {
        $save_path = Yii::app()->params['uploadPath'];
        $max_size = 10 * 1024 * 1024;
        $save_url = Yii::app()->request->getBaseUrl(true) . '/upload/attachments/';
        if(isset($_FILES['attachment']))
        {
            $file_name = $_FILES['attachment']['name'];
            $tmp_name = $_FILES['attachment']['tmp_name'];
            $file_size = $_FILES['attachment']['size'];
            if(!$file_name)
            {
                $msg = Yii::t('TOAST', 'Please select upload file');
                header('Content-type: text/html; charset=UTF-8');
                echo json_encode(array('error' => 1, 'message' => $msg));
                exit;
            }
            if(@is_dir($save_path) === false)
            {
                $msg = Yii::t('TOAST', 'Upload file directory does not exist');
                header('Content-type: text/html; charset=UTF-8');
                echo json_encode(array('error' => 1, 'message' => $msg));
                exit;
            }
            if(@is_writable($save_path) === false)
            {
                $msg = Yii::t('TOAST',
                                'Permission denied to write on upload file directory');
                header('Content-type: text/html; charset=UTF-8');
                echo json_encode(array('error' => 1, 'message' => $msg));
                exit;
            }
            if($file_size > $max_size)
            {
                $msg = Yii::t('TOAST', 'Upload file is too big');
                header('Content-type: text/html; charset=UTF-8');
                echo json_encode(array('error' => 1, 'message' => $msg));
                exit;
            }
            if(@is_uploaded_file($tmp_name) === false)
            {
                $msg = Yii::t('TOAST', 'Temporary file is not upload file');
                header('Content-type: text/html; charset=UTF-8');
                echo json_encode(array('error' => 1, 'message' => $msg));
                exit;
            }

            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);

            $new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
            $file_path = $save_path . $new_file_name;
            if(move_uploaded_file($tmp_name, $file_path) === false)
            {
                $msg = Yii::t('TOAST', 'Upload file fail');
                header('Content-type: text/html; charset=UTF-8');
                echo json_encode(array('error' => 1, 'message' => $msg));
                exit;
            }
            @chmod($file_path, 0644);
            $file_url = $save_url . $new_file_name;

            header('Content-type: text/html; charset=UTF-8');
            echo json_encode(array('error' => 0, 'url' => $file_url, 'name' => $file_name, 'newname' => $new_file_name));
        }
        else
        {
            $msg = Yii::t('TOAST', 'Upload file is too big');
            header('Content-type: text/html; charset=UTF-8');
            echo json_encode(array('error' => 1, 'message' => $msg));
            exit;
        }
    }

    public function actionRunTask()
    {
        $task = Task::model()->findByAttributes(array(
            'project_id' => Yii::app()->request->getParam('project_id'),
            'type' => Yii::app()->request->getParam('type'),
            'status' => Task::STATUS_AVAILABLE
                ));
        $this->runTask($task);
    }

    public function actionRunTaskById()
    {
        $task = Task::model()->findByAttributes(array(
            'id' => Yii::app()->request->getParam('id'),
            'status' => Task::STATUS_AVAILABLE
                ));

        // receive build info from api caller, and modify the task run build
        $build = Yii::app()->request->getParam('build', '');
        if(!empty($build))
        {
            $build = '"' . addslashes($build) . '"';
        }

        $this->runTask($task, $build);
    }

    public function actionRunTaskByBuild()
    {
        $build = Yii::app()->request->getParam('build');

        $status = self::STATUS_SUCCESS;
        $msg = 'OK';
        if(empty($build))
        {
            $status = self::STATUS_FAILED;
            $msg = 'build should not be empty.';
        }
        else
        {
            $arr = array();
            if(!($arr = json_decode($build, true)))
            {
                $status = self::STATUS_FAILED;
                $msg = 'build should be json format.';
            }
            else
            {
                $buildArr = array();
                $condition = new CDbCriteria();
                foreach($arr as $info)
                {
                    $buildArr[] = addslashes(trim($info['package'], '.rpm'));
                    $condition->compare('build',
                            ',' . $info['package_name'] . ',', 'true', 'OR');
                }

                $tasks = Task::model()->findAllByAttributes(array(), $condition);
                foreach($tasks as $task)
                {
                    $this->runTask($task, ' "' . join(' ', $buildArr) . '"');
                }
            }
        }

        if(self::STATUS_FAILED == $status)
        {
            Yii::log($msg, 'info', 'toast.ApiController.runTaskByBuild');
        }
    }

    public function actionGetTwfInfo($id)
    {
        $twfInfos = array();
        $twfStorys = TwfStory::model()->findAllByAttributes(array('ifree_id' => $id),
                array('condition' => 'twf_rev_id IS NOT NULL'));
        foreach($twfStorys as $twfStory)
        {
            $attr = array();
            $attr['id'] = $twfStory->vtwf->id;
            $attr['name'] = $twfStory->vtwf->name;
            $attr['status'] = $twfStory->vtwf->getStatusText();
            $attr['requestor'] = $twfStory->vtwf->created_by_username;
            $attr['tester'] = $twfStory->vtwf->assigned_to_username;
            $attr['requestTime'] = $twfStory->vtwf->create_time;
            $attr['completeTime'] = $twfStory->vtwf->update_time;
            $attr['reportId'] = $twfStory->vtwf->id;
            $attr['rev'] = $twfStory->vtwf->rev_num;
            $twfInfos[] = $attr;
        }

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        //header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        echo CJSON::encode($twfInfos);
    }

    public function actionGetTaskRun()
    {
        $status = self::STATUS_FAILED;
        $msg = Yii::t('TOAST', 'id is required');
        if(isset($_GET['id']))
        {
            $taskRun = TaskRun::model()->findByPk($_GET['id']);
            if($taskRun != null)
            {
                $status = self::STATUS_SUCCESS;
                $msg = array();
                $msg['id'] = $taskRun->id;
                $msg['status'] = $taskRun->status;
                $msg['result'] = $taskRun->result;
                foreach($taskRun->commandruns as $idx => $commandRun)
                {
                    $msg['output'][$idx] =
                            Yii::app()->getBaseUrl(true) . '/run/getoutput/id/' . $commandRun->id;
                }
            }
            else
            {
                $msg = Yii::t('TOAST', 'TaskRun#{(id)} is null',
                                array('{(id)}' => $_GET['id']));
            }
        }
        echo CJSON::encode(array('status' => $status, 'result' => $msg));
    }

    private function runTask($task, $params = '')
    {
        $msg = array('msg' => 'Run task failed, because no task found');
        $status = self::STATUS_FAILED;
        if($task !== null)
        {
            // if runs count is more that 5, then not create new run.
            $runs = $task->taskruns(array('condition' => 'status=' . CommandRun::STATUS_WAITING));
            if(count($runs) >= 5)
            {
                $msg = array('msg' => 'Run task failed, waitting runs is more than 5');
                Yii::log($msg['msg'], 'trace', 'toast.ApiController.runTask');
                echo CJSON::encode(array('status' => self::STATUS_FAILED, 'result' => $msg));
                return;
            }
            
            $created_by = Yii::app()->request->getParam('user', 'Toast');

            // hard code for abs crontab call
            if(empty($created_by) || 'system' == strtolower($created_by))
            {
                $created_by = 'ABS';
            }
            //$user = User::model()->findByAttributes(array('username' => $created_by));
            $condition = new CDbCriteria();
            $condition->compare('username', $created_by);
            $condition->compare('username', $created_by . '.%', true, 'OR', false);
            $findUser = User::model()->find($condition);
            $user = ($findUser == NULL)?User::model()->findByAttributes(array('username' => 'ABS')):$findUser;
            
            Yii::app()->user->setId($user->id);

            $jobs = $task->jobs(array('condition' => 'stage_num=0'));
            $run = $task->createRun($jobs, null, 1, $params);

            if($run)
            {
                if($created_by != 'ABS')
                {
                    if($findUser == NULL)
                        $run->addReportTo($created_by);
                    else
                        $run->addReportTo($findUser->username);
                }

                // receive dev_log
                $dev_log = Yii::app()->request->getParam('dev_log', '');
                if(!empty($dev_log))
                {
                    $run->dev_log = $dev_log;
                    $run->save();
                }
                Yii::app()->user->logout();

                $msg['id'] = $run->id;
                $msg['created_by'] = $user->username;
                $msg['msg'] = 'Create Run #' . $run->id . ' By ' . $user->username;
                $status = self::STATUS_SUCCESS;
            }
            else
            {
                $msg['msg'] = 'Create Run Failed from Task ' . $task->id;
                $status = self::STATUS_FAILED;
            }
        }
        Yii::log($msg['msg'], 'trace', 'toast.ApiController.runTask');
        echo CJSON::encode(array('status' => $status, 'result' => $msg));
    }

    public function actionAddTestCase()
    {
        $status = self::STATUS_SUCCESS;
        $msg = array();
        if(isset($_REQUEST['TestCase']))
        {
            $case = new TestCase();
            $case->attributes = $_REQUEST['TestCase'];
            if($case->save())
            {
                $msg[] = 'Add test case success, #' . $case->id . ' just created';
            }
            else
            {
                $status = self::STATUS_FAILED;
                foreach($case->getErrors() as $errors)
                {
                    $msg = array_merge($msg, $errors);
                }
            }
        }
        else
        {
            $status = self::STATUS_FAILED;
            $msg[] = 'Receive empty.';
        }

        echo CJSON::encode(array(
            'status' => $status,
            'msg' => join(' ', $msg),
        ));
    }
//    private function mail($vRun)
//    {
//        $this->layout = false;
//        $clazz = Task::getDetailClass($vRun->task_type);
//        $task = Task::model()->findByPk($vRun->task_id);
//        $mailHtml = $this->render('/run/' . $clazz . '/mail', array('vRun' => $vRun, 'task' => $task), true);
//        $mailer = TMailer::init();
//        
//        // hard code to add committer as receiver
//        $infos = @CJSON::decode($vRun->desc_info);
//        if(is_array($infos) && isset($infos['author']))
//        {
//            $mailer->AddAddress($infos['author'] . '@taobao.com');
//        }        
//        
//        $reportTo = explode(',', $vRun->task->report_to);
//        $emailValid = new CEmailValidator();
//        foreach($reportTo as $addr)
//        {
//            if(!$emailValid->validateValue($addr))
//            {
//                $user = User::model()->findByAttributes(array('realname' => $addr));
//                if($user !== null)
//                {
//                    $addr = $user->email;
//                    $mailer->AddAddress($addr);
//                }
//            }
//            else
//            {
//                $mailer->AddAddress($addr);
//            }
//        }
//
//        if($vRun->result != Run::RESULT_NONE)
//        {
//            $mailer->Subject = '[' . $vRun->getResultText() . '] ';
//        }
//        $mailer->Subject .= Yii::t('TOAST', 'Auto Task Label') . $vRun->task_name .' ' . $vRun->name;
//        $mailer->Body = $mailHtml;
//        $flag = $mailer->Send();
//        Yii::log('Mail sended, the status is ' . $flag, 'trace', 'toast.RunController.mail');
//        if(!$flag)
//        {
//            Yii::log('Mail sended fail, because ' . $mailer->ErrorInfo, 'trace', 'toast.RunController.mail');
//        }
//    }
}
?>