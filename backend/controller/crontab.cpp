/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "crontab.h"
#include <list>
using namespace std;
#define YEAR_MONTH      12
#define HOUR_MINUTE     60
#define MINUTE_SECOND   60
#define DAY_HOUR        24
#define MAX_MONTH_DAY   31

/*
For non-leap years, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31. For leap years, add one to February using the formula:

((year MOD 4 = 0 AND year MOD 100 <> 0) OR year MOD 400 = 0)  */

static int GetMonthDays(int year, int month)
{
    const int MonthDays[YEAR_MONTH] = {31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31};
    const int LeapMonthDays[YEAR_MONTH] = {31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31};
    int is_leap_year = (year && (((year % 4 == 0) && (year % 100 != 0)) || (year % 400 == 0)));
    if (is_leap_year)
        return LeapMonthDays[month];
    else
        return MonthDays[month];
}

// return month diff
// 
static int GetMonthDayDiff(CronTaskTime *cron_time, struct tm *current_time, int *next_day, int *next_month, int *next_year)
{
    int month_days = GetMonthDays(current_time->tm_year + 1900, current_time->tm_mon);
    int find_year = current_time->tm_year + 1900;
    int month = current_time->tm_mon;
    int day_diff = 0;
    int i;
    for(i = current_time->tm_mday + 1; i <= month_days; i++) // bit 0-31 , bit 0 unused
    {
        day_diff++;
        if (cron_time->day.test(i) && cron_time->month.test(month))
        {
            *next_day = i;
            *next_month = current_time->tm_mon;
            *next_year = find_year;
            return day_diff;
        }
    }
    if (i > month_days) // next month
    {
        for(int num_month = 0; num_month < YEAR_MONTH; num_month++)
        {
            month++;
            if(month >= YEAR_MONTH)
            {
                find_year++;
                month = 0;
            }
            bool is_find = 0;
            for (i = 1; i <= GetMonthDays(find_year, month); i++)
            {
                day_diff++;
                if (cron_time->day.test(i) && cron_time->month.test(month))
                {
                    *next_day = i;
                    is_find = 1;
                    break;
                }
            }
            if(is_find)
            {
                break;   // we find the day, break the month loop
            }
        }
    }
    *next_year = find_year;
    *next_month = month;
    return day_diff;
}


