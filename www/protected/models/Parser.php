<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Parser model
 * 
 * @package application.models
 */
class Parser extends Model
{

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'parser';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('name, parser_class', 'required'),
            array('name, parser_class, desc_info', 'safe'),
            array('name, parser_class', 'unique'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
        );
    }
    public function getParserOptions()
    {
        $opts = array();
        $parsers = Parser::model()->findAll();
        foreach ($parsers as $parser)
        {
            $opts[$parser->id] = $parser->name;
        }
        return $opts;
    }

    public function parse($commandRun)
    {
        $outputFile = Yii::app()->params['runOutputPath'] . $commandRun->id . '.log';
        $size = @filesize($outputFile);
        if($size > 100*1024*1024)
        {
            $msg = 'CommandRun #' . $commandRun->id  . ' Content is too big, can not parse.';
            Yii::log($msg, 'trace', 'toast.Parser.parse');
            return;
        }
        $output = @file_get_contents($outputFile);

        if (!@file_exists(Yii::app()->params['parserPath'] . $this->parser_class . '.php'))
        {
            $msg = 'Can not find the parser file ' . $this->parser_class;
            Yii::log($msg, 'trace', 'toast.Parser.parse');
            return false;
        }
        @include_once(Yii::app()->params['parserPath'] . $this->parser_class . '.php');
        if(@class_exists($this->parser_class))
        {        
            $parser = new $this->parser_class($output);
            if (@method_exists($this->parser_class, 'run'))
            {
                $res = @call_user_func(array($parser, 'run'));
                $commandRun->case_total_amount += $parser->parserInfo->case_total_amount;
                $commandRun->case_pass_amount += $parser->parserInfo->case_passed_amount;
                $commandRun->case_fail_amount += $parser->parserInfo->case_failed_amount;
                $commandRun->case_skip_amount += $parser->parserInfo->case_skipped_amount;
                $commandRun->case_block_amount += $parser->parserInfo->case_blocked_amount;

                $commandRun->build = $parser->parserInfo->build;
                $commandRun->cc_result = $parser->parserInfo->cc_result;
                $commandRun->cc_line_hit = $parser->parserInfo->cc_line_hit;
                $commandRun->cc_line_total = $parser->parserInfo->cc_line_total;
                $commandRun->cc_branch_hit = $parser->parserInfo->cc_branch_hit;
                $commandRun->cc_branch_total = $parser->parserInfo->cc_branch_total;

                $commandRun->desc_info = '';
                if(!empty($parser->parserInfo->revision))
                    $commandRun->desc_info .= 'SVN REVISION INFORMATION: ' . $parser->parserInfo->revision . "\n";
                
                if(!empty($parser->parserInfo->runningData))
                    $commandRun->desc_info .= 'RUNNING DATA WAS SAVED TO: ' . $parser->parserInfo->runningData . "\n";
                
                if(!empty($parser->parserInfo->custom_info))
                    $commandRun->desc_info .= "CUSTOM INFO:\n" . $parser->parserInfo->custom_info . "\n";
                
               if('ComparisonTestParser' == $this->parser_class)
               {
                   $commandRun->desc_info .= $hostInfo = 'HOST1: ' . $parser->host1 . "\nHOST2: " . $parser->host2 . "\n";
               }
                
               if(isset($parser->parserInfo->mail_to) && !empty($parser->parserInfo->mail_to) && isset($commandRun->task_run_id))
               {
                   $taskRun = TaskRun::model()->findByPk($commandRun->task_run_id);
                   $taskRun->report_to .= ',' . $parser->parserInfo->mail_to;
                   $taskRun->save();
               }
               
               $commandRun->desc_info .= $parser->parserInfo->desc_info;
               
                foreach ($parser->parserInfo->cases as $caseInfo)
                {
                    $this->createCaseResult($caseInfo, $commandRun->id);
                }
            }
        }
    }

    public function createCaseResult($caseInfo, $command_run_id)
    {
        $caseResult = new CaseResult();
        $caseResult->test_case_id = $caseInfo->id;
        $caseResult->case_name = $caseInfo->name;
        $caseResult->case_result = $caseInfo->result;
        $caseResult->case_info = $caseInfo->info;
        $caseResult->command_run_id = $command_run_id;
            
        if(mb_detect_encoding($caseResult->case_name, "UTF-8, GBK") != "UTF-8")
                $caseResult->case_name = @iconv("GBK", "UTF-8//IGNORE", $caseResult->case_name); 
        if(mb_detect_encoding($caseResult->case_info, "UTF-8, GBK") != "UTF-8")
                $caseResult->case_info = @iconv("GBK", "UTF-8//IGNORE", $caseResult->case_info);

        if ($caseResult->save())
        {
            $msg = 'Receive create result command from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'trace', 'toast.Parser.parse');
        }
        else
        {
            $errorMsg = '';
            foreach ($caseResult->attributes as $attr => $val)
            {
                $errorMsg .= $caseResult->getError($attr);
            }
            $msg = 'Receive failed create result command because of ' . $errorMsg
                    . ' from ' . Yii::app()->request->userHost
                    . '(' . Yii::app()->request->userHostAddress . ')';
            Yii::log($msg, 'trace', 'toast.Parser.parse');
        }
    }
}