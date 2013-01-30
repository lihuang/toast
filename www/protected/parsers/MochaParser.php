<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class MochaParser extends BaseParser
{
    //put your code here
    protected function parseCaseAmount()
    {
        foreach($this->parserInfo->cases as $case)
        {
            if($case->result == CaseInfo::RESULT_PASSED)
                $this->parserInfo->case_passed_amount++;
            else if($case->result == CaseInfo::RESULT_FAILED)
                $this->parserInfo->case_failed_amount++;
            else if($case->result == CaseInfo::RESULT_SKIPPED)
                $this->parserInfo->case_skipped_amount++;
            else if($case->result == CaseInfo::RESULT_BLOCKED)
                $this->parserInfo->case_blocked_amount++;
        }
        $this->parserInfo->case_total_amount = $this->parserInfo->case_passed_amount +
                $this->parserInfo->case_failed_amount + $this->parserInfo->case_skipped_amount +
                $this->parserInfo->case_blocked_amount;
    }
    
    protected function parseCases()
    {
        $matches[1] = array();
        $casePattern = "#(\bok\b|\bnot\b\s\bok\b)\s(\d+)\s(.*)#";
        $result = array('ok' => CaseInfo::RESULT_PASSED, 
            'not ok' => CaseInfo::RESULT_FAILED);
        
        preg_match_all($casePattern, $this->output, $matches);
        foreach($matches[1] as $key => $val)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->id = trim($matches[2][$key]);
            $caseInfo->name = trim($matches[3][$key]);
            $caseInfo->result = $result[trim($matches[1][$key])];
            
            if(CaseInfo::RESULT_FAILED == $caseInfo->result)
            {
                $infoArr = explode($matches[0][$key], '#' . $this->output);
                $info = explode($matches[0][$key+1], $infoArr[1]);
                $caseInfo->info = trim($info[0]);
            }
            
            if(mb_detect_encoding($caseInfo->name,"UTF-8, GBK") != "UTF-8")
                    $caseInfo->name = iconv("GBK", "UTF-8", $caseInfo->name); 
            if(mb_detect_encoding($caseInfo->info,"UTF-8, GBK") != "UTF-8")
                    $caseInfo->info = iconv("GBK", "UTF-8", $caseInfo->info);
            
            $this->parserInfo->cases[] = $caseInfo;
        }
        
        $errorPattern = "#\n(\bAssertionError\b):.*#";
        preg_match_all($errorPattern, $this->output, $matches);
        foreach($matches[1] as $key => $val)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->name = trim($matches[1][$key]);
            $caseInfo->result = CaseInfo::RESULT_FAILED;
            $caseInfo->info = trim($matches[0][$key]);
            
            if(mb_detect_encoding($caseInfo->name,"UTF-8, GBK") != "UTF-8")
                    $caseInfo->name = iconv("GBK", "UTF-8", $caseInfo->name); 
            if(mb_detect_encoding($caseInfo->info,"UTF-8, GBK") != "UTF-8")
                    $caseInfo->info = iconv("GBK", "UTF-8", $caseInfo->info);
            
            $this->parserInfo->cases[] = $caseInfo;
        }
    }
}
?>