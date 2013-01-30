<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Database user identity class.
 *
 * @package application.components 
 */
class DBUserIdentity extends CUserIdentity
{
    private $_id;
    
    /**
     * Authenticates a user  based on username and password via database. 
     */
    public function authenticate()
    {
        $user = User::model()->avaliable()->findByAttributes(array('username' => $this->username));
        $this->errorCode = self::ERROR_NONE;
        if(null == $user)
        {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }
        else if(User::encrypt($this->password) != $user->password)
        {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        }
        else
        {
            $this->_id = $user->id;
            $this->username = $user->username;
        }
    }
    
    public function getId()
    {
        return $this->_id;
    }
}
?>
