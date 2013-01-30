<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class TMailer
{
    public static function init()
    {
        $mailer = Yii::createComponent('application.extensions.mailer.EMailer');
        $mailer->Host = Yii::app()->params['smtp']['host'];
        $mailer->IsSMTP();
        $mailer->FromName = Yii::app()->params['smtp']['FromName'];
        $mailer->From = Yii::app()->params['smtp']['From'];
        $mailer->ContentType = 'text/html';
        $mailer->CharSet = 'UTF-8';
        return $mailer;
    }

    public static function getEmailByRealname($realname)
    {
        $user = User::model()->findByAttributes(array('realname' => $realname));
        $email = '';
        if($user != null)
        {
            $email = $user->email;
        }
        return $email;
    }
}
?>