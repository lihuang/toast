<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class PyUnitParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $totalPattern = '#Ran (\d+) tests in (?:\d*).?(?:\d+)s[\r\n]+(OK|FAILED)#';
        preg_match_all($totalPattern, $this->output, $totalMatches);
        $this->parserInfo->case_total_amount = array_sum($totalMatches[1]);
        
        $this->parserInfo->case_passed_amount = $this->parserInfo->case_total_amount - $this->parserInfo->case_failed_amount;
    }
    
    protected function parseCases()
    {
        $casePattern = "#={70}[\r\n]+(?:FAIL|ERROR): ([^\r\n]+)[\r\n]+-{70}[\r\n](.*?)[\r\n]{4}#s";
        $this->parserInfo->case_failed_amount  = 0;
        preg_match_all($casePattern, $this->output, $matches);
        foreach ($matches[0] as $key => $value)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->name = trim($matches[1][$key]);
            $caseInfo->info = trim($matches[2][$key]);
            $caseInfo->result = CaseInfo::RESULT_FAILED;
            $this->parserInfo->cases[] = $caseInfo;
            $this->parserInfo->case_failed_amount += 1;
        }
        
        $passedPattern = "#(test_[^ ]* \([^\)]*\)).*?\b(ok|FAIL|ERROR)\b#s";
        preg_match_all($passedPattern, $this->output, $matches);
        foreach ($matches[0] as $key => $value)
        {
            if('ok' == $matches[2][$key])
            {
                $caseInfo = new CaseInfo();
                $caseInfo->name = trim($matches[1][$key]);
                $caseInfo->result = CaseInfo::RESULT_PASSED;
                $this->parserInfo->cases[] = $caseInfo;  
            }
        }
    }
}
?>