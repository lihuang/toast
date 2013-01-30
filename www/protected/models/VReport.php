<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * View Report Model
 * 
 * @package application.models
 */
class VReport extends Report
{
    public $task_id;
    public $task_name;
    public $task_type;
    public $case_total_amount;
    public $case_complete_amount;
    public $case_pass_amount;
    public $case_fail_amount;
    public $cc_result;
    public $build;
    public $cc_line_hit;
    public $cc_line_total;
    public $result;
    public $status;
    public $project_id;
    public $project_name;
    public $product_id;
    public $product_name;
    public $module_id;
    public $module_name;
    public $responsible_username;
    public $responsible_realname;
    public $count;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'vreport';
    }

    public function primarykey()
    {
        return 'id';
    }

    public function rules()
    {
        return array(
            array('task_id, task_type, product_id, project_id, module_id,
                year, month, week, date', 'safe'),
        );
    }

    public function relations()
    {
        return array(
            '_run' => array(self::BELONGS_TO, 'Run', 'run_id'),
            '_task' => array(self::BELONGS_TO, 'Task', 'task_id'),
            '_project' => array(self::BELONGS_TO, 'Project', 'project_id'),
            '_product' => array(self::BELONGS_TO, 'Product', 'product_id'),
            '_module' => array(self::BELONGS_TO, 'Project', 'module_id')
        );
    }

    public function attributeLabels()
    {
        return array(
            'date' => Yii::t('VReport', 'Date'),
            'task_name' => Yii::t('VReport', 'Task Name'),
            'product_name' => Yii::t('VReport', 'Product Name'),
            'project_name' => Yii::t('VReport', 'Project Name'),
            'task_type' => Yii::t('VReport', 'Task Type'),
            'status' => Yii::t('VReport', 'Status'),
            'result' => Yii::t('VReport', 'Result'),
            'cc_result' => Yii::t('VReport', 'CC Result'),
            'build' => Yii::t('VReport', 'Build'),
            'cc_line_hit' => Yii::t('VReport', 'Line Hit'),
            'cc_line_total' => Yii::t('VReport', 'Line Total'),
            'case_total_amount' => Yii::t('VReport', 'Case Total Amount'),
            'case_complete_amount' => Yii::t('VReport', 'Case Completed Amount'),
            'case_pass_amount' => Yii::t('VReport', 'Case Passed Amount'),
            'case_fail_amount' => Yii::t('VReport', 'Case Failed Amount'),
            'responsible_realname' => Yii::t('VReport', 'Responsible By Realname'),
            'scan_all' => Yii::t('VReport', 'ALL'),
            'scan_alllogs' => Yii::t('VReport', 'All Logs'),
            'scan_comment' => Yii::t('VReport', 'Comment'),
        );
    }

    private function getStatusOptions()
    {
        return CommandRun::model()->getStatusOptions();
    }

    private function getResultOptions()
    {
        return CommandRun::model()->getResultOptions();
    }

    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status])
               ? $status[$this->status]
               : Yii::t('Run', 'Unknown status({status})', array('{status}' => $this->status));
    }

    public function getResultText()
    {
        $results = $this->getResultOptions();
        return isset($results[$this->result])
               ? $results[$this->result]
               : Yii::t('Run', 'Unknown result({result})', array('{result}' => $this->result));
    }

    public function getStatusStyle()
    {
        $style = '';
        switch ($this->status) {
            case CommandRun::STATUS_RUNNING :
            case CommandRun::STATUS_WAITING:
            case CommandRun::STATUS_CANCELING: {
                $style = 'running';
                break;
            }
            default : {
                break;
            }
        }
        return $style;
    }

    public function getResultStyle()
    {
        $style = '';
        if(CommandRun::RESULT_PASSED == $this->result)
        {
            $style = 'passed';
        }
        else if(CommandRun::STATUS_COMPLETED < $this->status)
        {
            $style = "skipped";
        } 
        else if(CommandRun::RESULT_FAILED == $this->result)
        {
            $style = "failed";
        }
        return $style;
    }

    public function getTypeOptions()
    {
        $opts = array();
        $opts[Task::TYPE_UNIT] = Yii::t('Task', 'Unit Test Task');
        $opts[Task::TYPE_REGRESS] = Yii::t('Task', 'Regression Test Task');
        return $opts;
    }

    public function getTypeText()
    {
        $types= $this->getTypeOptions();
        return isset($types[$this->task_type])
               ? $types[$this->task_type]
               : Yii::t('Task', 'Unknown Type({type})', array('{type}' => $this->task_type));
    }

    public function getTitle()
    {
        $title = $this->getDurationText() . '  ' . $this->_product->name . ' '  . $this->getTypeText() . Yii::t('VReport', 'Report');
        if(isset($this->_module))
        {
            $title = $this->getDurationText() . '  ' . $this->_product->name . '-' 
                    . $this->_module->name . ' '  . $this->getTypeText() . Yii::t('VReport', 'Report');
        }
        return $title;
    }

    public function getDurationText()
    {
        $text = date('Y' . Yii::t('Report', 'Year') . 'n' . Yii::t('Report', 'Month') .'j' . Yii::t('Report', 'Day'), strtotime($this->date));
        $text .= ' (' . $this->getWeekText() . ')';
        return $text;
    }

    private function getWeekText()
    {
        $text = '';
        $week = date('N', strtotime($this->date));
        switch($week)
        {
            case 1: {
                $text = Yii::t('Report', 'Monday');
                break;
            }
            case 2: {
                $text = Yii::t('Report', 'Tuesday');
                break;
            }
            case 3: {
                $text = Yii::t('Report', 'Wednesday');
                break;
            }
            case 4: {
                $text = Yii::t('Report', 'Thursday');
                break;
            }
            case 5: {
                $text = Yii::t('Report', 'Friday');
                break;
            }
            case 6: {
                $text = Yii::t('Report', 'Saturday');
                break;
            }
            case 7: {
                $text = Yii::t('Report', 'Sunday');
                break;
            }
            default: {
                break;
            }
        }
        return $text;
    }    
    
    public function getDetailObj()
    {
        $clazz = 'VReport';
        switch($this->task_type)
        {
            case Task::TYPE_REGRESS: {
                $clazz = 'ReportRegress';
                break;
            }
            case Task::TYPE_UNIT: {
                $clazz = 'ReportUnit';
                break;
            }
            case Task::TYPE_BVT: {
                $clazz = 'ReportBVT';
                break;
            }
            case Task::TYPE_SYSTEM: {
                $clazz = 'ReportSystem';
                break;
            }
            default: {
                break;
            }
        }
        $report = new $clazz();
        $report->product_id = $this->product_id;
        $report->date = $this->date;
        $report->task_type = $this->task_type;
        $report->module_id = $this->module_id;
        $report->result = $this->result;
        $report->syncDate();
        
        return $report;
    }
    
    public function get12MonthsBefore()
    {
        $months = array();
        $now = date('Y-n', strtotime($this->date));
        list($year, $month) = explode('-', $now);
        for($i = 0; $i < 12; $i++)
        {
            $m = $month - $i;
            $y = $year;
            if($m <= 0)
            {
                $m += 12;
                $y -= 1;
            }
            $monthes[] = $y . '-' . $m;
        }
        return $months;
    }
    
    public function getPrevDate()
    {
        return date('Y-m-d', strtotime($this->date . ' -1 day'));
    }

    public function getNextDate()
    {
        return date('Y-m-d', strtotime($this->date . ' + 1 day'));
    }
    
    public function getPassedPercent()
    {
        $percent = "0%";
        if ($this->getCompleteAmount() > 0)
        {
            $percent = round((float) $this->case_pass_amount / (float) $this->getCompleteAmount(), 4) * 100 . "%";
        }
        return $percent;
    }
    
    public function getAutoCoverageRate()
    {
        $rate = Yii::t('VReport', 'Not Count');
        if($this->all_case_amount > 0)
        {
            $rate = round((float) $this->case_total_amount / (float) $this->all_case_amount, 4) * 100 . "%";
            if($this->case_total_amount > $this->all_case_amount)
            {
                 $rate = "100%";
            }
        }
        return $rate;
    }
    
    /**
     * Return the passed case bar length.
     * 
     * @param integer $length the whole bar length
     * @return integer return passed bar length
     */
    public function getPassedBarLength($length = 200)
    {
        if(0 != $this->case_pass_amount)
        {
            $length = ceil($length * round((float) $this->case_pass_amount / (float) $this->getCompleteAmount(), 4));
            if($length < 20)
            {
                $length = 20;
            }
            else if(180 < $length && $this->case_fail_amount != 0)
            {
                $length = 180;
            }
            else if(0 == $this->case_fail_amount)
            {
                $length = 200;
            }
        }
        else if(0 == $this->case_fail_amount)
        {
            $length = 200;
        }
        else
        {
            $length = 0;
        }
        return $length;
    }
    
    /**
     * Return the failed case bar length.
     * 
     * @param integer $length the whole bar length
     * @return integer return failed bar length
     */
    public function getFailedBarLength($length = 200)
    {
        return $length - $this->getPassedBarLength();
    }
    
    public function getCaseRatioHtml()
    {
        $html = CHtml::tag('div', array('class' => 'ratio-bar passed', 'style' => 'width: ' . $this->getPassedBarLength(200) . 'px'), $this->case_pass_amount);
        $html .= CHtml::tag('div', array('class' => 'ratio-bar failed', 'style' => 'width: ' . $this->getFailedBarLength(200) . 'px'), $this->case_fail_amount);
        
        $percent = $this->getPassedPercent();
        if('100%' == $percent || 0 == $this->case_fail_amount)
        {
            $html .= CHtml::tag('span', array('class' => 'ratio-num', 'style' => 'color: #89A54E'), $percent);
        }
        else
        {
            $html .= CHtml::tag('span', array('class' => 'ratio-num', 'style' => 'color: #AA4643'), $percent);
        }
        
        if($this->status >= CommandRun::STATUS_TIMEOUT)
        {
            $failedText = $this->getStatusText();
            $html = CHtml::tag('div', array('class' => 'ratio-bar other', 'style' => 'width: 200px'), $failedText);
        }
        
        return $html; 
   }
   
   private function getCompleteAmount()
   {
       return $this->case_fail_amount + $this->case_pass_amount;
   }
   
   public function search()
   {
       // empty
   }
   
    public function getFailedTaskOwner()
    {
        $condition = new CDbCriteria();
        $condition->compare('case_fail_amount', '>0');
        $owners = array();
        $records = $this->search($condition);
        foreach($records as $record)
        {
            $owners[] = $record->responsible_realname;
        }
        return $owners;
    }
}
?>