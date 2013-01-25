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
* based on sun's multithread programming guide.pdf 
*/
#include <pthread.h>
#include <signal.h>
#include <list>
typedef unsigned int uint_t;
typedef struct job job_t;
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
    static ThreadPool * Create(uint_t min_threads, uint_t max_threads, uint_t linger, pthread_attr_t *attr);

    int AddWork(void *(*func)(void *), void *arg);

   
    static void Destroy(ThreadPool * pool, int timeout_sec);
   
    void PoolWait(int timeout);
private:
    ThreadPool(uint_t min_threads, uint_t max_threads, uint_t linger, pthread_attr_t *attr);
    ~ThreadPool();
  
    static void WorkerCleanup(void *param);

    void NotifyWaiters();

    static void JobCleanup(void *param);

    int CreateWorker();

    static void * WorkerThreadRun(void *pool);

    static void CloneAttributes(pthread_attr_t *new_attr, pthread_attr_t *old_attr);

private:
    pthread_mutex_t m_pool_mutex; // protects the pool data
    pthread_cond_t m_pool_busycv; // synchronization in pool_queue  
    pthread_cond_t m_pool_workcv; // synchronization with workers
    pthread_cond_t m_pool_waitcv; // synchronization in PoolWait all the work down, no active work thread
    std::list<pthread_t> m_active_threads;   // list of threads performing work 
    std::list<job*>         m_works;  // job queue      
    pthread_attr_t m_thread_attr; // attributes of the workers
    volatile int m_pool_flags; // see below 
    sigset_t m_fillset;
    volatile uint_t m_pool_linger; // seconds before idle workers exit 
    volatile int m_pool_minimum; // minimum number of worker threads 
    volatile int m_pool_maximum; // maximum number of worker threads 
    volatile int m_pool_nthreads; // current number of worker threads
    volatile int m_pool_idle; //number of idle workers 
};
#endif

