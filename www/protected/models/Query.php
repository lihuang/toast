<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * This is query class.
 * 
 * @package application.models
 */
class Query extends Model
{
    public $id;
    public $title;
    public $query_str;
    public $table;
    public $created_by;
    public $create_time;
    public $update_time;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'query';
    }
    
    public function rules()
    {
        return array(
            array('title, query_str, table', 'required'),
            array('id, created_by', 'numerical', 'integerOnly' => true),
            array('title', 'length', 'max' => 45),
            array('title', 'unique'),
            array('table', 'length', 'max' => 255),
        );
    }
    
    public function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
            $this->created_by  = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
        }
        
        return parent::beforeSave();
    }
    
    public function attributeLabels()
    {
        return array(
            'title' => Yii::t('Query', 'Title'),
        );
    }
    
    public static function getListByOwner($userId, $table)
    {
        $querys = Query::model()->findAllByAttributes(array('created_by' => $userId, 'table' => $table));
        $queryList = array();
        foreach($querys as $query)
        {
            $queryArr = array();
            $queryArr['id'] = $query->id;
            $queryArr['title'] = $query->title;
            $queryArr['query_str'] = $query->query_str;
            $queryList[] = $queryArr;
        }
        
        return $queryList;
    }
}
?>