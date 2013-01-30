<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class JUnitAntParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $amountPattern = '#Tests run: (\d+), Failures: (\d+), Errors: (\d+), Time elapsed: .*#';
        preg_match_all($amountPattern, $this->output, $amountMatches);
        $this->parserInfo->case_total_amount = array_sum($amountMatches[1]);
        $this->parserInfo->case_failed_amount = array_sum($amountMatches[2]) + array_sum($amountMatches[3]);
        $this->parserInfo->case_passed_amount = $this->parserInfo->case_total_amount - $this->parserInfo->case_failed_amount - $this->parserInfo->case_skipped_amount;
    }
    
    protected function parseCases()
    {
        $casePattern = "#\[junit\] Test (.*)FAILED#";
//        $result = array('ERROR' => CaseInfo::RESULT_FAILED);
        
        preg_match_all($casePattern, $this->output, $matches);
        foreach ($matches[0] as $key => $value)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->name = trim($matches[1][$key]);
            $caseInfo->result = CaseInfo::RESULT_FAILED;
            $this->parserInfo->cases[] = $caseInfo;
        }
    }
}
?>