/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

// agent list ∞¥≥¨ ±÷µ≈≈–Ú
#include "agentlist.h"
#include "../net/buffer.h"
#include "singleton.h"
#include "sendpacket.h"
#include <pthread.h>
#include <sys/socket.h>
#include "../log/Log.h"
#include "../poll/epollmanager.h"
#include "taskjobmanager.h"
#include "webinterface.h"
extern int CloseProcessing(EpollManager *epoll_manager, int fd);
//const int m_default_timeout = 60; // default time out 60 seconds
ActiveAgentsManager::ActiveAgentsManager()
{
    Log::Debug("ActivAgentsManager Creating ");
    m_running_list.clear();
    m_fd_info.clear();
    pthread_mutex_init(&m_running_list_mutex, NULL);
}

ActiveAgentsManager::~ActiveAgentsManager()
{
    Log::Debug("ActivAgentsManager Destroing ");
    LockList();
    list<AgentInfo *>::iterator iter = m_running_list.begin();
    if(iter != m_running_list.end())
    {
        delete *iter;
    }
    m_running_list.clear();
    m_fd_info.clear();
     UnlockList();
    pthread_mutex_destroy(&m_running_list_mutex);
}
AgentInfo *ActiveAgentsManager::FindByName(const string &name)
{
    list<AgentInfo*>::iterator iter = m_running_list.begin();
    while(iter != m_running_list.end() && (*iter)->name!= name)
    {
        iter++;
    }
    if(iter == m_running_list.end())
    {
        return NULL;
    }
    else
    {
        return *iter;
    }

}
// if the fd is not in the list, return NULL
AgentInfo *ActiveAgentsManager::FindByFD(int fd)          // find the agent from the list base on the fd.
{
    map<int, AgentInfo*>::iterator iter;
    iter = m_fd_info.find(fd);
    if(iter != m_fd_info.end())  
    {
        return iter->second;
    }
    return NULL;
}   

AgentInfo *ActiveAgentsManager::FindByIP(const string &ip)
{
    list<AgentInfo*>::iterator iter = m_running_list.begin();
    while(iter != m_running_list.end() && (*iter)->ip != ip)
    {
        iter++;
    }
    if(iter == m_running_list.end())
    {
        return NULL;
    }
    else
    {
        return *iter;
    }
}
extern EpollManager *g_epoll_manager;
// new add node's time is max, so just instert add to the list end
// 0 add ok -1 error occure
int  ActiveAgentsManager::InsertAgent(int fd, const string &ip, int timeout)
{
    AgentInfo *oldInfo = FindByIP(ip);
    if(oldInfo)
    {
        Log::Info("Agent ip %s already connected, close the old connection", ip.c_str());
        return -1;
    }
    AgentInfo *agent = new (std::nothrow)AgentInfo();
    if(!agent)
    {
        //log out of memory and return
        Log::Debug( "out of memeory");
        return -1;
    }
    SendBuffer *send_buffer = new (std::nothrow)SendBuffer();
    if(!send_buffer)
    {
        delete agent;
        Log::Debug( "out of memeory");
        return -1;
    }
    ReceiveBuffer *receive_buffer = new (std::nothrow)ReceiveBuffer();
    if(!receive_buffer)
    {
        delete agent;
        delete send_buffer;
        Log::Debug( "out of memeory");
        return -1;
    }
    agent->fd = fd;
    agent->ip = ip;
    agent->timeout = timeout;
    agent->send_buffer = send_buffer;
    agent->receive_buffer = receive_buffer;      
    // check the agent is exist or not
    // if not exist, insert to the database, otherwise get the id
    LockList();
    m_running_list.push_back(agent);
    m_fd_info[fd] = agent;
    UnlockList();
    Log::Debug( "Insert agent fd %d, total agents %d ", fd, m_running_list.size());
    return 0;
}
// If there is agent fd, than delete otherwise nothing to do
int ActiveAgentsManager::DeleteAgent(int fd)
{
    AgentInfo *agent = NULL;
    list<AgentInfo *>::iterator iter = InnerSearch(fd);
    if(iter != m_running_list.end())
    {
        agent = *iter;
        m_running_list.erase(iter);
        m_fd_info.erase(fd);
        delete agent->send_buffer;       // When delete buffer, the send buffer may include some command, thest command's status need to be set
        // update this machine's command status
        delete agent->receive_buffer;
        delete agent;
    }
    return 0;
}

// check if there are timeout machine, delete the machine from the list, and get next timeout value.
int ActiveAgentsManager::CheckTimeout(EpollManager *epoll_manager)     // find out timeout machine, and get next timeout value 
{
    time_t current_time = time(NULL);  // need not error checking
    if(m_running_list.empty())
        return INFINITE_TIMEOUT;       // if there is no connection timeout is infinite

    AgentInfo *agent;
    // processing the time outed agent's delete it from the list
    while(m_running_list.size())
    {
        agent = m_running_list.front();
        if(agent->timeout <= current_time)
        {
            // there is timeout agents
            Log::Info("Agent %s is timeout, deleted it, total agents %d", agent->name.c_str(), m_running_list.size());
            // delete this agent from the list
            // 1 close the connection and fd
            LockList();
            CloseProcessing(epoll_manager, agent->fd);
            UnlockList();
            //shutdown(agent->fd, SHUT_RDWR);
            //close(agent->fd);
            //DeleteAgent(agent->fd);
            // need delete from the epoll events 
        }
        else
        {
            break;
        }   

    }
    if(m_running_list.empty())
        return INFINITE_TIMEOUT;
    return (m_running_list.front()->timeout - current_time);
}
// update the time out value of fd
// 0 success -1 no agent
int ActiveAgentsManager::UpdateTimeout(int fd)
{
    time_t current_time = time(NULL);  // need not error checking
    AgentInfo *agent = DeleteFromList(fd);
    if(agent)
    {
        agent->timeout = current_time + AGENT_HB_TIMEOUT;
        m_running_list.push_back(agent);
        return 0;
    }
    return -1;
}
 int ActiveAgentsManager::UpdateAgentName(int fd, const string &name)
 {
     int result = 0;
    LockList();
    AgentInfo *info = FindByFD(fd);
    if(info)
    	{
    info->name = name;
    	}
	else
    {
     Log::Error("update agent name error");
     result = 1;
    }
    UnlockList();
    return result;
 }

void ActiveAgentsManager::LockList()
{
    pthread_mutex_lock(&m_running_list_mutex);
}
void ActiveAgentsManager::UnlockList()
{
    pthread_mutex_unlock(&m_running_list_mutex);
}
list<AgentInfo *>::iterator ActiveAgentsManager::InnerSearch(int fd)
{
    list<AgentInfo *>::iterator iter = m_running_list.begin();
    while(iter != m_running_list.end())
    {
        if((*iter)->fd == fd)
        {
            break;
        }
        else
        {
            iter++;
        }
    }
    return iter;
}    
// remove the element which fd is fd and return the AgentInfo
AgentInfo * ActiveAgentsManager::DeleteFromList(int fd)
{
    AgentInfo *agent = NULL;
    list<AgentInfo *>::iterator iter = InnerSearch(fd);
    if(iter != m_running_list.end())
    {
        agent = *iter;
        m_running_list.erase(iter);
    }
    return agent;
}

