<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * DiffAttribute Model
 * 
 * @package application.models
 */
class DiffAttribute extends Model
{
    public $id;
    public $model_name;
    public $model_id;
    public $attribute;
    public $old;
    public $new;
    public $diff_action_id;

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
        return '{{diff_attribute}}';
    }

    /**
     * validation rules
     */
    public function rules()
    {
        return array(
            array('model_name, model_id, attribute, diff_action_id', 'required'),
            array('model_name, model_id, attribute, old, new, diff_action_id', 'safe'),
        );
    }

    /**
     * relations
     */
    public function relations()
    {
        return array(
            'diffaction' => array(self::BELONGS_TO, 'DiffAction', 'diff_action_id'),
        );
    }

    public function attributeLabels()
    {
        return array(
        );
    }
}
?>