<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 

/**
 * This query controller
 * 
 * @package appliaction.controllers
 */
class QueryController extends Controller
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
                'actions' => array('getList', 'create', 'delete', 'update'),
                'users' => array('@'),
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }
    
    public function actionGetList()
    {
        if(isset($_REQUEST['table']))
        {
            $queryList = Query::getListByOwner(Yii::app()->user->id, $_REQUEST['table']);
            echo CJSON::encode($queryList);
        }
    }
    
    public function actionCreate()
    {
        $this->save(new Query());
    }
    
    public function actionUpdate()
    {
        $query = null;
        if(isset($_REQUEST['Query']['title']))
        {
            $query = Query::model()->findByAttributes(array('title' => $_REQUEST['Query']['title']));
        }
        $this->save($query);
    }
    
    public function actionDelete()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $query = $this->loadModel('Query');
            $response = array('status' => false);
            if($query->delete())
            {
                $response['status'] = true;
            }
            echo CJSON::encode($response);
        }
        else
        {
            throw new CHttpException(404, Yii::t('TOAST', 'The requested page does not exist.'));
        }
    }
    
    private function save($query)
    {
        if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['Query']) && $query)
        {
            $query->attributes = $_REQUEST['Query'];
            $response = array('status' => false);
            if($query->save())
            {
                $response['status'] = true;
                $response['query'] = array(
                    'id' => $query->id,
                    'title' => $query->title,
                    'query_str' => $query->query_str
                );
            }
            else
            {
                $response['error'] = $query->getError("title");
            }
            echo CJSON::encode($response);
        }
        else
        {
            throw new CHttpException(404, Yii::t('TOAST', 'The requested page does not exist.'));
        }
    }
}
?>