/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/*
* Declarations for the clients of a thread pool.
* Modified according sun's multithread programming guide.pdf 
*/
#include "threadpool.h"
#include <Windows.h>
#include <process.h> 
#include <list>
#include <iostream>
using namespace std;
/*
* Create a thread pool.
* min_threads: the minimum number of threads kept in the pool,
* always available to perform work requests.
* max_threads: the maximum number of threads that can be
* in the pool, performing work requests.
* linger: the number of seconds excess idle worker threads
* (greater than min_threads) linger before exiting.
* attr: attributes of all worker threads (can be NULL);
* can be destroyed after calling thr_pool_create().
* On error, thr_pool_create() returns NULL with errno set to the error code.
*/
ThreadPool * ThreadPool::Create(uint_t min_threads, uint_t max_threads, uint_t linger)
{
    ThreadPool *pool;
    if (min_threads > max_threads || max_threads < 1) 
    {
        errno = EINVAL;
        return (NULL);
    }
    if((pool = new (std::nothrow)ThreadPool(min_threads, max_threads, linger)) == NULL)
    {
        errno = ENOMEM;
        return (NULL);
    }
    return (pool);
}
/*
* Enqueue a work request to the thread pool job queue.
* If there are idle worker threads, awaken one to perform the job.
* Else if the maximum number of workers has not been reached,
* create a new worker thread to perform the job.
* Else just return after adding the job to the queue;
* an existing worker thread will perform the job when
* it finishes the job it is currently performing.
*
* The job is performed as if a new detached thread were created for it:
* pthread_create(NULL, attr, void *(*func)(void *), void *arg);
*
* On error, thr_pool_queue() returns -1 with errno set to the error code.
*/
int ThreadPool::AddWork(void *(*func)(void *), void *arg)
{
    job_t *job;
    if((job = new (std::nothrow)job_t) == NULL)
    {
        errno = ENOMEM;
        return (-1);
    }
    job->job_func = func;
    job->job_arg = arg;
    EnterCriticalSection(&m_pool_critical_section);
    m_works.push_back(job);
    if (m_pool_idle > 0)
        SetEvent(m_pool_work_event);
    else if (m_pool_nthreads < m_pool_maximum 
        && CreateWorker() == 0)
        m_pool_nthreads++;
    LeaveCriticalSection(&m_pool_critical_section);
    return (0);
}
/*
* Cancel all queued jobs and destroy the pool.
*/
typedef void (*PthreadCleanupPushCB)(void*);
void ThreadPool::Destroy()
{
    EnterCriticalSection(&m_pool_critical_section);
    /* mark the pool as being destroyed; wakeup idle workers */
    m_pool_flags |= POOL_DESTROY;
    SetEvent(m_destroy_event);
    LeaveCriticalSection(&m_pool_critical_section);
    /* the last worker to terminate will wake us up */
    while (m_pool_nthreads != 0)
    {
        WaitForSingleObjectEx(m_pool_busy_event, INFINITE, FALSE);
    }
    /*
    * There should be no pending jobs, but just in case...
    */
    m_works.clear();
}
/*
* Wait for all queued jobs to complete.
*/
void ThreadPool::PoolWait()
{
    EnterCriticalSection(&m_pool_critical_section);
    while(m_works.size() != 0 || m_active_threads.size() != 0)
    {
        m_pool_flags |= POOL_WAIT;
        LeaveCriticalSection(&m_pool_critical_section);
        WaitForSingleObjectEx(m_poll_wait_event, INFINITE, FALSE);
    }
}
ThreadPool::ThreadPool(uint_t min_threads, uint_t max_threads, uint_t linger)
{
    if (min_threads > max_threads || max_threads < 1) 
    {
        errno = EINVAL;
        return ;
    }

    InitializeCriticalSection(&m_pool_critical_section);
    m_destroy_event = CreateEvent(NULL, TRUE, FALSE, NULL);
    m_pool_work_event  = CreateEvent(NULL, FALSE, FALSE, NULL);  // this event is auto event
    m_pool_busy_event  = CreateEvent(NULL, TRUE, FALSE, NULL);
    m_poll_wait_event  = CreateEvent(NULL, TRUE, FALSE, NULL);
    m_active_threads.clear();
    m_works.clear();
    m_pool_flags = 0;
    m_pool_linger = linger;
    m_pool_minimum = min_threads;
    m_pool_maximum = max_threads;
    m_pool_nthreads = 0;
    m_pool_idle = 0;
}
/*
* Worker thread is terminating. Possible reasons:
* - excess idle thread is terminating because there is no work.
* - thread was cancelled (pool is being destroyed).
* - the job function called pthread_exit().
* In the last case, create another worker thread
* if necessary to keep the pool populated.
*/
void ThreadPool::WorkerCleanup(void *param)
{
    ThreadPool *pool = (ThreadPool *)param;
    --pool->m_pool_nthreads;
    if (pool->m_pool_flags & POOL_DESTROY) 
    {
        if (pool->m_pool_nthreads == 0)
            SetEvent(pool->m_pool_busy_event);
    } 
    else if(pool->m_works.size() > 0 && pool->m_pool_nthreads < pool->m_pool_maximum 
        && pool->CreateWorker() == 0)
    {
        pool->m_pool_nthreads++;
    }
    LeaveCriticalSection(&pool->m_pool_critical_section);
}
void ThreadPool::NotifyWaiters()
{
    if(m_works.size() == 0 && m_active_threads.size() == 0)
    {
        m_pool_flags &= ~POOL_WAIT;
        SetEvent(m_poll_wait_event);
    }
}
/*
* Called by a worker thread on return from a job.
*/
void ThreadPool::JobCleanup(void *param)
{
    ThreadPool *pool = (ThreadPool *)param;
    EnterCriticalSection(&pool->m_pool_critical_section);
    DWORD my_tid = GetCurrentThreadId();
    pool->m_active_threads.remove(my_tid);

    if (pool->m_pool_flags & POOL_WAIT)
        pool->NotifyWaiters();
    LeaveCriticalSection(&pool->m_pool_critical_section);
}
int ThreadPool::CreateWorker()
{
    HANDLE hThread;
    uint_t uThreadId;
#ifdef USE_WIN32API_THREAD
    hThread = CreateThread(NULL, 0, WorkerThreadRun, this, CREATE_SUSPENDED, &dwThreadId);
    ASSERT(NULL != hThread);

    if(NULL == hThread)
    {
        return false;
    }
#else
    hThread = (HANDLE)_beginthreadex(NULL, 0, WorkerThreadRun, this,  0, (UINT*)&uThreadId);  //CREATE_SUSPENDED

    if(INVALID_HANDLE_VALUE == hThread)
    {
        return false;
    }
#endif
    return 0;
}
unsigned __stdcall  ThreadPool::WorkerThreadRun(void *param)
{
    ThreadPool *pool = (ThreadPool *)param;
    void *arg;
    int timedout;
    job_t *job;
    void *(*func)(void *);
    DWORD wait_result;
    HANDLE hEvents[2] = {pool->m_pool_work_event, pool->m_destroy_event};
    /*
    * This is the worker¡¯s main loop. It will only be left
    * if a timeout occurs or if the pool is being destroyed.
    */
    //active.active_tid = pthread_self();
    while(1)
    {
        EnterCriticalSection(&pool->m_pool_critical_section);
        timedout = 0;
        pool->m_pool_idle++;
        if (pool->m_pool_flags & POOL_WAIT)
            pool->NotifyWaiters();
         while(pool->m_works.size() == 0 && !(pool->m_pool_flags & POOL_DESTROY))
        {
             if (pool->m_pool_nthreads <= pool->m_pool_minimum) 
            {
                LeaveCriticalSection(&pool->m_pool_critical_section);
                // waite for work to do or destroy the pool
                wait_result = WaitForMultipleObjectsEx(2, hEvents, FALSE, INFINITE, FALSE);
                switch(wait_result)
                {
                case WAIT_OBJECT_0: // work come
                    break;
                case WAIT_OBJECT_0 + 1: // destroy the pool
                    break;
                default:
                    break;
                }
            } 
            else 
            {
                // calc the timeout value
                if (pool->m_pool_linger == 0) 
                {
                    LeaveCriticalSection(&pool->m_pool_critical_section);
                    timedout = 1;
                    break;
                }
                else
                {
                    LeaveCriticalSection(&pool->m_pool_critical_section);
                    wait_result = WaitForMultipleObjectsEx(2, hEvents, FALSE, INFINITE, FALSE);
                    switch(wait_result)
                    {
                    case WAIT_OBJECT_0: // work come
                        break;
                    case WAIT_OBJECT_0 + 1: // destroy the pool
                        break;
                    case WAIT_TIMEOUT:
                        timedout = 1;
                        break;
                    default:
                        break;
                    }
                    if(timedout)  // timeout 
                        break;
                }
            }
            EnterCriticalSection(&pool->m_pool_critical_section);
        }
        
        if (timedout &&pool->m_pool_nthreads > pool->m_pool_minimum) 
        {
            LeaveCriticalSection(&pool->m_pool_critical_section);
            break;
        }
        pool->m_pool_idle--;
        if (pool->m_pool_flags & POOL_DESTROY)
        {
            WorkerCleanup(pool);  // live critical section in the worker cleanup
            break;
        }
        if(pool->m_works.size() > 0)
        {
            job = pool->m_works.front();
            pool->m_works.pop_front();
            timedout = 0;
            func = job->job_func;
            arg = job->job_arg;
            DWORD my_tid = GetCurrentThreadId();
            pool->m_active_threads.push_back(my_tid);
            LeaveCriticalSection(&pool->m_pool_critical_section);
            free(job);
            /*
            * Call the specified job function.
            */
            (void) func(arg);

            JobCleanup(pool); 
        }
    }
    return 0;
}
