<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class LightFaceParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $amountPattern = '#Files=(.*), Tests=(.*).*#s';
        preg_match($amountPattern, $this->output, $amountMatches);
        if(isset($amountMatches[1]))
        {
            $this->parserInfo->case_total_amount = $amountMatches[1];
        }
        $output = explode('Test Summary Report', $this->output);
        $failedPattern = '#.31m([^\n\.]*\.t)\s*[^\.]*: [0-9]*#s';
        if(isset($output[1]))
        {
            preg_match_all($failedPattern, $output[1], $failedMatches);
            $this->parserInfo->case_failed_amount = count($failedMatches[1]);
            $this->parserInfo->case_passed_amount = $amountMatches[1] - $this->parserInfo->case_failed_amount;
        } 
        else
        {
            $this->parserInfo->case_passed_amount = $this->parserInfo->case_total_amount;
        }
    }

    protected function parseCases()
    {
        $output = explode('Test Summary Report', $this->output);
        $failedPattern = '#.31m([^\n\.]*\.t)\s*[^\.]*: [0-9]*#s';
        if(isset($output[1]))
        {
            preg_match_all($failedPattern, $output[1], $failedMatches);
            foreach($failedMatches[1] as $key => $val)
            {
                $caseInfo = new CaseInfo();
                $caseInfo->name = $val;
                $info = str_replace('[31m', '', trim($failedMatches[0][$key]));
                $info = str_replace('[0m', '', $info);
                $caseInfo->info = $info;
                $caseInfo->result = CaseInfo::RESULT_FAILED;
                $this->parserInfo->cases[] = $caseInfo;
            }   
        }
    }
}
?>
