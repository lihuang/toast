/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include <dirent.h>
#include <fstream>
#include <string>
#include <list>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/stat.h>
#include <map>


#include "../log/Log.h"
#include "../daemon/Daemon.h"
#include "../config/SimpleConfig.h"
//#include "../util/StringUtil.h"
//#include "../util/CMstat.h"
//#include "../util/common/Common.h"
#include "../threadpool/linux/multiworkqueuethreadpool.h"
#include "ToastEngine.h"
#include "ToastThread.h"
#include "../thread/threadbase.h"
#include "timertaskmanager.h"
#include "systemconfig.h"
#include "webinterface.h"
#include "taskjobmanager.h"
#include "connectionmanager.h"
#include "agentlist.h"

using namespace std;

TimerTaskManager *g_timer_task_manager;
extern MultiWorkQueueThreadPool* g_agent_response_threads;
toast::SystemConfig *g_config;
namespace toast
{
    ToastEngine* ToastEngine::m_instance = NULL;
    ToastEngine* ToastEngine::instance()
    {
        if(m_instance == NULL)
        {
            m_instance = new (std::nothrow)ToastEngine();
        }
        return m_instance;
    }

    bool ToastEngine::GetInfoFromConfig()
    {
        return true;
    }
    bool ToastEngine::LoadConfig(const string& filePath)
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

        try
        {
            g_config->monitor_path = SimpleConfig::Instance()->getStringValue("monitor_path", "/tmp/toast/");
            g_config->rrd_path       = SimpleConfig::Instance()->getStringValue("rrd_path", "/tmp/rra");
	     DIR * dir = opendir(g_config->rrd_path.c_str());
	     if(!dir)      // if the folder is not exist, create it
	     	{
                    Log::Info("There is  no rrd directory " + g_config->rrd_path);
                    Log::Info("Create a new directory");
	     	    if(mkdir(g_config->rrd_path.c_str(), S_IRWXU|S_IRWXG|S_IRWXO) != 0)
	     	    	{
	     	    	     Log::Error("Create rrd directory failed");
			     exit(1);
	     	    	}
	     	}
		 else
		 	closedir(dir);
            g_config->num_response_process_threads = SimpleConfig::Instance()->getIntegerValue("response_thread_num", 6);
            g_config->log_path = SimpleConfig::Instance()->getStringValue("log_path", ".");
	     dir = opendir(g_config->log_path.c_str());
	     if(!dir)      // if the folder is not exist, create it
	     	{
                    Log::Info("There is no log directory " + g_config->log_path);
                    Log::Info("Create a new log directory");
	     	    if(mkdir(g_config->log_path.c_str(), S_IRWXU|S_IRWXG|S_IRWXO) != 0)
	     	    	{
	     	    	     Log::Error("Create log directory failed, please create the folder manual and restart controller");
			     exit(1);
	     	    	}
	     	}
		else
			closedir(dir);
	     g_config->root_url = SimpleConfig::Instance()->getStringValue("root_url", "http://toast url/");
            g_config->task_list_url = SimpleConfig::Instance()->getStringValue("task_list_url", "job/getallruntime");
            g_config->update_agent_url = SimpleConfig::Instance()->getStringValue("agent_info_url", "machine/updatemachine");
	     g_config->update_all_agent_url = SimpleConfig::Instance()->getStringValue("update_all_agent_url", "machine/updateallmachine");
	     g_config->update_all_run_url = SimpleConfig::Instance()->getStringValue("update_all_run_url", "run/updateallrun");
	     g_config->update_run_url = SimpleConfig::Instance()->getStringValue("update_run_url", "run/updaterun");
            g_config->run_timer_task_url = SimpleConfig::Instance()->getStringValue("run_timer_task_url", "api/runtaskbyid");
	     g_config->CI_agent = SimpleConfig::Instance()->getStringValue("CIAgent", "ciagent name");
		}
        catch(...)
        {
            Log::Error("Failed to get socket/group information from config file ");
            return false;
        }

        return true;
    }
    bool ToastEngine::ReLoadConfig(const string& filePath)
    {
        return true;
    }

    ToastEngine::ToastEngine()
    {
    }

    ToastEngine::~ToastEngine()
    {
    }
    void ToastEngine::Initlize()
    {

	WebInterfaces::SetAllAgentToDown();
	WebInterfaces::SetAllRunToComplete();

        // initlize timer task
        g_timer_task_manager = new (std::nothrow)TimerTaskManager();
	if(!g_timer_task_manager)
		{
		Log::Error("Create timer task manager failed");
		return;
		}
	int timer_task_counter = WebInterfaces::GetTimerTaskList();
        Log::Info("There are %d timer tasks", timer_task_counter);
        ActiveAgentsManager::Instance();
        TaskRunManager::Instance()->Initlize();
    }
    void ToastEngine::run()
    {
        g_config = new (std::nothrow)toast::SystemConfig;
        LoadConfig(Daemon::Instance()->config);

        int lastTime = time(NULL);
        // initlize global varables 
        Initlize();

        // Create agent response thread;
        g_agent_response_threads = MultiWorkQueueThreadPool::Create(g_config->num_response_process_threads,  NULL);

        CreateFunctionalThreads();

        Log::Debug("All the thread created " );
        while(false==Daemon::Instance()->IsStop())
        {
            int now = time(NULL);
            usleep(1000000);
        }
        KillThreads(m_OtherThreadPool);
    }

    void ToastEngine::CreateFunctionalThreads()
    {
        m_OtherThreadPool.push_back(new (std::nothrow)FrontCommandProcessorThread());
        m_OtherThreadPool.push_back(new (std::nothrow)TimerThread());
        m_OtherThreadPool.push_back(new (std::nothrow)CommThread());
        StartThreads(m_OtherThreadPool);
        Log::Notice("Start other threads in Other Thread Pools");
    }

    void ToastEngine::StartThreads(vector<Thread*>& pool)
    {
        for(size_t i=0; i<pool.size(); i++)
        {
            pool[i]->Start();
            usleep(1000);
        }
    }

    void ToastEngine::KillThreads(vector<Thread*>& pool)
    {
        for(size_t i = 0; i<pool.size(); i++)
        {
            pool[i]->RequestStop();
        }
        MultiWorkQueueThreadPool::Destroy(g_agent_response_threads, 10);
	 int timeout = 30;
        for(size_t i=0; i<pool.size()&&timeout; i++)
        {
            while(!pool[i]->IsStoped()&&timeout)
            {
                Log::Info("waiting for thread stop...");
		  timeout--;
                sleep(1);
            }
        }
	if(timeout)
		{
		Log::Info("Normal exit");
		exit(0);
		}
	else
		{
		Log::Info("Abnormal exit");
	_exit(1);
		}
    }
}
