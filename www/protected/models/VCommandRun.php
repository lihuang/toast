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
class VCommandRun extends CommandRun
{
    public $command_name;
    public $machine_name;
    public $machine_status;
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
        return 'vcommand_run';
    }

    public function primarykey()
    {
        return 'id';
    }
    
    public function rules()
    {
        return array(
        );
    }
    
    public function relations()
    {
        return parent::relations() + array(
            'vcommand' => array(self::BELONGS_TO, 'VCommand', 'command_id'),
            'vmachine' => array(self::BELONGS_TO, 'VMachine', 'machine_id'),
            'vtaskrun' => array(self::BELONGS_TO, 'VTaskRun', 'task_run_id'),
        );
    }
    
    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'command_name' => Yii::t('VRun', 'Command Name'),
            'machine_name' => Yii::t('VRun', 'Machine Name'),
            'machine_status' => Yii::t('VRun', 'Machine Status'),
            'created_by_realname' => Yii::t('VRun','Created By Realname'),
            'updated_by_realname' => Yii::t('VRun','Updated By Realname')
        );
    }

    public function getLineCoverRate()
    {
        if ($this->cc_line_total > 0)
        {
            return $this->cc_line_hit/$this->cc_line_total;
        }
        return 0;
    } 
    
    /**
     * Passed/Failed/Skipped/Blocked Case Rate
     * @param type $which
     * @param type $real
     * @return int 
     */
    public function getCaseRate()
    {
        if ($this->case_total_amount == 0) 
            return array(CaseResult::RESULT_PASSED => 0,
                CaseResult::RESULT_FAILED => 100,
                CaseResult::RESULT_SKIPPED => 0,
                CaseResult::RESULT_BLOCKED => 0);
        $passedRate = round(100 * $this->case_pass_amount / $this->case_total_amount);
        if ($passedRate < 10 && $this->case_pass_amount > 0)
            $passedRate = 10;
        $rate[CaseResult::RESULT_PASSED] = $passedRate;
        $failedRate = round(100 * $this->case_fail_amount / $this->case_total_amount);
        if ($failedRate < 10 && $this->case_fail_amount > 0)
            $failedRate = 10;
        $rate[CaseResult::RESULT_FAILED] = $failedRate;
        $skippedRate = round(100 * $this->case_skip_amount / $this->case_total_amount);
        if ($skippedRate < 10 && $this->case_skip_amount > 0)
            $skippedRate = 10;
        $rate[CaseResult::RESULT_SKIPPED] = $skippedRate;
        $blockedRate = round(100 * $this->case_block_amount / $this->case_total_amount);
        if ($blockedRate < 10 && $this->case_block_amount > 0)
            $blockedRate = 10;
        $rate[CaseResult::RESULT_BLOCKED] = $blockedRate;
        $rest = array_sum($rate) - 100;
        if($rest > 0)
        {
            $rests = array_fill(0, 3, floor($rest/4));
            $rests[3] = $rest - array_sum($rests);
            asort($rate);
            $index = 0;
            foreach ($rate as $key => $value) 
            {
                if ($value > 10 && $value - $rests[$index] > 10)
                {
                    $rate[$key] = $value - $rests[$index];
                    $rest = $rest - $rests[$index];
                }
                else
                {
                    $count = 3 - $index;
                    if ($count <= 0) continue;
                    $rests = array_fill($index + 1, $count, floor($rest/$count));
                    $rests[3] = 0;
                    $rests[3] = $rest - array_sum($rests);
                }
                $index++;
            }
        }
        return $rate;
    }
    
    public function search($pageSize)
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'id, status, result, machine_name, sudoer, timeout, start_time, stop_time';

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
    
    public function getCaseRatioHtml()
    {
        $html = '';
        if($this->hasCompleted())
        {
            $rates = $this->getCaseRate();
            if($rates[CaseResult::RESULT_PASSED] > 0)
            {
                $html .= CHtml::link(
                        CHtml::tag('div', array(
                            'class' => 'case-ratio passed', 
                            'style' => 'width: ' . $rates[CaseResult::RESULT_PASSED] . '%'), 
                                $this->case_pass_amount), 'javascript:;',array(
                        'class' => 'view_passed_detail',
                        'title' => Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Passed'))));
            }
            if($rates[CaseResult::RESULT_FAILED] > 0)
            {            
                $html .= CHtml::link(
                        CHtml::tag('div', array(
                            'class' => 'case-ratio failed', 
                            'style' => 'width: ' . $rates[CaseResult::RESULT_FAILED] . '%'), 
                                $this->case_fail_amount), 'javascript:;',array(
                        'class' => 'view_failed_detail',
                        'title' => Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Failed'))));
            }
            if($rates[CaseResult::RESULT_SKIPPED] > 0)
            {            
                $html .= CHtml::link(
                        CHtml::tag('div', array(
                            'class' => 'case-ratio skipped', 
                            'style' => 'width: ' . $rates[CaseResult::RESULT_SKIPPED] . '%'), 
                                $this->case_skip_amount), 'javascript:;',array(
                        'class' => 'view_skipped_detail',
                        'title' => Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Skipped'))));
            }
            if($rates[CaseResult::RESULT_BLOCKED] > 0)
            {            
                $html .= CHtml::link(
                        CHtml::tag('div', array(
                            'class' => 'case-ratio blocked', 
                            'style' => 'width: ' . $rates[CaseResult::RESULT_BLOCKED] . '%'), 
                                $this->case_block_amount), 'javascript:;',array(
                        'class' => 'view_blocked_detail',
                        'title' => Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Blocked'))));            
            }
        }
        else
        {
            $html = CHtml::tag('div', array('class' =>'null', 'style' => 'text-align: center'), 'NA');
        }
        return $html;
    }
    
    public function getLineCoverHtml()
    {
        $rate = $this->getLineCoverRate();
        $html = 'NA';
        $colorClass = 'rate-low';
        if($rate >= 0.75)
        {
            $colorClass = 'rate-high';
        }
        else if($rate >= 0.5)
        {
            $colorClass = 'rate-medium';
        }
        
        if(is_numeric($this->cc_line_hit) && is_numeric($this->cc_line_total) && ($this->cc_line_total != 0))
        {
            $text = Yii::t('Run', 'Line Hit'). '/'. Yii::t('Run', 'Line Total'). ':&nbsp;' 
                    . $this->cc_line_hit . '/'. $this->cc_line_total . '&nbsp;&nbsp;&nbsp;&nbsp;'
                    . Yii::t('Run', 'Line Hit Rate'). ':&nbsp;'
                    . round($rate * 100, 1) . '%';
                            
            if ($this->cc_result !== '' && $this->cc_result !== 'NA')
            {
                $html = CHtml::link($text, $this->cc_result, array('target' => '_blank', 'class' => $colorClass, 
                    'title' => Yii::t('Run', 'Click For Coverage Detail')));
            }
            else
            {
                $html = CHtml::tag('span', array('class' => $colorClass), $text);
            }
        }
        else if(!empty($this->cc_result) && $this->cc_result !== 'NA')
        {
            $html = CHtml::link($this->cc_result, $this->cc_result,  
                    array('target' => '_blank',  'class' => $colorClass, 
                        'title' => Yii::t('Run', 'Click For Coverage Detail')));
        }
        
        return $html;
    }
    
    public function getBranchCoverHtml()
    {
        $html = '';
        if(is_numeric($this->cc_branch_hit) && is_numeric($this->cc_branch_total) && ($this->cc_branch_total != 0))
        {
            $html = Yii::t('Run', 'Branch Hit'). '/'. Yii::t('Run', 'Branch Total'). ':&nbsp;'
                    . $this->cc_branch_hit . '/'. $this->cc_branch_total;
        } else {
            $html = 'NA';
        }
        return $html;
    }
    
    public function getCaseAmount()
    {
        $case_total_amount = 'NA';
        $case_passed_amount = 'NA';
        $case_failed_amount = 'NA';
        $case_notrun_amount = 'NA';
        if($this->hasCompleted())
        {
            $case_total_amount = $this->case_total_amount;
            $case_passed_amount = $this->case_pass_amount;
            $case_failed_amount = $this->case_fail_amount;
            $case_notrun_amount = $this->case_skip_amount + $this->case_block_amount;
        }

        return array($case_total_amount, $case_passed_amount, $case_failed_amount, $case_notrun_amount);
    }
}