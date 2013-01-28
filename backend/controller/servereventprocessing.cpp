/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include "../net/tcpsocket.h"
#include "servereventprocessing.h"
#include "agentlist.h"
#include "../poll/epollmanager.h"
#include "../net/sendreceivedata.h"
#include "../log/Log.h"
#include "../threadpool/linux/threadpool.h"
#include <fcntl.h>
#include <errno.h>
#include <sys/socket.h>
#include <unistd.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <sys/epoll.h>
#include "taskjobmanager.h"
#include "webinterface.h"


using namespace std;
extern ThreadPool *g_agent_response_threads;
void DispatchResponse(char * data);
// 1. get the out buffer lock
// 2. send the data out 
// 3. clear the epoll write event
// 4. free the out buffer lock
// return 0 need send another time(send buffer is full), 1 all data sendout,  -1 error
// only called in communication thread
int SendProcessing(int fd)
{
    int res;
    AgentInfo *agent = ActiveAgentsManager::Instance()->FindByFD(fd);   
    if(!agent)
        res = -1;
    else
    {
        res = SendDataInBuffer(fd, agent->send_buffer);
    }
    return res;
}


// 0 success 1, no such agent
int CloseProcessing(EpollManager *epoll_manager, int fd)
{
    //When client is closed
    // 1. close the client connection fd
    // 2. remove the client from the active list and update the machine status to down
    // 3. remove the fd from epoll manager
    // 4. update the task that run in this connection
    epoll_manager->Delete(fd);
    //shutdown(fd, SHUT_RDWR);
    close(fd);
    // update machine status to down
    AgentInfo *agent = ActiveAgentsManager::Instance()->FindByFD(fd);
    if(agent)
    {
        WebInterfaces::UpdateMachineStatus(agent->name, AGENT_STATUS_DOWN);
        Log::Debug("Agent %s is closed, it's fd is  %d, total agents %d", agent->name.c_str(), fd, ActiveAgentsManager::Instance()->Size());
        TaskRunManager::Instance()->ProcessAgentDown(agent->ip);
        ActiveAgentsManager::Instance()->DeleteAgent(fd);
        return 0;
    }
    return 1;
}
void ProcessingData(char *data, int datalength)
{
    if(datalength == 0)
        return;

    if(data)
    {
        // Dispatch the agent response to the worker thread
        DispatchResponse(data);
    }
    else
    {
        Log::Error("There is no response data");
    }
}

int ReceiveProcessing(EpollManager *epoll_manager, int fd)
{
    //Log::Info("Receive data from fd %d", fd);
    AgentInfo *agent = ActiveAgentsManager::Instance()->FindByFD(fd);
    
    if(!agent)   // need more carefully design
    {
        epoll_manager->Delete(fd);
        //shutdown(fd, SHUT_RDWR);
        close(fd);
        return -1;
    }
    ReceiveBuffer *receive_buffer = agent->receive_buffer;

    // 0 end of file, the client is closed
    // 1 the receive buffer is empty
    // -1 error
    int res = ReceiveDataToBuffer(fd, fd, receive_buffer, ProcessingData);
    if(0 == res || -1 == res)  
    {
        Log::Debug("Receive end of file or error %d  fd %d ", res, fd);
        ActiveAgentsManager::Instance()->LockList();
        CloseProcessing(epoll_manager, fd);
        ActiveAgentsManager::Instance()->UnlockList();
    }
    else
    {
        ActiveAgentsManager::Instance()->UpdateTimeout(fd);
    }
    return res;
}
//
int NewConnection(EpollManager *epoll_manager, TCPSocket *listen_socket, unsigned int events)
{
    int connection_number = 0;
    if(events & EPOLLIN ) //new connection come
    {
        Log::Debug("Processing  new connections");
        int conn_fd;
        struct sockaddr client_addr;
        char hostname[NI_MAXHOST];
        char servbuf[NI_MAXSERV];
        time_t current_time = time(NULL);  // need not error checking
        // there are may not only one connection in the connecton queue
        while((conn_fd = listen_socket->Accept(&client_addr)) != -1)
        {
            char ipbuf[256];
	     char portbuf[16];
            inet_ntop(AF_INET, &(*(sockaddr_in*)(&client_addr)).sin_addr, ipbuf, sizeof(ipbuf));
            string ipstr(ipbuf);
	     short port = ntohs((*(sockaddr_in*)(&client_addr)).sin_port);
	     sprintf(portbuf, "%d", port);
	     string portstr(portbuf);
             Log::Info("New connection " + ipstr + portstr);
		 // add new ip to active list
            if(ActiveAgentsManager::Instance()->InsertAgent(conn_fd, ipstr, current_time + AGENT_HB_TIMEOUT) == 0)
            {
                //make the new connection no blocked   
                int opt = fcntl(conn_fd, F_GETFL, 0);
                fcntl(conn_fd, F_SETFL, opt | O_NONBLOCK);
                epoll_manager->AddRead(conn_fd);
                connection_number++;
            }
            else         // insert the agent to system and db failed
            {
                close(conn_fd);
            }
        }
    }
    return connection_number;
}

