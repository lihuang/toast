/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#ifndef EPOLL_MANAGER_H
#define EPOLL_MANAGER_H
//int epoll_create(int size);  : size how many fds want to monitor
//int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event);
//int epoll_wait(int epfd, struct epoll_event * events, int maxevents, int timeout);
//The EpollManager create and manager the epoll function
#include <sys/epoll.h>
#include <map>
const int EPOLL_MAX_EV = 1024;
class EpollManager
{
public:
    static  EpollManager*   Create(int max_fds);
    ~EpollManager();

    int     Poll(int msec);
    epoll_event *GetEvents()
    {
        return m_events;
    }
    int     GetFD() { return m_epollfd; }

    int     GetOpenMax() const{return m_max_fds;}   //

    int    AddRead(int fd);   //EPOLL_CTL_ADD
    int    AddWrite(int fd);
    int    AddError(int fd);

    int    Delete(int fd);
    int    DeleteRead(int fd);
    int    DeleteWrite(int fd);
    int    DeleteError(int fd);
    epoll_event *GetReadinessEvents();
private:
    EpollManager(int fd, int max_events, int max_fds);  // max_events corresponse with maxevents int epoll_wait(int epfd, struct epoll_event * events, int maxevents, int timeout);
    EpollManager(const EpollManager &);
    EpollManager &operator=(const EpollManager&);
    int GetFDEeventMask(int fd)
    {
        if(m_fds.find(fd) == m_fds.end())   //the fd doesn't include in the fds
        {
            return 0;
        }
        else
            return m_fds[fd];
    }
    void SetFDEventMask(int fd, int mask)
    {
        m_fds[fd] = mask;
    }
    int ControlEpoll(int fd, int op, int ev_mask)
    {
        if (GetFDEeventMask(fd) == ev_mask)
            return 0;

        epoll_event ev;
        ev.data.u64 = 0;
        ev.data.fd = fd;
        ev.events = ev_mask;

        SetFDEventMask(fd, ev_mask);

        return epoll_ctl(m_epollfd, op, fd, &ev); 
    }

    int            m_epollfd;  //this epoll instance fd
    int            m_max_events;  //max events wait at epoll_wait
    int            m_max_fds;   //max number of fds need to monitor epoll_create(int size)
    std::map<int, unsigned int> m_fds;   //file descriptor that add to m_fd <int fd, int ev_mask>
    epoll_event*        m_events;       //epoll_wait(int epfd, struct epoll_event * events, int maxevents, int timeout); epoll_event parameter
};
#endif
