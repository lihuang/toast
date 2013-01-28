/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <string>
#include "../log/Log.h"
//#include "../util/StringUtil.h"
#ifdef WIN32
#include "../threadpool/win/threadpool.h"
#else
#include "../daemon/Daemon.h"
#include "../threadpool/linux/threadpool.h"
#endif
#include "AgentThread.h"
#include "AgentEngine.h"
#include "agentconnection.h"
extern int InitConnectData();
extern int AgentThreadDataInit();
extern void CancelAllCommands();
using namespace std;
using namespace toast;

string serverhost;
 string serverport;

ThreadPool *g_process_command_threads = NULL;
namespace toast
{
bool InitConfigFile(string filePath)
{
        try
        {
            SimpleConfig::SetConfigFileName(filePath.c_str());
            SimpleConfig::Instance()->Init();
        }
        catch(...)
        {
            Log::Error("config file init failed");
            return false;
        }
	return true;
}

    AgentEngine* AgentEngine::m_instance = NULL;

    AgentEngine* AgentEngine::instance()
    {
        if(m_instance == NULL)
        {
            m_instance = new (std::nothrow)AgentEngine();
        }
        return m_instance;
    }

    bool AgentEngine::LoadConfig(const string& filePath)
    {
    /*
        try
        {
            SimpleConfig::SetConfigFileName(filePath.c_str());
            SimpleConfig::Instance()->Init();
        }
        catch(...)
        {
            Log::Error("config file init failed");
            return false;
        }*/
     
        try
        {
            ::serverhost  = SimpleConfig::Instance()->getStringValue("server", "server url");
            ::serverport = SimpleConfig::Instance()->getStringValue("port", "16868");
        }
        catch(...)
        {
            Log::Error("Failed to get socket/group information from config file ");
            return false;
        }

        return true;
    }

    AgentEngine::AgentEngine()
    {
        //  LoadConfig(Daemon::Instance()->config);   
        LoadConfig("./AgentDaemon.conf");
    }

    AgentEngine::~AgentEngine()
    {
    }
     NetThread *g_communication_thread;
     HBThread *g_heartbeat_thread;
    void AgentEngine::run()
    {
     //   Inform_Machine_Information();
	// Create the command process thread pool
	 InitConnectData();
        AgentThreadDataInit();
#ifdef WIN32
        g_process_command_threads = ThreadPool::Create(3, 1024, 300);
#else
        g_process_command_threads = ThreadPool::Create(3, 1024, 300,  NULL);
#endif
	// Create communication threads
        g_communication_thread = new (std::nothrow)NetThread();
        g_communication_thread->Start();
#ifdef WIN32
       Sleep(8000);
#else
       sleep(5);
#endif
	// Create heart beat thread( upload system information with fix interval)
	 g_heartbeat_thread = new (std::nothrow)HBThread();
        g_heartbeat_thread->Start();

#ifndef WIN32
        while(false==Daemon::Instance()->IsStop())
        {
            int now = time(NULL);
            sleep(1);
        }
	stop();
#endif
     
        }
    void AgentEngine::stop()
    	{
    	CancelAllCommands();
#ifdef WIN32
      g_process_command_threads->Destroy();
#else
       ThreadPool::Destroy(g_process_command_threads, 120);
#endif
        g_heartbeat_thread->RequestStop();
        g_communication_thread->RequestStop();
		
#ifdef WIN32
	  g_heartbeat_thread->Join();
	  g_communication_thread->Join();
#else
	 int timeout = 30;
        while(!g_heartbeat_thread->IsStoped() && timeout)
        	{
        	Log::Info("Waiting for heart beat stop...");
			sleep(1);
			timeout--;
        	}
	 int timeout1 = 30;
        while(!g_communication_thread->IsStoped() && timeout1)
        	{
        	Log::Info("Waiting for communication stop...");
			sleep(1);
			timeout1--;
        	}

	if(timeout&&timeout1)
		{
		Log::Info("Normal exit");
		exit(0);
		}
	else
		{
		Log::Info("Abnormal exit");
	_exit(1);
		}
#endif
    }
}
