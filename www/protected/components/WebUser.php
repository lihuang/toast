<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class WebUser extends CWebUser
{
    /**
     * A instance of User as current user.
     * @var User
     */
    private $_model;

    /**
     * Signed session key.
     */
    const SIGNED_KEY = '_toastsignedkey';
    /**
     * Signed user session key.
     */
    const SIGNED_USER_KEY = '_toastsigneduserkey';
    /**
     * User access product ids key.
     */
    const PRODUCT = '_toastproduct';
    /**
     * Product options key.
     */
    const PRODUCT_OPT = '_toastproductopt';
    /**
     * Current product id key.
     */
    const CURRENT_PRODUCT_ID = '_toastcurrentproductid';
    /**
     * Current product object key.
     */
    const CURRENT_PRODUCT = '_toastcurrentproduct';
    /**
     * Current project id key.
     */
    const CURRENT_PROJECT_ID = '_toastcurrentprojectid';
    /**
     * Current project object key.
     */
    const CURRENT_PROJECT = '_toastcurrentproject';
    /**
     * Current controller key.
     */
    const CONTROLLER = '_toastcontroller';
    /**
     * Page size key.
     */
    const PAGE_SIZE = '_toastpagesize';
    /**
     * Show back ground key.
     */
    const SHOW_BACKGROUND = '_toastshowbackground';
    
    const USER_OPT = '_toastuseropt';
    const USERNAME_OPT = '_toastusernameopt';

    /**
     * Return current login user.
     * 
     * @return User current login user.
     */
    protected function loadUser()
    {
        if(null === $this->_model)
        {
            $this->_model = User::model()->findByPk($this->id);
        }
        return $this->_model;
    }

    /**
     * Store product options and product ids to sesssion.
     *
     * @param boolean $fromCookie if it is true, means login from cookie. 
     */
    protected function afterLogin($fromCookie)
    {
        $this->getProductIds();
        $this->getProductOpts();
    }

    /**
     * Return a list of current user access product ids.
     * 
     * @return array a list of current user access product ids. 
     */
    public function getProductIds()
    {
//        $products = array();
//        if(isset($_SESSION[WebUser::PRODUCT]))
//        {
//            $products = Yii::app()->session->get(WebUser::PRODUCT);
//        }
//        else
//        {
//            $products = $this->loadUser()->getProducts();
//            Yii::app()->session->add(WebUser::PRODUCT, $products);
//        }
//        return $products;
        
        return $this->loadUser()->getProducts();
    }

    /**
     * Return current user control product ids.
     * 
     * @return array a list of product ids
     */
    public function getAdminProductIds()
    {
        return $this->loadUser()->getProducts(false, true);
    }
    

    /**
     * Return a list of product options.
     * 
     * @return array a list of product options
     */
    public function getProductOpts()
    {
//        $opts = array();
//        if(isset($_SESSION[WebUser::PRODUCT_OPT]))
//        {
//            $opts = Yii::app()->session->get(WebUser::PRODUCT_OPT);
//        }
//        else
//        {
//            $opts = Product::model()->getProductOpts();
//            Yii::app()->session->add(WebUser::PRODUCT_OPT, $opts);
//        }
//        return $opts;
        
        return Product::model()->getProductOpts();
    }

    /**
     * Return a list of product options' class
     * 
     * @return array a list of product options' class
     */
    public function getProductionOptsClass()
    {
        $opts = array();
        $accessProductIds = $this->getProductIds();
        $productOpts = $this->getProductOpts();
        foreach($productOpts as $key => $val)
        {
            if(!in_array($key, $accessProductIds))
            {
                $opts[$key] = array('class' => 'deny');
            }
        }
        return $opts;
    }
    
    /**
     * Return a list of current user control product options.
     * 
     * @return array a list of current user control product options
     */
    public function getAdminProductOpts()
    {
        $opts = array();
        $products = $this->loadUser()->getProducts(true, true);
        foreach($products as $product)
        {
            $opts[$product->id] = $product->name;
        }
        return $opts;
    }
    
    public function getProjectOpts($productId, $fullPath = false, $hasBlank = false)
    {
        $opts = array();
        $product = Product::model()->findByPk($productId);
        if($product !== null)
        {
            $projects = $product->getProjects(true);
            if(!$fullPath)
            {
                foreach($projects as $project)
                {
                    $opts[$project->id] = $project->name;
                }
            }
            else
            {
                if(!$hasBlank)
                {
                    $opts = array(0 => '/');
                }
                else
                {
                    $opts = array('' => '/');
                }
                $name = '';
                foreach($projects as $index => $project)
                {
                    $name .= '/' . $project['name'];
                    $opts[$project->id] = $name;
                    if(($project['rgt'] - 1) == $project['lft'])
                    {
                        $name = substr($name, 0, strrpos($name, '/'));
                        if(isset($projects[$index+1]))
                        {
                            $count = $projects[$index+1]['lft'] - $project['rgt'] - 1;
                            if($count)
                            {
                                for($i = 0; $i < $count; $i++)
                                {
                                    $name = substr($name, 0, strrpos($name, '/'));
                                }
                            }
                        }
                    }
                }
            }
        }

        return $opts;
    }

    public function getCurrentProduct($returnObj = false)
    {
        $val = null;
        $key = WebUser::CURRENT_PRODUCT_ID;
        if(isset($_SESSION[$key]))
        {
            $val = Yii::app()->session->get($key);
            if($returnObj)
            {
                $val = Product::model()->findByPk($val);
            }
        }
        else if(isset($_COOKIE[$key]))
        {
            $val = Yii::app()->request->cookies[$key]->value;
            if($returnObj)
            {
                $val = Product::model()->findByPk($val);
            }
        }
        else
        {
            $productIds = $this->getProductIds();
            if(!empty($productIds))
            {
                $val = current($productIds);
                if($returnObj)
                {
                    $val = Product::model()->findByPk($val);
                }
            }
            else
            {
                $val = Product::model()->find()->id;
            }
        }
        return $val;
    }

    public function setCurrentProduct($productId)
    {
        $product = Product::model()->findByPK($productId);
        if($product !== null)
        {
            $cookie = new CHttpCookie(WebUser::CURRENT_PRODUCT_ID, $productId);
            $cookie->expire = time() + 60 * 60 * 24 * 30;
            Yii::app()->request->cookies[WebUser::CURRENT_PRODUCT_ID] = $cookie;
            Yii::app()->session->add(WebUser::CURRENT_PRODUCT_ID, $productId);
        }
    }

    public function getDefaultController()
    {
        return 'task';
//        $controller = 'task';
//        if(isset($_COOKIE[WebUser::CONTROLLER]))
//        {
//            $controller = Yii::app()->request->cookies[WebUser::CONTROLLER]->value;
//        }
//        return $controller;
    }

    public function getRealName(){
        $realname = '';
        $user = $this->loadUser();
        if($user !== null)
        {
            $realname = $user->realname;
        }
        return $realname;
    }

    public function getUsername()
    {
        $username = '';
        $user = $this->loadUser();
        if($user !== null)
        {
            $username = $user->username;
        }
        return $username;
    }
    
    public function getDomain()
    {
        $domain = '';
        $user = $this->loadUser();
        if($user !== null)
        {
            $domain = $user->domain;
        }
        return $domain;
    }

    public function getPageSize()
    {
        $pageSize = 25; //Yii::app()->params['pageSize'];
        if(isset($_SESSION[WebUser::PAGE_SIZE]))
        {
            $pageSize = Yii::app()->session->get(WebUser::PAGE_SIZE);
        }
        return $pageSize;
    }
    
    public function setPageSize($pageSize)
    {
        Yii::app()->session->add(WebUser::PAGE_SIZE, $pageSize);
    }

    public function getUserOpts($hasBlank = true)
    {
        $opts = null;
        if(Yii::app()->cache->get(self::USER_OPT . '_' . Yii::app()->user->id))
        {
            $opts = Yii::app()->cache->get(self::USER_OPT . '_' . Yii::app()->user->id);
        }
        else
        {
            $opts = $hasBlank ? array('' => '') : array();
            $productIds = $this->getProductIds();
            $users = VProductUser::model()->getUsers($productIds);
            foreach($users as $user)
            {
                if($user->realname != '')
                {
                    $opts[$user->user_id] = PinYin::getPinYin($user->realname) . '  ' . $user->realname . ' (' . $user->username . ')';
                }
                else
                {
                    $opts[$user->user_id] = $user->username;
                }
            }
            $opts = array_unique($opts);
            asort($opts);
            Yii::app()->cache->set(self::USER_OPT . '_' . Yii::app()->user->id, $opts);
        }
        
        return $opts;
    }
    
    public function getUsernameOpts($hasBlank = true)
    {
        $opts = null;
        if(Yii::app()->cache->get(self::USERNAME_OPT . '_' . Yii::app()->user->id))
        {
            $opts = Yii::app()->cache->get(self::USERNAME_OPT . '_' . Yii::app()->user->id);
        }
        else
        {
            $opts = $hasBlank ? array('' => '') : array();
            $productIds = $this->getProductIds();
            $users = VProductUser::model()->getUsers($productIds);
            foreach($users as $user)
            {
                if($user->realname != '')
                {
                    $opts[$user->username] = PinYin::getPinYin($user->realname) . '  ' . $user->realname . ' (' . $user->username . ')';
                }
                else
                {
                    $opts[$user->username] = $user->username;
                }
            }
            $opts = array_unique($opts);
            asort($opts);
            Yii::app()->cache->set(self::USERNAME_OPT . '_' . Yii::app()->user->id, $opts);
        }
        
        return $opts;
    }
    
    public function isAdmin()
    {
        $flag = false;
        $user = $this->loadUser();
        if($user && $user->role == User::ROLE_ADMIN)
        {
            $flag = true;
        }
        return $flag;
    }
    
    public function isProductAdmin()
    {
        $flag = false;
        if(in_array($this->getName(), VProductUser::getAllProductAdmin()))
        {
            $flag = true;
        }
        return $flag;
    }
    
    /**
     *  is show background end entrance
     */
    public function isShowBE()
    {
        $flag = false;
        if(isset($_SESSION[WebUser::SHOW_BACKGROUND]))
        {
            $flag = Yii::app()->session->get(WebUser::SHOW_BACKGROUND);
        }
        else
        {
            $flag = $this->isAdmin() || $this->isProductAdmin();
        }
        Yii::app()->session->add(WebUser::SHOW_BACKGROUND, $flag);
        return $flag;
    }
    
    public function getGroup()
    {
        $user = User::model()->findByPk($this->id);
        if($user && $group = $user->group)
            return $group;
        else
            return NULL;
    }
}
?>