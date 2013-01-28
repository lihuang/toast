/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include "../net/tcpsocket.h"
#include "../poll/epollmanager.h"
#include "../log/Log.h"
#include "agentlist.h"
#include "servereventprocessing.h"
#include "connectionmanager.h"
#include <time.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <stdlib.h>
#include <stdio.h>
#include <sys/types.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <netdb.h>
#include <list>
#include <sys/epoll.h>
#include <errno.h>
#include <signal.h>
#include <sys/resource.h>
#define MAXLINE  1024
using namespace std;
EpollManager *g_epoll_manager;
// server should set max fd the process can open. because lots of sockets fd
// system default 1024. set to 65535
// 0 success -1 error
int SetFileLimit(int max_open_file)
{
    struct rlimit limit;
    limit.rlim_cur = limit.rlim_max = max_open_file;
    if(setrlimit(RLIMIT_NOFILE, &limit) == -1)
    {
        Log::Info("Set max open file numbers error");
        exit(1);
        return -1;
    }
    return 0;
}
void CommThread::Run(void )
{
    Log::Debug("CommThread: start now id %ld", pthread_self());

    SetFileLimit(65535);
    //Ignore the SIGPIPE signal w. This prevents the server from receiving the SIGPIPE
    //signal if it tries to write to a socket whose peer has been closed; instead, the
    //write() fails with the error EPIPE
    if (signal(SIGPIPE, SIG_IGN) == SIG_ERR)
    	{
    	    Log::Info("Set ignore the SIGPIPE failed");
        _exit(1) ;
    	}
    g_epoll_manager = EpollManager::Create(2048);
    if(g_epoll_manager == NULL)
    {
        Log::Debug("Create epoll handler failed");
        _exit(1);
    }
    TCPSocket *listen_socket = TCPSocket::CreateStreamListenSocket(INADDR_ANY, 16868, 1024);
    if(listen_socket == NULL)
    {
        Log::Debug("Create listen socket failed");
        _exit(1) ;
    }
    int listen_fd = listen_socket->GetFd();
    g_epoll_manager->AddRead(listen_fd);
    int epoll_timeout = -1;
    int timeout;
    int res;
    Log::Debug("Server create socket success!");
    // time_t current_time = time(NULL);
    while(!IsRequestStop())
    {
        int n_events = g_epoll_manager->Poll(epoll_timeout); //single para ms timeout
        if(IsRequestStop())
		break;
        if(n_events == -1)
        {
            if(errno == EINTR)
            {
                Log::Info("Epoll wait interrupted by a event");
            }
            else
            {
                Log::Info("Epoll wait error %d ", errno);
                //exit(1);       // never go there only if unknown error occur
            }
            continue;
        }
        epoll_event*  events = g_epoll_manager->GetReadinessEvents();
        for(int i = 0; i < n_events; i++)
        {
            if(events[i].data.fd == listen_fd)    //new connection come
            {
                NewConnection(g_epoll_manager, listen_socket, events[i].events);                
            }
            else  //data come read data
            {
                if(events[i].events & EPOLLIN ) 
                {
                    ReceiveProcessing(g_epoll_manager, events[i].data.fd);
                }
                if(events[i].events & EPOLLOUT)
                {
                    // 0 need send another time(send buffer is full), 1 all data sendout,  -1 error
                    // lock send
                    ActiveAgentsManager::Instance()->LockList();
                    res = SendProcessing(events[i].data.fd);
                    if(res == 1)
                    {
                        g_epoll_manager->DeleteWrite(events[i].data.fd);
                    }
                    else if(res == -1)
                    {
                        CloseProcessing(g_epoll_manager, events[i].data.fd);
                    }
       		     ActiveAgentsManager::Instance()->UnlockList();
                }
                if(events[i].events & EPOLLHUP || events[i].events & EPOLLERR)
                {
                    Log::Debug("Epoll HUP or EPOLLERR %d", events[i].data.fd);
                    ActiveAgentsManager::Instance()->LockList();	     
                    CloseProcessing(g_epoll_manager, events[i].data.fd);
       		     ActiveAgentsManager::Instance()->UnlockList();				
                }
            }
        }
        // update next timeout value
        timeout = ActiveAgentsManager::Instance()->CheckTimeout(g_epoll_manager);
        if(timeout == INFINITE_TIMEOUT)
        {
            epoll_timeout = timeout;
        }
        else
        {
            epoll_timeout = timeout * 1000;
        }
    }
    delete listen_socket;
    Log::Info("CommThread: run exit now");
}
CommThread::CommThread()
{
}

