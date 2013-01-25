/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include "epollmanager.h"
#include <unistd.h>
#include <stdlib.h>
#include <fcntl.h>
EpollManager::~EpollManager()
{
	close(m_epollfd);
	delete [] m_events;
}
EpollManager::EpollManager(int fd, int max_events, int max_fds):
  m_epollfd(fd),
  m_max_events(max_events),
  m_max_fds(max_fds),
  m_events(new (std::nothrow)epoll_event[max_events])
{
}
EpollManager* EpollManager::Create(int max_fds)
{
    int fd = epoll_create(max_fds);
    if (fd == -1)
    	{
    	    exit(1);
    	}
    int flags;
    flags = fcntl(fd, F_GETFD); 
    if (flags == -1)
    {
        exit(1);
    }
    else
    {
        flags |= FD_CLOEXEC; 
        if (fcntl(fd, F_SETFD, flags) == -1) 
		exit(1);
    }
    return new (std::nothrow)EpollManager(fd, EPOLL_MAX_EV, max_fds);
}
int  EpollManager::Poll(int msec)
{
	return epoll_wait(m_epollfd, m_events, m_max_events, msec);
}

int EpollManager::AddRead(int fd)
{
	return ControlEpoll(fd, GetFDEeventMask(fd) ? EPOLL_CTL_MOD : EPOLL_CTL_ADD, GetFDEeventMask(fd) | EPOLLIN);
}
int EpollManager::AddWrite(int fd)
{
	return ControlEpoll(fd, GetFDEeventMask(fd) ? EPOLL_CTL_MOD : EPOLL_CTL_ADD, GetFDEeventMask(fd) | EPOLLOUT);
}
int EpollManager::AddError(int fd)
{
	return ControlEpoll(fd,GetFDEeventMask(fd) ? EPOLL_CTL_MOD : EPOLL_CTL_ADD, GetFDEeventMask(fd) | EPOLLERR);
}
int EpollManager::DeleteRead(int fd)
{
   int mask = GetFDEeventMask(fd) & ~EPOLLIN;
   return ControlEpoll(fd, mask ? EPOLL_CTL_MOD : EPOLL_CTL_DEL, mask);
}
int EpollManager::DeleteWrite(int fd)
{
   int mask = GetFDEeventMask(fd) & ~EPOLLOUT;
   return ControlEpoll(fd, mask ? EPOLL_CTL_MOD : EPOLL_CTL_DEL, mask);
}
int EpollManager::DeleteError(int fd)
{
   int mask = GetFDEeventMask(fd) & ~EPOLLERR;
   return ControlEpoll(fd, mask ? EPOLL_CTL_MOD : EPOLL_CTL_DEL, mask);
}
int EpollManager::Delete(int fd)
{
	return ControlEpoll(fd, EPOLL_CTL_DEL, 0);
}
epoll_event *EpollManager::GetReadinessEvents()
{
	return m_events;
}
