<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * View Command Model
 * 
 * @package application.models
 */
class VCommand extends Command
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
        return 'vcommand';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('name, $created_by_username, $created_by_realname, 
                $updated_by_username, $updated_by_realname', 'safe'),
        );
    }

    public function relations()
    {
        return parent::relations() + array(
            'vcommandruns' => array(self::HAS_MANY, 'VCommandRun', 'command_id'),
        );
    }
    
    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'created_by_realname' => Yii::t('Command', 'Created By Realname'),
            'updated_by_realname' => Yii::t('Command', 'Updated By Realname'),
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
        
        $criteria->compare('id', $this->name, true, 'OR');
        $criteria->compare('name', $this->name, true, 'OR');
        $criteria->compare('created_by_realname', $this->name, true, 'OR');
        $criteria->compare('updated_by_realname', $this->name, true, 'OR');
        $criteria->compare('created_by_username', $this->name, true, 'OR');
        $criteria->compare('updated_by_username', $this->name, true, 'OR');
        $criteria->compare('status', '<>' . self::STATUS_DISABLE);
        
        if (Yii::app()->user->isAdmin() == false)
        {
            $criteria->addCondition('created_by=' . Yii::app()->user->id . ' OR ' . 'status=' . self::STATUS_PUBLISH);
        }

        return new CActiveDataProvider(__CLASS__, array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => $pageSize
            ),
            'sort' => array(
                'defaultOrder' => "create_time DESC"
            ),
        ));
    }
    
    public function getNavItems($vCommandRun = null)
    {
        $label = '#' . $this->id . ' ';
        if($this->status == Command::STATUS_DISABLE)
            $label .= '[' . Yii::t('Command', 'Deleted') . ']';
        $label .= $this->name;
        $items = array(array('label' => $label, 'itemOptions' => array('title' => $this->name)));
        if($vCommandRun)
        {
            $items[0]['url'] = array('/command/view/id/' . $this->id);
            $items[] = array('label' => Yii::t('Run', 'Run #{id} By {created_by} @ {create_time}', array('{id}' => $vCommandRun->id, 
                '{created_by}' => $vCommandRun->created_by_realname, '{create_time}' => $vCommandRun->create_time)));
        }
        return $items;
    }
    
    public function getBtnList($vCommandRun = null)
    {
        $btns = array();
        if($this->status != self::STATUS_DISABLE)
        {
            $disabled  = '';
            $cancelLabel = Yii::t('Command', 'Cancel');
            if($vCommandRun && CommandRun::STATUS_CANCELING == $vCommandRun->status)
            {
                $cancelLabel = Yii::t('Command', 'Canceling');
                $disabled = 'disabled';
            }
            $btns[] = '<div class="action-group">';
            $btns[] = CHtml::button(Yii::t('Command', 'Run'), array('class' => 'btn run-command'));
            if($vCommandRun && !$vCommandRun->hasCompleted())
            {
                $btns[] = CHtml::button($cancelLabel, array('class' => 'btn cancel-run', 'disabled' => $disabled));
            }
            $btns[] = '</div>';
            $btns[] = CHtml::button(Yii::t('Command', 'Update'), array('class' => 'btn update-command'));
            $btns[] = CHtml::button(Yii::t('Command', 'Delete'), array('class' => 'btn delete-command'));
        }
        
        $btns[] = CHtml::button(Yii::t('Command', 'History'), array('class' => 'btn right update-history', 'style' => 'float: right'));
        $btns[] = CHtml::button(Yii::t('Command', 'Related Tasks'), array('class' => 'btn right show-task', 'style' => 'float: right'));
        return join(' ', $btns);
    }
}