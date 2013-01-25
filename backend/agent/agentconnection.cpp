/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
#include "../net/packet.h"
#include "../net/buffer.h"
#include "../net/sendreceivedata.h"
#include "../log/Log.h"
#include "AgentThread.h"
#include "../thread/threadbase.h"
#include "agentconnection.h"
#include "agentinformation.h"
#include <stdlib.h>
#ifdef WIN32
#include <ws2tcpip.h>
#include <windows.h>
#include "../threadpool/win/threadpool.h"
#include "../trayicon/SystemTraySDK.h"
#include "../winagent/resource.h"
#else
#include "../threadpool/linux/threadpool.h"
#include "../poll/epollmanager.h"
#include <time.h>
#include <sys/socket.h>
#include <sys/select.h>
#include <sys/un.h>
#include <stdio.h>
#include <sys/types.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <netdb.h>
#include <list>
#include <errno.h>
#include <fcntl.h>
#endif
#define MAXLINE  1024
using namespace std;
typedef unsigned int uint32;

#ifndef WIN32
EpollManager *g_epoll_manager = NULL;
static volatile int g_connection_fd = 3;
#else
SOCKET g_connect_sock;
WSAEVENT g_connect_event;
extern CSystemTray g_TrayIcon;
#endif
extern void *ProcessingCommand(void *param);

Mutex *g_p_send_buffer_mutex = NULL;
static SendBuffer *g_send_buffer;
// receive buffer need not lock, it only used in communication thread
static ReceiveBuffer *g_receive_buffer;

// is the client connected to the server 0 unconnected 1 connected
// this flag only used in the heart beat thread, and need not sync
// It's doesn't matter to send more than one heartbeat information
Mutex *g_p_is_connected_mutex = NULL;
volatile int g_is_connected = 0;

// there is bug, but it doesn't matter, when the client is start the connection fd 3 may not opend, 
// the send data just add to the send buffer, and the set fd is invalidate
extern ThreadPool *g_process_command_threads;
//EpollManager *g_epoll_manager;
extern  string serverhost;
extern  string serverport;

NetThread::NetThread()
{
} 

int InitConnectData()
{
    g_p_send_buffer_mutex = new (std::nothrow)Mutex;
    g_p_is_connected_mutex = new (std::nothrow)Mutex;
    return 0;
}
void SetIsConnected(int is_connected)
{
    g_p_is_connected_mutex->acquire();
    g_is_connected = is_connected;
    g_p_is_connected_mutex->release();
}
#ifndef WIN32
static void SetFileCloseOnExec(int fd)
{
    int flags;
    flags = fcntl(fd, F_GETFD); 
    if (flags == -1)
    {
        Log::Error("Could not get flags forfile %d", fd);
    }
    else
    {
        flags |= FD_CLOEXEC; 
        if (fcntl(fd, F_SETFD, flags) == -1) 
            Log::Error("Could not set flags for file %d", fd);
    }
}

