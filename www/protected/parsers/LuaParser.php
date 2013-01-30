<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
include_once('BaseParser.php');
class LuaParser extends BaseParser
{
    protected function parseCaseAmount()
    {
        $amountPattern = '#Testsuite finished \((\d+) passed, (\d+) failed, (\d+) errors\)#s';
        preg_match_all($amountPattern, $this->output, $amountMatches);
        $this->parserInfo->case_passed_amount = array_sum($amountMatches[1]);
        $this->parserInfo->case_failed_amount = array_sum($amountMatches[2]) + array_sum($amountMatches[3]);
        $this->parserInfo->case_total_amount = $this->parserInfo->case_passed_amount + $this->parserInfo->case_failed_amount;
    }
    
    protected function parseCases()
    {
    }
}
?>