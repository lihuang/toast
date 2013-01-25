/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

// agent list ∞¥≥¨ ±÷µ≈≈–Ú
#include "singleton.h"
#include <list>
#include <time.h>
#include <string>
#include <map>
#include <pthread.h>
#include "../agentcmdrsp/agentcmdrsp.h"
#ifndef AGENT_LIST_H
#define AGENT_LIST_H
using namespace std;
#define        AGENT_HB_TIMEOUT         (HBTIMEOUT*3)
#define        INFINITE_TIMEOUT          -1
struct SendBuffer;
struct ReceiveBuffer;
class EpollManager;
#define AGENT_STATUS_IDLE        0x00
#define AGENT_STATUS_RUNNING  0x01
#define AGENT_STATUS_DOWN      0x02
struct AgentInfo
{
    int           fd;                 // the tcp connection fd
    string        name;               // this machine's DNS name, if the dns name can't get, use ip string
    string        ip;
    time_t        timeout;            // the time out value, it's Seconds since 00:00:00, 1 Jan 1970 UTC
    SendBuffer    *send_buffer;       // send buffer 
    ReceiveBuffer *receive_buffer;    // Receive buffer
    AgentInfo()
    {
        fd = -1;
        name = "";
        timeout = -1;
        send_buffer = NULL;
        receive_buffer = NULL;
    }
    ~AgentInfo()
    {
        send_buffer = NULL;
        receive_buffer = NULL;
    }
};
class ActiveAgentsManager:public Singleton<ActiveAgentsManager>
{
    friend class Singleton<ActiveAgentsManager>;
public:
    // if the fd is not in the list, return NULL
    AgentInfo *FindByName(const string &name);
    AgentInfo *FindByFD(int fd);          // find the agent from the list base on the fd.
    AgentInfo *FindByIP(const string &ip);
    // if we can't find the name then return NULL
    int UpdateAgentName(int fd, const string &name);
    int  UpdateTimeout(int fd); // update the timeout value of the fd.
    // new add node's time is max, so just instert add to the list end
    // 0 add ok -1 error occure
    int  InsertAgent(int fd, const string &ip, int timeout);
    int DeleteAgent(int fd);
    int GetAgentId(const string &name);
    // check if there are timeout machine, delete the machine from the list, and get next timeout value.
    int CheckTimeout(EpollManager *epoll_manager);      // find out timeout machine, and get next timeout value 
    void LockList();
    void UnlockList();
    int Size()
    {
        return m_running_list.size();
    }
    list<AgentInfo *> * GetRunningList()
    {
        return &m_running_list;
    }
private:
    ActiveAgentsManager();
    ~ActiveAgentsManager();
    list<AgentInfo *> m_running_list;   // this list is sorted according the timeout value. 
    map<int, AgentInfo *>  m_fd_info;             // speed up the find from fd to AgentInfo
    //map<int, AgentInfo*> m_id_info;
    pthread_mutex_t   m_running_list_mutex;  // when send thread send data, need to lock this, and when the connection thread modify the list need also need lock
    //const int m_default_timeout;   // default timeout 60 s
    list<AgentInfo *>::iterator InnerSearch(int fd); 
    // remove the element which fd is fd and return the AgentInfo
    AgentInfo * DeleteFromList(int fd); 

};
#endif