static int GetNextWeek(CronTaskTime *cron_time, struct tm *current_time)
{
    int next_week = -1;
    int i;
    for (i = current_time->tm_wday; i < 7; i++)
    {
        if (cron_time->week.test(i))
        {
            next_week = i;
            break;
        }
    }
    if (i >= 7)
    {
        for (i = 0; i < current_time->tm_wday; i++)
        {
            if (cron_time->week.test(i))
            {
                next_week = i;
                break;
            }
        }
    }
    return next_week;
}
/**
* return next_minute
*/
static int GetNextMinuteHour(CronTaskTime *cron_time, struct tm*current_time, int *minute_diff, int *next_hour)
{
    int next_minute = -1;
    int n_hour = current_time->tm_hour;
    int m_diff = 0;
    int h_diff = 0;
    *minute_diff = 0;
    int i;

    for(i = current_time->tm_min + 1; i < HOUR_MINUTE; i++) 
    {
        m_diff++; // check if current hour has time to run
        if(cron_time->minute.test(i) && cron_time->hour.test(current_time->tm_hour))
        {
            *minute_diff = m_diff;
            next_minute = i;
            break;
        }
    }

    if(i == HOUR_MINUTE) // need take another hour
    {
        // first we need to get next minute
        for(i = 0; i < HOUR_MINUTE; i++)
        {
            m_diff++;
            if(cron_time->minute.test(i))
            {
                next_minute = i;
                break;
            }
        }
        n_hour++;
        for(;n_hour < DAY_HOUR; n_hour++)
        {		
            h_diff++;
            if(cron_time->hour.test(n_hour))
            {
                break;
            }
        }
        if(n_hour >= DAY_HOUR)
        {
            n_hour = 0;
            for(i = 0; i < DAY_HOUR; i++)
            {
                h_diff++;
                if(cron_time->hour.test(i))
                {
                    n_hour = i;
                    break;
                }
            }
        }
        *minute_diff = m_diff + (h_diff -1) * MINUTE_SECOND;
    }
    *next_hour = n_hour;
    return next_minute;
}
time_t GetNextRunTime(CronTaskTime *cron_time, struct tm *current_time)
{
    int next_minute;
    int next_hour;
    int next_day;
    int next_week;
    int next_month;
    int next_year;

    time_t now = mktime(current_time);

    int minute_diff;
    next_minute = GetNextMinuteHour(cron_time, current_time, &minute_diff, &next_hour);
    if(next_minute == -1)
        return 0;
    time_t next_time = now + minute_diff * MINUTE_SECOND;
    tm next_tm = *localtime(&next_time);
    if((cron_time->asterisk_flags & 0x1C) == 0x0C) // day of month is *, only need check day of week
    {
        if(cron_time->week.test(next_tm.tm_wday))  // day
        {
            // next run time is next_tm;  
            return next_time;
        }
        else  //  ¿ÉÄÜ¿ç¶àÌì
        {
            // get next day, next month, next week next hour
            // get next hour
            next_week = GetNextWeek(cron_time, &next_tm);
            if(next_week == -1)
                return 0;
            int week_day_diff = (next_week > next_tm.tm_wday) 
                ?(next_week - next_tm.tm_wday)
                :(7 - next_tm.tm_wday + next_week);
            next_time = next_time + week_day_diff* DAY_HOUR * HOUR_MINUTE * MINUTE_SECOND;
            return next_time;
        }
    }
    else if(((cron_time->asterisk_flags & 0x10) == 0x10) && ((cron_time->asterisk_flags & 0x0C) != 0x0C))// day of week is *, only need check day of month
    {
        if(cron_time->day.test(next_tm.tm_mday) && cron_time->month.test(next_tm.tm_mon))  // day
        {
            // next run time is next_tm;  
            return next_time;
        }
        else  //  ¿ÉÄÜ¿ç¶àÌì
        {
            // get next day, next month, next week next hour
            int month_day_diff = GetMonthDayDiff(cron_time, &next_tm, &next_day, &next_month, &next_year);
            //next run time is mim next_week or day of month hour minute
            next_time = next_time + month_day_diff* DAY_HOUR * HOUR_MINUTE * MINUTE_SECOND;
            return next_time;
        }
    }
    else   // all not * or all *
    {
        if((cron_time->day.test(next_tm.tm_mday) && cron_time->month.test(next_tm.tm_mon)) || (cron_time->week.test(next_tm.tm_wday)))  // day
        {
            // next run time is next_tm;  
            return next_time;
        }
        else  //  ¿ÉÄÜ¿ç¶àÌì
        {
            // get next day, next month, next week next hour
            next_week = GetNextWeek(cron_time, &next_tm);
            if(next_week == -1)
                return 0;
            int month_day_diff = GetMonthDayDiff(cron_time, &next_tm, &next_day, &next_month, &next_year);
            //next run time is mim next_week or day of month hour minute
            // ÏÂ´ÎÔËÐÐÊ±¼ä°´ÖÜËã²îµÄÌìÊý
            int week_day_diff = (next_week > next_tm.tm_wday) 
                ?(next_week - next_tm.tm_wday)
                :(7 - next_tm.tm_wday + next_week);

            if(month_day_diff > week_day_diff)
            {
                next_time = next_time + week_day_diff* DAY_HOUR * HOUR_MINUTE * MINUTE_SECOND;
                return next_time;
            }
            else
            {
                next_time = next_time + month_day_diff* DAY_HOUR * HOUR_MINUTE * MINUTE_SECOND;
                next_tm = *localtime(&next_time);
                return next_time;
            }
        }
    }
}

// skip the spaces from index include '\t' and ' '

static void SkipSpaces(const string &str, unsigned int *index)
{
    while (*index < str.length())
    {
        if (str[*index] == '\t' || str[*index] == ' ')
            (*index)++;
        else
            break;
    }
}

static int GetNumber(const string &str, unsigned int *index)
{
    int retValue = 0;
    while ((*index < str.length()) && (isdigit(str[*index])))
    {
        retValue *= 10;
        retValue += str[*index] - '0';
        (*index)++;
    }
    return retValue;
}
// > 0 success -1 at the end of string

