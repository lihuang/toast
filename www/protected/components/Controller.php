<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class Controller extends CController
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    
    protected function listModels($clazz)
    {
        $model = new $clazz();
        if(isset($_GET[$clazz]))
        {
            $model->attributes = $_GET[$clazz];
        }
        return $model;
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     */
    public function loadModel($clazz)
    {
        $model = null;
        if(isset($_GET['id']))
        {
            $model = $clazz::model()->findByPk($_GET['id']);
        }
        if($model === null)
        {
            if(isset($_GET['token']) && !empty($_GET['token']))
            {
                $res = array(
                    'status' => 'failure',
                    'msg' => 'The requested page does not exist.'
                );
                echo CJSON::encode($res);
                exit;
            }
            else
            {
                throw new CHttpException(404, Yii::t('TOAST', 'The requested page does not exist.'));
            }
        }
        return $model;
    }
    
    public function getCondition($q)
    {
       $qb = new QueryBuilder();
       return $qb->str2Condition($q);
    }
}
?>