<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Report Regress Model
 * 
 * @package application.models.report
 */
class ReportRegress extends VReport
{
    public $task_type = Task::TYPE_REGRESS;

    /**
     * 获取ReportRegress静态实例
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function search($condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            $criteria->compare('task_name', $condition, true);
        }

        $criteria->select = 'id, task_id, task_run_id, task_name, product_name, project_name, module_name, module_id,
            task_type, status, result, case_total_amount, case_pass_amount,
            case_fail_amount, date, responsible_realname';
        
        $criteria->compare('product_id', $this->product_id);
        $criteria->compare('project_id', $this->project_id);
        $criteria->compare('task_type', $this->task_type);
        $criteria->compare('result', $this->result);
        $criteria->compare('date', $this->date);
        $criteria->order = 'module_id ASC, project_id ASC, task_name ASC';
        $criteria->group = 'task_id';

        return $this->findAll($criteria);
    }

    public function getCount($condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            $criteria->compare('task_name', $condition, true);
        }

        $criteria->select = 'sum(case_total_amount) as case_total_amount,
            sum(case_pass_amount) as case_pass_amount,
            sum(case_fail_amount) as case_fail_amount, date';

        $criteria->compare('product_id', $this->product_id);
        $criteria->compare('project_id', $this->project_id);
        $criteria->compare('module_id', $this->module_id);
        $criteria->compare('task_type', $this->task_type);
        $criteria->compare('result', $this->result);
        $months = $this->get12MonthsBefore();
        $conditiones = array();
        foreach($months as $date)
        {
            list($year, $month) = explode('-', $date);
            $conditiones[] = "(`year` = {$year} AND `month` = {$month})";
        }
        if(!empty($conditiones))
                $criteria->condition .= 'AND (' . join(' OR ', $conditiones) . ')';
        $criteria->group = 'date';

        $reports = $this->findAll($criteria);
        $info = array();
        $casePassed = array();
        $caseFailed = array();
        $caseCompleted = array();
        $caseTotal = array();

        $last = strtotime($this->date) - 86400 * 364;
        for($i = 0; $i < 365; $i++)
        {
            $time = $last + 86400 * $i;
            $day = date('Y-m-d', $time);
            $date[] = $time * 1000;
            $casePassed[$day] = $caseFailed[$day] = $caseCompleted[$day] = $caseTotal[$day] = 0;
        }

        foreach($reports as $report)
        {
            if(!isset($casePassed[$report->date])) continue;
            $casePassed[$report->date] = intval($report->case_pass_amount);
            $caseFailed[$report->date] = intval($report->case_fail_amount);
            $caseCompleted[$report->date] = intval($report->case_complete_amount);
            $caseTotal[$report->date] = intval($report->case_total_amount);
        }

        $info['case'] = array(
            'case_passed' => array_values($casePassed),
            'case_failed' => array_values($caseFailed),
            'case_completed' => array_values($caseCompleted),
            'case_total' => array_values($caseTotal)
        );

        $info['task'] = $this->getResultCount($condition);

        return $info;
    }

    private function getResultCount($condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            $criteria->compare('task_name', $condition, true);
        }

        $criteria->select = 'result, status, case_fail_amount';
        $criteria->compare('product_id', $this->product_id);
        $criteria->compare('project_id', $this->project_id);
        $criteria->compare('module_id', $this->module_id);
        $criteria->compare('task_type', $this->task_type);
        $criteria->compare('result', $this->result);
        $criteria->compare('date', $this->date);
        $reports = VReport::model()->findAll($criteria);
        $data = array(
            'success' => 0,
            'failure' => 0,
            'other' => 0
        );
        foreach($reports as $report)
        {
            if($report->status >= CommandRun::STATUS_TIMEOUT)
            {
                $data['other'] = $data['other'] + 1;
            }
            else if(CommandRun::RESULT_FAILED == $report->result && $report->case_fail_amount > 0)
            {
                $data['failure'] = $data['failure'] + 1;
            }
            else if(CommandRun::RESULT_PASSED == $report->result)
            {
                $data['success'] = $data['success'] + 1;
            }
        }
        return $data;
    }
}
?>