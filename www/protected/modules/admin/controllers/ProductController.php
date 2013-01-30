<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Product Controller
 * 
 * @package application.models
 */
class ProductController extends Controller
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
                'actions' => array('index', 'update', 'project', 'user', 'permission'),
                'users' => array_merge(VProductUser::getAllProductAdmin(), User::getAllAdmin())
            ),
            array(
                'allow',
                'actions' => array('create'),
                'users' => User::getAllAdmin()
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    public function actionIndex()
    {
        $vProduct = $this->listModels('VProduct');
        $condition = null;
        if(isset($_GET['q']))
            $condition = $this->getCondition($_GET['q']);
        
        $this->render('index',array(
            'vProductProvider' => $vProduct->search(Yii::app()->user->getPageSize(), $condition),
            'vProduct' => $vProduct
        ));
    }
    
    public function actionCreate()
    {
        $product = new Product();
        if(isset($_POST['Product']))
        {
            $product->attributes = $_POST['Product'];
            if($product->save())
            {
                $msg = 'Create Product #' . $product->id . ' ' . $product->name. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.ProductController.actionCreate');
                $this->redirect(array('update', 'id' => $product->id));
            }
        }
        $this->render('create', array(
            'product' => $product
        ));
    }
    
    public function actionUpdate()
    {
        $product = $this->loadModel('Product');
        if(isset($_POST['Product']))
        {
            $product->attributes = $_POST['Product'];
            if($product->save())
            {
                $msg = 'Update Product #' . $product->id . ' ' . $product->name. ' By ' . Yii::app()->user->name;
                Yii::log($msg, 'trace', 'toast.ProductController.actionUpdate');
                $this->redirect(array('index'));
            }
        }
        
        $allUserList = User::model()->getAllUserList(true);
        $productUserList = $product->getProductUserList();
        $productAdminList = $product->getProductAdminList();
        $allUserList = array_diff_key($allUserList, $productUserList);
        $productUserList = array_diff($productUserList, $productAdminList);
        $permissionUserList = array();
        $permissions = ProductUser::model()->findAllByAttributes(
            array('product_id' => $product->id, 'status' => ProductUser::STATUS_DISABLE));
        foreach ($permissions as $permission)
        {
            $user = User::model()->findByPk($permission->user_id);
            if($user)
            {
                $permissionUserList[] = $user;
            }
        }
        $this->render('update', array(
            'product' => $product, 
            'allUserList' => $allUserList,
            'productUserList' => $productUserList,
            'productAdminList' => $productAdminList,
            'permissionUserList' => $permissionUserList,
        ));        
    }
    
    public function actionProject()
    {
        $product = $this->loadModel('Product');
        $this->render('project', array('product' => $product));
    }
    
    public function actionUser()
    {
        $product = $this->loadModel('Product');
        $allUserList = User::model()->getAllUserList(true);
        $productUserList = $product->getProductUserList();
        $productAdminList = $product->getProductAdminList();
        $allUserList = array_diff($allUserList, $productUserList);
        $productUserList = array_diff($productUserList, $productAdminList);
        $permissionUserList = array();
        $permissions = ProductUser::model()->findAllByAttributes(
            array('product_id' => $product->id, 'status' => ProductUser::STATUS_DISABLE));
        foreach ($permissions as $permission)
        {
            $user = User::model()->findByPk($permission->user_id);
            if($user)
            {
                $permissionUserList[] = $user;
            }
        }
        $this->render('user', array(
            'product' => $product, 
            'allUserList' => $allUserList,
            'productUserList' => $productUserList,
            'productAdminList' => $productAdminList,
            'permissionUserList' => $permissionUserList,
        ));
    }
}
?>