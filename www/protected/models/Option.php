<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 

/**
 * This is the option class.
 * 
 * @package application.models
 */
class Option //extends Model
{
    /**
     * option key 
     * @var string
     */
    public $optKey;
    /**
     * option value
     * @var string 
     */
    public $optVal;
    /**
     * Return a instance of option class.
     * 
     * @param string $className class name, option as default
     * @return type 
     */
    public static function model($className = __CLASS__)
    {
        return new Option();
        //return parent::model($className);
    }
    
    /**
     * Return the table name.
     * 
     * @return string table name
     */
    public function tableName()
    {
        return 'option';
    }
    
    /**
     * Return the validation rules for attributes.
     * 
     * @return array a list of validation rules
     */
    public function rules()
    {
        return array(
            array('optKey, optVal', 'required'),
            array('optKey', 'unique'),
        );
    }
    
    /**
     * Get option value via option key. 
     * 
     * @param string $optKey option key
     * @param boolean $isArr default as false. If it is true, explode the value via $delimiter.
     * @param string $delimiter ',' as default.
     * @return mixed option value
     */
    private function getOptVal($optKey, $isArr = false, $delimiter = ',')
    {
        $val = '';
        
        $option = Option::model()->findByAttributes(array('optKey' => $optKey));
        if(null !== $option)
        {
            $val = $option->optVal;
        }
        
        if($isArr)
        {
            $val = explode($delimiter, $val);
        }
        
        return $val;
    }
    
    /**
     * Return toast system date format.
     * 
     * @return string date format 
     */
    public function getDateFormatOpt()
    {
        return 'Y-m-d H:i:s';
    }
}
?>