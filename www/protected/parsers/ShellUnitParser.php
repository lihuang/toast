<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class ShellUnitParser extends BaseParser
{
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
        $testClasses = preg_split('#--- Executing the #', $this->output);
        foreach($testClasses as $testClass)
        {
            preg_match('#\'(.*)\' test suite ---#', $testClass, $matchClassName);
            if(!isset($matchClassName[1]))
                continue;
            $className = $matchClassName[1];
            preg_match_all('#[\r\n]+(test.*)((?:[\r\n]+ASSERT:.*)+)#', $testClass, $matchFailedCase);
            $failedCase = array();
            foreach ($matchFailedCase[0] as $key => $value)
            {
                $failedCase[] = trim($matchFailedCase[1][$key]);
                
                $caseInfo = new CaseInfo();
                $caseInfo->name = $className . '::' . trim($matchFailedCase[1][$key]);
                $caseInfo->info = trim($matchFailedCase[2][$key]);
                $caseInfo->result = CaseInfo::RESULT_FAILED;
                $this->parserInfo->cases[] = $caseInfo;
            }
            preg_match_all('#[\r\n]+(test.*)#', $testClass, $matchPassedCase);
            foreach ($matchPassedCase[0] as $key => $value)
            {
                if(in_array(trim($matchPassedCase[1][$key]), $failedCase))
                    continue;
                $caseInfo = new CaseInfo();
                $caseInfo->name = $className . '::' . trim($matchPassedCase[1][$key]);
                $caseInfo->result = CaseInfo::RESULT_PASSED;
                $this->parserInfo->cases[] = $caseInfo;
            }
        }
    }
}
?>