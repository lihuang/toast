<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * View Machine Model
 * 
 * @package application.models
 */
class VMachine extends Machine
{
    public $product_name;
    public $responsible_username;
    public $responsible_realname;
    public $created_by_username;
    public $created_by_realname;
    public $updated_by_username;
    public $updated_by_realname;
    public $activity;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'vmachine';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('name, product_id', 'safe'),
        );
    }

    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'responsible_realname' => Yii::t('VMachine', 'Responsible Realname'),
            'product_name' => Yii::t('VMachine', 'Product Name'),
            'created_by_realname' => Yii::t('VMachine', 'Created By Realname'),
            'updated_by_username' => Yii::t('VMachine', 'Updated By Username'),
        );
    }

    public function search($pageSize, $condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
            $product_id = $this->product_id;
            if ($product_id == NULL)
                $product_id = Yii::app()->user->getCurrentProduct();
            $criteria->compare('product_id', $product_id);
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            // TODO: do not use the name field for searching
            $this->name = $condition; 
        }
        
        $criteria->select = '*, 10 as activity';
        $criteria->compare('id', $this->name, true, 'OR');
        $criteria->compare('name', $this->name, true, 'OR');
        $criteria->compare('agent_version', $this->name, true, 'OR');
        $criteria->compare('responsible_realname', $this->name, true, 'OR');
        $criteria->compare('responsible_username', $this->name, true, 'OR');

        if(isset($this->product_id))
        {
            Yii::app()->user->setCurrentProduct($this->product_id);
        }

        
        $sort = new CSort();
        $sort->attributes = array(
            'activity'=>array(
                'asc'=>'activity ASC',
                'desc'=>'activity DESC',
            ),
            '*', // this adds all of the other columns as sortable
        );
        $sort->defaultOrder = 'create_time DESC';
        
        return new CActiveDataProvider(__CLASS__, array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => $pageSize
            ),
            'sort' => $sort,
        ));
    }
}