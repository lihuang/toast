<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class UserIdentity extends CUserIdentity
{
    /**
     * Current user id if login successfully. 
     * @var integer
     */
    private $_id;
    
    /**
     * Currnt realname id if login successfully.
     * @var string
     */
    public $realname;
    /**
     * Domain.
     * @var string 
     */
    public $domain;

    /**
     * Error code of the user being disable.
     */
    const ERROR_USER_DISABLE = 10;
    /**
     * Error code of the create user failed.
     */
    const ERROR_CREATE_USER_FAILED = 11;
    
    /**
     * Construct function of UserIdentity.
     * 
     * @param string $username username 
     * @param string $password password
     * @param string $domain domain
     * @return UserIdentity
     */
    public function __construct($username, $password, $domain)
    {
        $username = strtolower($username);
        $this->domain = $domain;
        parent::__construct($username, $password);
    }

    /**
     * Authenticate user.
     * Return error code and error message.
     * 
     * @return array error code and error message.
     */
    public function authenticate()
    {
        list($this->errorCode, $this->errorMessage, $user) = $this->dbAuthenticate();
        if(Option::LOCAL_DOMIAN != $this->domain
                && $this->errorCode != UserIdentity::ERROR_USER_DISABLE)
        {
            $ldap = new LDAPIdentity($this->username, $this->password, $this->domain);
            list($ldapErrorCode, $ldapErrorMessage, $ldapUser) = $ldap->authenticate();
            
            // if domain controller is not available, skip it.
            if(LDAPIdentity::ERROR_CONNECT_INVALID != $ldapErrorCode)
            {
                $this->errorCode = $ldapErrorCode;
                $this->errorMessage = $ldapErrorMessage;
                
                if(LDAPIdentity::ERROR_NONE == $this->errorCode)
                {
                    // sync user info or add user
                    if(null != $user)
                    {
                        $user->password = $ldapUser->password;
                        $user->realname = $ldapUser->realname;
                        if($user->save())
                        {
                            Yii::log('Synchronize user info successfully.', CLogger::LEVEL_TRACE,
                                    'toast.protected.components.UserIdentity.authenticate');
                        }
                        else
                        {
                            Yii::log('Synchronize user info failed.', CLogger::LEVEL_WARNING,
                                    'toast.protected.components.UserIdentity.authenticate');
                        }
                    }
                    else
                    {
                        $user = $ldapUser;
                        if($user->save())
                        {
                            Yii::log('Create user #' . $user->id . ' via LDAP successfully.', CLogger::LEVEL_TRACE,
                                    'toast.protected.components.UserIdentity.authenticate');
                        }
                        else
                        {
                            $msg = '';
                            foreach($user->getErrors() as $error)
                            {
                                $msg .= join(' ', $error);
                            }
                            $this->errorCode = UserIdentity::ERROR_CREATE_USER_FAILED;
                            $this->errorMessage = Yii::t('User', 'Create user failed, because {message}.',
                                    array('{message}' => $msg));
                            Yii::log('Create user via LDAP failed.', CLogger::LEVEL_WARNING,
                                    'toast.protected.components.UserIdentity.authenticate');
                        }
                    }
                }
            }
            else
            {
                Yii::log('Domain controller is not available.', CLogger::LEVEL_WARNING,
                        'toast.protected.components.UserIdentity.');
            }
        }
        
        if(UserIdentity::ERROR_NONE == $this->errorCode)
        {
            $this->_id = $user->id;
            $this->username = $user->username;
            $this->realname = $user->realname;
        }
        
        return array($this->errorCode, $this->errorMessage);
    }

    /**
     * Datebase authenticate.
     * Return error code, error message and user info.
     * 
     * @return array a list of error code, error message and user info.
     */
    private function dbAuthenticate()
    {
        $user = User::model()->findByAttributes(array('username' => $this->username));
        $errorCode = UserIdentity::ERROR_UNKNOWN_IDENTITY;
        $errorMessage = Yii::t('User', 'Unknow error.');
        if(null === $user)
        {
            $errorCode = UserIdentity::ERROR_USERNAME_INVALID;
            $errorMessage = Yii::t('User', 'Username is invalidate.');
        }
        else if(User::STATUS_DISABLE == $user->status)
        {
            $errorCode = UserIdentity::ERROR_USER_DISABLE;
            $errorMessage = Yii::t('User', 'User\'s status is disable.');
        }
        else if(md5($this->password) != $user->password)
        {
            $errorCode = UserIdentity::ERROR_PASSWORD_INVALID;
            $errorMessage = Yii::t('User', 'Password is invalidate.');
        }
        else
        {
            $errorCode = UserIdentity::ERROR_NONE;
            $errorMessage = '';
        }
        return array($errorCode, $errorMessage, $user);
    }
    
    /*
     * Return id.
     * 
     * @return integer id.
     */
    public function getId()
    {
        return $this->_id;
    }
}
?>