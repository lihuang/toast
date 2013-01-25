/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */


#ifndef MONITORSVNTHREAD_H
#define	MONITORSVNTHREAD_H
#include <string>
#include <list>
#include "svn_types.h"
#include "svn_client.h"
#include "svn_client.h"
#include "svn_cmdline.h"
#include "svn_pools.h"
#include "svn_config.h"
#include "svn_fs.h"
#include "svn_path.h"
#include "svn_time.h"
#include "svn_compat.h"

class CITaskManager 
{
public:
    CITaskManager();
    ~CITaskManager();
    void Initlize();
    int Insert_Monitors_Task(int taskid, string url, int interval = 1);
    void Delete_Monitors_Task(int taskid);
        void CheckChanged();
    typedef struct svn_monitor_task
    {
        string url; //the url of this task's revision, current it's a folder
        // delete the / at end of url
        int interval; //svn monitor interval uint minute
        int taskid; //frand end task id
        svn_revnum_t last_revision; //lastest revision, need this check the svn number is changed
        //when create it's 0, means new monitor task
        svn_revnum_t new_revision; // every time the list callback function assign the result to
        // this, and the task will check this value and last_revision,
        // if there is difference, notify frant end and update last_revison
        // with new_revision.
    }svn_monitor_task_t;

    typedef struct svn_monitors_list
    {
        pthread_mutex_t mtx_lock; //lock the list access
        list<svn_monitor_task_t*> monitor_list;
    } svn_monitors_list_t;
    struct print_baton
    {
        svn_boolean_t verbose;
        svn_monitor_task *monitor_task;
    };
private:
    void Init_Monitor_Task_List();
    apr_pool_t *m_pl;
    apr_pool_t *m_subpl;
    svn_client_ctx_t *m_ctx;
    svn_monitor_task_t* Is_Exist(int taskid);
    svn_monitors_list_t m_svn_monitor_tasks_list;
};


#endif	/* MONITORSVNTHREAD_H */