// create socket and connect to the server and return the socket fd
// connection to server until it successfully connected return the fd;
SOCKET NetThread::ConnectToServer(const char *host, const char *serv)
{
    int sockfd, n;
    struct addrinfo hints;
    struct addrinfo *res = NULL;
    struct addrinfo *res_save = NULL;
    Log::Debug("Trying to connect to server: %s, port: %s", host, serv);
    bzero(&hints, sizeof(struct addrinfo));
    hints.ai_family = AF_INET;
    hints.ai_socktype = SOCK_STREAM;
    hints.ai_protocol = IPPROTO_TCP;
    while(!IsRequestStop())
    {
        sleep(20);
        if((n = getaddrinfo(host, serv, &hints, &res)) != 0)
        {
            Log::Debug(gai_strerror(n));
            //freeaddrinfo(res);
            continue;
        }
        res_save = res;
        do
        {
            sockfd = socket(res->ai_family, res->ai_socktype, res->ai_protocol);
            if(sockfd < 0)
                continue;
            // set resue socket
            int resuse_addr = 1;
            setsockopt(sockfd, SOL_SOCKET, SO_REUSEADDR, (const void *)&resuse_addr, sizeof(resuse_addr));
            struct linger l;
	        l.l_onoff = 1;
	        l.l_linger = 0;
	        setsockopt(sockfd, SOL_SOCKET, SO_LINGER, (void*)&l, sizeof(l));

            if(connect(sockfd, res->ai_addr, res->ai_addrlen) == 0)
                break;
            close(sockfd);
        }while((res = res->ai_next) != NULL);

        if(res != NULL)
        {
            break;
        }
        else
        {
            freeaddrinfo(res_save);
            res_save = NULL;
            Log::Debug("Connection error sleep about 60 second and reconnection again");
	     // randium wait time 
	     int r = 1 + (int)(30.0 * (rand() / (RAND_MAX + 1.0)));
            sleep(30 + r);
        }
    }
    if(res_save)
        freeaddrinfo(res_save);
    Log::Debug("connect to server: %s, port: %s fd: %d", host, serv, sockfd);
    return (sockfd);
}
#else
void SetWrite()
{
    //WSAEventSelect(g_connect_sock, g_connect_event, FD_READ|FD_WRITE|FD_CLOSE);
}
void ClearWrite()
{
    //WSAEventSelect(g_connect_sock, g_connect_event, FD_READ|FD_CLOSE);
}
SOCKET NetThread::ConnectToServer(const char *host, const char *serv)
{
    SOCKET sockfd = -1;
    SOCKET n;
    struct addrinfo hints;
    struct addrinfo *res = NULL;
    struct addrinfo *res_save = NULL;
    Log::Debug("Trying to connect to server: %s, port: %s", host, serv);
    memset(&hints, 0, sizeof(struct addrinfo));
    hints.ai_family = AF_INET;
    hints.ai_socktype = SOCK_STREAM;
    hints.ai_protocol = IPPROTO_TCP;
    while(!IsRequestStop())
    {
        Sleep(20*1000);
        if((n = getaddrinfo(host, serv, &hints, &res)) != 0)
        {
            //  Log::Debug(gai_strerror(n));
            //freeaddrinfo(res);
            continue;
        }
        res_save = res;
        do
        {
            sockfd = socket(res->ai_family, res->ai_socktype, res->ai_protocol);
            if(sockfd < 0)
                continue;
            // set resue socket
            BOOL bOptVal = TRUE;
            setsockopt(sockfd, SOL_SOCKET, SO_REUSEADDR, (char *)&bOptVal, sizeof(bOptVal));
	            struct linger l;
	        l.l_onoff = 1;
	        l.l_linger = 0;
	        setsockopt(sockfd, SOL_SOCKET, SO_LINGER, (const char*)&l, sizeof(l));

            int con = connect(sockfd, res->ai_addr, res->ai_addrlen);
            if( con== 0)
                break;
            closesocket(sockfd);
        }while((res = res->ai_next) != NULL);

        if(res != NULL)
        {
            break;
        }
        else
        {
            freeaddrinfo(res_save);
            res_save = NULL;
            Log::Debug("Connection error sleep about 60 second and reconnection again");
			int r = 1 + (int)(30.0 * (rand() / (RAND_MAX + 1.0)));
            Sleep(30000 + r * 1000);
        }
    }
    if(res_save)
        freeaddrinfo(res_save);
    //Log::Debug("connect to server: %s, port: %s fd: %d", host, serv, sockfd);
    return (sockfd);
}
#endif

// 对于客户端，根据应用特点其接收的数据会比较少，发送的数据会比较多，只接收任务
// 很多东西需要发送给controller或前端
// always return 0
int CloseConnectionToServer(SOCKET fd)
{
    Log::Info("Server closed or other error occured, close the connection to server");
    SetIsConnected(0);
#ifdef WIN32
    ClearWrite();
    //shutdown(fd,SD_BOTH);
    closesocket(fd);
#else
    g_epoll_manager->Delete(fd);
    //shutdown(fd, SHUT_RDWR);
    close(fd);
#endif
    if(NULL != g_receive_buffer->data_buffer)
    {
        delete [] g_receive_buffer->data_buffer;
        g_receive_buffer->data_buffer = NULL;
        g_receive_buffer->data_pointer = 0;
        g_receive_buffer->header_pointer = 0;
    }
    CancelAllCommands();
    // delete the send buffer, and all data in send buffer
    g_p_send_buffer_mutex->acquire();
    if(NULL != g_send_buffer->current_send_data)
    {
        delete [] g_send_buffer->current_send_data;
        g_send_buffer->current_send_data = NULL;
        g_send_buffer->current_send_length = 0;
        g_send_buffer->current_send_pointer = 0;
    }
    g_send_buffer->number = 0;
    list<char *>::iterator send_list_iter = g_send_buffer->send_list.begin();
    while(send_list_iter != g_send_buffer->send_list.end())
    {
        delete[] (*send_list_iter);                   // delete the send_list elements
        send_list_iter++;
    }
    g_send_buffer->send_list.clear();
    g_p_send_buffer_mutex->release();

    return 0;
}
static void ProcessingData(char *data, int datalength)
{
    g_process_command_threads->AddWork(ProcessingCommand, data);
}
int ReceiveProcessing(SOCKET fd, ReceiveBuffer *receive_buffer)
{
    return ReceiveDataToBuffer(fd, 0, receive_buffer, ProcessingData);
}

