<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class RunController extends Controller
{
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
                'actions' => array('initTaskRun', 'case', 'initCommandRun', 'updateRun', 'createResult', 'updateStatus', 'renderResultImg', 'getOutput', 'updateAllRun', 'getCaseOutput'),
                'users' => array('*')
            ),
            array(
                'allow',
                'actions' => array('index', 'view', 'case', 'openOutput', 'cancel', 'updateCause', 'getDetail', 'getStages', 'getTaskRunStatus', 'getCommandRunStatus'),
                'users' => array('@')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    public function actionIndex()
    {
        $vTaskRun = $this->listModels('VTaskRun');
        $condition = null;
        if(isset($_GET['q']))
            $condition = $this->getCondition($_GET['q']);        
        
        $this->render('index',array(
            'vTaskRunProvider' => $vTaskRun->search(Yii::app()->user->getPageSize(), $condition),
            'vTaskRun' => $vTaskRun
        ));
    }
    
    public function actionView()
    {
        $vTaskRun = $this->loadModel('VTaskRun');
        $task = $vTaskRun->task;
        $this->redirect(array('/task/view', 'id' => $task->id, 'runID' => $vTaskRun->id));
    }

    /**
     * get commandrun case result
     */
    public function actionCase($taskrun = null, $commandrun = null, $filter = null)
    {
        $this->layout = false;
        $condition = RunUtility::getRunIdCondition($taskrun, $commandrun);
        $vTaskRun = RunUtility::getVTaskRun($taskrun, $commandrun);
        $view = RunUtility::getCaseView($commandrun);
        $items = RunUtility::getTabItems($vTaskRun, $commandrun, $filter, $condition);
        $condition->mergeWith(RunUtility::getFilterCondition($filter));
        $condition->order = 'id ASC';
        
        $this->render($view, array(
            'resultProvider' => CaseResult::model()->search(Yii::app()->user->getPageSize(), $condition),
            'items' => $items,
        ));
    }
    
    public function actionInitTaskRun($id, $stage)
    {
        $taskRun = TaskRun::model()->findByPk($id);
        if(!$taskRun) return;
        
        if($taskRun->status == CommandRun::STATUS_CANCELED)
        {
            $commandRuns = $taskRun->commandruns;
            foreach ($commandRuns as $commandRun)
            {
                $commandRun->updateRun();
            }
        }
        else
        {
            $criteria = new CDbCriteria();
            $criteria->addInCondition('status', array(CommandRun::STATUS_WAITING, CommandRun::STATUS_RUNNING, CommandRun::STATUS_CANCELING));
            $criteria->addCondition("id<$taskRun->id");
            
            $running = TaskRun::model()->findByAttributes(array('task_id' => $taskRun->task_id), $criteria);
            if(!$taskRun->task->exclusive || $taskRun->status != CommandRun::STATUS_WAITING || $running === NULL)
                $taskRun->sendAction(TaskRun::ACTION_CREATE, $stage, $taskRun->build);
        }
    }
    
    public function actionInitCommandRun($id)
    {
        $commandRun = CommandRun::model()->findByPk($id);
        if(!$commandRun) return;
        
        if($commandRun->status == CommandRun::STATUS_CANCELED)
        {
            $commandRun->updateRun();
        }
        else
            $commandRun->sendAction(CommandRun::ACTION_CREATE);
    }
    
    public function actionGetOutput()
    {
        $run = $this->loadModel('CommandRun');
        if(Yii::app()->request->isAjaxRequest)
        {
            $json['runid'] = $run->id;
            $json['hascompleted'] = $run->hasCompleted();
            $json['status'] = $run->getStatusText();
            $outputFile = Yii::app()->params['runOutputPath'] . $run->id . '.log';
            $size = @filesize($outputFile);
            if($size > 4194304)
            {
                $output = Yii::t('TOAST', 'Content is too big');
            }
            else
            {
                $output = @file_get_contents($outputFile);
                //$json['output'] = preg_replace( '/\n/i',"\r", $output);
                if (FALSE === $output)
                    $output = '';
            }

            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Content-Type: text/html');

            echo CJSON::encode($json) . 'OUTPUT:' . $output;
        }
        else
        {
            $outputFile = Yii::app()->params['runOutputPath'] . $run->id . '.log';
            header('Pragma: public');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: pre-check=0, post-check=0, max-age=0');
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . filesize($outputFile));
            header('Content-Disposition:attachment;filename=' . $run->id . '.txt');
            ob_clean();
            flush();
            $msg = 'Download TaskRun#' . $run->id . ' output By ' . Yii::app()->user->name;
            Yii::log($msg, 'trace', 'toast.RunController.actionGetOutput');
            @readfile($outputFile);
        }
    }

    public function actionGetCaseOutput($resultId)
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Content-Type: text/html');
        echo RunUtility::getComparisonCaseOutput($resultId);
    }
    
    /**
     * get commandrun detail
     */
    public function actionGetDetail()
    {
        $vCommandRun = $this->loadModel('VCommandRun');
        $this->layout = false;
        $resultProvider = new CActiveDataProvider('CaseResult', array(
            'criteria' => array(
                'condition' => 'command_run_id=' . $vCommandRun->id,
            ),
            'pagination' => array(
                'pageSize' => Yii::app()->params['pageSize']
            ),
            'sort' => array(
                'defaultOrder' => "id DESC"
            ),
        ));

        
        $this->render('detail', array('vRun' => $vCommandRun,
            'resultProvider' => $resultProvider));
    }
    
    /**
     * get this taskrun's stages html
     */
    public function actionGetStages()
    {
        $vTaskRun = $this->loadModel('VTaskRun');
        $this->layout = false;
        $this->render('jobruns', array('vTaskRun' => $vTaskRun));
    }    
    
    public function actionGetTaskRunStatus()
    {
        $taskRun = $this->loadModel('TaskRun');
        $json['runid'] = $taskRun->id;
        $json['hascompleted'] = $taskRun->hasCompleted();
        echo CJSON::encode($json);
    }
    
    public function actionGetCommandRunStatus()
    {
        $commandRun = $this->loadModel('CommandRun');
        $json['runid'] = $commandRun->id;
        $json['hascompleted'] = $commandRun->hasCompleted();
        echo CJSON::encode($json);
    }
    
    public function actionOpenOutput()
    {
        $CommandRun = $this->loadModel('CommandRun');
        $this->layout = false;
        $this->render('output', array(
            'run' => $CommandRun
        ));
    }
    
    /**
     * Update all run which not completed 
     */
    public function actionUpdateAllRun()
    {
        $status = Yii::app()->request->getParam('status', CommandRun::STATUS_CANCELED);
        $condition = new CDbCriteria();
        $condition->compare('status', CommandRun::STATUS_WAITING, false, 'OR');
        $condition->compare('status', CommandRun::STATUS_RUNNING, false, 'OR');
        $condition->compare('status', CommandRun::STATUS_CANCELING, false, 'OR');
        $runs = CommandRun::model()->findAll($condition);
        foreach($runs as $run)
        {
            $_REQUEST['id'] = $run->id;
            $_REQUEST['status'] = CommandRun::STATUS_ABORTED;
            $this->actionUpdateRun();
        }
    }

    public function actionCancel()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $taskRun = $this->loadModel('TaskRun');
            $res = $taskRun->cancelRun();
            
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Content-Type: text/json');

            echo CJSON::encode($res);
        }
    }

    public function actionUpdateRun()
    {
        if(isset($_REQUEST['id']))
        {
            $commandRun = CommandRun::model()->findByPk($_REQUEST['id']);
            if($commandRun != null)
            {
                $msg = 'Receive update command run info with id#' . $commandRun->id . 
                        ' info: ' . CJSON::encode($_REQUEST) . 
                        ' source is ' . Yii::app()->request->userHost
                        . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.RunController.actionUpdateRun');
                echo $msg;
                if(isset($_REQUEST['return_value']))
                    $_REQUEST['return_code'] = $_REQUEST['return_value'];
                $complete = $commandRun->updateRun($_REQUEST);
                
                if($complete == true && $commandRun->taskrun != NULL)
                {
                    $task = $commandRun->taskrun->task;
                    if (($task->report_filter == Task::REPORT_FAIL && $commandRun->taskrun->result == CommandRun::RESULT_FAILED) 
                            || $task->report_filter == Task::REPORT_ALL)
                    {
                        $vTaskRun = VTaskRun::model()->findByPk($commandRun->taskrun->id);
                        $this->mail($vTaskRun);
                    }
                }
            }
            else
            {
                $msg = 'Receive failed update run command because can not find run with id = ' 
                        . $_REQUEST['id'] . ' from ' . Yii::app()->request->userHost
                        . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'error', 'toast.RunController.actionUpdateRun');
                echo 'update failed, because can not find run with id = ' . $_REQUEST['id'];
            }
        }
        else
        {
            $msg = 'Receive failed update run command from ' . Yii::app()->request->userHost
                     . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'error', 'toast.RunController.actionUpdateRun');
            echo 'update failed, because id is required.';
        }
    }

    public function actionCreateResult()
    {
        if(isset($_REQUEST['run_id']))
        {
            $run = TaskRun::model()->findByPk($_REQUEST['run_id']);
            if($run != null)
            {
                $result = new Result();
                if(isset($_REQUEST['case_id']))
                {
                    $result->test_case_id = $_REQUEST['case_id'];
                    echo 'Receive result case_id: ' . $result->test_case_id;
                }
                if(isset($_REQUEST['case_name']))
                {
                    $result->case_name = $_REQUEST['case_name'];
                    echo 'Receive result case_name: ' . $result->case_name;
                }
                if(isset($_REQUEST['result_code']))
                {
                    $result->result = $_REQUEST['result_code'];
                    echo 'Receive result result_code: ' . $result->result;
                }
                if(isset($_REQUEST['append_info']))
                {
                    $result->append_info = $_REQUEST['append_info'];
                    echo 'Receive result append_info: ' . $result->append_info;
                }
                $result->run_id = $run->id;
                if($result->save())
                {
                    $msg = 'Receive create result command from ' . Yii::app()->request->userHost
                     . '(' . Yii::app()->request->userHostAddress . ')';
                    Yii::log($msg, 'trace', 'toast.RunController.actionCreateResult');
                    echo 'update run #' . $run->id . ' success';
                }
                else
                {
                    $errorMsg = '';
                    foreach($result->attributes as $attr => $val)
                    {
                        $errorMsg .= $result->getError($attr);
                    }
                    $msg = 'Receive failed create result command because of ' . $errorMsg .  ' from ' . Yii::app()->request->userHost
                     . '(' . Yii::app()->request->userHostAddress . ')';
                    Yii::log($msg, 'trace', 'toast.RunController.actionCreateResult');
                    echo 'create result failed, because ' . $errorMsg;
                }
            }
            else
            {
                $msg = 'Receive failed create result command  because can not find run with run_id = ' . $_REQUEST['run_id'] 
                     . ' from ' . Yii::app()->request->userHost
                     . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.RunController.actionCreateResult');
                echo 'update failed, because can not find run with run_id = ' . $_REQUEST['run_id'];
            }
        }
        else
        {
            $msg = 'Receive failed create result command because of run_id required from ' . Yii::app()->request->userHost
                     . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'trace', 'toast.RunController.actionCreateResult');
            echo 'create result failed, because run_id is required.';
        }
    }

    public function actionRenderResultImg()
    {
        $run = $this->loadModel('TaskRun');
        $chart = Yii::createComponent('application.extensions.pchart.Chart', 525, 310, Yii::t('VReport', 'Test Case Chart'));
        header('Content-Type: image/gif');
        $pass = $run->case_passed_amount;
        $fail = $run->case_failed_amount;
        $other = $run->case_total_amount - $run->case_completed_amount;
        $data = array();
        $labels = array();

        if(($other + $pass + $fail) == 0)
        {
            $pass = 1;
        }

        if($other == 0)
        {
            $data = array($pass, $fail);
            $labels = array(Yii::t('Result', 'Result Passed'), Yii::t('Result',  'Result Failed'));
        }
        else
        {
            $data = array($pass, $fail, $other);
            $labels = array(Yii::t('Result', 'Result Passed'), Yii::t('Result', 'Result Failed'), Yii::t('Result', 'Result Other'));
        }
        $chart->setPieData($data, $labels);
        $chart->getPie()->Render();
    }

    public function actionUpdateCause()
    {
        return;
//        $run = $this->loadModel('TaskRun');
//        if(isset($_REQUEST['failure_cause']))
//        {
//            $json['status'] = 'failure';
//            $run->failure_cause = trim($_REQUEST['failure_cause']);
//            if($run->save())
//            {
//                $json['status'] = 'success';
//                $json['cause'] = $run->failure_cause;
//                
//                $msg = 'Update run #' . $run->id . ' failure cause By ' . Yii::app()->user->name;
//                Yii::log($msg, 'trace', 'toast.RunController.actionUpdateCause');
//                
//                $vRun = VTaskRun::model()->findByPk($run->id);
//                $this->mail($vRun);
//            }
//            echo CJSON::encode($json);
//        }
//        $this->mail($vRun);
    }
    
    private function mail($vRun)
    {
        $this->layout = false;
        $mailHtml = $this->render('mail', array('vRun' => $vRun), true);
        $mailer = TMailer::init();
        // Normal
        $priority = 3;
        if(CommandRun::RESULT_FAILED == $vRun->result)
        {
            // High
            $priority = 1;
        }
        $mailer->Priority = $priority;
        $responsibleEmail = User::model()->findByPk($vRun->vtask->responsible);
        if($responsibleEmail !== null)
        {
            $mailer->AddAddress($responsibleEmail->email);
        }
        $reportTo = explode(',', TString::arrangeSplit($vRun->report_to, array(',', '，', ';', '；')));
        $emailValid = new CEmailValidator();
        foreach($reportTo as $addr)
        {
            if(!$emailValid->validateValue($addr))
            {
                $condition = new CDbCriteria();
                $condition->compare('realname', $addr . '%', true, 'AND', false);
                $user = User::model()->find($condition);
                if($user !== null)
                {
                    $addr = $user->email;
                    $mailer->AddAddress($addr);
                }
            }
            else
            {
                $mailer->AddAddress($addr);
            }
        }
        
        // for dev
        $commiterEmails = $vRun->getCommiterEmails();
        foreach($commiterEmails as $email)
        {
            $mailer->AddAddress($email);
        }

        if($vRun->result != CommandRun::RESULT_NONE)
        {
            $mailer->Subject = '[' . $vRun->getResultText() . '] ';
        }
        $mailer->Subject .= Yii::t('TOAST', 'Auto Task Label') . $vRun->task_name . ' ' . Yii::t("Run", "Run Result") . " #$vRun->id By $vRun->created_by_realname @ $vRun->create_time";
        $mailer->Body = $mailHtml;
        $flag = $mailer->Send();
        Yii::log('Mail sended, the status is ' . $flag, 'trace', 'toast.RunController.mail');
        if(!$flag)
        {
            Yii::log('Mail sended fail, because ' . $mailer->ErrorInfo, 'error', 'toast.RunController.mail');
        }
    }
}
?>