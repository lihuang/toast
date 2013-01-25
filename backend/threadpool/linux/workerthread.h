/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef WORKERTHREAD_H
#define WORKERTHREAD_H

#include <pthread.h>
#include <signal.h>
#include <list>
typedef unsigned int uint_t;
typedef struct job job_t;
struct job 
{
    void *(*job_func)(void *); // the worker function to call
    void *job_arg; // the worker function  argument 
};
#define WORKER_WAIT    0x01    // waiting in WaitWorkerThread
#define WORKER_DESTROY 0x02    // worker is being destroyed
#define WORKER_RUNNING 0x04    // worker is running
#define WORKER_IDLE    0x08    // worker is idle

class WorkerThread
{
public:
    static WorkerThread * Create(pthread_attr_t *attr); 
    int AddWork(void *(*func)(void *), void *arg);
    static void Destroy(WorkerThread * worker, int timeout_sec);
    void WaitWorkerThread(int timeout_sec);
private:
    WorkerThread(pthread_attr_t *attr);
    ~WorkerThread();
    static void WorkerCleanup(void *param);
    void NotifyWaiters();
    static void JobCleanup(void *param);
    int CreateWorker();
    static void * WorkerThreadRun(void *work_thread);
    static void CloneAttributes(pthread_attr_t *new_attr, pthread_attr_t *old_attr);
private:
    pthread_mutex_t     m_worker_mutex; 
    pthread_cond_t      m_worker_workcv; 
    pthread_t           m_active_thread;
    std::list<job*>     m_works;  /* job queue */      
    pthread_attr_t m_thread_attr; /* attributes of the workers */
    volatile int m_worker_flags; // stop or wait
    sigset_t m_fillset;
};
#endif

