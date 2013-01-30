<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class CommandController extends Controller
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
                'actions' => array('updateCommand', 'getCommandOpts', 'getCommandDetail'),
                'users' => array('*')
            ),
            array(
                'allow',
                'actions' => array('update', 'index', 'create', 'view',
                    'delete', 'getTasks', 'getCommandOpts', 'getHistory',
                    'createRun', 'cancel', 'viewRun', 'openOutput', 'getOutput'),
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
        $vCommand = $this->listModels('VCommand');
        
        $condition = null;
        if(isset($_GET['q']))
            $condition = $this->getCondition($_GET['q']);  
        
        $this->render('index', array(
            'vCommandProvider' => $vCommand->search(Yii::app()->user->getPageSize(), $condition),
            'vCommand' => $vCommand
        ));
    }
    
    /**
     * action view
     */
    public function actionView()
    {
        $vCommand = $this->loadModel('VCommand');
        $options = array('order' => 'id DESC', 'limit' => 1);
        if (isset ($_REQUEST['runID']))
            $options['condition'] = 'id=' . $_REQUEST['runID'];
        $vCommandRun = current($vCommand->vcommandruns($options));
        $lastRunInfo = NULL;
        if(!$vCommandRun)
        {
            $vCommandRun = NULL;
            $lastRunInfo = new VCommandRun();
            $usedJob = Job::model()->findByAttributes(
                    array('updated_by' => $vCommand->updated_by, 'command_id' => $vCommand->id),
                    array('order' => 'id DESC'));
            if($usedJob)
            {
                if($usedJob->machine)
                {
                    $lastRunInfo->machine_id = $usedJob->machine_id;
                    $lastRunInfo->machine_name = $usedJob->machine->name;
                }
            }
        }
        else
        {
            $lastRunInfo = $vCommandRun;
        }
        
        $vTaskProvider = VTask::model()->getTasksByCommand($vCommand->id, Yii::app()->params['pageSize']);
        $vRunProvider = new CActiveDataProvider('VCommandRun', array(
            'criteria' => array(
                'condition' => 'command_id=' . $vCommand->id,
            ),
            'pagination' => array(
                'pageSize' => Yii::app()->params['pageSize']
            ),
            'sort' => array(
                'defaultOrder' => "id DESC"
            ),
        ));

        $this->render('view', array(
            'vCommand' => $vCommand,
            'vCommandRun' => $vCommandRun,
            'vTaskProvider' => $vTaskProvider,
            'vRunProvider' => $vRunProvider,
            'lastRunInfo' => $lastRunInfo,
        ));
    }

    public function actionCreate()
    {
        $command = new Command();
        
        if(Yii::app()->request->isAjaxRequest)
        {
            if(isset($_REQUEST['Command']))
            {
                $command->attributes = $_REQUEST['Command'];
                if(isset($_REQUEST['Command']['parser_id']))
                {
                    $command->parser_id = join(',', $_REQUEST['Command']['parser_id']);
                }
                if($command->validate() && $command->save())
                {
                    $msg = 'Add Command #' . $command->id . ' ' . $command->name. ' By ' . Yii::app()->user->name;
                    Yii::log($msg, 'trace', 'toast.CommandController.actionCreate');
                    $json['result'] = true;
                    $json['commandID'] = $command->id;
                    $json['commandName'] = $command->name;
                }
                else
                {
                    $json['result'] = false;
                    $json['error'] = $command->getErrors();
                }
            }
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Content-Type: text/json');

            echo CJSON::encode($json);
        }
        else
        {
            if(isset($_POST['Command']))
            {
                $command->attributes = $_POST['Command'];
                if(isset($_POST['Command']['parser_id']))
                {
                    $command->parser_id = join(',', $_POST['Command']['parser_id']);
                }
                if($command->save())
                {
                    $msg = 'Add Command #' . $command->id . ' ' . $command->name. ' By ' . Yii::app()->user->name;
                    Yii::log($msg, 'trace', 'toast.CommandController.actionCreate');
                    $this->redirect(array('view', 'id' => $command->id));
                }
            }
            $this->render('create', array('command' => $command));
        }
    }

    /**
     * action update
     */
    public function actionUpdate()
    {
        $command = NULL;
        if(isset($_REQUEST['id']))
        {
            $command = Command::model()->findByPk($_REQUEST['id']);
        }
        if(!$command)
        {
            $command = new Command();
        }
        if(Yii::app()->request->isAjaxRequest)
        {
            if(isset($_REQUEST['Command']))
            {
                $command->attributes = $_REQUEST['Command'];
                if(isset($_REQUEST['Command']['parser_id']))
                {
                    $command->parser_id = join(',', $_REQUEST['Command']['parser_id']);
                }
                else
                {
                    $command->parser_id = '';
                }
                if($command->validate() && $command->save())
                {
                    $msg = 'Update Command #' . $command->id . ' ' . $command->name. ' By ' . Yii::app()->user->name;
                    Yii::log($msg, 'trace', 'toast.CommandController.actionUpdate');
                    $json['result'] = true;
                    $json['commandID'] = $command->id;
                    $creator = User::model()->findByPk($command->created_by);
                    $json['commandName'] = $command->name;
                }
                else
                {
                    $json['result'] = false;
                    $json['error'] = $command->getErrors();
                }
            }
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            header('Content-Type: text/json');

            echo CJSON::encode($json);
        }
        else
        {
            if(isset($_POST['Command']))
            {
                $command->attributes = $_POST['Command'];
                if(!isset($_POST['Command']['status']))
                    $command->status = Command::STATUS_AVAILABLE;
                if(isset($_POST['Command']['parser_id']))
                {
                    $command->parser_id = join(',', $_POST['Command']['parser_id']);
                }
                else
                {
                    $command->parser_id = '';
                }
                
                if($command->update())
                {
                    $msg = 'Update Command #' . $command->id . ' ' . $command->name. ' By ' . Yii::app()->user->name;
                    Yii::log($msg, 'trace', 'toast.CommandController.actionUpdate');
                    $this->redirect(array('view', 'id' => $command->id));
                }
            }
            if($command->parser_id)
            {
                $command->parser_id = preg_split('/,/', $command->parser_id);
            }
            $this->render('update', array('command' => $command));
        }
    }

    public function actionDelete()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $command = $this->loadModel('Command');
            $command->status = Command::STATUS_DISABLE;
            $flag = false;
            if ($command->update())
                $flag = true;
            
            echo CJSON::encode(array('flag' => $flag));
        }
    }

    public function actionCreateRun()
    {
        $command = $this->loadModel('Command');
        $commandRun = new CommandRun();
        $validate = false;
        if (isset($_REQUEST['VCommandRun']))
        {
            $commandRun->attributes = $_REQUEST['VCommandRun'];
            $commandRun->command_id = $command->id;
            $commandRun->status = CommandRun::STATUS_WAITING;
            $commandRun->result = CommandRun::RESULT_NULL;
            $validate = $commandRun->validate();
            if($validate && $commandRun->save())
            {
                $commandRun->sendAction(CommandRun::ACTION_CREATE);
                $msg = 'Create Command Run #' . $commandRun->id . ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.TaskController.actionCreateRun');
            }
        }
        echo CJSON::encode(array('validate' => $validate, 'errors' => $commandRun->getErrors()));
    }
    
    public function actionCancel()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $commandRun = $this->loadModel('CommandRun');
            if(!$commandRun->hasCanceled())
            {
                if($commandRun->status == CommandRun::STATUS_WAITING)
                {
                    $commandRun->status = CommandRun::STATUS_CANCELED;
                    $commandRun->update();
                }
                else
                {
                    $commandRun->status = CommandRun::STATUS_CANCELING;
                    $commandRun->update();
                    $commandRun->sendAction(CommandRun::ACTION_CANCEL);
                }

                $json['id'] = $commandRun->id;
                $json['status'] = $commandRun->getStatusText();

                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
                header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                header('Pragma: no-cache');
                header('Content-Type: text/json');

                echo CJSON::encode($json);
                $msg = 'Cancel CommandRun #' . $commandRun->id . ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.CommandController.actionCancel');
            }
        }
    }
    
    public function actionGetCommandOpts()
    {
        if(Yii::app()->request->isAjaxRequest && isset($_GET['name']))
        {
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            echo CHtml::dropDownList($_GET['name'], '',
                Command::model()->getCommandOptions(), array('class' => 'command-list'));
        }
    }
    
    public function actionGetCommandDetail($id)
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $command = Command::model()->findByPk($id);
            if ($command)
            {
                if($command->parser_id)
                {
                    $command->parser_id = preg_split('/,/', $command->parser_id);
                }
                echo CJSON::encode($command->attributes);
            }
        }
    }
    
    public function actionGetHistory($id)
    {
        $this->layout = false;
        $historyProvider = new CActiveDataProvider('DiffAction', array(
            'criteria' => array(
                'condition' => 'model_name="Command" AND model_id=' . $id
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
}
?>