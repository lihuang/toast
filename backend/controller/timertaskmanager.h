/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef TIMER_TASK_MANAGER_H
#define TIMER_TASK_MANAGER_H
#include<list>
#include<string>
#include<pthread.h>
struct CronTaskTime;
class TimerTaskManager
{
public:
    TimerTaskManager();
    ~TimerTaskManager();
    void Insert(int task_id, std::string time_string);
    void Delete(int task_id);
    int GetNextRunTask();
private:
    std::list<CronTaskTime *>::iterator Find(int task_id);
private:
    std::list<CronTaskTime *> m_task_list;   // this list is sorted according the timeout value. 
    pthread_mutex_t   m_task_list_mutex;  // when send thread send data, need to lock this, and when the connection thread modify the list need also need lock
};
#endif
