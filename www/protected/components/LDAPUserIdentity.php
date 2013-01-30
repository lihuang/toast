<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * LDAP user identity class.
 *
 * @package application.components
 */
class LDAPUserIdentity extends CUserIdentity
{
    /**
     * Authenticates a user  based on username and password via ldap. 
     */
    public function authenticate()
    {
        $this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
    }
}
?>
