/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef TOAST_THREAD_H
#define TOAST_THREAD_H
#ifdef WIN32
#include <process.h>
#include <Windows.h>
#else
#include <pthread.h>
#endif
class Thread
{
public:
    Thread();
    virtual ~Thread();
    virtual void Run() = 0;
    void Start();
    void RequestStop();
    int IsRequestStop(){return m_stopRequest;}
    int IsStoped(){return m_isStoped;}
    void Join();
private:
    void SetStoped(){m_isStoped = 1;}
    volatile int m_stopRequest;
    int m_isStoped;    
#ifdef WIN32
    HANDLE          m_ThreadID;
    static unsigned int WINAPI _Run(LPVOID inThread);
#else
    pthread_t       m_ThreadID;  
    static void* _Run(void *param);
#endif
};
#endif
