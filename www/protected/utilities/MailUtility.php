<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class MailUtility extends Utility
{

    public static function sendMail($subject = '', $body = '', $receivers = '',
            $ccs = '', $bccs = '', $delimiter = ',', $priority = 3)
    {
        $mailer = TMailer::init();
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->Priority = $priority;

        $emailValid = new CEmailValidator();
        if(!empty($receivers))
        {
            $receiverArr = explode($delimiter, TString::arrangeSplit($receivers, array(',', '，', ';', '；')));
            foreach($receiverArr as $receiver)
            {
                if(!$emailValid->validateValue($receiver))
                {
                    $condition = new CDbCriteria();
                    $condition->compare('realname', $receiver . '%', true, 'AND', false);
                    $user = User::model()->find($condition);
                    if($user !== null)
                    {
                        $receiver = $user->email;
                    }
                }
                $mailer->AddAddress($receiver);
            }
        }

        if(!empty($ccs))
        {
            $ccArr = explode($delimiter, TString::arrangeSplit($ccs, array(',', '，', ';', '；')));
            foreach($ccArr as $cc)
            {
                if(!$emailValid->validateValue($cc))
                {
                    $condition = new CDbCriteria();
                    $condition->compare('realname', $cc . '%', true, 'AND', false);
                    $user = User::model()->find($condition);
                    if($user !== null)
                    {
                        $cc = $user->email;
                    }
                }
                $mailer->AddCC($cc);
            }   
        }
        
        if(!empty($bccs))
        {
            $bccArr = explode($delimiter, TString::arrangeSplit($bccs, array(',', '，', ';', '；')));
            foreach($bccArr as $bcc)
            {
                if(!$emailValid->validateValue($bcc))
                {
                    $condition = new CDbCriteria();
                    $condition->compare('realname', $bcc . '%', true, 'AND', false);
                    $user = User::model()->find($condition);
                    if($user !== null)
                    {
                        $bcc = $user->email;
                    }
                }
                $mailer->AddBCC($bcc);
            }  
        }
        $flag = $mailer->Send();
        return array($flag, $mailer->ErrorInfo);
    }
}
?>