static int GetFieldStep(const string &str, unsigned int *index)
{
    int steps = 1;
    if ((*index < str.length()) && (str[*index] == '/'))
    {
        (*index)++;
        steps = GetNumber(str, index);
    }
    return steps;
}
int VerifySteps(int step, int maxStep)
{
    if(step >= maxStep)
        return 1;
    return 0;
}
// check if the cron string has invalidate char, if has invalidate char return 1
// no invalidate char return 0
int ValidateTimeStringChars(const string &cron_str)
{
    char validate_chars[] = {'0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '-', '/', ',' , ' '};
    for(int i =0; i < cron_str.length(); i++)
    {
        char ch = cron_str[i];
        int j = 0;
        for(j = 0; j < sizeof(validate_chars); j++)
        {
            if(ch == validate_chars[j])
            {
                break;
            }
        }
        if(j == sizeof(validate_chars))
            return 1;
    }
    return 0;
}
#define MIN_TIME_STRING_LENGTH          9
int TimeStringToCronTime(const string &cron_str, CronTaskTime *task_time)
{
    unsigned int cron_str_index = 0;
    int steps = 0;
    int type = 0; // 0 minute 1 hour  2 day 3 month 4 week
    if(ValidateTimeStringChars(cron_str))
        return -1;
    SkipSpaces(cron_str, &cron_str_index);
    if (cron_str.length() == 0 || cron_str_index >= cron_str.length() || ((cron_str.length() - cron_str_index) < MIN_TIME_STRING_LENGTH))
        return -1;
    // set the bitset to zero
    task_time->minute.reset();
    task_time->hour.reset();
    task_time->day.reset();
    task_time->month.reset();
    task_time->week.reset();
next:
    // skip the lead spaces
    SkipSpaces(cron_str, &cron_str_index);
    if(cron_str_index >= cron_str.length() && type ==5)
        return 0;
    else if(cron_str_index >= cron_str.length())
        return -1;
    // start processing the minutes
    if (cron_str[cron_str_index] == '*')
    {
        switch (type)
        {
        case 0:
            task_time->asterisk_flags |= 0x01;
            break;
        case 1:
            task_time->asterisk_flags |= 0x02;
            break;
        case 2:
            task_time->asterisk_flags |= 0x04;
            break;
        case 3:
            task_time->asterisk_flags |= 0x08;
            break;
        case 4:
            task_time->asterisk_flags |= 0x10;
            break;
        default:
            return -1; // invalide type
        }
        cron_str_index++;
        if (cron_str_index < cron_str.length())
        {
            steps = GetFieldStep(cron_str, &cron_str_index);
        }
        else
            steps = 1;
        if(!steps)
            return -1; // characher after / is not number

        switch (type)
        {
        case 0:
            if(VerifySteps(steps, HOUR_MINUTE))
                return -1;
            for (int i = 0; i < HOUR_MINUTE; i += steps)
            {
                task_time->minute.set(i, true);
            }
            break;
        case 1:
            {
                if(VerifySteps(steps, DAY_HOUR))
                    return -1;
                for (int i = 0; i < DAY_HOUR; i += steps)
                {
                    task_time->hour.set(i, true);
                }
            }
            break;
        case 2:
            {
                if(VerifySteps(steps, MAX_MONTH_DAY))
                    return -1;
                for (int i = 1; i <= MAX_MONTH_DAY; i += steps)
                {
                    task_time->day.set(i, true);
                }
            }
            break;
        case 3:
            {
                if(VerifySteps(steps, YEAR_MONTH))
                    return -1;
                for (int i = 0; i < YEAR_MONTH; i += steps)
                {
                    task_time->month.set(i, true);
                }

            }
            break;
        case 4:
            {
                if(VerifySteps(steps, 7))
                    return -1;

                for (int i = 0; i < 7; i += steps)
                {
                    task_time->week.set(i, true);
                }

            }
            break;
        default:
            return -1; // invalide type
        }
    }
    else if (!(isdigit(cron_str[cron_str_index])))  // should be number, otherwise error time string, such as "* / * * *"
    {
        return -1;
    }
    else // should the numbers
    {
        int num_start = GetNumber(cron_str, &cron_str_index);
        if (cron_str[cron_str_index] == '-') // a range
        {
            cron_str_index++;
            if (cron_str_index >= cron_str.length())
                return -1;
            // check if it's digits, 10-/
            if(!isdigit(cron_str[cron_str_index]))
                return -1;
            int num_end = GetNumber(cron_str, &cron_str_index);
            if(num_end < num_start)
                return -1;
            if (cron_str_index > cron_str.length())
                return -1;
            steps = GetFieldStep(cron_str, &cron_str_index);
            if(!steps)
                return -1;
            switch (type)
            {
            case 0:
                if(VerifySteps(steps, HOUR_MINUTE))
                    return -1;
                if(num_start < 0 || num_start > HOUR_MINUTE || num_end >HOUR_MINUTE)
                    return -1;  // invalidate minute value 70-80 or 50-90
                for (int i = num_start; i >= 0 && i <= num_end && i < HOUR_MINUTE; i += steps)
                {
                    task_time->minute.set(i, true);
                }
                break;
            case 1:
                {
                    if(VerifySteps(steps, DAY_HOUR))
                        return -1;
                    if(num_start < 0 || num_start > DAY_HOUR || num_end >DAY_HOUR)
                        return -1;
                    for (int i = num_start; i >= 0 && i <= num_end && i < DAY_HOUR; i += steps)
                    {
                        task_time->hour.set(i, true);
                    }
                }
                break;
            case 2:
                {
                    if(VerifySteps(steps, MAX_MONTH_DAY))
                        return -1;
                    if(num_start < 0 || num_start > MAX_MONTH_DAY || num_end >MAX_MONTH_DAY)
                        return -1;
                    for (int i = num_start; i > 0 && i <= num_end && i <= MAX_MONTH_DAY; i += steps)
                    {
                        task_time->day.set(i, true);
                    }
                }
                break;
            case 3:
                {
                    if(VerifySteps(steps, YEAR_MONTH))
                        return -1;
                    if(num_start < 0 || num_start > YEAR_MONTH || num_end >YEAR_MONTH)
                        return -1;

                    for (int i = num_start - 1; i >= 0 && (i <= num_end - 1) && i <= YEAR_MONTH; i += steps)
                    {
                        task_time->month.set(i, true);
                    }
                }
                break;
            case 4:
                {
                    if(VerifySteps(steps, 7))
                        return -1;
                    if(num_start < 0 || num_start > 7 || num_end >7)
                        return -1;

                    int i;
                    for (i = num_start; i >= 0 && i <= num_end && i < 7; i += steps)
                    {
                        task_time->week.set(i, true);
                    }
                    if (i == 7)
                    {
                        task_time->week.set(0, true); // set 0 sunday
                    }
                }
                break;
            default:
                return -1; // invalide type
            }

        }
        else
        {
            switch (type)
            {
            case 0:
                {
                    if(num_start >= 0 && num_start < HOUR_MINUTE)
                        task_time->minute.set(num_start, true);
                    else
                        return -1; // invalidate value
                }
                break;
            case 1:
                {
                    if(num_start >= 0 && num_start < DAY_HOUR)
                        task_time->hour.set(num_start, true);
                    else
                        return -1;
                }
                break;
            case 2:
                {
                    if(num_start>=0 && num_start <= MAX_MONTH_DAY)
                        task_time->day.set(num_start, true);
                    else
                        return -1;
                }
                break;
            case 3:
                {
                    if(num_start > 0 && num_start <= YEAR_MONTH)
                        task_time->month.set(num_start - 1, true);
                    else
                        return -1;
                }
                break;
            case 4:
                {
                    if (num_start >=0 && num_start < 7)
                        task_time->week.set(num_start, true);
                    else if(num_start == 7)
                        task_time->week.set(0, true); // set sunday
                    else
                        return -1;
                }
                break;
            default:
                return -1; // invalide type
            }
        }
    }
    if (cron_str_index < cron_str.length() && cron_str[cron_str_index] == ',')
    {
        cron_str_index++;
        if(cron_str_index < cron_str.length())
            goto next;
    }
    else if(cron_str_index < cron_str.length() && cron_str[cron_str_index] == ' ')
    {
        type++;
        if (type < 5)
            goto next;
         else
             return -1; // has ' ' after last char
    }
    else if(cron_str_index == cron_str.length() && type == 4)
    {
        return 0;
    }
    else
    {
        // invalidate format string
        return -1;
    }
    return 0;
}
bool CheckRunTime(CronTaskTime *cron_time, time_t run_time)
{
    struct tm tm = *localtime(&run_time);
    bool dom_dow = (((cron_time->asterisk_flags & 0x04) || (cron_time->asterisk_flags & 0x10))
        ?((cron_time->day.test(tm.tm_mday)) && (cron_time->week.test(tm.tm_wday)))
        : ((cron_time->day.test(tm.tm_mday)) || (cron_time->week.test(tm.tm_wday))));
    if(cron_time->minute.test(tm.tm_min)
        && cron_time->hour.test(tm.tm_hour)
        && cron_time->month.test(tm.tm_mon)
        && dom_dow)
        return true;
    return false;
}
