/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "multiworkqueuethreadpool.h"
#include <pthread.h>
#include <vector>
#include <errno.h>

using namespace std;
MultiWorkQueueThreadPool * MultiWorkQueueThreadPool::Create(uint_t num_threads, pthread_attr_t *attr)
{
    MultiWorkQueueThreadPool *pool;
    if (num_threads < 1) 
    {
        errno = EINVAL;
        return (NULL);
    }
    if((pool = new (std::nothrow)MultiWorkQueueThreadPool(num_threads, attr)) == NULL)
    {
        errno = ENOMEM;
        return (NULL);
    }
    for(unsigned int i = 0; i < num_threads; i++)
    {
       pool-> m_threads[i] = WorkerThread::Create(attr);
    }
    return (pool);
}

void MultiWorkQueueThreadPool::Destroy(MultiWorkQueueThreadPool * pool, int timeout_sec)
{
    for(unsigned int i= 0; i < pool->m_num_threads; i++)
    {
        if(timeout_sec == -1)
        	{
        WorkerThread::Destroy(pool->m_threads[i], timeout_sec);
        	}
		else
			{
        WorkerThread::Destroy(pool->m_threads[i], timeout_sec/pool->m_num_threads);
			}
        pool->m_threads[i] = NULL;
    }
}

int MultiWorkQueueThreadPool::AddWork(uint_t index, void *(*func)(void *), void *arg)
{    
    if(index >= m_num_threads)
		return -1;
    return m_threads[index]->AddWork( func,  arg);	
}
// Wait for all queued jobs to complete.
void MultiWorkQueueThreadPool::PoolWait(int timeout_sec)
{
    for(unsigned int i = 0; i < m_num_threads; i++)
    {
       if(timeout_sec == -1)
       	{
       	m_threads[i]->WaitWorkerThread(timeout_sec);
       	}
	   else
	   	{
        m_threads[i]->WaitWorkerThread(timeout_sec/m_num_threads);
	   	}
    }
}
MultiWorkQueueThreadPool::MultiWorkQueueThreadPool(uint_t num_threads, pthread_attr_t *attr)
{
    m_threads.resize(num_threads);
    m_num_threads = num_threads;
}
MultiWorkQueueThreadPool::~MultiWorkQueueThreadPool()
{
}

