/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "taskjobmanager.h"
#include <unistd.h>
#include "ToastEngine.h"
#include <limits.h>
#include <stddef.h>
#include <errno.h>
#include <string.h>
#include "systemconfig.h"
#include "webinterface.h"
#include "webstatus.h"
TaskRunManager::TaskRunManager()
{
    m_running_tasks.clear();
    pthread_mutex_init(&m_running_tasks_lock, NULL);
}
TaskRunManager::~TaskRunManager()
{
    pthread_mutex_destroy(&m_running_tasks_lock);
}

// when agent is down, all the task that running need to be delete and updated
void TaskRunManager::ProcessAgentDown(const string &agent)
{
    pthread_mutex_lock(&m_running_tasks_lock);

    map<int, TaskRun*>::iterator task_iter = m_running_tasks.begin();
    TaskRun *task = NULL;
    while(task_iter != m_running_tasks.end())
    {
        task = task_iter->second;
        if(task->agent == agent)    // find the task
        {
            // Update the task status
            Log::Info("Agent %s is down, set it's task %d status to down", agent.c_str(), task->id); 
            //toast::PostAPI::UpdateTaskStatus(task->id, 2, "Agent is Down");
            WebInterfaces::UpdateTaskInfo(task->id, WEB_STATUS_AGENT_DOWN, 0, "Agent is down");
            delete task;
            task = NULL;
            m_running_tasks.erase(task_iter);
            task_iter = m_running_tasks.begin();       // the iterator may invalidate after erase, so from begin again
        }
        else
        {
            task_iter++;
        }
    }
    pthread_mutex_unlock(&m_running_tasks_lock);
}
void TaskRunManager::Initlize()
{
    Log::Info("Task run manager initlize");
}
int TaskRunManager::InsertTaskRun(TaskRun *task)
{
    int res = 0;
    pthread_mutex_lock(&m_running_tasks_lock);
    m_running_tasks[task->id] = task;
    Log::Info("Add task %d to running list.", task->id);    
    pthread_mutex_unlock(&m_running_tasks_lock);
    return res;
}
void TaskRunManager::TaskRunCompleted(const AgentResponseResult*rsp)
{
    TaskRun *task = NULL;
    pthread_mutex_lock(&m_running_tasks_lock);
    map<int, TaskRun*>::iterator task_iter = m_running_tasks.find(rsp->head.id);
    if(task_iter != m_running_tasks.end()) // bingo, we find the task
    {
        task = task_iter->second;
        Log::Info("Task %d is run completed", task->id);
        // UpdateTaskStatus();    //update the task status in db or through web
        // 1. update the task status in db or through web
        // 2. remove the task from the m_running_tasks.
        delete task;
        m_running_tasks.erase(task_iter);
    }
    else
    {
        Log::Error("There is no task %d", rsp->head.id);   
    }
    pthread_mutex_unlock(&m_running_tasks_lock);
    string info;
    if((rsp-> head.length - offsetof(AgentResponseResult, data)))
        info= string(rsp->data, rsp->head.length - offsetof(AgentResponseResult, data));
     // write the appinfo to the log file if there is other info
     if(!info.empty())
     	{
    char log_file_name[PATH_MAX];
    strcpy(log_file_name, g_config->log_path.c_str());
    sprintf(log_file_name+g_config->log_path.length(), "/%d.log", rsp->head.id);
    int log_fd = open(log_file_name,  O_WRONLY | O_CREAT | O_APPEND,
        S_IRUSR | S_IWUSR | S_IRGRP | S_IWGRP | S_IROTH);
    if(log_fd == -1)
    {
        Log::Error("open log file: %d.log failed", rsp->head.id);
        return;
    }
    int log_data_length = info.length();
    int num_write = 0;
    int num_left = log_data_length;
    while(num_write < num_left)
    {
        num_write = write(log_fd, info.c_str()+log_data_length - num_left,  num_left);
        if(num_write == -1)
        {
            Log::Error("Write log file failed, %d of log fail druped ", log_data_length);
            break;
        }
        else if(num_write < num_left)
        {
            Log::Error("Write log filed failed, want write % but write %", num_left, num_write);
            num_left -= num_write;
        }
    }
    close(log_fd);
     	}
/*
enum COMMAND_STATUS
{
    COMMAND_WAITING   = 0,
    COMMAND_RUNNING   = 1,
    COMMAND_COMPLETED = 2,
    COMMAND_CANCELED  = 3,
    COMMAND_TIMEOUT   = 4
};*/

    int webstatus = COMMAND_COMPLETED;
    switch(rsp->result)
    	{
    	    case COMMAND_COMPLETED:
			webstatus = WEB_STATUS_COMPLETED;
			break;
	    case COMMAND_CANCELED:
			webstatus = WEB_STATUS_CANCELED;
			break;
	    case COMMAND_TIMEOUT:
			webstatus = WEB_STATUS_TIMEOUT;
			break;
    	}
    
    if(WebInterfaces::UpdateTaskInfo(rsp->head.id, webstatus, rsp->return_code/256, info))
    {
        Log::Error("Update test run failed with run id: %d", rsp->head.id);
    }
}
void TaskRunManager::TaskRunStart(const AgentResponseStart *rsp)
{
    Log::Info("Task %d start to run", rsp->head.id);
    if(WebInterfaces::UpdateTaskInfo(rsp->head.id, WEB_STATUS_RUNNING, 0, ""))
    {
        Log::Error("Update test run failed with run id: %d", rsp->head.id);
    }

}
void TaskRunManager::TaskRunLog(const AgentResponseLog *rsp)
{
    //if the log file is not exist, create a new one otherwise append content to file
    char log_file_name[PATH_MAX];
    strcpy(log_file_name, g_config->log_path.c_str());
    sprintf(log_file_name+g_config->log_path.length(), "/%d.log", rsp->head.id);
    int log_fd = open(log_file_name,  O_WRONLY | O_CREAT | O_APPEND,
        S_IRUSR | S_IWUSR | S_IRGRP | S_IWGRP | S_IROTH);
    if(log_fd == -1)
    {
        Log::Error("open log file: %d.log failed", rsp->head.id);
        return;
    }
    int log_data_length = rsp->head.length - sizeof(AgentResponseHead) - 4;
    int num_write = 0;
    int num_left = log_data_length;
    while(num_write < num_left)
    {
        num_write = write(log_fd, rsp->data+log_data_length - num_left,  num_left);
        if(num_write == -1)
        {
            Log::Error("Write log file failed, %d of log fail druped ", log_data_length);
            break;
        }
        else if(num_write < num_left)
        {
            Log::Error("Write log filed failed, want write % but write %", num_left, num_write);
            num_left -= num_write;
        }
    }
    close(log_fd);
}


JobManager::JobManager()
{
    m_jobs.clear();
    pthread_mutex_init(&m_jobs_lock, NULL);
}
JobManager::~JobManager()
{
    pthread_mutex_destroy(&m_jobs_lock);
}
void JobManager::InsertJob(int job_id, int run_interval, int run_time)
{
    Job *job = new (std::nothrow)Job;
    if(job)
    {
        job->id = job_id;
        job->run_interval = run_interval;
        job->run_time = run_time;
        job->next_run_time = run_time + run_time;
        pthread_mutex_lock(&m_jobs_lock);
        m_jobs[job->id] = job;
        pthread_mutex_unlock(&m_jobs_lock);
    }
    else
    {
        Log::Error("There is no memory in at insert job");
    }
}
void JobManager::DeleteJob(int job_id)
{
    pthread_mutex_lock(&m_jobs_lock);
    map<int, Job*>::iterator iter = m_jobs.find(job_id);
    if(iter != m_jobs.end())
    {
        delete iter->second;
        m_jobs.erase(iter);
    }
    pthread_mutex_unlock(&m_jobs_lock);
}



