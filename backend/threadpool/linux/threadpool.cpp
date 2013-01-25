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
#include <pthread.h>
#include <signal.h>
#include <errno.h>
#include <list>
#include <time.h>
using namespace std;

// Create a thread pool.
// min_threads: the minimum number of threads kept in the pool,
// always available to perform work requests.
// max_threads: the maximum number of threads that can be
// in the pool, performing work requests.
// linger: the number of seconds excess idle worker threads
// (greater than min_threads) linger before exiting.
// attr: attributes of all worker threads (can be NULL);
// can be destroyed after calling thr_pool_create().
// On error returns NULL with errno set to the error code.

ThreadPool * ThreadPool::Create(uint_t min_threads, uint_t max_threads, uint_t linger, pthread_attr_t *attr)
{
    ThreadPool *pool;
    if (min_threads > max_threads || max_threads < 1) 
    {
        errno = EINVAL;
        return (NULL);
    }
    if((pool = new (std::nothrow)ThreadPool(min_threads, max_threads, linger, attr)) == NULL)
    {
        errno = ENOMEM;
        return (NULL);
    }
    return (pool);
}

// Cancel all queued jobs and destroy the pool.
// When the thread doesn't need any more pleast Destroy it

typedef void (*PthreadCleanupPushCB)(void*);
void ThreadPool::Destroy(ThreadPool * pool, int timeout_sec)
{
    struct timespec ts;
    (void) pthread_mutex_lock(&pool->m_pool_mutex);
    pthread_cleanup_push((PthreadCleanupPushCB)pthread_mutex_unlock, &pool->m_pool_mutex);
    // mark the pool as being destroyed; wakeup idle workers 
    pool->m_pool_flags |= POOL_DESTROY;
    (void) pthread_cond_broadcast(&pool->m_pool_workcv);
    // cancel all active workers 
    for(list<pthread_t>::iterator iter=pool->m_active_threads.begin(); iter != pool->m_active_threads.end(); iter++)
        (void) pthread_cancel(*iter);

    // the last worker to terminate will wake us up

    while(pool->m_pool_nthreads != 0)
    {
        if(timeout_sec == -1)
        {
             (void) pthread_cond_wait(&pool->m_pool_busycv, &pool->m_pool_mutex);
        }
        else
        {
            (void) clock_gettime(CLOCK_REALTIME, &ts);
            ts.tv_sec += timeout_sec;
            if(pthread_cond_timedwait(&pool->m_pool_busycv, &pool->m_pool_mutex, &ts) == ETIMEDOUT) 
            {
                break;
            }
        }
    }
    pthread_cleanup_pop(1); // pthread_mutex_unlock(&m_pool_mutex); 

    delete pool;
}

//Enqueue a work request to the thread pool job queue.
// If there are idle worker threads, awaken one to perform the job.
// Else if the maximum number of workers has not been reached,
// create a new worker thread to perform the job.
// Else just return after adding the job to the queue;
// an existing worker thread will perform the job when
// it finishes the job it is currently performing.

// The job is performed as if a new detached thread were created for it:
// pthread_create(NULL, attr, void *(*func)(void *), void *arg);

// On error, returns -1 with errno set to the error code.

int ThreadPool::AddWork(void *(*func)(void *), void *arg)
{
    int res = 0;
    job_t *job;
    if((job = new (std::nothrow)job_t) == NULL)
    {
        errno = ENOMEM;
        return (-1);
    }
    job->job_func = func;
    job->job_arg = arg;
    (void) pthread_mutex_lock(&this->m_pool_mutex);
    m_works.push_back(job);
    if (this->m_pool_idle > 0)
        (void) pthread_cond_signal(&this->m_pool_workcv);
    else if (this->m_pool_nthreads < this->m_pool_maximum 
        && CreateWorker() == 0)
        this->m_pool_nthreads++;
    (void) pthread_mutex_unlock(&this->m_pool_mutex);
    return res;
}


