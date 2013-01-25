/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include "threadbase.h"
Thread::Thread():m_stopRequest(0), m_isStoped(0)
{
}
Thread::~Thread()
{
}
void Thread::Start()
{
#ifdef WIN32
    m_ThreadID = (HANDLE)_beginthreadex( NULL, 0, _Run, (void*)this, 0, NULL);
#else
    // the linux thread default is detached
    pthread_attr_t thAttr;
    pthread_attr_init(&thAttr);
    pthread_attr_setdetachstate(&thAttr, PTHREAD_CREATE_DETACHED); 
    pthread_create((pthread_t*)&m_ThreadID, &thAttr, _Run, (void*)this);
    pthread_attr_destroy(&thAttr);
#endif
}
void Thread::Join()
{
#ifdef WIN32
    WaitForSingleObject(m_ThreadID, INFINITE);
#else
    pthread_join((pthread_t)m_ThreadID, NULL);
#endif
}
#ifdef WIN32
unsigned int WINAPI Thread::_Run(LPVOID param)
#else
void* Thread::_Run(void *param)
#endif
{
    Thread* theThread = (Thread*)param;
    
    theThread->Run();
    theThread->SetStoped();
    return 0;
}
void Thread::RequestStop()
{
       m_stopRequest = 1;
}


