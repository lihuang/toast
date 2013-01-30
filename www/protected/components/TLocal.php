<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class TLocal
{
    public static function touch($filename, $content, $umask = 0022, $lock = FALSE)
    {
        $old = @umask($umask);
        if ($lock)
        {
            $tempFile = '/tmp/' . uniqid('temp_', true);
            @file_put_contents($tempFile, $content, LOCK_EX);
            if (@copy($tempFile, $filename)) 
            {
                @unlink($tempFile);
            }
        }
        else 
        {
            @file_put_contents($filename, $content, LOCK_EX);
        }
        @umask($old);
    }
}
?>