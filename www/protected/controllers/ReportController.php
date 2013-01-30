<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * This is the report controller class.
 * 
 * @package application.controllers
 */
class ReportController extends Controller
{
    const TASK_TYPE = '_toasttasktype';
    const DATE = '_toastdate';
    const QUERY = '_toastreportq';
    
    /**
     * Return the filter configurations.
     * 
     * @return array a list of filter configurations
     */
    public function filters()
    {
        return array(
            'accessControl + index',
            array(
                'application.filters.ProductAccessFilter + index'
            )
        );
    }

    /**
     * Returns the access rules for this controller.
     * 
     * @return array list of access rules
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('index', 'sendcurrentreport'),
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
    
    /**
     * Render the report page.
     */
    public function actionIndex()
    {
        $report = $this->getCurrentReport();
        Yii::app()->user->setCurrentProduct($report->product_id);
        
        $condition = null;
        if(isset($_GET['q']))
        {
            $condition = $this->getCondition($_GET['q']);
            Yii::app()->user->setFlash(self::QUERY, $_GET['q']);
        }

        $this->render(get_class($report) . '/index', array('report' =>  $report, 'condition' => $condition));
    }

    /**
     * Send the report mail.
     */
    public function actionSendReport($onlyfail = false)
    {
        $products  = Product::model()->findAll();
        $report = new VReport();
        $reportTypes = array(
            Task::TYPE_REGRESS => 'regress',
            Task::TYPE_UNIT => 'unit', 
            Task::TYPE_SYSTEM => 'system',
            Task::TYPE_BVT => 'bvt',
        );
        $delimiter = ',';
        foreach($products as $product)
        {
            $report->product_id = $product->id;
            $report->date = date('Y-m-d', time() - 86400);
            unset($report->result);
            $prefix = '[TOAST]';
            $address = '_notice';
            if($onlyfail)
            {
                $prefix = '[Failed Report]';
                $address = '_fail_notice';
            }
            foreach($reportTypes as $reportType => $addrPrefix)
            {
                $report->task_type = $reportType;
                $reportIns = $report->getDetailObj();
                $notice = $addrPrefix . $address;
                $receivers = $product->$notice;
                $cc = '';
                if($onlyfail)
                {
                    $cc = join($delimiter, $reportIns->getFailedTaskOwner());
                }
                $subject = $prefix . $reportIns->getTitle();
                $this->sendReport($subject, $reportIns, $onlyfail, $receivers, $cc);
            }
        }
    }
    
    public function actionSendCurrentReport()
    {
        $report = $this->getCurrentReport('sendcurrentreport');
        $subject = '[TOAST]' . $report->getTitle();
        $receivers = Yii::app()->user->realname;
        $this->sendReport($subject, $report, false, $receivers);
    }
    
    private function sendReport($subject, $report, $onlyfail = false, $receivers = null, $cc = null)
    {
        $clazz = get_class($report);
        $this->layout = false;
        $condition = new CDbCriteria();
         if($onlyfail)
        {
             $condition->compare('case_fail_amount', '>0');
        }
        $reports = $report->search($condition);
        if(count($reports) <= 0)
        {
            $flag = false;
            $errorInfo = Yii::t('Report', 'The report is empty.');
        }
        else
        {
            $body = $this->render($clazz . '/mail',array(
                'report' => $report, 'onlyfail' => $onlyfail, 'reports' => $reports,
            ),true);
            list($flag, $errorInfo) = MailUtility::sendMail($subject, $body, $receivers, $cc);
        }
        if($flag)
        {
            Yii::log(ucfirst($clazz) . ' mail sended successfully', 'trace', 'toast.ReportController.actionSendReport');
        }
        else
        {
            Yii::log(ucfirst($clazz) . ' mail sended failed, because ' . $errorInfo, 'trace', 'toast.ReportController.actionSendReport');
        }
        echo CJSON::encode(array('status' => $flag, 'msg' => $errorInfo));
    }
    
    /**
     * Return the current report obj.
     * 
     * @return VReport report
     */
    private function getCurrentReport($action = 'index')
    {
        $report = new VReport();
        $report->module_id = Yii::app()->request->getParam('module_id');
        $report->task_type = Yii::app()->request->getParam('task_type');
        $report->date = Yii::app()->request->getParam('date');
        $report->product_id = Yii::app()->request->getParam('product_id');

        if(!isset($_REQUEST['r']))
        {
            $fields = array();
            if(!isset($report->task_type))
            {
                $report->task_type = Yii::app()->session->get(self::TASK_TYPE);
                if(!isset($report->task_type))
                {
                    $report->task_type = Task::TYPE_REGRESS;
                }
            }
            $fields['task_type'] = $report->task_type;
            
            if(!isset($report->date))
            {
                $report->date = Yii::app()->session->get(self::DATE);
                if(!isset($report->date))
                {
                    $report->date = date('Y-m-d');
                }
            }
            $fields['date'] = date('Y-m-d', strtotime($report->date));
            
            if(!isset($report->product_id))
            {
                $report->product_id = Yii::app()->user->currentProduct;
            }
            $fields['product_id'] = $report->product_id;
            
            if(isset($report->module_id) && !empty($report->module_id))
            {
                $fields['module_id'] = $report->module_id;
            }
            
            $url = Yii::app()->getBaseUrl(true) . '/report/' . $action;
            foreach($fields as $key => $val)
            {
                $url .= '/' . $key . '/' . $val;
            }
            $url .= '/r/1';
            if($query = Yii::app()->user->getFlash(self::QUERY))
            {
                $url .= '/q/' . $query;
            }
            $this->redirect($url);
        }
        
        Yii::app()->session->add(self::TASK_TYPE, $report->task_type);
        Yii::app()->session->add(self::DATE, $report->date);
        Yii::app()->user->setCurrentProduct($report->product_id);
        
        return $report->getDetailObj();
    }
}
?>