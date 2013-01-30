<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class ToastParser extends BaseParser
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
        $blocks = preg_split('/##TESTCASE (START|END)##+/', $this->output);
        foreach($blocks as $key => $block)
        {
            if($key % 2 == 0)
                continue;
            if(mb_detect_encoding($block, "UTF-8, GBK") != "UTF-8")
                $block = @iconv("GBK", "UTF-8//IGNORE", $block); 
            $suite = CJSON::decode($block);
            if($suite)
                $this->getSuiteCases($suite);
        }
    }
    
    private function getSuiteCases($suite, $parentName = '')
    {
        $result = array(
            'PASS' => CaseInfo::RESULT_PASSED, 
            'FAIL' => CaseInfo::RESULT_FAILED,
            'SKIP' => CaseInfo::RESULT_SKIPPED,
            'BLOCK' => CaseInfo::RESULT_BLOCKED);

        $suiteName = isset($suite['name'])?$suite['name']:'';
        if($parentName)
            $suiteName = $parentName . '::' . $suiteName;
        if(isset($suite['testcase']))
        {
            foreach($suite['testcase'] as $testCase)
            {
                $caseInfo = new CaseInfo();
                $caseInfo->id = isset($testCase['id'])?$testCase['id']:NULL;
                $caseInfo->name = isset($testCase['name'])?$testCase['name']:NULL;
                if($caseInfo->name && $suiteName)
                    $caseInfo->name = $suiteName . '::' . $caseInfo->name;
                $caseInfo->result = (isset($testCase['result']) && isset($result[$testCase['result']]))?$result[$testCase['result']]:NULL;
                $caseInfo->info = isset($testCase['info'])?trim($testCase['info']):NULL;
                
                if($caseInfo->name !== NULL && $caseInfo->result !== NULL)
                    $this->parserInfo->cases[] = $caseInfo;
            }
        }
        if(isset($suite['testsuite']))
        {
            foreach($suite['testsuite'] as $testSuite)
            {
                $this->getSuiteCases($testSuite, $suiteName);
            }
        }
    }
}
?>