// Wait for all queued jobs to complete.
// wait timeout seconds -1 wait no timeout
void ThreadPool::PoolWait(int timeout_sec)
{
    struct timespec ts;
    (void) pthread_mutex_lock(&m_pool_mutex);
    pthread_cleanup_push((PthreadCleanupPushCB)pthread_mutex_unlock, &m_pool_mutex);
    while((!m_works.empty()) ||(! m_active_threads.empty()))
    {
        m_pool_flags |= POOL_WAIT;
        if(timeout_sec == -1)
        {
             (void) pthread_cond_wait(&m_pool_waitcv, &m_pool_mutex);
        }
        else
        {
            (void) clock_gettime(CLOCK_REALTIME, &ts);
            ts.tv_sec += timeout_sec;
            if(pthread_cond_timedwait(&m_pool_workcv, &m_pool_mutex, &ts) == ETIMEDOUT) 
            {
                break;
            }
        }
    }
    pthread_cleanup_pop(1); 
}
ThreadPool::ThreadPool(uint_t min_threads, uint_t max_threads, uint_t linger, pthread_attr_t *attr)
{
    (void) sigfillset(&m_fillset);
    (void) pthread_mutex_init(&m_pool_mutex, NULL);
    (void) pthread_cond_init(&m_pool_busycv, NULL);
    (void) pthread_cond_init(&m_pool_workcv, NULL);
    (void) pthread_cond_init(&m_pool_waitcv, NULL);
    m_active_threads.clear();
    m_works.clear();
    m_pool_flags = 0;
    m_pool_linger = linger;
    m_pool_minimum = min_threads;
    m_pool_maximum = max_threads;
    m_pool_nthreads = 0;
    m_pool_idle = 0;
    // We cannot just copy the attribute pointer.
    // We need to initialize a new pthread_attr_t structure using
    // the values from the caller-supplied attribute structure.
    // If the attribute pointer is NULL, we need to initialize
    // the new pthread_attr_t structure with default values.
    CloneAttributes(&m_thread_attr, attr);
}
ThreadPool::~ThreadPool()
{
    list<job*>::iterator iter = m_works.begin();
    while(iter != m_works.end())
    	{
        delete *iter;
	 iter++;
    	}
    m_works.clear();
    (void) pthread_mutex_destroy(&m_pool_mutex);
    (void) pthread_cond_destroy(&m_pool_busycv);
    (void) pthread_cond_destroy(&m_pool_workcv);
    (void) pthread_cond_destroy(&m_pool_waitcv);
    (void) pthread_attr_destroy(&m_thread_attr);
}

// Worker thread is terminating. Possible reasons:
// - excess idle thread is terminating because there is no work.
// - thread was cancelled (pool is being destroyed).
// - the job function called pthread_exit().
// In the last case, create another worker thread
//if necessary to keep the pool populated.

void ThreadPool::WorkerCleanup(void *param)
{
    ThreadPool *pool = (ThreadPool *)param;
    --pool->m_pool_nthreads;
    if (pool->m_pool_flags & POOL_DESTROY) 
    {
        if (pool->m_pool_nthreads == 0)
            (void) pthread_cond_broadcast(&pool->m_pool_busycv);
    } 
    else if(!pool->m_works.empty() && pool->m_pool_nthreads < pool->m_pool_maximum 
        && pool->CreateWorker() == 0)
    {
        pool->m_pool_nthreads++;
    }
    (void) pthread_mutex_unlock(&pool->m_pool_mutex);
}
void ThreadPool::NotifyWaiters()
{
    if(m_works.empty() && m_active_threads.empty())
    {
        m_pool_flags &= ~POOL_WAIT;
        (void) pthread_cond_broadcast(&m_pool_waitcv);
    }
}

// Called by a worker thread on return from a job.

