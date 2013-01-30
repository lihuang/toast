<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class CPPUnitParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $totalPattern = '#Failures !!![\r\n]+Run: (\d+)\s+Failure total: \d+\s+Failures: \d+\s+Errors:\s+\d+#';
        preg_match_all($totalPattern, $this->output, $totalMatches);
        $this->parserInfo->case_total_amount = array_sum($totalMatches[1]);
        
        $passPattern = '#\s*OK\s+\((\d+)\)#';
        preg_match_all($passPattern, $this->output, $passMatches);
        $this->parserInfo->case_passed_amount = array_sum($passMatches[1]);
        
        $failPattern = '#Failures !!![\r\n]+Run: \d+\s+Failure total: (\d+)\s+Failures: \d+\s+Errors:\s+\d+#';
        preg_match_all($failPattern, $this->output, $failMatches);
        $this->parserInfo->case_failed_amount = array_sum($failMatches[1]);
        
        if ($this->parserInfo->case_total_amount<$this->parserInfo->case_passed_amount)
            $this->parserInfo->case_total_amount = $this->parserInfo->case_passed_amount;
    }
    
    protected function parseCases()
    {
        $casePattern = "#Test name: ([^\n]*)\r?\n(.*)\r?\n\r?\n#sU";
        
        preg_match_all($casePattern, $this->output, $matches);
        foreach ($matches[0] as $key => $value)
        {
            $caseInfo = new CaseInfo();
            $caseInfo->name = trim($matches[1][$key]);
            $caseInfo->info = trim($matches[2][$key]);
            $caseInfo->result = CaseInfo::RESULT_FAILED;
            $this->parserInfo->cases[] = $caseInfo;
        }
    }
}
?>