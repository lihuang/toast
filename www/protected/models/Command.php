<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */


/**
 * Command Model
 * 
 * @package application.models
 */
class Command extends Model
{
    public $id;
    public $name;
    public $command;
    public $desc_info;
    public $parser_id;
    public $status = self::STATUS_AVAILABLE;
    public $mode = self::MODE_BASIC;

    const STATUS_DISABLE = 0;
    const STATUS_AVAILABLE = 1;
    const STATUS_PUBLISH = 2;
    
    const MODE_BASIC = 0;
    const MODE_UT = 1;
    const MODE_CI = 4;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'command';
    }

    public function rules()
    {
        return array(
            array('name, command', 'required'),
            array('name', 'length', 'max' => 255),
            array('name, status, command, desc_info, parser_id, mode', 'safe'),
        );
    }

    public function relations()
    {
        return array(
            'tasks' => array(self::HAS_MANY, 'Task', 'command_id'),
            'commandruns' => array(self::HAS_MANY, 'CommandRun', 'command_id'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Command', 'Id'),
            'name' => Yii::t('Command', 'Name'),
            'command' => Yii::t('Command', 'Command Info'),
            'desc_info' => Yii::t('Command', 'Description Info'),
            'parser_id' => Yii::t('Command', 'Test Tool'),
            'created_by' => Yii::t('Command', 'Created By'),
            'updated_by' => Yii::t('Command', 'Updated By'),
            'create_time' => Yii::t('Command', 'Create Time'),
            'update_time' => Yii::t('Command', 'Update Time'),
            'status' => Yii::t('Command', 'Status'),
        );
    }

    protected function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
            $this->created_by = $this->updated_by = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
            $this->updated_by = Yii::app()->user->id;
            
            $diffs = $this->getDiff();
            if(!empty($diffs))
            {
                $diffAction = new DiffAction();
                $diffAction->model_name = get_class($this);
                $diffAction->model_id = $this->id;
                $diffAction->save();
                foreach($diffs as $diff)
                {
                    $diffAttr = new DiffAttribute();
                    $diffAttr->attributes = $diff;
                    $diffAttr->diff_action_id = $diffAction->id;
                    $diffAttr->save();
                }
            }
        }
        return parent::beforeSave();
    }
    
    /**
     * get command parsers
     * 
     * @return Array ParserList
     */
    public function getParsers($object = TRUE)
    {
        $parsers = array();
        $parserIDs = preg_split('/,/', $this->parser_id);
        $parserStr = '';
        foreach($parserIDs as $parserID)
        {
            $parser = Parser::model()->findByPk($parserID);
            if($parser)
            {
                $parsers[] = $parser;
                $parserStr .= $parser->name . ', ';
            }
        }
        $parserStr = trim($parserStr, ', ');
        if(empty($parserStr))
        {
            $parserStr = Yii::t('Parser', 'No need parse');
        }
        if($object)
            return $parsers;
        else 
            return trim($parserStr, ', ');
    }
    
    public function getCommandOptions()
    {
        $opts = array();
        $commands = VCommand::model()->findAll('status <> ' . self::STATUS_DISABLE 
                . ' AND created_by=' . Yii::app()->user->id . ' ORDER BY name');
        foreach($commands as $command)
        {
            $opts[$command->id] = $command->name;
        }
        return $opts;
    }
    
    public function getStatusOptions()
    {
        return array(
            self::STATUS_DISABLE => Yii::t('Command', 'Status Disabled'),
            self::STATUS_AVAILABLE => Yii::t('Command', 'Status Available'),
            self::STATUS_PUBLISH => Yii::t('Command', 'Status Publish'),
        );
    }
    
    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status]) ? $status[$this->status] 
                : Yii::t('Machine', 'Unknown status({status})', array('{status}' => $this->status));
    }
    
    public function getNavItems()
    {
        $items = array();
        if ($this->isNewRecord)
            $items[] = array('label' => Yii::t('Command', 'New Command'));
        else
        {
            $items[] = array(
                'label' => '#' . $this->id  . ' '. $this->name,
                'url' => array('/command/view/id/' . $this->id));
            $items[] = array('label' => Yii::t('TOAST', 'Modify'));
        }
        return $items;
    }
}
?>