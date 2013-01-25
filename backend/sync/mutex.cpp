/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "mutex.h"

Mutex::Mutex()
{
#ifdef WIN32
    InitializeCriticalSection(&m_mutex);
#else
	pthread_mutex_init(&m_mutex,NULL);
#endif
}

Mutex::~Mutex()
{
#ifdef WIN32
    DeleteCriticalSection(&m_mutex);
#else
	pthread_mutex_destroy(&m_mutex);
#endif
}

void Mutex::acquire(void)
{
#ifdef WIN32
    EnterCriticalSection(&m_mutex);
#else
	pthread_mutex_lock(&m_mutex);
#endif
}

void Mutex::release(void)
{
#ifdef WIN32
    LeaveCriticalSection(&m_mutex);
#else
	pthread_mutex_unlock(&m_mutex);
#endif
}


