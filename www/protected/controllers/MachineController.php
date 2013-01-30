<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class MachineController extends Controller
{
    public function filters()
    {
        return array(
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
                'actions' => array('updateMachine', 'getInstallScript', 'updateAgent', 'rrd', 'addMachine', 
                    'getAllMachine', 'updateAllMachine', 'unassigned', 'responsiblebyme', 'lookup'),
                'users' => array('*'),
            ),
            array(
                'allow',
                'actions' => array('syncMachineStatus', 'update', 'index',
                    'create', 'installAgent', 'getStatus', 'getMachineListByProduct',
                    'getTasks', 'getMachineOpts', 'view', 'delete'),
                'users' => array('@')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    /**
     * before action
     */
    protected function beforeAction($action)
    {
        $cookie = new CHttpCookie(WebUser::CONTROLLER, $this->getId());
        $cookie->expire = time() + 60 * 60 * 24 * 30;
        Yii::app()->request->cookies[WebUser::CONTROLLER] = $cookie;
        return parent::beforeAction($action);
    }
    
    public function actionIndex()
    {
        $vMachine = $this->listModels('VMachine');
        
        $condition = null;
        if(isset($_GET['q']))
            $condition = $this->getCondition($_GET['q']);
        
        $this->render('index',array(
            'vMachineProvider' => $vMachine->search(Yii::app()->user->getPageSize(), $condition),
            'vMachine' => $vMachine
        ));
    }
    
    public function actionUnassigned()
    {
        $vMachine = $this->listModels('VMachine');
        $vMachine->product_id = NULL;
        $this->render('index',array(
            'vMachineProvider' => $vMachine->search(Yii::app()->user->getPageSize()),
            'vMachine' => $vMachine
        ));
    }
    
    public function actionResponsibleByMe()
    {
        $vMachine = $this->listModels('VMachine');
        if ($vMachine->product_id == NULL)
            $vMachine->product_id = Yii::app()->user->getCurrentProduct();
        $vMachine->responsible = Yii::app()->user->id;
        $this->render('index',array(
            'vMachineProvider' => $vMachine->search(Yii::app()->user->getPageSize()),
            'vMachine' => $vMachine
        ));
    }
    
    public function actionAddMachine()
    {
        $this->actionUpdateMachine();
    }

    public function actionGetAllMachine()
    {
        $machines = Machine::model()->findAll();
        $arr = array();
        foreach($machines as $machine)
        {
            $arr[$machine->id] = $machine->name;
        }
        echo json_encode($arr);
    }
    
    public function actionUpdateAllMachine()
    {
        $status = Yii::app()->request->getParam('status', Machine::STATUS_DOWN);
        if(Machine::model()->updateAll(array('status' => $status, 'update_time' => date(Yii::app()->params->dateFormat))))
        {
            echo json_encode(array('result' => 'success'));
        }
        else
        {
            echo json_encode(array('result' => 'failed'));
        }
        $msg = 'Update All Machines status to ' . $status;
        Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateAllMachine');
    }
    
    public function actionCreate()
    {
        $machine = new Machine();
        $machine->product_id = Yii::app()->user->currentProduct;
        $machine->port = TString::randomString(8);
        $machine->type = Machine::TYPE_LINUX;
        if(isset($_POST['Machine']))
        {
            $machine->attributes = $_POST['Machine'];
            if($machine->save())
            {
                $machine->sendAction(Machine::ACTION_ADD);
                $msg = 'Add Machine #' . $machine->id . ' ' . $machine->name. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.MachineController.actionCreate');
                $this->redirect(array('installAgent', 'id' => $machine->id));
            }
        }
        $this->render('create', array('machine' => $machine));
    }

    public function actionInstallAgent()
    {
        $machine = $this->loadModel('Machine');
        $this->layout = false;
        $this->render('install', array('machine' => $machine));
    }

    public function actionGetInstallScript()
    {
        $machine = $this->loadModel('Machine');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=toast.py');
        echo $machine->getAgentInstallScript();
    }

    public function actionGetStatus()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $machine = $this->loadModel('Machine');
            $json['status'] = $machine->getStatusText();
            $json['clazz'] = $machine->getStatusStyle();
            echo CJSON::encode($json);
        }
    }

    public function actionGetDefaultPort()
    {
        if(isset($_GET['type']))
        {
            $machine = new Machine();
            $machine->type = $_GET['type'];
            $json['port'] = $machine->getDefaultPort();
            echo CJSON::encode($json);
        }
    }

    public function actionSyncMachineStatus()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $style = array();
            $machines = Machine::model()->findAll();
            foreach($machines as $machine)
            {
                $style[] = array('id' => $machine->id, 'clazz' => $machine->getStatusStyle());
            }
            echo CJSON::encode($style);
        }
    }

    public function actionGetMachineOpts()
    {
        if(Yii::app()->request->isAjaxRequest && isset($_GET['name']))
        {
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control: no-cache, must-revalidate, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Pragma: no-cache');
            echo CHtml::dropDownList($_GET['name'], '',
                    Machine::model()->getMachineOptions(), array('class' => 'machine-list'));
        }
    }

    public function actionUpdateMachine()
    {
        $machine = NULL;
        if(isset($_REQUEST['hostname']))
        {
            $machine = Machine::model()->findByAttributes(array('hostname' => $_REQUEST['hostname']));
        }
        if($machine == NULL)
            $machine = new Machine();
        
        if($machine != null)
        {
            if(isset($_REQUEST['hostname']))
            {
                $machine->name = $_REQUEST['hostname'];
                $machine->hostname = $_REQUEST['hostname'];
                $msg = 'Receive update machine name  ' . $machine->name . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['status']))
            {
                $machine->status = $_REQUEST['status'];
                $msg = 'Receive update machine status  ' . $machine->status . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['type']))
            {
                $machine->type = $_REQUEST['type'];
                $msg = 'Receive update machine type  ' . $machine->type . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['version']))
            {
                $machine->agent_version = $_REQUEST['version'];
                $msg = 'Receive update machine version  ' . $machine->agent_version . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['desc_info']))
            {
                $machine->desc_info = $_REQUEST['desc_info'];
                $msg = 'Receive update machine desc_info ' . $machine->desc_info . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['ip']))
            {
                $machine->ip = $_REQUEST['ip'];
                $msg = 'Receive update machine ip ' . $machine->ip . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['os']))
            {
                $machine->os = $_REQUEST['os'];
                $msg = 'Receive update machine os ' . $machine->os . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['cpu']))
            {
                $machine->cpu = $_REQUEST['cpu'];
                $msg = 'Receive update machine cpu ' . $machine->cpu . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['memory']))
            {
                $machine->memory = $_REQUEST['memory'];
                $msg = 'Receive update machine memory ' . $machine->memory . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if(isset($_REQUEST['disk']))
            {
                $machine->disk = $_REQUEST['disk'];
                $msg = 'Receive update machine disk ' . $machine->disk . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.MachineController.actionUpdateMachine');
            }
            if($machine->save())
            {
                $msg = 'Receive update machine status command from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace', 'toast.RunController.actionUpdateStatus');

                echo CJSON::encode(array('code' => 0, 'msg' => 'success', 'machine' => $machine->attributes));
                
                if($machine->status == Machine::STATUS_DOWN && $machine->notify && $machine->responsible)
                {
                    // send mail to the responsible
                    $vMachine = VMachine::model()->findByPk($machine->id);
                    $this->mail($vMachine);
                }
                
                if($machine->status == Machine::STATUS_IDLE)
                {
                    // array save CommandRuns which will be called
                    $waitRunCommands = array();
                    // array save TaskRun $waitRunCommands belong
                    $waitTaskRunID = array();
                    // array save Task $waitRunCommands belong
                    $waitTaskID = array();
                    
                    // let task run which is waiting for this machine.
                    $criteria = new CDbCriteria();
                    $criteria->compare('machine_id', $machine->id);
                    $criteria->addInCondition('status', array(CommandRun::STATUS_WAITING, CommandRun::STATUS_RUNNING, CommandRun::STATUS_CANCELING));
                    $criteria->order = 'task_run_id ASC';
                    $commandRuns = CommandRun::model()->findAll($criteria);
                    
                    foreach ($commandRuns as $commandRun)
                    {
                        // if just run a command, then add it to $waitRunCommands
                        if($commandRun->task_run_id == NULl)
                        {
                            $waitRunCommands[$commandRun->id] = $commandRun;
                        }
                        else
                        {
                            // if this commandrun is the save taskrun then add it
                            if(in_array($commandRun->task_run_id, $waitTaskRunID))
                            {
                                $waitRunCommands[$commandRun->id] = $commandRun;
                                $waitTaskRunID[$commandRun->id] = $commandRun->task_run_id;
                                $waitTaskID[$commandRun->id] = $commandRun->taskrun->task_id;
                            }
                            else
                            {
                                // if task is not exclusive then add it
                                if(!in_array($commandRun->taskrun->task_id, $waitTaskID) || $commandRun->taskrun->task->exclusive == 0)
                                {
                                    $waitRunCommands[$commandRun->id] = $commandRun;
                                    $waitTaskRunID[$commandRun->id] = $commandRun->task_run_id;
                                    $waitTaskID[$commandRun->id] = $commandRun->taskrun->task_id;
                                }
                            }
                        }
                    }

                    foreach($waitRunCommands as $commandRun)
                    {
                        if($commandRun->status == CommandRun::STATUS_WAITING)
                            $commandRun->sendAction(CommandRun::ACTION_CREATE);
                    }
                }
            }
            else
            {
                $errorMsg = '';
                foreach($machine->attributes as $attr => $val)
                {
                    $errorMsg .= $machine->getError($attr) . ' ';
                }
                $msg = 'Receive failed update machine status command from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'error', 'toast.RunController.actionUpdateStatus');
                
                echo urldecode(CJSON::encode(array('code' => 1, 'msg' => urlencode($errorMsg), 'machine' => NULL)));
            }
        }
        else
        {
            $msg = 'Receive failed update machine status command from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'error', 'toast.RunController.actionUpdateMachine');
            echo 'update machine status failed, because can not find machine with id = ' . $_REQUEST['id'];
        }
    }
    
    public function actionUpdateAgent()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $flag = false;  
            $machine = $this->loadModel('Machine');
            $machine->updateAgent();
            $machine->status = Machine::STATUS_DOWN;
            if($machine->update())
                $flag = true;
            echo CJSON::encode(array('flag' => $flag));

            $msg = 'Update Machine Agent #' . $machine->id . ' By ' . Yii::app()->user->name;
            Yii::log($msg, 'info', 'toast.Machine.actionUpdateAgent');
        }
    }

    /**
     * action img
     */
    public function actionRrd()
    {
        $moniter = $this->loadModel('MachineMonitor');
        if(isset($_REQUEST['MachineMonitor']))
        {
            $moniter->attributes = $_REQUEST['MachineMonitor'];
        }
        header("Content-type: image/png");
        $moniter->getDetailObj()->getRRDImg();
    }
    
    /**
     * action view
     */
    public function actionView()
    {
        $machine = $this->loadModel('VMachine');
        $start = Yii::app()->request->getParam('start', date('Y-m-d H:i:s', (time() - 86400)));
        $end = Yii::app()->request->getParam('end', date('Y-m-d H:i:s'));
        $groups = Yii::app()->request->getParam('groups', array('cpu'));
        $vTaskProvider = VTask::model()->getTasksByMachine($machine->id, Yii::app()->params['pageSize']);
        $this->render('view', array(
            'machine' => $machine,
            'vTaskProvider' => $vTaskProvider,
            'groups' => $groups,
            'start' => $start,
            'end' => $end
        ));
    }

    /**
     * action update
     */
    public function actionUpdate()
    {
        $machine = $this->loadModel('Machine');
        if(isset($_POST['Machine']))
        {
            $oldProcesses = $machine->processes;
            $machine->attributes = $_POST['Machine'];
            if($machine->product_id === NULL)
            {
                $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
                $this->created_by = $this->updated_by = Yii::app()->user->id;
            }
            if($machine->update())
            {
                $machine->mapp($oldProcesses);
                $this->redirect(array('view', 'id' => $machine->id));
            }
        }
        $this->render('update', array('machine' => $machine));
    }

    /**
     * action delete
     */
    public function actionDelete()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $machine = $this->loadModel('Machine');
            $jobs = Job::model()->findAllByAttributes(array('machine_id' => $machine->id));
            $flag = false;
            if(empty($jobs))
            {
                if($machine->delete())
                {
                    $machine->sendAction(Machine::ACTION_DEL);
                    $flag = true;
                }
            }
            echo CJSON::encode(array('flag' => $flag));
        }
    }
    
    /**
     * action get machine by product
     */
    public function actionGetMachineListByProduct()
    {
        if(isset($_GET['product_id']))
        {
            $machines = Machine::model()->findAllByAttributes(array('product_id' => $_GET['product_id']));
            $jsonArr = array();
            foreach($machines as $machine)
            {
                $machineArr['id'] = $machine->id;
                $machineArr['name'] = $machine->name;
                $machineArr['status'] = $machine->status;
                $jsonArr[] = $machineArr;
            }
            echo CJSON::encode($jsonArr);
        }
    }
    
    /**
     * action get tasks
     */
    public function actionGetTasks()
    {
        $machine = $this->loadModel('Machine');
        $tasks = VTask::model()->getTasksByMachine($machine->id, Yii::app()->user->getPageSize());
        $this->render('tasks', array(
            'machine' => $machine,
            'tasks' => $tasks
        ));
    }
    
    public function actionLookUp($term, $task = NULL, $limit = 0, $page = 1)
    {
//        if(Yii::app()->request->isAjaxRequest)
        {
            $query = $term;
            $condition = new CDbCriteria();
            $condition->compare('name', $query, true);
            $condition->compare('hostname', $query, true, 'OR');
            $condition->compare('ip', $query, true, 'OR');
            $condition->addCondition ('product_id IS NOT NULL');
            $condition->compare('responsible_username', $query, true, 'OR');
            $condition->compare('responsible_realname', $query, true, 'OR');
            
            $dataProvider = new CActiveDataProvider('VMachine', array(
                'criteria' => $condition,
                'pagination' => ($limit > 0)?array(
                    'pageSize' => $limit,
                    'pageVar' => 'page',
                ):false
            ));
            $machines = $dataProvider->getData();
            $total = $dataProvider->totalItemCount;
            
            $linux = array();
            $windows = array();
            foreach ($machines as $machine)
            {
                $machineInfo = array('id' => $machine->id, 'name' => $machine->name, 
                    'hostname' => $machine->hostname, 'ip' => $machine->ip, 'responsible' => $machine->responsible_realname,
                    'type' => $machine->getTypeText(), 'style' => $machine->getStatusStyle());
                if($machine->type == Machine::TYPE_LINUX)
                    $linux[] = $machineInfo;
                else
                    $windows[] = $machineInfo;
            }
            $result[] = array('name' => Yii::t('Machine', 'Linux'), 'children' => $linux);
            $result[] = array('name' => Yii::t('Machine', 'Windows'), 'children' => $windows);
            header('Content-type: text/html; charset=UTF-8');
            echo CJSON::encode(array('total' => $total, 'machines' => $result));
       }
    }
    
    private function mail($vMachine)
    {
        $this->layout = false;
        $mailHtml = $this->render('mail', array('vMachine' => $vMachine), true);
 
        $mailer = TMailer::init();
        $responsibleEmail = User::model()->findByPk($vMachine->responsible);
        if($responsibleEmail !== null)
        {
            $mailer->AddAddress($responsibleEmail->email);
        }
        
        $mailer->Subject = '[TOAST][' . Yii::t('Machine', 'Notify') . '] ' . $vMachine->name;
        $mailer->Body = $mailHtml;
        
        $flag = $mailer->Send();
        Yii::log('Mail sended, the status is ' . $flag, 'trace', 'toast.MachineController.mail');
        if(!$flag)
        {
            Yii::log('Mail sended fail, because ' . $mailer->ErrorInfo, 'trace', 'toast.MachineController.mail');
        }
    }
}
?>