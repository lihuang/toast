<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class JUnitMvnParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $amountPattern = '#Tests run: (\d+), Failures: (\d+), Errors: (\d+), Skipped: (\d+)[\r\n]+#';
        preg_match_all($amountPattern, $this->output, $amountMatches);
        $this->parserInfo->case_total_amount = array_sum($amountMatches[1]);
        $this->parserInfo->case_failed_amount = array_sum($amountMatches[2]) + array_sum($amountMatches[3]);
        $this->parserInfo->case_skipped_amount = array_sum($amountMatches[4]);
        $this->parserInfo->case_passed_amount = $this->parserInfo->case_total_amount - $this->parserInfo->case_failed_amount - $this->parserInfo->case_skipped_amount;
    }
    
    protected function parseCases()
    {
        $partPattern = "#Results :\s*(.*?)Tests run:#is";
        $idPattern = "#\[INFO\][^\n]*\nCASE\sID:\s(\d*)#s";
        preg_match_all($partPattern, $this->output, $matches);
        preg_match_all($idPattern, $this->output, $idMatches);
        foreach ($matches[1] as $match)
        {
            $casePattern = "#(.*)\((.*)\)#";
            preg_match_all($casePattern, $match, $cases);
            foreach ($cases[0] as $key => $value)
            {
                $caseInfo = new CaseInfo();
                if(isset($idMatches[1][$key]))
                {
                    $caseInfo->id = trim($idMatches[1][$key]);
                }
                else if(isset($idMatches[1]))
                {
                    $idx = count($idMatches[1]);
                    if(isset($idMatches[1][$idx-1]))
                    {
                         $caseInfo->id = trim($idMatches[1][$idx-1]);
                    }
                }
                $caseInfo->name = trim($cases[1][$key]);
                $caseInfo->info = trim($cases[2][$key]);
                $caseInfo->result = CaseInfo::RESULT_FAILED;
                if(!empty($caseInfo->id))
                {
                    $testcase = TestCase::model()->findByPk($caseInfo->id);
                    if($testcase !== null)
                    {
                        $caseInfo->name = $testcase->name;
                    }
                }
                $this->parserInfo->cases[] = $caseInfo;
            }
        }
        
        $stepPatten = "#\[step case=(.*) number=\"(.*)\"](.*)\[/step\]#i";
        preg_match_all($stepPatten, $this->output, $matches);
        foreach($matches[1] as $idx => $match)
        {
            if(isset($this->parserInfo->cases[$match]))
            {
                $caseInfo = $this->parserInfo->cases[$match];
                $caseInfo->info .= "\n<b>" . $matches[2][$idx] . "</b>: (". $matches[3][$idx] . ')';
                $this->parserInfo->cases[$match] = $caseInfo;
            }
        }
        
        $picPatten = "#\[img case=(.*)\](.*)\[/img\]#i";
        preg_match_all($picPatten, $this->output, $matches);
        foreach($matches[1] as $idx => $match)
        {
            if(isset($this->parserInfo->cases[$match]))
            {
                $caseInfo = $this->parserInfo->cases[$match];
                $caseInfo->info .= "\n<a target=\"_blank\" href=\"" . $matches[2][$idx] . "\">" . Yii::t('CaseResult', 'Selectional Drawing') . "</a>";
                $this->parserInfo->cases[$match] = $caseInfo;
            }
        }
    }
}
?>