void ThreadPool::JobCleanup(void *param)
{
    ThreadPool *pool = (ThreadPool *)param;
    pthread_t my_tid = pthread_self();

    (void) pthread_mutex_lock(&(pool->m_pool_mutex));
    pool->m_active_threads.remove(my_tid);

    if (pool->m_pool_flags & POOL_WAIT)
        pool->NotifyWaiters();
}
int ThreadPool::CreateWorker()
{
    sigset_t oset;
    int error;
    pthread_t thread;
    (void) pthread_sigmask(SIG_SETMASK, &m_fillset, &oset);
    error = pthread_create(&thread, &m_thread_attr, WorkerThreadRun, (void*)this);
    (void) pthread_sigmask(SIG_SETMASK, &oset, NULL);
    return (error);
}
void * ThreadPool::WorkerThreadRun(void *param)
{
    ThreadPool *pool = (ThreadPool *)param;
    void *arg;
    int timedout;
    job_t *job;
    void *(*func)(void *);
    struct timespec ts;
    // This is the worker¡¯s main loop. It will only be left
    // if a timeout occurs or if the pool is being destroyed.
    (void) pthread_mutex_lock(&pool->m_pool_mutex);
    pthread_cleanup_push(pool->WorkerCleanup, (void*)pool);
    //active.active_tid = pthread_self();
    while(1)
    {
        //We don¡¯t know what this thread was doing during
        // its last job, so we reset its signal mask and
        //cancellation state back to the initial values.
        (void) pthread_sigmask(SIG_SETMASK, &pool->m_fillset, NULL);
        (void) pthread_setcanceltype(PTHREAD_CANCEL_DEFERRED, NULL);
        (void) pthread_setcancelstate(PTHREAD_CANCEL_ENABLE, NULL);
        timedout = 0;
        pool->m_pool_idle++;
        if (pool->m_pool_flags & POOL_WAIT)
            pool->NotifyWaiters();
        while(pool->m_works.empty() && !(pool->m_pool_flags & POOL_DESTROY))
        {
            if (pool->m_pool_nthreads <= pool->m_pool_minimum) 
            {
                (void) pthread_cond_wait(&pool->m_pool_workcv, &pool->m_pool_mutex);
            } 
            else 
            {
                (void) clock_gettime(CLOCK_REALTIME, &ts);
                ts.tv_sec += pool->m_pool_linger;
                if (pool->m_pool_linger == 0 
                    ||pthread_cond_timedwait(&pool->m_pool_workcv, &pool->m_pool_mutex, &ts) == ETIMEDOUT) 
                {
                    timedout = 1;
                    break;
                }
            }
        }
        pool->m_pool_idle--;
        if (pool->m_pool_flags & POOL_DESTROY)
            break;
        if(!pool->m_works.empty())
        {
            job = pool->m_works.front();
            pool->m_works.pop_front();
            timedout = 0;
            func = job->job_func;
            arg = job->job_arg;
            pool->m_active_threads.push_back(pthread_self());
            (void) pthread_mutex_unlock(&pool->m_pool_mutex);
            pthread_cleanup_push(pool->JobCleanup, pool);
            delete job;

            //Call the specified job function.
            (void) func(arg);

            // If the job function calls pthread_exit(), the thread
            // calls JobCleanup(pool) and worker_cleanup(pool);
            // the integrity of the pool is thereby maintained.

            pthread_cleanup_pop(1); //JobCleanup(pool) 
        }
        if (timedout &&pool->m_pool_nthreads > pool->m_pool_minimum) 
        {
            //We timed out and there is no work to be done
            // and the number of workers exceeds the minimum.
            //Exit now to reduce the size of the pool.
            break;
        }
    }
    pthread_cleanup_pop(1); // worker_cleanup(pool)
    return (NULL);
}
void ThreadPool::CloneAttributes(pthread_attr_t *new_attr, pthread_attr_t *old_attr)
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
    // make all pool threads be detached threads 
    (void) pthread_attr_setdetachstate(new_attr, PTHREAD_CREATE_DETACHED);
}
