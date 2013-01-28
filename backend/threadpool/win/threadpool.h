/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef THREADPOOL_H
#define THREADPOOL_H
/*
* Declarations for the clients of a thread pool.
* based on sun's multithread programming guide.pdf 
*/
#include <windows.h>
#include <list>
typedef unsigned int uint_t;
typedef struct job job_t;
typedef void *(*JobFunc)(void *); /* function to call */
struct job 
{
    void *(*job_func)(void *); /* function to call */
    void *job_arg; /* its argument */
};
/* pool_flags */
#define POOL_WAIT 0x01 /* waiting in thr_pool_wait() */
#define POOL_DESTROY 0x02 /* pool is being destroyed */
class ThreadPool
{
public:
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
    static ThreadPool * Create(uint_t min_threads, uint_t max_threads, uint_t linger);
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
    int AddWork(void *(*func)(void *), void *arg);

    /*
    * Cancel all queued jobs and destroy the pool.
    */
    void Destroy();

    /*
    * Wait for all queued jobs to complete.
    */
    void PoolWait();

private:
    ThreadPool(uint_t min_threads, uint_t max_threads, uint_t linger);
    /*
    * Worker thread is terminating. Possible reasons:
    * - excess idle thread is terminating because there is no work.
    * - thread was cancelled (pool is being destroyed).
    * - the job function called pthread_exit().
    * In the last case, create another worker thread
    * if necessary to keep the pool populated.
    */
    static void WorkerCleanup(void *param);

    void NotifyWaiters();

    /*
    * Called by a worker thread on return from a job.
    */
    static void JobCleanup(void *param);

    int CreateWorker();

    static unsigned __stdcall  WorkerThreadRun(void *pool);

private:

    CRITICAL_SECTION m_pool_critical_section;     /* protects the pool data */
    HANDLE           m_destroy_event;    /* when destroy the pool set this event */
    HANDLE           m_pool_work_event;  /* work thrad wait at this event for work to do, auto event */
    HANDLE           m_pool_busy_event;  /* when last work thread is about complete, set this event, 
                                         inform the controller thread the thread pool is destroyed */
    HANDLE           m_poll_wait_event;  /* when all the working is down, and there is no active thread, 
                                         and the pool flag is POOL_WAIT(some thread is waiting for all the work down), set this event */
    std::list<DWORD> m_active_threads;   /* list of threads performing work DWORD WINAPI GetCurrentThreadId(void);*/
    std::list<job*>         m_works;  /* job queue */      
    volatile int m_pool_flags; /* see below */
    uint_t m_pool_linger; /* seconds before idle workers exit */
    volatile int m_pool_minimum; /* minimum number of worker threads */
    volatile int m_pool_maximum; /* maximum number of worker threads */
    volatile int m_pool_nthreads; /* current number of worker threads */
    volatile int m_pool_idle; /* number of idle workers */
};
#endif
