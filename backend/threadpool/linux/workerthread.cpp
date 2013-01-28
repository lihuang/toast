/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "workerthread.h"
#include <errno.h>
#include <list>
#include <time.h>
using namespace std;
// worker thread is a thread that wait work in the work queue
WorkerThread * WorkerThread::Create(pthread_attr_t *attr)
{
    WorkerThread *worker;
    if((worker = new (std::nothrow)WorkerThread(attr)) == NULL)
    {
        errno = ENOMEM;
        return (NULL);
    }
    worker->CreateWorker();
    return (worker);
}

typedef void (*PthreadCleanupPushCB)(void*);
void WorkerThread::Destroy(WorkerThread * worker, int timeout_sec)
{
    struct timespec ts;
    pthread_mutex_lock(&worker->m_worker_mutex);
    pthread_cleanup_push((PthreadCleanupPushCB)pthread_mutex_unlock, &worker->m_worker_mutex);

    worker->m_worker_flags |= WORKER_DESTROY;
    if(worker->m_worker_flags & WORKER_IDLE)
        pthread_cond_broadcast(&worker->m_worker_workcv);
    else
	pthread_cancel(worker->m_active_thread);
	
      if(timeout_sec == -1)
        {
             (void) pthread_cond_wait(&worker->m_worker_workcv, &worker->m_worker_mutex);
        }
        else
        {
            (void) clock_gettime(CLOCK_REALTIME, &ts);
            ts.tv_sec += timeout_sec;
            if(pthread_cond_timedwait(&worker->m_worker_workcv, &worker->m_worker_mutex, &ts) == ETIMEDOUT) 
            {
                return;
            }
        }
    pthread_cleanup_pop(1); /* pthread_mutex_unlock(&m_worker_mutex); */
    delete worker;
}
int WorkerThread::AddWork(void *(*func)(void *), void *arg)
{
    job_t *job;
    if((job = new (std::nothrow)job_t) == NULL)
    {
        errno = ENOMEM;
        return (-1);
    }
    job->job_func = func;
    job->job_arg = arg;
    (void) pthread_mutex_lock(&this->m_worker_mutex);
    m_works.push_back(job);
    if(this->m_worker_flags & WORKER_IDLE)
        pthread_cond_signal(&this->m_worker_workcv);
    pthread_mutex_unlock(&this->m_worker_mutex);
    return (0);
}

WorkerThread::WorkerThread(pthread_attr_t *attr)
{
    (void) sigfillset(&m_fillset);
    (void) pthread_mutex_init(&m_worker_mutex, NULL);
    (void) pthread_cond_init(&m_worker_workcv, NULL);
    m_works.clear();
    m_worker_flags = 0;
    CloneAttributes(&m_thread_attr, attr);
}
WorkerThread::~WorkerThread()
{
    list<job*>::iterator iter = m_works.begin();
    while(iter != m_works.end())
    	{
        delete *iter;
		iter++;
    	}
    m_works.clear();
    pthread_mutex_destroy(&m_worker_mutex);
    pthread_cond_destroy(&m_worker_workcv);
    pthread_attr_destroy(&m_thread_attr);
}
void WorkerThread::WorkerCleanup(void *param)
{
    WorkerThread *worker = (WorkerThread *)param;
    if (worker->m_worker_flags & WORKER_DESTROY) 
    {
        pthread_cond_broadcast(&worker->m_worker_workcv);
    } 
    else
    {
        worker->CreateWorker();
    }
    pthread_mutex_unlock(&worker->m_worker_mutex);
}
// Wait for all queued jobs to complete.
typedef void (*PthreadCleanupPushCB)(void*);
void WorkerThread::WaitWorkerThread(int timeout_sec)
{
    struct timespec ts;
    (void) pthread_mutex_lock(&m_worker_mutex);
    pthread_cleanup_push((PthreadCleanupPushCB)pthread_mutex_unlock, &m_worker_mutex);
    while(!m_works.empty() || (this->m_worker_flags & WORKER_RUNNING))
    {
        m_worker_flags |= WORKER_WAIT;
        (void) pthread_cond_wait(&m_worker_workcv, &m_worker_mutex);
	 if(timeout_sec == -1)
        {
            (void) pthread_cond_wait(&m_worker_workcv, &m_worker_mutex);
        }
        else
        {
            (void) clock_gettime(CLOCK_REALTIME, &ts);
            ts.tv_sec += timeout_sec;
            if(pthread_cond_timedwait(&m_worker_workcv, &m_worker_mutex, &ts) == ETIMEDOUT) 
            {
                break;
            }
        }
    }
    pthread_cleanup_pop(1); 
}
void WorkerThread::NotifyWaiters()
{
    if(m_works.empty())
    {
        m_worker_flags &= ~WORKER_WAIT;
        pthread_cond_broadcast(&m_worker_workcv);
    }
}

