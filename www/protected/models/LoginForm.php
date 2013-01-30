<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Login form class.
 *
 * @package application.models
 */
class LoginForm extends CFormModel
{
    /**
     * @var string Username
     */
    public $username;
    /**
     * @var string Password
     */
    public $password;
    /**
     * @var CBaseUserIdentity Identity
     */
    private $_identity;

    /**
     * Integer number of seconds that the user can remain in logged-in status.
     */
    const DURATION = 2592000;

    /**
     * Define LoginForm's rule for validator.
     * @return array Rules of LoginForm class.
     */
    public function rules()
    {
        return array(
            array('username, password', 'required'),
            array('password', 'authenticate'),
        );
    }

    /**
     * Authenticates the password.
     */
    public function authenticate($attribute, $params)
    {
        $identites = $this->identites();
        if(empty($identites))
        {
            $this->addError('password', Yii::t('LoginForm', 'Unknown error'));
        }
        else
        {
            foreach($identites as $identity)
            {
                $identity->authenticate();
                $this->_identity = $identity;
                if(CBaseUserIdentity::ERROR_NONE == $identity->errorCode)
                {
                    break;
                }
                else if(CBaseUserIdentity::ERROR_UNKNOWN_IDENTITY != $identity->errorCode)
                {
                    $this->addError('password', Yii::t('LoginForm', 'Incorrect username or password'));
                    break;
                }
            }
            if(CBaseUserIdentity::ERROR_UNKNOWN_IDENTITY == $this->_identity->errorCode)
            {
                $this->addError('password', Yii::t('LoginForm', 'Unknown error'));
            }
        }
    }
    
    /**
     * Define the attribute labels of LoginForm class.
     * @return array Attribute labels of LoginForm class.
     */
    public function attributeLabels()
    {
        return array(
            'username' => Yii::t('LoginForm', 'Username'),
            'password' => Yii::t('LoginForm', 'Password'),
        );
    }
    
    /**
     * Login.
     * @param CBaseUserIdentity $identity Not based on username and password identity, default as null.
     * @return boolean Result of login.
     */
    public function login($identity = null)
    {
        if(null !== $identity)
        {
            $this->_identity = $identity;
        }
        else
        {
            $this->validate();
        }
        
        if(CBaseUserIdentity::ERROR_NONE === $this->_identity->errorCode)
        {
            Yii::app()->user->login($this->_identity, self::DURATION);
            return true;
        }
        return false;
    }

    /**
     * Define login identites which based on username and password, priority follow the order.
     * @return array Login identities. 
     */
    private function identites()
    {
        return array(
//            new LDAPUserIdentity($this->username, $this->password),
            new DBUserIdentity($this->username, $this->password),
        );
    }
}
?>
