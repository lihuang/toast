<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Product User Controller
 * 
 * @package application.models
 */
class ProductUserController extends Controller
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
                'actions' => array('productSetUser'),
                'users' => array_merge(VProductUser::getAllProductAdmin(),
                        User::getAllAdmin())
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    public function actionProductSetUser()
    {
        if(Yii::app()->request->isAjaxRequest && isset($_REQUEST['id']))
        {
            $product = Product::model()->findByPk($_REQUEST['id']);
            if($product !== null)
            {
                if(isset($_REQUEST['notproductuser']) && !empty($_REQUEST['notproductuser']))
                {
                    $product->setNotProductUser(explode(',',
                                    $_REQUEST['notproductuser']));
                    foreach(explode(',', $_REQUEST['notproductuser']) as $userid)
                    {
                        $user = User::model()->findByPk($userid);
                        if($user !== null)
                        {
                            $user->updateStatus();
                        }
                    }
                }
                if(isset($_REQUEST['productuser']) && !empty($_REQUEST['productuser']))
                {
                    $product->setProductUser(explode(',',
                                    $_REQUEST['productuser']));
                    foreach(explode(',', $_REQUEST['productuser']) as $userId)
                    {
                        $user = User::model()->findByPk($userId);
                        if($user !== null)
                        {
                            $user->updateStatus();
                            try
                            {
                                $this->mail($user, $product);
                            }
                            catch(Exception $e)
                            {
                                Yii::log('Send Mail Exception: ' . $e->getMessage(),
                                        'error',
                                        'toast.ProductUserController.mail');
                            }
                        }
                    }
                }
                if(isset($_REQUEST['productadmin']) && !empty($_REQUEST['productadmin']))
                {
                    $product->setProductAdmin(explode(',',
                                    $_REQUEST['productadmin']));
                    foreach(explode(',', $_REQUEST['productadmin']) as $userid)
                    {
                        $user = User::model()->findByPk($userid);
                        if($user !== null)
                        {
                            $user->updateStatus();
                        }
                    }
                }
            }
        }
    }

    private function mail($user, $product)
    {
        $this->layout = false;
        if($user !== null && $product !== null)
        {
            $mailer = TMailer::init();
            $reportTo = array();
            $emailValid = new CEmailValidator();
            $mailer->AddAddress($user->email);
            $mailer->Subject = '[TOAST][' . Yii::t('TOAST',
                            'Product Permission Confirm') . ']' . ' ' . $product->name;
            $mailer->Body = $this->render('//site/applied_mail',
                    array('user' => $user, 'product' => $product, 'status' => ProductUser::STATUS_AVAILABLE),
                    true);
            $flag = $mailer->Send();
            Yii::log('Mail sended, the status is ' . $flag, 'trace',
                    'toast.ProductUserController.mail');
            if(!$flag)
            {
                Yii::log('Mail sended fail, because ' . $mailer->ErrorInfo,
                        'trace', 'toast.ProductUserController.mail');
            }
        }
    }
}
?>