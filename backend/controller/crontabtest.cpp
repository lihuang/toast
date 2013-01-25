/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "crontab.h"
#include <list>
#include <iostream>
#include <vector>
using namespace std;

void only_sunday_7(time_t current_time)
{
    cout << "only sunday 7 test " << endl;
    CronTaskTime cron_time;
    string only_sun_7 = "30 19 * * 7";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = only_sun_7;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(only_sun_7, &cron_time);
    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 10; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next) << endl;
        }
        else
        {
            cout << "Invalidate: " << asctime(&tm_next);
        }
        tm_current = tm_next;
    }
    cout << "end of only_sunday_7 " << endl;

}
void every_day_0Minute_0Hour(time_t current_time)
{
    CronTaskTime cron_time;
    string every_day_10_30 = "0 0 * * *";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);
    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 100; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }
        tm_current = tm_next; 
    }
}
void every_year_Dec_31(time_t current_time)
{
    CronTaskTime every_minute_cron_time;
    string every_12_31  = "0 0 3 5 *";
    every_minute_cron_time.asterisk_flags = 0;
    every_minute_cron_time.cron_string = every_12_31;
    every_minute_cron_time.task_id =0;
    every_minute_cron_time.next_run_time = 0;
    TimeStringToCronTime(every_12_31, &every_minute_cron_time);
    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 100; i++)
    {
        time_t return_time = GetNextRunTime(&every_minute_cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&every_minute_cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }
        tm_current = tm_next; 
    } 
}
void every_day_work_day(time_t current_time)
{
    CronTaskTime cron_time;
    string every_day_10_30 = "0 0 * * 1-5";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);
    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 1000; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }
        tm_current = tm_next; 
    }
}
void every_day_of_week(time_t current_time)
{
    CronTaskTime cron_time;
    string every_day_10_30 = "0 0 * * 1-7";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);
    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 1000; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }
        tm_current = tm_next; 
    }
}
void every_day_month_3_or_weed_4_5(time_t current_time)
{
    CronTaskTime cron_time;
    string every_day_10_30 = "0 0 3 * 4-5";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);

    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 1000; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }        
        tm_current = tm_next; 
    }
}
void week_with_some_dot(time_t current_time)
{
    CronTaskTime cron_time;
    string every_day_10_30 = "0 0 * * 1, 2, 3, 4, 5";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);

    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 100; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }        
        tm_current = tm_next; 
    }
}
void dot_dot(time_t current_time)
{
    CronTaskTime cron_time;

    string every_day_10_30 = "0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,15,24,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31 1,2,3,4,5,6,7,8,9,10,11,12 0,1,2,3,4,5,6";
    cout << every_day_10_30 << endl;
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);

    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 1000; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }        
        tm_current = tm_next; 
    }
}
void invalidate_day2_31(time_t current_time)
{
    CronTaskTime cron_time;
    string every_day_10_30 = "0 0 31 2 *";
    cron_time.asterisk_flags = 0;
    cron_time.cron_string = every_day_10_30;
    cron_time.task_id = 0;
    cron_time.next_run_time = 0;
    TimeStringToCronTime(every_day_10_30, &cron_time);

    struct tm tm_current;
    struct tm *tmp;
    tmp = localtime(&current_time);
    tm_current = *tmp;
    struct tm tm_next;
    cout << "Orig: " << asctime(&tm_current) << endl;
    for(int i = 0; i < 100; i++)
    {
        time_t return_time = GetNextRunTime(&cron_time, &tm_current);
        tm_next = *localtime(&return_time);
        if(CheckRunTime(&cron_time, return_time))
        {
            cout << "Next: " << asctime(&tm_next);
        }
        else
        {
            cout << "Invalide: " << asctime(&tm_next);
        }
        tm_current = tm_next; 

    }
}
void test_line_data()
{
    string times[] = {
        "0,5,10,15,20,25,30,35,40,45,50,55 9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *"
        ,"* 0 * * *"
        ,"* 1 * * *"
        ,"* 2 * * *"
        ,"0 * * * *"
        ,"0 0 * * *"
        ,"0 0 * * 0"
        ,"0 0 * * 7"
        ,"0 0 1 * *"
        ,"0 0 1 * 5"
        ,"0 0 1 1 *"
        ,"0 0 8 * *"
        ,"0 0,8,18 * * *"
        ,"0 01 * * *"
        ,"0 02 * * *"
        ,"0 03 * * *"
        ,"0 04 * * *"
        ,"0 08 * * *"
        ,"0 08 * * *"
        ,"0 1 * * *	"
        ,"0 1 * * 7	"
        ,"0 1 21 8 *"
        ,"0 10 * * *"
        ,"0 11 * * *"
        ,"0 12 * * *"
        ,"0 13 * * *"
        ,"0 14 * * *"
        ,"0 15 * * *"
        ,"0 16 * * *"
        ,"0 17 * * *"
        ,"0 18 * * *"
        ,"0 2 * * *"
        ,"0 2 * * 7"
        ,"0 20 * * *"
        ,"0 21 * * *"
        ,"0 22 * * *"
        ,"0 22 1 * *"
        ,"0 23 * * *"
        ,"0 3 * * *	"
        ,"0 3 * * 1"
        ,"0 4 * * *"
        ,"0 4 * * 7"
        ,"0 5 * * *"
        ,"0 5 * * 5"
        ,"0 0 * * 0"
        ,"0 0 * * 1"
        ,"0 0 * * 2"
        ,"0 0 * * 3"
        ,"0 0 * * 4"
        ,"0 0 * * 5"
        ,"0 0 * * 6"
        ,"0 0 * * 7"
        ,"0 6 * * *"
        ,"0 7 * * *"
        ,"0 8 * * *"
        ,"0 8 10 10 *"
        ,"0 8,18 * * *"
        ,"0 8,18 * * *"
        ,"0 9 * * *"
        ,"1 1 * * *"
        ,"1 1 * * 1,2,3,4,5"
        ,"1 12 * * *"
        ,"1 14 * * 5"
        ,"1 2 * * *"
        ,"1 5 * * *"
        ,"1 6 * * *"
        ,"1 7 * * *"
        ,"10 1 * * *"
        ,"10 17 * * *"
        ,"10 2 * * *"
        ,"10 5 * * *"
        ,"10 7 * * *"
        ,"10 8 * * *"
        ,"12 2 * * *"
        ,"14 21 * * *"
        ,"15 5 * * *"
        ,"15 6 * * *"
        ,"15 7 * * *"
        ,"18 2 * * *"
        ,"2 1 * * *"
        ,"2 16 * * *"
        ,"2 2 * * *"
        ,"2 3 * * *"
        ,"2 5 * * *"
        ,"20 0 * * *"
        ,"20 1 * * *"
        ,"20 10 * * *"
        ,"20 2 * * *"
        ,"20 3 * * *"
        ,"20 5 * * *"
        ,"20 6 * * *"
        ,"20 7 * * *"
        ,"20 8 * * *"
        ,"20 9 * * *"
        ,"22 5 * * *"
        ,"23 5 * * *"
        ,"25 15 * * *"
        ,"3 2 * * *"
        ,"3 3 * * *"
        ,"30 0 * * *"
        ,"30 01 * * *"
        ,"30 02 * * *"
        ,"30 03 * * *"
        ,"30 04 * * *"
        ,"30 05 * * *"
        ,"30 07 * * *"
        ,"30 1 * * *"
        ,"30 10 * * *"
        ,"30 11 * * *"
        ,"30 16 * * *"
        ,"30 19 * * 7"
        ,"30 2 * * *"
        ,"30 3 * * *"
        ,"30 7 * * *"
        ,"30 8 * * *"
        ,"30 8 * * *"
        ,"30 9 * * *"
        ,"4 1 * * *"
        ,"4 4 * * *"
        ,"4 5 * * *"
        ,"4 6 * * *"
        ,"40 10 * * *"
        ,"40 12 * * *"
        ,"40 2 * * *"
        ,"40 7 * * *"
        ,"40 9 * * *"
        ,"5 1 * * *"
        ,"5 10 * * *"
        ,"5 21 * * *"
        ,"5 3 * * *"
        ,"5 5 * * *"
        ,"5 6 * * *"
        ,"5 6,17 * * *"
        ,"5 7 * * *"
        ,"5 8 * * *"
        ,"50 * * * *"
        ,"50 15 * * *"
        ,"50 16 * * *"
        ,"50 4 * * *"
        ,"55 18 * * *"
        ,"6 2 * * *"
        ,"6 6 * * *"
        ,"7 7 * * *"
        ,"8 0 * * *"
        ,"8 9 * * *"
        ,"8 9 * * *"
        ,"9 0 * * *"
        ,""
    };		   
    time_t current_time = time(NULL);
    int i = 0;
    while(!times[i].empty())
    {
        CronTaskTime cron_time;
        cron_time.asterisk_flags = 0;
        cron_time.cron_string = times[i];
        cron_time.task_id = 0;
        cron_time.next_run_time = 0;
        TimeStringToCronTime(times[i], &cron_time);
        struct tm tm_current;
        struct tm *tmp;
        tmp = localtime(&current_time);
        tm_current = *tmp;
        struct tm tm_next;
        cout << "orig: " << asctime(&tm_current) << endl;
        cout << "time string: " << times[i] << endl;
        for(int i = 0; i < 500; i++)
        {
            time_t return_time = GetNextRunTime(&cron_time, &tm_current);
            tm_next = *localtime(&return_time);
            if(CheckRunTime(&cron_time, return_time))
            {
                cout << "Next: " << asctime(&tm_next) << endl;
            }
            else
            {
                cout << "Invalidate: " << asctime(&tm_next);
            }
            tm_current = tm_next;
        }
        cout << "end of: " << times[i] << endl;
        i++;
    }

}
int main(int argc, char **argv)
{
    test_line_data();
    time_t current_time = time(NULL);
    only_sunday_7(current_time);
    cout << "every day 0 minute 0 hour " << endl;
    every_day_0Minute_0Hour(current_time);
    cout << "every_year_Dec_31" <<  endl;
    every_year_Dec_31(current_time);
    cout << "every_day_work_day" << endl;
    every_day_work_day(current_time);
    cout << "every_day_of_week" << endl;
    every_day_of_week(current_time);
    cout << "every_day_month_3_or_weed_4_5" << endl;
    every_day_month_3_or_weed_4_5(current_time);
    cout << "invalidate_day_2_31" << endl;
    invalidate_day2_31(current_time);
    cout << "week_with_some_dot(time_t current_time)" << endl;  
    week_with_some_dot(current_time);
    cout << "dot_dot(time_t current_time)" << endl;
    dot_dot(current_time);
    cout << "end of run" << endl;
}
