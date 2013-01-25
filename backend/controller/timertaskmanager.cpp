/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "timertaskmanager.h"
#include "crontab.h"
#include "../log/Log.h"
using namespace std;

TimerTaskManager::TimerTaskManager()
{
    m_task_list.clear();
    pthread_mutex_init(&m_task_list_mutex, NULL);
}
TimerTaskManager::~TimerTaskManager()
{
    pthread_mutex_lock(&m_task_list_mutex);
    list<CronTaskTime *>::iterator iter = m_task_list.begin();
    if(iter != m_task_list.end())
    {
        delete *iter;
    }
    m_task_list.clear();
    pthread_mutex_unlock(&m_task_list_mutex);
    pthread_mutex_destroy(&m_task_list_mutex);
}
void TimerTaskManager::Insert(int task_id, string time_string)
{
    time_t current_time = time(NULL);
    struct tm tm_current = *localtime(&current_time);
    CronTaskTime *cron = new (std::nothrow)CronTaskTime();
    if(!cron)
        return;
    cron->task_id        = task_id;
    cron->cron_string    = time_string;
    cron->asterisk_flags = 0;
    int ret = 0;
    try
    {
        ret = TimeStringToCronTime(time_string, cron);
    }
    catch(...)
    {
        delete cron;
        Log::Error("Invalidate time string" + time_string);
        return;
    }
    if(ret == -1)
    {
        Log::Error("Invalidate time string" + time_string);
        delete cron;
        return;
    }
    cron->next_run_time  = GetNextRunTime(cron, &tm_current);
    tm_current = *localtime(&cron->next_run_time);
    Log::Info("Insert task %d, next run time: %s", task_id, asctime(&tm_current));
    pthread_mutex_lock(&m_task_list_mutex);
    list<CronTaskTime *>::iterator iter = Find(task_id);
    if(iter != m_task_list.end())   // if exist delete it
    {
        CronTaskTime *tmp = *iter;
        m_task_list.erase(iter);
        delete tmp;   // free the memory
    }
    iter = m_task_list.begin();   // insert it 
    while(iter!= m_task_list.end() && (*iter)->next_run_time < cron->next_run_time)
    {
        iter++;
    }
    m_task_list.insert(iter, cron);
    pthread_mutex_unlock(&m_task_list_mutex);
}
void TimerTaskManager::Delete(int task_id)
{
    pthread_mutex_lock(&m_task_list_mutex);
    list<CronTaskTime *>::iterator iter = m_task_list.begin();
    while(iter!= m_task_list.end() && (*iter)->task_id != task_id)
    {
        iter++;
    }
    if(iter != m_task_list.end())
    {
        CronTaskTime *tmp = *iter;
        m_task_list.erase(iter);
        delete tmp;   // free the memory
    }
    pthread_mutex_unlock(&m_task_list_mutex);
}
list<CronTaskTime *>::iterator TimerTaskManager::Find(int task_id)
{
    list<CronTaskTime *>::iterator iter = m_task_list.begin();
    while(iter!= m_task_list.end() && (*iter)->task_id != task_id)
    {
        iter++;
    }
    return iter;
}
// -1 no task, otherwise task id
int TimerTaskManager::GetNextRunTask()
{
    int task_id=INVALIDATE_TASK_ID;
    time_t current_time = time(NULL);
    pthread_mutex_lock(&m_task_list_mutex);
    list<CronTaskTime *>::iterator iter = m_task_list.begin();
    if(iter != m_task_list.end() && (*iter)->next_run_time <= current_time) // it's time to run
    {
        try
        {
            task_id = (*iter)->task_id;
            CronTaskTime * cron = *iter;
            m_task_list.pop_front();
            struct tm tm_current = *localtime(&current_time);
            time_t this_run_time = cron->next_run_time;
            time_t next_run_time = GetNextRunTime(cron, &tm_current);
            if(next_run_time <= current_time) // invalidate time string
            {
                Log::Error("Timer string error %s for task %d, we will delete this task", cron->cron_string.c_str(), task_id);
                delete *iter;
                task_id = INVALIDATE_TASK_ID;
            }
            else
            {
                cron->next_run_time  = next_run_time;
                // re insert to the list
                iter = m_task_list.begin();    
                while(iter!= m_task_list.end() && (*iter)->next_run_time < cron->next_run_time)
                {
                    iter++;
                }
                m_task_list.insert(iter, cron);
                tm_current = *localtime(&cron->next_run_time);
                Log::Info("task %d need run, next run time: %s", task_id, asctime(&tm_current));

                // double check the run day, some day such as 2 30, 31 is invalidate
                if(!CheckRunTime(cron, this_run_time))
                    task_id = INVALIDATE_TASK_ID;
            }
        }
        catch(...)
        {
            Log::Error("Get next run task exception");
            task_id = INVALIDATE_TASK_ID;
        }
    }
    pthread_mutex_unlock(&m_task_list_mutex);
    return task_id;    
}
