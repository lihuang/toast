/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef MUTEX_H
#define MUTEX_H
#ifdef WIN32
#include <windows.h>
#else
#include <pthread.h>
#endif
class Mutex
{
public:
	Mutex();
	~Mutex();

	void acquire(void);
	void release(void);

private:
   Mutex & operator=(const Mutex&);	
   Mutex(const Mutex&);
private:
#ifdef WIN32
    CRITICAL_SECTION m_mutex;
#else
	pthread_mutex_t m_mutex;
#endif
};
#endif
