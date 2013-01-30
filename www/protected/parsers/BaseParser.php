<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class CaseInfo
{
    public $id;
    public $name;
    public $result;
    public $actual;
    public $expected;
    public $info;
    
    const RESULT_PASSED = CaseResult::RESULT_PASSED;
    const RESULT_FAILED = CaseResult::RESULT_FAILED;
    const RESULT_SKIPPED = CaseResult::RESULT_SKIPPED;
    const RESULT_BLOCKED = CaseResult::RESULT_BLOCKED;
}

class ParserInfo
{
    public $cases = array();
    
    public $case_total_amount = 0;
    public $case_passed_amount = 0;
    public $case_failed_amount = 0;
    public $case_skipped_amount = 0;
    public $case_blocked_amount = 0;
    
    public $build;
    public $revision;
    public $runningData;
    public $desc_info;
    public $custom_info;
    public $mail_to;
    
    public $cc_result;
    public $cc_line_hit = 0;
    public $cc_line_total = 0;
    public $cc_branch_hit = 0;
    public $cc_branch_total = 0;
}

abstract class BaseParser
{
    public $parserInfo; 
    protected $output;
    
    function __construct($output) {
       $this->parserInfo = new ParserInfo;
       $this->output = $output;
   }

   function __destruct() {
   }
   
    protected function parseBuildInfo()
    {
        $this->parserInfo->build = '';
        $buildPattern = "/^BUILD INFORMATION:\s*(.*)/m";
        preg_match_all($buildPattern, $this->output, $matchBuild);
        foreach ($matchBuild[0] as $key => $val)
        {
            $this->parserInfo->build .= CHtml::encode($matchBuild[1][$key]) . ' <br/>';
        }
        
        $yumBuild = "/^ (\S+)\s+(noarch|x86_64|i\d86)\s+(\S+)\s+\S+\s+\S+ \S/m";
        preg_match_all($yumBuild, $this->output, $matchYum);
        foreach($matchYum[0] as $key => $val)
        {
            $this->parserInfo->build .= ' ' . $matchYum[1][$key] . '-' . $matchYum[3][$key] . '.' . $matchYum[2][$key];
        }
        
        $linkPattern = "/https?:\/\/[^,;<>\[\]\t\r\n ]*/";
        $this->parserInfo->build = preg_replace($linkPattern, '<a href="$0">$0</a>', $this->parserInfo->build);
    }

    protected function parseCoverageInfo()
    {
        $ccResultPattern = "/^CODE COVERAGE RESULT WAS SAVED TO:\s*(http:\/\/.*)/m";
        $ccLinePattern = "/^CODE COVERAGE RESULT OF LINES IS:\s*(\d+)\/(\d+)/m";
        $ccBranchPattern = "/^CODE COVERAGE RESULT OF BRANCHES IS:\s*(\d+)\/(\d+)/m";
        preg_match_all($ccResultPattern, $this->output, $matchResult);
        if (count($matchResult[0]) > 0)
            $this->parserInfo->cc_result = $matchResult[1][0];
        preg_match_all($ccLinePattern, $this->output, $matchLine);
        if (count($matchLine[0]) > 0)
        {
            $this->parserInfo->cc_line_hit = (int)$matchLine[1][0];
            $this->parserInfo->cc_line_total = (int)$matchLine[2][0];
        }
        preg_match_all($ccBranchPattern, $this->output, $matchBranch);
        if (count($matchBranch[0]) > 0)
        {
            $this->parserInfo->cc_branch_hit = (int)$matchBranch[1][0];
            $this->parserInfo->cc_branch_total = (int)$matchBranch[2][0];
        }
    }
    
    protected function parseRevision()
    {
        $revisionPattern = "/^REVISION IS:\s*(.*)/m";
        preg_match_all($revisionPattern, $this->output, $matchResult);
        if (count($matchResult[0]) > 0)
            $this->parserInfo->revision = $matchResult[1][0];
    }
    
    protected function parseRunningData()
    {
        $revisionPattern = "/^RUNNING DATA WAS SAVED TO:\s*(.*)/m";
        preg_match_all($revisionPattern, $this->output, $matchResult);
        if (count($matchResult[0]) > 0)
            $this->parserInfo->runningData = $matchResult[1][0];
    }

    protected function parseDescInfo()
    {
        $this->parserInfo->desc_info  = '';
        $picPattern = '#\[img case=(.*)\](.*)\[/img\]#i';
        preg_match_all($picPattern, $this->output, $matches);
        foreach($matches[1] as $idx => $match)
        {
            if(isset($this->parserInfo->cases[$match]))
            {
                $this->parserInfo->desc_info .= '[img case=' . trim($matches[1][$idx]) . ']' . trim($matches[2][$idx]) . "[/img]\n";
             }
        }
    }
    
    protected function parseCustomInfo()
    {
        
        $customPattern = '#[\r\n]*CUSTOM INFO START[\r\n]+(.*?)[\r\n]+CUSTOM INFO END[\r\n]*#s';
        preg_match($customPattern, $this->output, $customMatches);
        if(isset($customMatches[1]))
        {
            $this->parserInfo->custom_info = trim(str_replace("\r\n", "\n", $customMatches[1]));
        }
    }
    
    protected function parseMailTo()
    {
        $mailToPattern = "#MAIL TO:\s*(.*)#";
        if(preg_match($mailToPattern, $this->output, $matchResult))
        {
           $this->parserInfo->mail_to = $matchResult[1];
        }
    }
    
    abstract protected function parseCaseAmount();
    
    abstract protected function parseCases();
    
    public function run()
    {
        $this->parseCases();
        $this->parseCaseAmount();
        $this->parseBuildInfo();
        $this->parseCoverageInfo();
        $this->parseRevision();
        $this->parseRunningData();
        $this->parseDescInfo();
        $this->parseCustomInfo();
        $this->parseMailTo();
    }
}

?>