<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
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
                'actions' => array('lookup', 'lookup2', 'feedback'),
                'users' => array('@')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    public function actionLookUp()
    {
        if(Yii::app()->request->isAjaxRequest && isset($_GET['q']) && trim($_GET['q']) != '')
        {
          $query = $_GET['q'];
          $condition = new CDbCriteria();
          $condition->compare('realname', $query, true, 'OR');
          $condition->compare('username', $query, true, 'OR');
          $condition->compare('pinyin', $query, true, 'OR');
          $condition->compare('abbreviation', $query, true, 'OR');
          $condition->compare('status', User::STATUS_AVAILABLE);
          $condition->limit = 50;
          $users = User::model()->findAll($condition);
          $result = '';
          foreach($users as $user)
          {
             $result .= $user->realname . '[' . $user->username . ']|' . $user->realname . '|' . $user->id . "\n";
          }
          echo $result;
       }
    }

    public function actionLookUp2($term, $limit = 0, $page = 1)
    {
        if(Yii::app()->request->isAjaxRequest)
        {
            $query = $term;
            $condition = new CDbCriteria();
            $condition->compare('realname', $query, true, 'OR');
            $condition->compare('username', $query, true, 'OR');
            $condition->compare('pinyin', $query, true, 'OR');
            $condition->compare('abbreviation', $query, true, 'OR');
            $condition->compare('status', User::STATUS_AVAILABLE);
            
            $dataProvider = new CActiveDataProvider('User', array(
                'criteria' => $condition,
                'pagination' => ($limit > 0)?array(
                    'pageSize' => $limit,
                    'pageVar' => 'page',
                ):false
            ));
            $users = $dataProvider->getData();
            $total = $dataProvider->totalItemCount;
            $result = array();
            foreach ($users as $user)
            {
                $result[] = array('label' => "$user->realname ($user->username)", 
                    'id' => $user->id, 'username' => $user->username, 'realname' => $user->realname);
            }
            header('Content-type: text/html; charset=UTF-8');
            echo CJSON::encode(array('total' => $total, 'users' => $result));
       }
    }
}
?>