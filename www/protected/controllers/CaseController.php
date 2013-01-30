<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Case controller class. 
 * 
 * @package appliaction.controllers
 */
class CaseController extends Controller
{

    /**
     * Get filter configuration array.
     * @return array Filters
     */
    public function filters()
    {
        return array(
            array(
                'application.filters.APIAccessFilter + create, update, delete'
            ),
            'accessControl',
            array(
                'application.filters.ProductAccessFilter + index'
            )
        );
    }

    /**
     * Get access rules array..
     * @return array Rules
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('index', 'create', 'view', 'update', 'delete', 'getcode'),
                'users' => array('@')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    /**
     * Save the default configuration before action.
     */
    protected function beforeAction($action)
    {
        $cookie = new CHttpCookie(WebUser::CONTROLLER, $this->getId());
        $cookie->expire = time() + 60 * 60 * 24 * 30;
        Yii::app()->request->cookies[WebUser::CONTROLLER] = $cookie;
        if(isset($_GET['VTestCase']['parent_id']))
        {
            Yii::app()->request->cookies[WebUser::CURRENT_PROJECT_ID] = 
                    new CHttpCookie(WebUser::CURRENT_PROJECT_ID, $_GET['VTestCase']['parent_id']);
        }
        return parent::beforeAction($action);
    }

    /**
     * Index page action.
     */
    public function actionIndex()
    {
        $vTestCase = $this->listModels('VTestCase');
        $condition = null;
        if(isset($_GET['q']))
        {
            $condition = $this->getCondition($_GET['q']);
        }
        if(Yii::app()->request->isAjaxRequest)
        {
            $testCases = $vTestCase->search(Yii::app()->user->getPageSize(), $condition, false);
            $data = array('status' => 'success', 'testcases' => $testCases);
            echo CJSON::encode($data);
        }
        else
        {
            $this->render('index', array(
                'vTestCase' => $vTestCase,
                'vTestCaseProvider' => $vTestCase->search(Yii::app()->user->getPageSize(), $condition),
            ));   
        }
    }
    
    /**
     * View action.
     */
    public function actionView()
    {
        $vTestCase = $this->loadModel('VTestCase');
        $this->render('view', array(
            'vTestCase' => $vTestCase
        ));
    }
        
    /**
     * Create action.
     */
    public function actionCreate()
    {
        $testCase = new TestCase();
        
        if(isset($_REQUEST['TestCase']))
        {
            $testCase->attributes = $_REQUEST['TestCase'];
            $vaild = $testCase->save();
            if(isset($_GET['token']) && !empty($_GET['token']))
            {
                $res = array(
                    'status' => 'failure',
                    'msg' => '',
                    'id' => null,
                );
                if($vaild)
                {
                    $res['msg'] = 'Create test case success, #' . $testCase->id . ' just created.';
                    $res['id'] = $testCase->id;
                }
                else
                {
                    foreach($testCase->getErrors() as $field => $errors)
                    {
                        $res['msg'] .= $field . ': ' . join(' ', $errors) . ' ';
                    }
                }
                echo CJSON::encode($res);
                Yii::app()->end();
            }
            else if($vaild)
            {
                $this->redirect(array('view', 'id' => $testCase->id));
            }
        }
        
        $this->render('create', array(
            'testCase' => $testCase
        ));
    }

    public function actionUpdate()
    {
        $testCase = $this->loadModel('TestCase');
        
        if(isset($_REQUEST['TestCase']))
        {
            $testCase->attributes = $_REQUEST['TestCase'];
            $vaild = $testCase->save();
            if(isset($_GET['token']) && !empty($_GET['token']))
            {
                $res = array(
                    'status' => 'failure',
                    'msg' => '',
                    'id' => null,
                );
                if($vaild)
                {
                    $res['msg'] = 'Update test case success, #' . $testCase->id . ' just updated.';
                    $res['id'] = $testCase->id;
                }
                else
                {
                    foreach($testCase->getErrors() as $field => $errors)
                    {
                        $res['msg'] .= $field . ': ' . join(' ', $errors) . ' ';
                    }
                }
                echo CJSON::encode($res);
                Yii::app()->end();
            }
            else if($vaild)
            {
                $this->redirect(array('view', 'id' => $testCase->id));
            }
        }
        $vTestCase = $this->loadModel('VTestCase');
        $this->render('create', array(
            'testCase' => $testCase,
            'vTestCase' => $vTestCase
        ));
    }
    
    public function actionDelete()
    {
        $testCase = $this->loadModel('TestCase');
        $vaild = $testCase->delete();
        if(isset($_GET['token']) && !empty($_GET['token']))
        {
            $res = array(
                'status' => 'failure',
                'msg' => '',
                'id' => null,
            );
            if($vaild)
            {
                $res['msg'] = 'Delete test case success, #' . $testCase->id . ' just deteled.';
                $res['id'] = $testCase->id;
            }
            else
            {
                foreach($testCase->getErrors() as $field => $errors)
                {
                    $res['msg'] .= $field . ': ' . join(' ', $errors) . ' ';
                }
            }
            echo CJSON::encode($res);
            Yii::app()->end();
        }
        else if($vaild)
        {
            $this->redirect(array('case/index'));
        }
    }
    
    /**
     * Get code action.
     * @param sting $url
     */
    public function actionGetCode($url)
    {
        $res = array(
            'status' => 'faliure',
            'info' => ''
         );
        $code = file_get_contents(str_replace('//', '//ads:dsa543@', $url));
        if(!empty($code))
        {
            $res['status'] = 'success';
            if(mb_detect_encoding($code,"UTF-8, GBK") != "UTF-8")
                    $code = iconv("GBK", "UTF-8", $code);
            $res['info'] = $code;
        }
        echo CJSON::encode($res);
    }
}
?>