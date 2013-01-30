<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class GrailsParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $passedAmountPattern = '#Tests passed: (\d+)#';
        $failedAmountPattern = '#Tests failed: (\d+)#';
        preg_match_all($passedAmountPattern, $this->output, $passedAmountMatches);
        preg_match_all($failedAmountPattern, $this->output, $failedAmountMatches);
        $this->parserInfo->case_passed_amount = array_sum($passedAmountMatches[1]);
        $this->parserInfo->case_failed_amount = array_sum($failedAmountMatches[1]);
        $this->parserInfo->case_total_amount = $this->parserInfo->case_passed_amount + $this->parserInfo->case_failed_amount;
    }
    
    protected function parseCases()
    {
        return; // user required
        $casePattern = "#Running test (.*?)\.\.\.(PASSED)?#";
        $result = array('PASSED' => CaseInfo::RESULT_PASSED,
            'FAILED' => CaseInfo::RESULT_FAILED);
        
        preg_match_all($casePattern, $this->output, $matches);
        foreach ($matches[0] as $key => $value)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->name = trim($matches[1][$key]);
            $caseInfo->result = $result[trim($matches[2][$key])];
            
            if(mb_detect_encoding($caseInfo->name,"UTF-8, GBK") != "UTF-8")
                    $caseInfo->name = iconv("GBK", "UTF-8", $caseInfo->name); 
            if(mb_detect_encoding($caseInfo->info,"UTF-8, GBK") != "UTF-8")
                    $caseInfo->info = iconv("GBK", "UTF-8", $caseInfo->info);
            
            $this->parserInfo->cases[] = $caseInfo;
        }
    }
}
?>