// Called by a worker thread on return from a job.
void WorkerThread::JobCleanup(void *param)
{
    WorkerThread *worker = (WorkerThread *)param;

    (void) pthread_mutex_lock(&(worker->m_worker_mutex));
    if (worker->m_worker_flags & WORKER_WAIT)
        worker->NotifyWaiters();
}
int WorkerThread::CreateWorker()
{
    sigset_t oset;
    int error;
    pthread_t thread;
    (void) pthread_sigmask(SIG_SETMASK, &m_fillset, &oset);
    error = pthread_create(&thread, &m_thread_attr, WorkerThreadRun, (void*)this);
    m_active_thread = thread;
    (void) pthread_sigmask(SIG_SETMASK, &oset, NULL);
    return (error);
}
void * WorkerThread::WorkerThreadRun(void *param)
{
    WorkerThread *worker = (WorkerThread *)param;
    void *arg;
    job_t *job;
    void *(*func)(void *);
    (void) pthread_mutex_lock(&worker->m_worker_mutex);
    pthread_cleanup_push(worker->WorkerCleanup, (void*)worker);
    while(1)
    {
        pthread_sigmask(SIG_SETMASK, &worker->m_fillset, NULL);
        pthread_setcanceltype(PTHREAD_CANCEL_DEFERRED, NULL);
        pthread_setcancelstate(PTHREAD_CANCEL_ENABLE, NULL);
        worker->m_worker_flags |= WORKER_IDLE;
	 worker->m_worker_flags &= ~WORKER_RUNNING;
        while(worker->m_works.empty() && !(worker->m_worker_flags & WORKER_DESTROY))
        {           
            pthread_cond_wait(&worker->m_worker_workcv, &worker->m_worker_mutex);
        }
        if (worker->m_worker_flags & WORKER_DESTROY)
            break;
        worker->m_worker_flags &= ~WORKER_IDLE;
        worker->m_worker_flags |= WORKER_RUNNING;
        if(!worker->m_works.empty())    // must > 0
        {
            job = worker->m_works.front();
            worker->m_works.pop_front();
            func = job->job_func;
            arg = job->job_arg;
            pthread_mutex_unlock(&worker->m_worker_mutex);
            pthread_cleanup_push(worker->JobCleanup, worker);
            delete job;

            (void) func(arg);

            pthread_cleanup_pop(1); //JobCleanup(worker) 
        }
    }
    pthread_cleanup_pop(1); // worker_cleanup(worker)
    return (NULL);
}
void WorkerThread::CloneAttributes(pthread_attr_t *new_attr, pthread_attr_t *old_attr)
{
    struct sched_param param;
    void *addr;
    size_t size;
    int value;
    (void) pthread_attr_init(new_attr);
    if (old_attr != NULL) 
    {
        (void) pthread_attr_getstack(old_attr, &addr, &size);
        // don¡¯t allow a non-NULL thread stack address 
        (void) pthread_attr_setstack(new_attr, NULL, size);
        (void) pthread_attr_getscope(old_attr, &value);
        (void) pthread_attr_setscope(new_attr, value);
        (void) pthread_attr_getinheritsched(old_attr, &value);
        (void) pthread_attr_setinheritsched(new_attr, value);
        (void) pthread_attr_getschedpolicy(old_attr, &value);
        (void) pthread_attr_setschedpolicy(new_attr, value);
        (void) pthread_attr_getschedparam(old_attr, &param);
        (void) pthread_attr_setschedparam(new_attr, &param);
        (void) pthread_attr_getguardsize(old_attr, &size);
        (void) pthread_attr_setguardsize(new_attr, size);
    }
    // make all worker threads be detached threads 
    (void) pthread_attr_setdetachstate(new_attr, PTHREAD_CREATE_DETACHED);
}

