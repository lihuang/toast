<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * View Run Model
 * 
 * @package application.models
 */
class VTaskRun extends TaskRun
{
    public $responsible;
    public $responsible_username;
    public $responsible_realname;
    public $report_to;
    public $task_name;
    public $task_type;
    public $product_id;
    public $project_id;
    public $project_name;
    public $product_name;
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
        return 'vtask_run';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('status, result, task_id, task_name, name, type, product_id, project_id, create_time, update_time,
                created_by_realname, updated_by_realname, parent_id', 'safe'),
        );
    }

    public function relations()
    {
        return parent::relations() + array(
            'vcommandruns' => array(self::HAS_MANY, 'VCommandRun', 'task_run_id'),
            'vtask' => array(self::BELONGS_TO, 'VTask', 'task_id'),
        );
    }
    
    public function count($condition = '', $params = array())
    {
        return parent::count($condition, $params);
    }
    
    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'task_name' => Yii::t('VRun', 'Task Name'),
            'product_name' => Yii::t('VRun', 'Product Name'),
            'project_name' => Yii::t('VRun', 'Project Name'),
            'created_by_realname' => Yii::t('VRun','Created By Realname'),
            'updated_by_realname' => Yii::t('VRun','Updated By Realname'),
            'responsible_realname' => Yii::t('VRun', 'Responsible'),
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
            $criteria->addCondition('task_id IN (SELECT id FROM vtask WHERE vtask.product_id=' . $product_id . ')');
//            $criteria->compare('product_id', $product_id);
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            // TODO: do not use the name field for searching
            $this->name = $condition; 
        }
        
        $criteria->select = 'id, name, task_name, status, result, project_name, 
            start_time, stop_time, created_by_realname, created_by_username, create_time';
        $criteria->compare('id', $this->name, true, 'OR');
//        $criteria->compare('name', $this->name, true, 'OR');
        $criteria->compare('task_name', $this->name, true, 'OR');
        $criteria->compare('created_by_realname', $this->name, true, 'OR');
        $criteria->compare('created_by_username', $this->name, true, 'OR');
        $criteria->compare('task_id', $this->task_id);

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
                'defaultOrder' => "id DESC"
            ),
        ));
    }
    
    public function getCommiterEmails($domain = '@taobao.com')
    {
        $emails = array();
        if(!empty($this->dev_log) && $logArr = CJSON::decode($this->dev_log))
        {
            foreach($logArr as $log)
            {
                $user = User::model()->findByAttributes(array('username' => $log['author']));
                if($user !== null)
                {
                    $emails[] = $user->email;
                }
                else
                {
                    $emails[] = $log['author'] . $domain;
                }
            }
        }
        return $emails;
    }
    
    public function getDevLog()
    {
        $content = '';
        if(!empty($this->dev_log) && $logArr = CJSON::decode($this->dev_log))
        {
            foreach($logArr as $log)
            {
                $content .= CHtml::tag('div', array('style' => 'margin: 5px 0px 3px 0px; clear: both;'),
                        Yii::t('Run', 'Change By {user} @ {time}' , array(
                            '{user}' => $log['author'],
                            '{time}' => date('Y年n月j日 G:i:s', strtotime($log['date'])))), true);
                foreach($log['lists'] as $list)
                {
                    if(!isset($list['action']))
                    {
                        $content .= CHtml::tag('div', array('style' => 'text-left'), $list, true);
                    }
                    else
                    {
                        if('D' == $list['action'])
                        {
                            $content .= CHtml::tag('div', array('style' => 'text-left'), 
                                    $list['action']  . '  ' . $list['file'], true);
                        }
                        else if('A' != $list['action'])
                        {
                             $content .= CHtml::tag('div', array('style' => 'text-left'), 
                                     $list['action']  . '  ' . $list['file']
                                     . ' ' . CHtml::link('diff', $this->getDiffUrl($list),
                                     array('target' => '_blank')), true);
                        }
                        else
                        {
                            $content .= CHtml::tag('div', array('style' => 'text-left'), 
                                     $list['action']  . '  ' . $list['file']
                                     . ' ' . CHtml::link('diff', $this->getDiffUrl($list),
                                     array('target' => '_blank')), true);                        
                        }
                    }
                }
                $content .= CHtml::tag('div', array('style' => 'text-left; margin-bottom: 5px;'), 
                         Yii::t('Run', 'Comment {comment}', array('{comment}' => $log['comment'] )), true);
            }
        }
        
        $commandConts = array();
        foreach($this->vcommandruns as $commandRun)
        {
            $rawDesInfo = nl2br(stripcslashes(htmlspecialchars($commandRun->desc_info)));
            $descinfoWithLink = preg_replace('/\[img case=(.*)\][ ]?([^ ]*)[ ]?\[\/img\]/i', 
                    '<div style="display: inline-block; margin-right: 20px">'
                  . '<a href="$2" rel="lightbox[1]" title="$1"><img width="200px" src="$2" /></a>'
                  . '</div>', $rawDesInfo);
            $descInfo = preg_replace('#([^"]|^)(http|https):\/\/[^ <]*#i', '<a href="$0" target="_blank">$0</a>', 
                    $descinfoWithLink);
            if(!empty($descInfo))
            {
                $commandConts[] = '<span style="color: #999999">COMMAND '
                    . $commandRun->command_name . '</span>:<br/>' . $descInfo;
            }
        }
        
        $content .= join('<br/><br/>', $commandConts);
        
        if(!empty($content))
        {
            echo CHtml::tag('div', array('class' => 'detail block clearfix', 'style' => 'margin-top: 0;'), trim($content), true);
        }
    }
    
    private function getDiffUrl($list)
    {
        $link = '#';
        if(isset($list['diffurl']))
        {
            $link = $list['diffurl'];
        }
        return $link;
    }
    
    public function getCaseAmount()
    {
        $case_total_amount = 'NA';
        $case_passed_amount = 'NA';
        $case_failed_amount = 'NA';
        $case_notrun_amount = 'NA';
        if($this->hasCompleted())
        {
            $case_total_amount = $case_passed_amount = 0;
            $case_failed_amount = $case_notrun_amount = 0;

            $vCommandRuns = $this->vcommandruns;
            foreach ($vCommandRuns as $vCommandRun)
            {
                $case_total_amount += $vCommandRun->case_total_amount;
                $case_passed_amount += $vCommandRun->case_pass_amount;
                $case_failed_amount += $vCommandRun->case_fail_amount;
                $case_notrun_amount += $vCommandRun->case_skip_amount + $vCommandRun->case_block_amount;
            }
        }
        
        return array($case_total_amount, $case_passed_amount, $case_failed_amount, $case_notrun_amount);
    }
    
    public function getTitle()
    {
        return Yii::t('Run', 'Run #{id} By {created_by} @ {create_time}', array(
            '{id}' => $this->id, 
            '{created_by}' => $this->created_by_realname,
            '{create_time}' => $this->create_time));
    }
}