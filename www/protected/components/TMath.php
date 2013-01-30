<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class TMath
{
    public static function getRate($dividend, $divisor, $end = "%")
    {
        $rate = "";
        if($divisor > 0)
        {
            $rate = round((float) $dividend / (float) $divisor, 4) * 100 . $end;
        }
        return $rate;
    }
}
?>