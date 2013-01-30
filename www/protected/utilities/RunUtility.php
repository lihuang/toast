<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Run utility class.
 *
 * @package application.utilities
 */
class RunUtility extends Utility
{

    /**
     * Get command run id by task run id. 
     * @param integer $taskRunId Task run id.
     * @return array Command run ids.
     */
    public static function getCommandRunIds($taskRunId)
    {
        $commandRunIds = array();
        if(null !== $taskRunId)
        {
            $commandRuns = CommandRun::model()->findAllByAttributes(array('task_run_id' => $taskRunId));
            foreach($commandRuns as $commandRun)
            {
                $commandRunIds[] = $commandRun->id;
            }
        }
        return $commandRunIds;
    }

    /**
     * Get command run id condition.
     * @param integer $taskRunId Task run id.
     * @param integer $commandRunId Command run id.
     * @return CDbCriteria Command run id condition.
     */
    public static function getRunIdCondition($taskRunId, $commandRunId)
    {
        $commandRunIds = array();
        $condition = new CDbCriteria();
        if(null !== $commandRunId)
        {
            $commandRunIds[] = $commandRunId;
        }
        else
        {
            $commandRunIds = self::getCommandRunIds($taskRunId);
        }
        $condition->addInCondition('command_run_id', $commandRunIds);
        return $condition;
    }

    /**
     * Get case view.
     * @param integer $commandRunId Command run id.
     * @return string Case view.
     */
    public static function getCaseView($commandRunId)
    {
        $view = 'case';
        $commandRun = CommandRun::model()->findByPk($commandRunId);
        if(null !== $commandRun)
        {
            $view = $commandRun->getCaseView();
        }
        return $view;
    }

    /**
     * Get task run view by task run id or command run id.
     * @param integer $taskRunId Task run id.
     * @param integer $commandRunId Command run id.
     * @return VTaskRun Task run view.
     */
    public static function getVTaskRun($taskRunId, $commandRunId)
    {
        $vTaskRun = VTaskRun::model()->findByPk($taskRunId);
        if(null === $vTaskRun)
        {
            $commandRun = CommandRun::model()->findByPk($commandRunId);
            if(null !== $commandRun)
            {
                $vTaskRun = VTaskRun::model()->findByPk($commandRun->task_run_id);
            }
        }
        return $vTaskRun;
    }

    public static function getCommandRunOpts($vTaskRun)
    {
        $opts = array('-1' => Yii::t('Run', 'All Command Runs'));
        if(null !== $vTaskRun)
        {
            foreach($vTaskRun->vcommandruns as $vCommandrun)
            {
                $opts[$vCommandrun->id] = $vCommandrun->command_name;
            }
        }
        return $opts;
    }

    public static function getResultOpts($condition)
    {
        $amounts = CaseResult::model()->getResultAmounts($condition);
        $opts = array();
        foreach($amounts as $result => $amount)
        {
            $caseResult = new CaseResult();
            $caseResult->case_result = $result;
            $opts[$result] = $caseResult->getResultText() . "({$amount})";
        }
        return $opts;
    }

    public static function getFilterCondition($filter)
    {
        $condition = new CDbCriteria();
        if(null !== $filter)
        {
            $filter = preg_split('/\|/', $filter, -1, PREG_SPLIT_NO_EMPTY);
            $condition->addInCondition('case_result', $filter);
        }
        return $condition;
    }

    public static function getTabItems($vTaskRun, $commandRunId, $filter,
            $condition)
    {
        $label = '';
        if($vTaskRun)
        {
            $label = "$vTaskRun->task_name Run By $vTaskRun->created_by_realname @ $vTaskRun->create_time";
            $label .= CHtml::hiddenField('taskrun-id', $vTaskRun->id);
        }
        else if(isset($commandRunId))
        {
            $commandRun = VCommandRun::model()->findByPk($commandRunId);
            $label = "$commandRun->command_name Run By $commandRun->created_by_realname @ $commandRun->create_time";
            $label .= CHtml::hiddenField('commands', $commandRunId);
        }

        $selectedResult = preg_split('/\|/', $filter, -1, PREG_SPLIT_NO_EMPTY);
        if(null === $filter)
        {
            $selectedResult = array(
                CaseResult::RESULT_PASSED,
                CaseResult::RESULT_FAILED,
                CaseResult::RESULT_SKIPPED,
                CaseResult::RESULT_BLOCKED,
            );
        }
        $resultOpts = self::getResultOpts($condition);
        $items = array(array('label' => $label, 'itemOptions' => array('title' => $label)));
        if(isset($vTaskRun))
        {
            $items[] = array('label' => CHtml::dropDownList('commands', $commandRunId,
                        self::getCommandRunOpts($vTaskRun), array('style' => 'width: 230px; height: 25px; font-size: 15px')));
        }
        $items[] = array('label' => CHtml::checkBoxList('results', $selectedResult,
                $resultOpts, array('separator' => '&nbsp;&nbsp;')), 'itemOptions' => array('style' => 'max-width: 1000px;'));
        return $items;
    }

    public static function getHost($dataProvider)
    {
        $data = $dataProvider->getData();
        $host1 = '';
        $host2 = '';
        if(count($data) > 0)
        {
            $host1 = ComparisonTestParser::getHost1($data[0]->case_info);
            $host2 = ComparisonTestParser::getHost2($data[0]->case_info);
        }
        return array($host1, $host2);
    }

    public static function getComparisonDetail($data)
    {
        return CHtml::link('diff', $data->case_name, array('target' => '_blank'));
    }

    public static function getComparisonCaseOutput($resultId)
    {
        $output = Yii::t('Comparison', 'Not fount case output');
        $caseResult = CaseResult::model()->findByPk($resultId);
        if((null !== $caseResult) && !empty($caseResult->command_run_id))
        {
            $outputFile = Yii::app()->params['runOutputPath'] . $caseResult->command_run_id . '.log';
            if(file_exists($outputFile))
            {
                $output = @file_get_contents($outputFile);
                if(FALSE !== $output)
                {
                    $arr = explode($caseResult->case_name, $output);
                    if(isset($arr[1]))
                    {
                        $arr = explode('RES RESULT', $arr[1]);
                        $output = trim($arr[0]);
                        if(@mb_detect_encoding($output, "UTF-8, GBK") != "UTF-8")
                        {
                            $output = @iconv("GBK", "UTF-8", $output);
                        }
                    }
                }
            }
        }
        return $output;
    }
}
?>
