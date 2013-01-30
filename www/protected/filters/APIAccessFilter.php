<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class APIAccessFilter extends CFilter
{
    private $oldUserID = NULL;
    
    protected function preFilter($filterChain)
    {
        $this->oldUserID = Yii::app()->user->id;
        if(isset($_GET['token']) && !empty($_GET['token']))
        {
            $user = User::model()->findByAttributes(array('token' => $_GET['token']));
            if($user !== NULL) 
            {
                Yii::app()->user->setId($user->id);
            }
        }
        return true;
    }
    
    protected function postFilter($filterChain)
    {
        Yii::app()->user->setId($this->oldUserID);
    }
}
?>