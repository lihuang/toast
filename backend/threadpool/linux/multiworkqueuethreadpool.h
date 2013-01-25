/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef MULTIWORDQUEUETHREADPOOL_H
#define MULTIWORDQUEUETHREADPOOL_H
#include "workerthread.h"
#include <vector>
// 本ThreadPool 中的 每个Thread 都对应一个工作队列，该线程
// 只处理该工作队列中的任务，外部可以指定发送到那个工作队列中(
// 按编号）这个线程池一但创建，线程个数便固定，在线程执行过程中
// 不能修改
class MultiWorkQueueThreadPool
{
public:
    static MultiWorkQueueThreadPool * Create(uint_t num_threads, pthread_attr_t *attr);
    int AddWork(uint_t index, void *(*func)(void *), void *arg);
    static void Destroy(MultiWorkQueueThreadPool * pool, int timeout_sec);
    void PoolWait(int timeout_sec);
    uint_t GetThreadNumber()
    {
        return m_num_threads;
    }
private:
    MultiWorkQueueThreadPool(uint_t num_threads, pthread_attr_t *attr);
    ~MultiWorkQueueThreadPool();
private:
    std::vector<WorkerThread*> m_threads;
    uint_t       m_num_threads;
};
#endif
