<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 

/**
 * View Task Model
 * 
 * @package application.models
 */
class VTask extends Task
{
    public $product_id;
    public $product_name;
    public $project_name;
    public $responsible_username;
    public $responsible_realname;
    public $created_by_username;
    public $created_by_realname;
    public $updated_by_username;
    public $updated_by_realname;
    public $parent_id;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'vtask';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('name, product_id, project_id, create_time, update_time,
                created_by_realname, updated_by_realname, parent_id', 'safe'),
        );
    }

    public function relations()
    {
        return parent::relations() + array(
            'product' => array(self::BELONGS_TO, 'Product', 'product_id'),
            'vtaskruns' => array(self::HAS_MANY, 'VTaskRun', 'task_id', 'order' => 'id DESC'),
        );
    }
    
    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'responsible_realname' => Yii::t('VTask', 'Responsible By Realname'),
            'product_name' => Yii::t('VTask', 'Product Name'),
            'project_name' => Yii::t('VTask', 'Project Name'),
            'created_by_realname' => Yii::t('VTask','Created By Realname'),
            'updated_by_realname' => Yii::t('VTask','Updated By Realname')
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
        
        $criteria->select = 'id, name, project_name, type, responsible_realname, updated_by_realname, update_time';
        $criteria->compare('id', $this->name, true, 'OR');
        $criteria->compare('name', $this->name, true, 'OR');
        $criteria->compare('updated_by_realname', $this->name, true, 'OR');
        $criteria->compare('responsible_realname', $this->name, true, 'OR');
        $criteria->compare('updated_by_username', $this->name, true, 'OR');
        $criteria->compare('responsible_username', $this->name, true, 'OR');
        $criteria->compare('status', Task::STATUS_AVAILABLE);
        
        if(isset($this->parent_id))
        {
            $project = Project::model()->findByPk($this->parent_id);
            if($project != null)
            {
                $subIds = $project->getSubProjects();
                $criteria->addInCondition('project_id', $subIds);
                Yii::app()->user->setCurrentProduct($project->product_id);
            }
        }
        
        if(isset($this->product_id))
        {
            Yii::app()->user->setCurrentProduct($this->product_id);
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
    
    /**
     * get tasks which on the machine
     */
    public function getTasksByMachine($machineId, $pageSize)
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'id, name, product_name, project_name, type, responsible_realname, updated_by_realname, update_time';
        $criteria->addInCondition('id', Job::model()->getTaskIdsByMachine($machineId));
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
    
    /**
     * get tasks which run this command
     */
    public function getTasksByCommand($commandId, $pageSize)
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'id, name, product_name, project_name, type, responsible_realname, updated_by_realname, update_time';
        $criteria->addInCondition('id', Job::model()->getTaskIdsByCommand($commandId));
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
    
    public function getNavItems($vTaskRun = null)
    {
        $label = '#' . $this->id . ' ';
        if($this->status == Task::STATUS_DISABLE)
            $label .= '[' . Yii::t("Task", "Deleted Task") . ']';
        $label .= $this->name;
        $items = array(array('label' => $label, 'itemOptions' => array('title' => $this->name)));
        if($vTaskRun)
        {
            $items[0]['url'] = array('/task/view/id/' . $this->id);
            $items[] = array('label' => Yii::t('Run', 'Run #{id} By {created_by} @ {create_time}', array('{id}' => $vTaskRun->id, 
                '{created_by}' => $vTaskRun->created_by_realname, '{create_time}' => $vTaskRun->create_time)));
        }
        return $items;
    }
    
    public function getBtnList($vTaskRun = null)
    {
        $btns = array();
        if($this->status != self::STATUS_DISABLE)
        {
            $disabled  = '';
            $cancelLabel = Yii::t('Task', 'Cancel');
            if($vTaskRun && CommandRun::STATUS_CANCELING == $vTaskRun->status)
            {
                $cancelLabel = Yii::t('Task', 'Canceling');
                $disabled = 'disabled';
            }
            $btns[] = '<div class="action-group">';
            $btns[] = CHtml::button(Yii::t('Task', 'Run'), array('class' => 'btn run-task'));
            if($vTaskRun && !$vTaskRun->hasCompleted())
            {
                $btns[] = CHtml::button($cancelLabel, array('class' => 'btn cancel-run', 'disabled' => $disabled));
            }
            $btns[] = '</div>';
            $btns[] = CHtml::button(Yii::t('Task', 'Update'), array('class' => 'btn update-task'));
            $btns[] = CHtml::button(Yii::t('Task', 'Copy'), array('class' => 'btn copy-task'));
            $btns[] = CHtml::button(Yii::t('Task', 'Delete'), array('class' => 'btn delete-task'));
        }
        
        $btns[] = CHtml::button(Yii::t('Task', 'History'), array('class' => 'btn right update-history', 'style' => 'float: right'));
        $detailClass = 'btn right task-detail';
        if(!$vTaskRun)
        {
            $detailClass .= ' active';
        }
        $btns[] = CHtml::button(Yii::t('Task', 'Task Detail'), array('class' =>$detailClass, 'style' => 'float: right'));
        return join(' ', $btns);
    }
    
    public function getExclusiveText()
    {
        $text = Yii::t('Task', 'Yes');
        if(!$this->exclusive)
        {
            $text = Yii::t('Task', 'No');
        }
        return $text;
    }
    
    public function getWaitMachineText()
    {
        $text = Yii::t('Task', 'Yes');
        if(!$this->wait_machine)
        {
            $text = Yii::t('Task', 'No');
        }
        return $text;
    }

    public function getReportToText()
    {
        $text = $this->report_to;
        return $text;
    }
}
?>