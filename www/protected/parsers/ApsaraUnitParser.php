<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class ApsaraUnitParser extends BaseParser
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
        $result = array('PASS' => CaseInfo::RESULT_PASSED, 
            'FAIL' => CaseInfo::RESULT_FAILED);
        $testClasses = preg_split('/\x1b\[0;35m#########  Unit test for class: /', $this->output);
        foreach($testClasses as $testClass)
        {
            preg_match('/(.*)  ########\x1b\[0m.*/', $testClass, $matchClassName);
            if(!isset($matchClassName[1]))
                continue;
            $className = $matchClassName[1];
            preg_match_all('/CaseId: \d+\t\x1b\[0;32mPASS\t\x1b\[0m(.*), (.*)/', $testClass, $matchPassedCase);
            foreach ($matchPassedCase[0] as $key => $value)
            {
                $caseInfo = new CaseInfo();
                $caseInfo->name = $className . '::' . trim($matchPassedCase[1][$key]);
                $caseInfo->info = trim($matchPassedCase[2][$key]);
                $caseInfo->result = CaseInfo::RESULT_PASSED;
                $this->parserInfo->cases[] = $caseInfo;
            }
            preg_match_all('/CaseId: \d+\t\x1b\[0;31mFAIL\t\x1b\[0m(.*)((?:[\r\n]+\x1b\[0;36m  \+ \t(.*) from.*)+)/', $testClass, $matchFailedCase);
            foreach ($matchFailedCase[0] as $key => $value)
            {
                $caseInfo = new CaseInfo();
                $caseInfo->name = $className . '::' . trim($matchFailedCase[3][$key]);
                $caseInfo->info = trim($matchFailedCase[1][$key] . $matchFailedCase[2][$key]);
                $caseInfo->info = preg_replace(array('/\x1b\[0;36m/', '/\x1b\[0m/'), '', $caseInfo->info);
                $caseInfo->result = CaseInfo::RESULT_FAILED;
                $this->parserInfo->cases[] = $caseInfo;
            }
        }
    }
}
?>