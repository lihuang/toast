<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * DiffAction Model 
 * 
 * @package application.models
 */
class DiffAction extends Model
{
    public $id;
    public $model_name;
    public $model_id;
    public $updated_by;
    public $update_time;

    /**
     * 
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 
     */
    public function tableName()
    {
        return 'diff_action';
    }

    /**
     * validation rules
     */
    public function rules()
    {
        return array(
            array('model_name, model_id', 'required'),
            array('model_name, model_id, updated_by, update_time', 'safe'),
        );
    }

    /**
     * relations
     */
    public function relations()
    {
        return array(
            'diffattrs' => array(self::HAS_MANY, 'DiffAttribute', 'diff_action_id'),
        );
    }

    public function attributeLabels()
    {
        return array(
        );
    }

    protected function beforeSave()
    {
        $this->update_time = date(Yii::app()->params->dateFormat);
        $this->updated_by = Yii::app()->user->id;
        return parent::beforeSave();
    }
    
}
?>