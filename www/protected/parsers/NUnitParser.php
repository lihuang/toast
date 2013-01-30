<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class NUnitParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $amountPattern = '#Tests run: (\d+), Errors: (\d+), Failures: (\d+), Inconclusive: \d+, Time: \d+\.?\d* seconds[\r\n]+  Not run: (\d+), Invalid: \d+, Ignored: \d+, Skipped: \d+#';
        preg_match_all($amountPattern, $this->output, $amountMatches);
        $this->parserInfo->case_total_amount = array_sum($amountMatches[1]) + array_sum($amountMatches[4]);
        $this->parserInfo->case_failed_amount = array_sum($amountMatches[2]) + array_sum($amountMatches[3]);
        $this->parserInfo->case_skipped_amount = array_sum($amountMatches[4]);
        $this->parserInfo->case_passed_amount = $this->parserInfo->case_total_amount - $this->parserInfo->case_failed_amount - $this->parserInfo->case_skipped_amount;
    }
    
    protected function parseCases()
    {
        $casePattern = "#\d+\) (.*)[\r\n]#";
        
        preg_match("#Errors and Failures:.*#s", $this->output, $casePart);
        
        preg_match_all($casePattern, $casePart[0], $cases);
        foreach ($cases[0] as $key => $value)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->name = trim($cases[1][$key]);
//            $caseInfo->info = trim($cases[2][$key]);
            $caseInfo->result = CaseInfo::RESULT_FAILED;
            $this->parserInfo->cases[] = $caseInfo;
        }
    }
}
?>