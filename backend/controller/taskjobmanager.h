/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef COMMAND_MANAGER_H
#define COMMAND_MANAGER_H
#include "singleton.h"
#include "../agentcmdrsp/agentcmdrsp.h"
#include "../log/Log.h"
#include <pthread.h>
#include <vector>
#include <map>
#include <string>

using namespace std;
#define INVALIDATE_JOB_ID           -1
enum RunType
{
    RUN_TYPE_RUN,        // run a command 
    RUN_TYPE_CANCEL_RUN,   // cancel a running command run
    RUN_TYPE_INFO,     // get the machine information
};
// 0 waitting; 1 running; 2 complete; 3 canceled; 4 timeout; 5 abort; 10 canceling

#define TASK_RUN_STATUS_WAITING     0
#define TASK_RUN_STATUS_RUNNING     1
#define TASK_RUN_STATUS_COMPLETED   2
#define TASK_RUN_STATUS_CANCELED     3
#define TASK_RUN_STATUS_TIMEOUT        4
#define TASK_RUN_STATUS_ABORT            5
#define TASK_RUN_STATUS_AGENT_DOWN       6
#define TASK_RUN_STATUS_CANCELING    10
// job stage task
// task information should include all the information for sake of rerun
struct TaskRun
{
    int id;          //
    int timeout;
   // int status;      // running, finished or other, agent down
    int fail_action; // if fail rerun or complete the job
    string agent;    // which agent run this task, when the agent is down, search this for tasks
    string account;
    string command;
    int      log_fd;          // the log file fd
    TaskRun()
    	{
    	   id = -1; 
          timeout = -1;
          fail_action = 0;   
          log_fd  = -1;          // invalid fd
    	}
};
struct Job
{
    int id;
    int run_interval;    // the run cycle
    int run_time;         // run at what time
    time_t next_run_time;      // next time run
};
/*
*  仅发送到agent的执行的Task加如TaskJobManager，对于取消运行的任务，不加如TaskJobManager
*  取消任务发送去后不保存，当Agent返回取消任务结果时也不处理， 任务的状态由任务执行结果判断
*  取消后任务在结果中会有相应的反应。
*  对Job， 进行相同的处理, 对取消Job的处理，直接在本接口中处理
*/
class TaskRunManager:public Singleton<TaskRunManager>
{
   friend class Singleton<TaskRunManager>;
public:

     // when agent is down, all the task that running need to be delete and updated
    void ProcessAgentDown(const string &agent);
    int InsertTaskRun(TaskRun *task);
     void TaskRunStart(const AgentResponseStart *rsp);
     void TaskRunCompleted(const AgentResponseResult *rsp);
    void TaskRunLog(const AgentResponseLog *rsp);
    void Initlize();

private: // private function
    TaskRunManager();
     ~TaskRunManager();
private: // data
    pthread_mutex_t m_running_tasks_lock;
    map<int, TaskRun*> m_running_tasks;  // task id and task pointer
};

// timer job and task
class JobManager:public Singleton<JobManager>
{
   friend class Singleton<JobManager>;
public:
    void InsertJob(int job_id, int run_interval, int run_time);
    void DeleteJob(int job_id);
private: // private function
    JobManager();
     ~JobManager();
private: // data
    pthread_mutex_t m_jobs_lock;
    map<int, Job*> m_jobs;    // job id and run time
};

#endif

