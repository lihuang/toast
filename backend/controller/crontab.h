/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef CRONTAB_H
#define CRONTAB_H
#include <bitset>
#include <string>
#include <time.h>

#define INVALIDATE_TASK_ID      -1
struct CronTaskTime
{
    int task_id;
    time_t next_run_time;
    std::string cron_string;
    int asterisk_flags; // 0x01 minute 0x02 hour 0x04 day 0x08 month 0x10 weed  
    std::bitset < 60 > minute; // 0 - 59
    std::bitset < 24 > hour; // 0-23
    std::bitset < 32 > day; // use 1-31  day + 1
    std::bitset < 12 > month; // use 0 - 11 month + 1
    std::bitset < 7 > week; // 0 - 6, 0  is sunday
};
time_t GetNextRunTime(CronTaskTime *cron_time, struct tm *current_time);
int TimeStringToCronTime(const std::string &cron_str, CronTaskTime *task_time);
bool CheckRunTime(CronTaskTime *cron_time, time_t run_time);
#endif
