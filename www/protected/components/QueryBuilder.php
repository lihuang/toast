<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class QueryBuilder
{
    const SYNTAX_PATTERN = '#^@[a-zA-Z0-9_]* ([a-zA-Z0-9_]*:\([^:]*\)[ |]+)*[a-zA-Z0-9_]*:\([^:]*\)$#';
    const SPLIT_GROUP_PATTERN = '#([ |]+)([^ |]*:\([^:]*\))#';
    const TABLE_PATTERN = '#^@([a-zA-Z0-9_]*) .*#';
    const GROUP_PATTERN = '#([a-zA-Z0-9_]*):\((.*)\)#';
    
    const EQUAL_PATTERN = '#^=={(.*)}$#';
    const NEGATED_EQUAL_PATTERN = '#^-={(.*)}$#';
    const NEGATED_PATTERN = '#^-{(.*)}$#';
    const IN_PATTERN = '#^={(.*)}$#';
    const NOT_IN_PATTERN = '#^\!={(.*)}$#';
    const GT_PATTERN = '#^>{(.*)}$#';
    const GE_PATTERN = '#^>={(.*)}$#';
    const LT_PATTERN = '#^<{(.*)}$#';
    const LE_PATTERN = '#^<={(.*)}$#';
    const UNDER_PATTERN = '#^in{(.*)}#';
    const TL_PATTERN = '#^tl{(.*)}#';
    
    const ESCAPE = '\\';
    const DELIMITER = ',';
    const OR_LOGIC = '|';
    
    public function condition2Str()
    {
        
    }
    
    public function str2Condition($str)
    {
        $condition = new CDbCriteria();
        
        $table = '';
        if(preg_match(QueryBuilder::SYNTAX_PATTERN, $str))
        {
            preg_match(QueryBuilder::TABLE_PATTERN, $str, $table);
            $table = $table[1];
            preg_match_all(QueryBuilder::SPLIT_GROUP_PATTERN, $str, $matches);
            foreach($matches[2] as $key => $group)
            {
                preg_match(QueryBuilder::GROUP_PATTERN, $group, $groupMatches);
                $field = $groupMatches[1];
                $val = $groupMatches[2];
                $operator = $matches[1][$key];
                $condition = self::parseVal($field, $val, $condition, $operator);
            }
        }
        else
        {
            // name as default search key
            $condition = $str;
        }
        return $condition;
    }
    
    private function parseVal($field, $val, $condition, $operator)
    {
        $operator = self::parseOperator($operator);
        if('' == $val)
        {
            $condition->addCondition("(" . $field . " = '' OR " . $field . ' IS NULL)', $operator);
        }
        else if(0 === strpos($val, self::ESCAPE))
        {
            $val = substr($val, 1);
            $condition->compare($field, $val, true, $operator);
        }
        else if(preg_match(self::EQUAL_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $condition->compare($field, $val, false, $operator);
        }
        else if(preg_match(self::NEGATED_EQUAL_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            if('' == $val)
            {
                $condition->addCondition($field . " != ''", $operator);
            }
            else
            {
                $condition->compare($field, '<>' . $val, false, $operator);
            }
        }
        else if(preg_match(self::NEGATED_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            if('' == $val)
            {
                $condition->addCondition($field . " != ''", $operator);
            }
            else
            {
                $condition->compare($field, '<>' . $val, true, $operator);
            }
        } 
        else if(preg_match(self::IN_PATTERN, $val, $matches))
        {
            $val = explode(self::DELIMITER, $matches[1]);
            $condition->addInCondition($field, $val, $operator);
        }
        else if(preg_match(self::NOT_IN_PATTERN, $val, $matches))
        {
            $val = explode(self::DELIMITER, $matches[1]);
            $condition->addNotInCondition($field, $val, $operator);
        }
        else if(preg_match(self::GT_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $condition->compare($field, '>' . $val, false, $operator);
        } 
        else if(preg_match(self::GE_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $condition->compare($field, '>=' . $val, false, $operator);
        } 
        else if(preg_match(self::LT_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $condition->compare($field, '<' . $val, false, $operator);
        }
        else if(preg_match(self::LE_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $condition->compare($field, '<=' . $val, false, $operator);
        }
        else if(preg_match(self::UNDER_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $condition->compare($field, '/' . $val, true, $operator);
        }
        else if(preg_match(self::TL_PATTERN, $val, $matches))
        {
            $val = $matches[1];
            $user = User::model()->findByAttributes(array('username' => $val));
            $reports = array();
            if($user !== null)
            {
                $ldap = new LDAP(Option::model()->getLDAPOpt());
                $reports = $ldap->getReporters($user->domain, $user->username);
            }
            $condition->addInCondition($field, $reports, $operator);
        }
        else
        {
            $condition->compare($field, $val, true, $operator);
        }
        
        return $condition;
    }
    
    private function parseOperator($str)
    {
        $operator = 'AND';
        if(self::OR_LOGIC == $str)
        {
            $operator = 'OR';
        }
        return $operator;
    }
}
?>