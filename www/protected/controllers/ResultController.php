<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
class ResultController extends Controller
{
    public function filters()
    {
        return array(
            array(
                'application.filters.APIAccessFilter + create'
            ),
            'accessControl'
        );
    }

    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('create'),
                'users' => array('@'),
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }
    
    public function actionCreate()
    {
        $caseResult = new CaseResult();
        if(isset($_REQUEST['CaseResult']))
        {
            $caseResult->attributes = $_REQUEST['CaseResult'];
            $vaild = $caseResult->save();
            if(isset($_GET['token']) && !empty($_GET['token']))
            {
                $res = array(
                    'status' => 'failure',
                    'msg' => '',
                    'id' => null,
                );
                if($vaild)
                {
                    $res['msg'] = 'Create test case result success, #' . $caseResult->id . ' just created.';
                    $res['id'] = $caseResult->id;
                }
                else
                {
                    foreach($caseResult->getErrors() as $field => $errors)
                    {
                        $res['msg'] .= $field . ': ' . join(' ', $errors) . ' ';
                    }
                }
                echo CJSON::encode($res);
                Yii::app()->end();
            }
        }
    }
}
?>
