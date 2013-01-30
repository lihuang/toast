<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * View Product Model
 * 
 * @package application.models
 */
class VProduct extends Product
{
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
        return 'vproduct';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('id, name, create_time, update_time, created_by_realname,
                updated_by_realname', 'safe'),
        );
    }

    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'created_by_realname' => Yii::t('VProduct', 'Created By Realname'),
            'updated_by_realname' => Yii::t('VProduct', 'Updated By Realname')
        );
    }

    public function search($pageSize, $condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            // TODO: do not use the name field for searching
            $this->name = $condition; 
        }

        $criteria->select = 'id, name, status, create_time, update_time, created_by_realname, updated_by_realname';
        $criteria->compare('id', $this->name, true, 'OR');
        $criteria->compare('name', $this->name, true, 'OR');
        $criteria->addInCondition('id', Yii::app()->user->getAdminProductIds());

        return new CActiveDataProvider('VProduct', array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => $pageSize
            ),
            'sort' => array(
                'defaultOrder' => "create_time DESC"
            ),
        ));
    }
}
?>