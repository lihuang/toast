<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * This site controller, it is default controller
 * 
 * @package application.controllers
 */
class SiteController extends Controller
{
    /**
     * Return the filter configurations.
     * 
     * @return array a list of filter configurations
     */
    public function filters()
    {
        return array(
            'accessControl + index'
        );
    }

    /**
     * Returns the access rules for this controller.
     * 
     * @return array list of access rules
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('index'),
                'users' => array('@'),
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    /**
     * Redirect to the default controller.
     */
    public function actionIndex()
    {
        $this->redirect(Yii::app()->request->getBaseUrl()
                . '/' . Yii::app()->user->getDefaultController());
    }


    /**
     * Render the error page.
     */
    public function actionError()
    {
        $error = Yii::app()->errorHandler->error;
        if(!empty($error))
        {
            $msg = $error['type'] . ': ' . $error['file'] . ' in line ' . $error['line']
                     . ' ' . $error['message']
                     . ' @ ' . Yii::app()->user->name . ' ' . Yii::app()->request->requestUri
                     . ' from ' . Yii::app()->request->userHostAddress;
            Yii::log($msg, CLogger::LEVEL_ERROR,
                    'toast.protected,controller.SiteController.actionError');
            
            if(Yii::app()->request->isAjaxRequest)
            {
                // TODO: add standard ajax/api error message
            }
            else
            {
                $this->render('error', array('error' => $error));
            }
        }
    }


    /*
     * Render the login page.
     * If login successfully, redirect to the return page.
     */
    public function actionLogin()
    {
        if(!Yii::app()->user->isGuest)
        {
            $this->redirect('index');
        }

        $model = new LoginForm();
        if(isset($_POST['LoginForm']))
        {
            $model->attributes = $_POST['LoginForm'];
            if($model->login())
            {
                $msg = Yii::app()->user->name . ' login from '
                        . Yii::app()->request->userHost
                        . '(' . Yii::app()->request->userHostAddress . ')';
                Yii::log($msg, 'trace',  'toast.SiteController.actionLogin');
                $this->redirect(Yii::app()->user->returnUrl);
            }
        }

        $this->layout = false;
        $this->render('login', array('loginForm' => $model));
    }


    /**
     * Logout and then redirect to home page.
     */
    public function actionLogout()
    {
        Yii::log(Yii::app()->user->name . ' logout', 'trace',
                'toast.SiteController.actionLogout');
        foreach($_COOKIE as $key => $val)
        {            
            $cookie = new CHttpCookie($key, null);
            $cookie->expire = -1;
            Yii::app()->request->cookies[$key] = $cookie;
        }
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }

    /**
     * Redirect to the sign up page.
     */
    public function actionSignUp()
    {
        $this->layout = false;
        if(isset($_GET['success']))
        {
            if(Yii::app()->user->getState(WebUser::SIGNED_KEY) == $_GET['success'])
            {
                $this->render('signed', array(
                    'username' => Yii::app()->user->getState(WebUser::SIGNED_USER_KEY),
                ));
            }
            else
            {
                throw new CHttpException(404, Yii::t('TOAST', 'The requested page does not exist.'));
            }
        }
        else
        {
            $model = new SignUpForm();
            if(isset($_POST['SignUpForm']))
            {
                $model->attributes = $_POST['SignUpForm'];
                if($model->createLocalUser())
                {
                    foreach((array)$model->products as $productId)
                    {
                        $productUser = ProductUser::model()->findByAttributes(
                                array('user_id' => $model->id, 'product_id' => $productId));
                        if(null === $productUser)
                        {
                            $productUser = new ProductUser();
                            $productUser->product_id = $productId;
                            $productUser->user_id = $model->id;
                            $productUser->status = ProductUser::STATUS_DISABLE;
                            $productUser->save();
                            $this->mail($model->id, $productId);
                        }
                    }

                    $randKey = TString::getRandomString(20);
                    Yii::app()->user->setState(WebUser::SIGNED_KEY, $randKey);
                    Yii::app()->user->setState(WebUser::SIGNED_USER_KEY, $model->username);

                    $this->redirect('signup?success=' . $randKey);
                }
            }
            $this->render('signup', array(
                'signupForm' => $model,
                'productOpts' => Product::model()->getProductOpts()
            ));
        }
    }
    