// 0 success add to send buffer -1 no memory -2 the connection is closed
int SendPacket(const char *data, int datalength)
{
    int res = 0;
    char *buffer = new (std::nothrow)char[datalength + HEADER_LENGTH];
    if(buffer)
    {
        Packet *packet = (Packet*)(buffer);
        packet->length = datalength + 4;
        for(int i=0; i<datalength; i++)
        {
            buffer[HEADER_LENGTH + i] = *data++;
        }
        g_p_is_connected_mutex->acquire();
        if(g_is_connected)
        {
            // lock to update the send queue, 
            g_p_send_buffer_mutex->acquire();
            packet->number = g_send_buffer->number++;
            g_send_buffer->send_list.push_back(buffer);
            // becareful there are bug, g_connection_fd may invalidate when the server is closed, or reconnection
            // but it doesn't matter, the send thread just add the data to send buffer, and when connected the data will be 
            // send through the new connection fd, inlzlize the g_connection_fd to 3 is same reason
#ifdef WIN32
            SetWrite();
#else
            g_epoll_manager->AddWrite(g_connection_fd);
#endif
            g_p_send_buffer_mutex->release();
        }
        else
        {
            res = -2;
            Log::Error("The connection is closed, droup the packet");
            delete [] buffer;
        }
        g_p_is_connected_mutex->release();
    }
    else
    {
        res = -1;
        Log::Debug("Out of memory, can't new send buffer" );
    }
    return res;
}
// 1. get the out buffer lock
// 2. send the data out 
// 3. clear the epoll write event
// 4. free the out buffer lock
// 0 success -1 error
int SendProcessing(SOCKET fd, SendBuffer *send_buffer)
{
    g_p_send_buffer_mutex->acquire();
    int res = SendDataInBuffer(fd, send_buffer);
    g_p_send_buffer_mutex->release();
    return res;
}
#ifdef WIN32
void NetThread::Run()
{
    g_receive_buffer = new (std::nothrow)ReceiveBuffer();
    g_send_buffer = new (std::nothrow)SendBuffer();

    WSAEVENT event_array[1];
    g_connect_event = WSACreateEvent();
    if(WSA_INVALID_EVENT == g_connect_event)
    {
        Log::Error("Create WSAEVENT failed %d", GetLastError());
    }
CONNECTTOSERVER:
    g_TrayIcon.SetIcon(IDI_ICONRED);
    g_TrayIcon.SetTooltipText((LPCTSTR)"Toast agent disconnected!");
    g_connect_sock = ConnectToServer(serverhost.c_str(), serverport.c_str());
    if(!IsRequestStop()&&g_connect_sock != -1)
    {
        SetIsConnected(1);
        WSAEventSelect(g_connect_sock, g_connect_event, FD_READ|FD_WRITE|FD_CLOSE);
        event_array[0] = g_connect_event;
        AgentSystemInfo info;
        GetAgentInfo(&info);
        SendAgentInfo(info);
    }
    else
    {
        Log::Info("Request exist or error, socket fd is -1");
        return;
    }
    g_TrayIcon.SetIcon(IDI_ICONGREEN);
    g_TrayIcon.SetTooltipText((LPCTSTR)"Toast agent connected!");

    while(!IsRequestStop())
    {
        DWORD Index;

        Index = WSAWaitForMultipleEvents(1, event_array, FALSE, 1000, FALSE);
        if(Index == WSA_WAIT_FAILED)
        {
            Log::Error("WSAWaitForMultipleEvents error: %d", WSAGetLastError());
            continue;
            // error occure
        }
        if(Index == WSA_WAIT_TIMEOUT)
        {
            int res = SendProcessing(g_connect_sock, g_send_buffer);
            if(res == 1)
            {
                ClearWrite();
            }
            else if(res == -1) // some error occur, may be server is closed
            {
                ClearWrite();
                Log::Debug("Send data error");
                CloseConnectionToServer(g_connect_sock);
                goto CONNECTTOSERVER;
            }
            // ok we send data
        }
        else
        {
            Index = Index - WSA_WAIT_EVENT_0;
            WSANETWORKEVENTS network_event;
            if(0==WSAEnumNetworkEvents(g_connect_sock, g_connect_event, &network_event))
            {
                if(network_event.lNetworkEvents&FD_READ)
                {
                    if(network_event.iErrorCode[FD_READ_BIT] != 0)
                    {
                        // read error
                        // close connection and 
                        Log::Error("WSAEnumNetworkEvents read error: %d", WSAGetLastError());
                        CloseConnectionToServer(g_connect_sock);
                        goto CONNECTTOSERVER;
                    }
                    else
                    {
                        // ok we receive data
                        int res = ReceiveProcessing(g_connect_sock, g_receive_buffer);
                        if(0 == res || -1 == res) 
                        {
                            Log::Debug("Server close or error %d ", res);
                            CloseConnectionToServer(g_connect_sock);
                            goto CONNECTTOSERVER;
                        }
                    }
                }
                if(network_event.lNetworkEvents&FD_WRITE)
                {
                    if(network_event.iErrorCode[FD_WRITE_BIT] != 0)
                    {
                        // network error
                        Log::Error("WSAEnumNetworkEvents Write error: %d", WSAGetLastError());
                        CloseConnectionToServer(g_connect_sock);
                        goto CONNECTTOSERVER;
                    }
                    int res = SendProcessing(g_connect_sock, g_send_buffer);
                    if(res == 1)
                    {
                        ClearWrite();
                    }
                    else if(res == -1) // some error occur, may be server is closed
                    {
                        ClearWrite();
                        Log::Debug("Send data error");
                        CloseConnectionToServer(g_connect_sock);
                        goto CONNECTTOSERVER;
                    }
                    // ok we send data
                }
                if(network_event.lNetworkEvents&FD_CLOSE)
                {
                    Log::Error("WSAEnumNetworkEvents connection closed error: %d", WSAGetLastError());
                    CloseConnectionToServer(g_connect_sock);
                    goto CONNECTTOSERVER;
                }
            }
            else
            {
                // error occureWSAGetLastError
                CloseConnectionToServer(g_connect_sock);
                goto CONNECTTOSERVER;
            }
        }
    }

    CloseConnectionToServer(g_connect_sock);
}
#else
void NetThread::Run()
{
    g_receive_buffer = new (std::nothrow)ReceiveBuffer();
    g_send_buffer = new (std::nothrow)SendBuffer();
    g_epoll_manager = EpollManager::Create(16);

CONNECTION:
    g_connection_fd = ConnectToServer(serverhost.c_str(), serverport.c_str());   // blocked cnnection
    if(!IsRequestStop())
    {
        Log::Info("Connection to server success fd is: %d", g_connection_fd);
        // connection allocate the buffer
             int opt = fcntl(g_connection_fd, F_GETFL, 0);
            fcntl(g_connection_fd, F_SETFL, opt|O_NONBLOCK);   // set the fd to noblocked
            SetFileCloseOnExec(g_connection_fd);

	   g_epoll_manager->AddRead(g_connection_fd);

        SetIsConnected(1);

        // send system information to the controller,
        // set the connection flag to 1, the connection is established.
        AgentSystemInfo info;
        GetAgentInfo(&info);
        SendAgentInfo(info);
    }
    while(!IsRequestStop())
    {
        int n_events = g_epoll_manager->Poll(-1); //single para ms timeout
        if(IsRequestStop())
        {
            CloseConnectionToServer(g_connection_fd);
            break;
        }
        if(n_events == -1)
        {
            if(errno == EINTR)
            {
                Log::Info("Epoll wait interrupted by a event");
            }
            else
            {
                Log::Error("Epoll wait error %d ", errno);
            }
            continue;
        }
        epoll_event*  events = g_epoll_manager->GetReadinessEvents();
        int res = -1;
        for(int i = 0; i < n_events; i++)
        {
            if(events[i].events & EPOLLIN ) 
            {
                res = ReceiveProcessing(g_connection_fd, g_receive_buffer);

                if(0 == res || -1 == res) 
                {
                    Log::Error("Server close or error %d , error number %d", res, errno);
                    CloseConnectionToServer(g_connection_fd);
                    goto CONNECTION;
                }
            }
            if(events[i].events & EPOLLOUT)
            {
                g_p_send_buffer_mutex->acquire();
                res = SendDataInBuffer(g_connection_fd, g_send_buffer);
                if(res == 1)
                {
                    g_epoll_manager->DeleteWrite(events[i].data.fd);
                    g_p_send_buffer_mutex->release();			
                }
                else if(res == -1) // some error occur, may be server is closed
                {
                    g_p_send_buffer_mutex->release();
                    Log::Debug("Send data error %d", events[i].data.fd);
                    CloseConnectionToServer(g_connection_fd);
                    goto CONNECTION;
                }
                else
                {
                    g_p_send_buffer_mutex->release();
                }
            }
            if(events[i].events & EPOLLHUP || events[i].events & EPOLLERR)
            {
                Log::Debug("Epoll HUP or EPOLLERR %d", events[i].data.fd);
                CloseConnectionToServer(g_connection_fd);
                goto CONNECTION;
            }
        }
    }
}

#endif


	
