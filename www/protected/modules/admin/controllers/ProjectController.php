<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Project Controller
 * 
 * @package application.models
 */
class ProjectController extends Controller
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

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
                'actions' => array('create', 'update', 'delete'),
                'users' => array_merge(VProductUser::getAllProductAdmin(), User::getAllAdmin())
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    public function actionCreate()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $jsonArr = array('status' => self::STATUS_FAILED);
            $project = new Project();
            $project->attributes = $_REQUEST['Project'];
            if($project->save())
            {
                $jsonArr['status'] = self::STATUS_SUCCESS; 
            }
            else
            {
                $jsonArr['errors'] = $project->getErrors();
            }
            header('Content-type: text/json; charset=UTF-8');
            echo CJSON::encode($jsonArr);
            exit;
        }
    }    
    
    public function actionUpdate()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $jsonArr = array('status' => self::STATUS_FAILED);
            $project = $this->loadModel('Project');
            $project->attributes = $_REQUEST['Project'];
            if($project->save())
            {
                $jsonArr['status'] = self::STATUS_SUCCESS; 
            }
            else
            {
                $jsonArr['errors'] = $project->getErrors();
            }
            header('Content-type: text/json; charset=UTF-8');
            echo CJSON::encode($jsonArr);
            exit;
        }
    }
    
    public function actionDelete()
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $jsonArr = array('status' => self::STATUS_FAILED);
            $project = $this->loadModel('Project');
            if($project->delete())
            {
                $jsonArr['status'] = self::STATUS_SUCCESS; 
            }
            else
            {
                $jsonArr['errors'] = $project->getErrors();
            }
            header('Content-type: text/json; charset=UTF-8');
            echo CJSON::encode($jsonArr);
            exit;
        }
    }
}
?>