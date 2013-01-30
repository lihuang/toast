<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * View Product User Model
 * 
 * @package application.models
 */
class VProductUser extends ProductUser
{
    public $username;
    public $realname;
    public $created_by_username;
    public $created_by_realname;
    public $updated_by_username;
    public $updated_by_realname;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'vproduct_user';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('id, product_id, user_id, role, status, created_by, updated_by,
                create_time, update_time, username, realname,
                created_by_username, created_by_realname,
                updated_by_username, updated_by_realname', 'safe'),
        );
    }

    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'username' => Yii::t('VProductUser', 'User name'),
            'realname' => Yii::t('VProductUser', 'Real name'),
            'created_by_realname' => Yii::t('VProductUser','Created By Realname'),
            'updated_by_realname' => Yii::t('VProductUser','Updated By Realname')
        );
    }

    public function getUsers($productIds)
    {
        $users = array();
        if(!is_array($productIds))
        {
            $productIds = array($productIds);
        }
        $condition = new CDbCriteria();
        $condition->compare('status', ProductUser::STATUS_AVAILABLE);
        $condition->addInCondition('product_id', $productIds);
        $users =  VProductUser::model()->findAll($condition);
        return $users;
    }
    
    public function getAdmins($productIds)
    {
        $admins = array();
        if(!is_array($productIds))
        {
            $productIds = array($productIds);
        }
        $condition = new CDbCriteria();
        $condition->compare('status', ProductUser::STATUS_AVAILABLE);
        $condition->compare('role', ProductUser::ROLE_ADMIN);
        $condition->addInCondition('product_id', $productIds);
        $admins =  VProductUser::model()->findAll($condition);
        return $admins;
    }
    
    public function getProducts($userIds)
    {
        $products = array();
        if(!is_array($userIds))
        {
            $userIds = array($userIds);
        }
        $condition = new CDbCriteria();
        $condition->compare('status', ProductUser::STATUS_AVAILABLE);
        $condition->addInCondition('user_id', $userIds);
        $products =  VProductUser::model()->findAll($condition);
        return $products;        
    }
    
    public static function getAllProductAdmin()
    {
        $admins = array();
        $sql = 'SELECT username FROM `' . VProductUser::model()->tableName()
                 . '` WHERE `role` = ' . ProductUser::ROLE_ADMIN . ' AND `status` = ' . ProductUser::STATUS_AVAILABLE
                 . ' GROUP BY username';
        $admins = Yii::app()->db->createCommand($sql)->queryColumn();
        return $admins;
    }
}
?>