/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef GUARD_H
#define GUARD_H
template<class T>
class Guard
{
public:
	Guard(T *lockable):m_lockable(lockable)
	{
		m_lockable->acquire();
	}

	~Guard()
	{
		m_lockable->release();
	}

private:
	T *m_lockable;
};
#endif