    /**
     * Redirect to the first page.
     */
    public function actionApplied()
    {
        if(Yii::app()->user->isGuest)
        {
            $this->redirect('logout');
        }
        else
        {
            $productIds = Yii::app()->user->getProductIds();
            if(empty($productIds))
            {
                $this->redirect('logout');
            }
            else
            {
                $this->redirect('index');
            }
        }
    }
    
    /**
     * Redirect to apply page
     */
    public function actionApply()
    {
        $this->layout = false;
        if(isset($_GET['success']))
        {
            if(Yii::app()->user->getState(WebUser::SIGNED_KEY) == $_GET['success'])
            {
                $this->render('applied');
            }
            else
            {
                throw new CHttpException(404, Yii::t('TOAST', 'The requested page does not exist.'));
            }
        }
        else
        {
            $productId = Yii::app()->request->getParam('product_id');
            $accessProductIds = Yii::app()->user->getProductIds();
            $productOpts = Product::model()->getProductOpts();
            if(isset($_POST['products']))
            {
                foreach($_POST['products'] as $productId)
                {
                    $productUser = ProductUser::model()->findByAttributes(
                            array('user_id' => Yii::app()->user->id, 'product_id' => $productId));
                    if(null === $productUser)
                    {
                        $productUser = new ProductUser();
                        $productUser->product_id = $productId;
                        $productUser->user_id = Yii::app()->user->id;
                        $productUser->status = ProductUser::STATUS_DISABLE;
                        $productUser->save();
                        $this->mail(Yii::app()->user->id, $productId);
                    }
                }

                $randKey = TString::getRandomString(20);
                Yii::app()->user->setState(WebUser::SIGNED_KEY, $randKey);
                Yii::app()->user->setState(WebUser::SIGNED_USER_KEY, Yii::app()->user->name);

                $this->redirect('apply/success/' . $randKey);
            }
                    
            $this->render('apply', array(
                'productId' => $productId,
                'accessProductIds' => $accessProductIds,
                'productOpts' => $productOpts
            ));
        }
    }
    
    private function mail($userId, $productId)
    {
        $this->layout = 'mail';
        $user = User::model()->findByPk($userId);
        $product = Product::model()->findByPk($productId);
        if ($user !== null && $product !== null)
        {
            $mailer = TMailer::init();
            $reportTo = array();
            $emailValid = new CEmailValidator();
            $productAdminList = $product->getProductAdminList();
            foreach ($productAdminList as $adminId => $adminName)
            {
                $admin = User::model()->findByPk($adminId);
                if ($admin !== null && $emailValid->validateValue($admin->email))
                {
                    $mailer->AddAddress($admin->email);
                }
            }
            $supers = User::model()->findAllByAttributes(array('role' => User::ROLE_ADMIN, 'status' => User::STATUS_AVAILABLE));
            foreach($supers as $super)
            {
                if ($super->username !== 'toast-noreply' && $emailValid->validateValue($super->email))
                {
                    $mailer->AddAddress($super->email);
                }
            }
            $mailer->Subject = '[TOAST][' . Yii::t('TOAST', 'Product Permission Request') . ']' . ' ' . $product->name;
            $mailer->Body = $this->render('mail', array('user' => $user, 'product' => $product), true);
            $flag = $mailer->Send();
            Yii::log('Mail sended, the status is ' . $flag, 'trace', 'toast.SiteController.mail');
            if (!$flag)
            {
                Yii::log('Mail sended fail, because ' . $mailer->ErrorInfo, 'trace', 'toast.SiteController.mail');
            }
        }
    }
}
?>