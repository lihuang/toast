<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * UserController
 * 
 * @package application.models
 */
class UserController extends Controller
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
                'actions' => array('index', 'create', 'view'),
                'users' => array_merge(VProductUser::getAllProductAdmin(), User::getAllAdmin())
            ),
            array(
                'allow',
                'actions' => array('update', 'disable'),
                'users' => User::getAllAdmin()
            ),
            array(
                'allow',
                'actions' => array('update', 'disable', 'gettoken'),
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
        $user = $this->listModels('User');
        $condition = null;
        if(isset($_GET['q']))
            $condition = $this->getCondition($_GET['q']);
        

        $this->render('index',array(
            'userProvider' => $user->search(Yii::app()->user->getPageSize(), $condition),
            'user' => $user
        ));
    }

    public function actionCreate()
    {
        $user = new User();
        if(isset($_POST['User']))
        {
            $user->attributes = $_POST['User'];
            if($user->save())
            {
                $msg = 'Create User #' . $user->id . ' ' . $user->username. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.admin.UserController.actionCreate');
                if(isset($_POST['products']))
                {
                    foreach($_POST['products'] as $productId)
                    {
                        $productUser = new ProductUser();
                        $productUser->product_id = $productId;
                        $productUser->user_id = $user->id;
                        $productUser->status = ProductUser::STATUS_AVAILABLE;
                        $productUser->save();
                        $msg = 'Add User #' . $user->id . ' ' . $user->username. ' to Product #' 
                                . $productId . ' By ' . Yii::app()->user->name;
                        Yii::log($msg, 'trace', 'toast.admin.UserController.actionCreate');
                    }
                }
                $this->redirect(array('view', 'id' => $user->id));
            }
        }
        $this->render('create',array(
            'user' => $user
        ));
    }
    
    public function actionView()
    {
        $user = $this->loadModel('User');
        $products = VProductUser::model()->getProducts($user->id);
        $this->render('view', array(
            'user' => $user,
            'products' => $products
        ));
    }
    
    public function actionUpdate()
    {
        $user = $this->loadModel('User');
        if(!Yii::app()->user->isAdmin() && $user->id != Yii::app()->user->id)
            throw new CHttpException(403, Yii::t('yii', 'You are not authorized to perform this action.'));

        if(isset($_POST['User']))
        {
            $user->attributes = $_POST['User'];
            $user->validate();
            if($user->save())
            {
                $msg = 'Update User #' . $user->id . ' ' . $user->username. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.admin.UserController.actionUpdate');
                if(Yii::app()->user->isAdmin())
                    $this->redirect(array('index'));
                else
                    $this->redirect('/');
            }
        }
        $this->render('update', array(
            'user' => $user
        ));
    }
    
    public function actionDisable()
    {
        $res = array('code' => 1, 'msg' => 'perform failed');
        $user = $this->loadModel('User');
        if(!Yii::app()->user->isAdmin() && $user->id != Yii::app()->user->id)
            $res['msg'] = 'permission denied';
        
        if(isset($_REQUEST['disable']) && $_REQUEST['disable'] == '1')
            $user->status = User::STATUS_DISABLE;
        else
            $user->status = User::STATUS_AVAILABLE;
        if($user->save())
        {
            $res['code'] = 0;
            $res['msg'] = 'success';
        }
        if($user->id == Yii::app()->user->id)
        {
            Yii::app()->user->logout();
        }
        echo CJSON::encode($res);
    }
    
    public function actionGetToken()
    {
        $res = array('code' => 1, 'msg' => 'generate failed');
        $user = $this->loadModel('User');
        if(!Yii::app()->user->isAdmin() && $user->id != Yii::app()->user->id)
            $res['msg'] = 'permission denied';
        
        $tokenStr = $user->username . '@' . microtime(true);
        $user->token = md5($tokenStr);
        if($user->save())
        {
            $res['code'] = 0;
            $res['msg'] = 'success';
            $res['token'] = $user->token;
        }
        echo CJSON::encode($res);
    }
}
?>