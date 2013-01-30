<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class TString
{

    /**
     * Arrange splited string.
     * 
     * @param string $str to be arranged string
     * @param mixed $split the split
     * @return string the arranged string
     */
    public static function arrangeSplit($str = '', $split = ',')
    {
        if(!empty($split))
        {
            if(is_array($split))
            {
                $str = str_replace($split, $split[0], $str);
                $split = $split[0];
            }
            $arr = explode($split, $str);
            $arr = array_unique($arr);
            foreach($arr as $key => $val)
            {
                if(empty($val))
                {
                    unset($arr[$key]);
                }
                else
                {
                    $arr[$key] = trim($val);
                }
            }
            $str = join($split, $arr);
        }
        return $str;
    }

    /**
     * Covert time to date
     * 
     * @param string $time
     * @return string
     */
    public static function covertTime2Date($time)
    {
        if(isset($time))
        {
            $time = explode(' ', $time);
            $time = $time[0];
        }
        return $time;
    }

    /**
     * Return a random string.
     * 
     * @param integer $length the random string length, 1 as minimum
     * @return string a random string
     */
    public static function getRandomString($length = 1)
    {
        if($length < 1)
        {
            $length = 1;
        }
        $str = '';
        while($length-- > 0)
        {
            $str .= sprintf('%x', rand(0, 15));
        }
        return $str;
    }

    /**
     * is utf8
     * 判断字符串是不是UTF8格式
     */
    public static function isUtf8($str)
    {
        // TODO: implement this function
        return true;
        //return preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*$%xs', $str);
    }

    public static function subStr($str, $len)
    {
        if(mb_strlen($str) >= $len)
        {
            $str = mb_substr($str, 0, ($len - 3), 'utf-8') . '...';
        }
        return $str;
    }

    public static function highLight($content, $language = 'sh',
            $isShowLineNumbers = true)
    {
        $hter = new CTextHighlighter();
        $hter->language = $language;
        $hter->showLineNumbers = $isShowLineNumbers;
        $hter->cssFile = Yii::app()->theme->baseUrl . '/assets/css/hightlight.css';
        return $hter->highlight($content);
    }

    public static function diffString($expected, $actual,
            $diffStyle = 'background: #FDFEBC; padding: 2px 0px',
            $missStyle = 'background: #F26E6E; padding: 2px 0px;',
            $restStyle = 'background: #C5FFC4; padding: 2px 0px')
    {
        $diffArr = array();
        $expectedArr = preg_split('//', $expected, -1, PREG_SPLIT_NO_EMPTY);
        $actualArr = preg_split('//', $actual, -1, PREG_SPLIT_NO_EMPTY);
        $diffCode = 0;
        $expectedIdx = 0;
        foreach($actualArr as $val)
        {
            $nowCode = -1;
            $nowStyle = null;
            if(isset($expectedArr[$expectedIdx]))
            {
                if($expectedArr[$expectedIdx] != $val)
                {
                    $nowCode = 1;
                    $nowStyle = $diffStyle;
                }
                else
                {
                    $nowCode = 0;
                    $nowStyle = null;
                    $expectedIdx++;
                }
            }
            else
            {
                $nowCode = 3;
                $nowStyle = $restStyle;
            }

            if($diffCode != $nowCode)
            {
                $diffArr[] = '</span>';
                $diffCode = $nowCode;
                if(null !== $nowStyle)
                {
                    $diffArr[] = '<span style="' . $nowStyle . '">';
                }
                else
                {
                    $diffArr[] = '<span>';
                }
            }
            $diffArr[] = $val;
        }
        $missLen = count($expectedArr) - $expectedIdx;
        if($missLen > 0)
        {
            $diffArr[] = '<span style="' . $missStyle . '">';
            $diffArr[] = str_repeat('&nbsp;', $missLen);
            $diffArr[] = '</span>';
        }
        return '<span>' . join('', $diffArr) . '</span>';
    }

    public static function pinyin($str, $code = 'utf-8')
    {
        $dataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha" .
                "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|" .
                "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er" .
                "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui" .
                "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang" .
                "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang" .
                "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue" .
                "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne" .
                "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen" .
                "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang" .
                "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|" .
                "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|" .
                "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu" .
                "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you" .
                "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|" .
                "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";

        $dataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990" .
                "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725" .
                "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263" .
                "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003" .
                "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697" .
                "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211" .
                "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922" .
                "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468" .
                "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664" .
                "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407" .
                "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959" .
                "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652" .
                "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369" .
                "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128" .
                "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914" .
                "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645" .
                "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149" .
                "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087" .
                "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658" .
                "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340" .
                "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888" .
                "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585" .
                "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847" .
                "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055" .
                "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780" .
                "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274" .
                "|-10270|-10262|-10260|-10256|-10254";
        $tDataKey = explode('|', $dataKey);
        $tDataValue = explode('|', $dataValue);

        //array_combine:创建一个数组，用一个数组的值作为其键名，另一个数组的值作为其值
        $data = array_combine($tDataKey, $tDataValue);
        //arsort:对数组进行逆向排序并保持索引关系
        //reset:将数组的内部指针指向第一个单元
        arsort($data);
        reset($data);

        /**
         *    假如编码不是gb2312,则启用utf-8
         */
        if($code != 'gb2312') $str = self::_utf82gb($str);
        $res = '';
        $len = strlen($str);
        for($i = 0; $i < $len; $i++)
        {
            $p = ord(substr($str, $i, 1));

            if($p > 160)
            {
                $q = ord(substr($str, ++$i, 1));
                $p = $p * 256 + $q - 65536;
            }
            $res .= self::_pinyin($p, $data);
        }
        return preg_replace("/[^a-z0-9]*/", '', $res);
    }

    private static function _pinyin($num, $data)
    {
        if($num > 0 && $num < 160)
        {
            return chr($num);
        }
        else if($num < -20319 || $num > -10247)
        {
            return '';
        }
        else
        {
            foreach($data as $k => $v)
            {
                if($v <= $num) break;
            }
            return $k;
        }
    }

    private static function _utf82gb($c)
    {
        $str = '';
        if($c < 0x80)
        {
            $str .= $c;
        }
        elseif($c < 0x800)
        {
            $str .= chr(0xC0 | $c >> 6);
            $str .= chr(0x80 | $c & 0x3F);
        }
        elseif($c < 0x10000)
        {
            $str .= chr(0xE0 | $c >> 12);
            $str .= chr(0x80 | $c >> 6 & 0x3F);
            $str .= chr(0x80 | $c & 0x3F);
        }
        elseif($c < 0x200000)
        {
            $str .= chr(0xF0 | $c >> 18);
            $str .= chr(0x80 | $c >> 12 & 0x3F);
            $str .= chr(0x80 | $c >> 6 & 0x3F);
            $str .= chr(0x80 | $c & 0x3F);
        }
        return @iconv('UTF-8', 'GB2312//IGNORE', $str);
    }
}
?>