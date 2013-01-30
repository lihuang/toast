<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * This is the sign up form class.
 * 
 * @package application.models
 */
class SignUpForm extends LoginForm
{
    /**
     * Id for the new user.
     * @var integer
     */
    public $id;
    /**
     * A list of product id.
     * @var array
     */
    public $products;
    /**
     * Retry password.
     * @var string
     */
    public $password2;
    /**
     * Realname.
     * @var string
     */
    public $realname;
    /**
     * Email.
     * @var string
     */
    public $email;

    /**
     * Returns the labels for attributes.
     * 
     * @return array a list of label 
     */
    public function attributeLabels() {
        return CMap::mergeArray(parent::attributeLabels(), array(
            'password2' => Yii::t('SignUpForm', 'Retry password'),
            'email' => Yii::t('SignUpForm', 'Email'),
            'realname' => Yii::t('SignUpForm', 'Realname'),
        ));
    }
    
    /**
     * validation rules
     *
     * @return array
     */
    public function rules()
    {
        return array(
            array('username, realname, products, password, username, email, password2, products', 'required'),
            array('username', 'length', 'min' => 5, 'max' => 20),
            array('username', 'match', 'pattern' => '/^[a-zA-z][a-zA-Z_0-9\.]*$/'),
            array('password', 'length', 'min' => 8),
            array('realname', 'length', 'min' => 2, 'max' => 128),
            array('email', 'length', 'max' => 255),
            array('email', 'email'),
            array('password', 'compare', 'compareAttribute'=>'password2'),
        ); 
    }
    
    /**
     * Create local domain user.
     * Return the action result.
     * 
     * @return boolean crreate user result
     */
    public function createLocalUser()
    {
        $flag = false;
        if($this->validate());
        {
            $user = new User();
            $user->username = $this->username;
            $user->realname = $this->realname;
            $user->password = $this->password;
            $user->email = $this->email;
            $flag = true;
            if(!$user->save())
            {
                foreach($user->getErrors() as $key => $val)
                {
                    $this->addError($key, join(',', $val));
                }
                $flag = false;
            }
            else
            {
                $this->id = $user->id;
            }
        }
        if(!$flag)
        {
            $this->password = $this->password2 = null;
        }
        return $flag;
    }
}
?>