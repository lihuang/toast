/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <iostream>
#include <fstream>
#include <map>
#include <pthread.h>
#include <paths.h>
#include <unistd.h>
#include <dirent.h>
#include "../log/Log.h"
#include "../config/SimpleConfig.h"
#include "../threadpool/linux/multiworkqueuethreadpool.h"
#include "ToastEngine.h"
#include "ToastThread.h"
#include "sendpacket.h"
#include "../agentcmdrsp/agentcmdrsp.h"
#include "taskjobmanager.h"
#include "ToastEngine.h"
#include "agentlist.h"
#include "systemconfig.h"
#include "timertaskmanager.h"
#include "webinterface.h"
#include "rrdcontrol.h"
#include "jsoncommandprocess.h"
using namespace std;
using namespace toast;
extern TimerTaskManager *g_timer_task_manager;
MultiWorkQueueThreadPool* g_agent_response_threads;
const string TIMERTASK   = "TimerTask";
static int StringToInt(const string& str)
{
    stringstream ss(str);
    int res;

    ss >> res;
    if(!ss.fail())
    {
        return res;
    }
    return 0;
}
void *HeartBeatProcessing(void *data)
{
    int fd = *(int*)data;
	// find the agent 
    AgentInfo *info = ActiveAgentsManager::Instance()->FindByFD(fd);
    Log::Info("Agent %s heart beat", info->name.c_str());
    int dataLength = *((int*)((char*)data + 4)) - sizeof(AgentResponseHead);
    
    SystemPerformanceInfo *p_perf_info;
    p_perf_info = (SystemPerformanceInfo*)((char*)data + 4 +sizeof(AgentResponseHead));

    //Log::Debug("userTime: %d", p_perf_info->userTime);
    //Log::Debug("systemTime: %d", p_perf_info->systemTime);
    //Log::Debug("idleTime: %d", p_perf_info->idleTime);
    //Log::Debug("totalMemory: %d", p_perf_info->totalMemory);
   // Log::Debug("freeMemory: %d", p_perf_info->freeMemory);
    //Log::Debug("totalDiskSpace: %d", p_perf_info->totalDiskSpace);
   // Log::Debug("freeDiskSpace: %d", p_perf_info->freeDiskSpace);
   // Log::Debug("diskRead: %d", p_perf_info->diskRead);
    //Log::Debug("diskWrite: %d", p_perf_info->diskWrite);
    //Log::Debug("inBytes: %d", p_perf_info->inBytes);
   // Log::Debug("outBytes: %d", p_perf_info->outBytes);
   // Log::Debug("inPackets: %d", p_perf_info->inPackets);
   // Log::Debug("outPackets: %d", p_perf_info->outPackets);

    UpdateMemoryRRD(info->name, p_perf_info->totalMemory, p_perf_info->freeMemory);
    int total_time = p_perf_info->idleTime + p_perf_info->systemTime + p_perf_info->userTime;
    if(total_time != 0)
    	{
    int idle = (double)(p_perf_info->idleTime) / total_time * 100.0;
    int system = (double)(p_perf_info->systemTime) / total_time * 100.0;
    int user = (double)(p_perf_info->userTime) /total_time *100.0;

    UpdateCPURRD(info->name, idle, system, user);
    	}
    UpdateDiskRRD(info->name, p_perf_info->totalDiskSpace, p_perf_info->freeDiskSpace, p_perf_info->diskRead, p_perf_info->diskWrite);
    UpdateNetworkRRD(info->name, p_perf_info->inBytes, p_perf_info->outBytes, p_perf_info->inPackets, p_perf_info->outPackets);
    if(dataLength > sizeof(SystemPerformanceInfo) - 12)
    	{
    UpdateLoadRRD(info->name, p_perf_info->load1min, p_perf_info->load5min, p_perf_info->load15min);
    	}
    delete [] (char*)data;
    return 0;
}
void *AgentInformationProcessing(void *data)
{
    int fd = *(int*)data;
    AgentSystemInfo info;
    int tag;
    int data_index = 4;
    int information_length = *(int*)((char*)data+data_index);
    data_index = 4 + sizeof(AgentResponseHead);
    int field_length = 0;
    while(data_index < information_length + 4)
    {
        tag = *(int*)((char*)data + data_index); 
        data_index += 4;
        field_length = *(int*)((char*)data + data_index);
        data_index += 4;
        switch(tag)
        {
        case AGENT_INFORMATION_TAG_HOSTNAME:
            info.hostname = string((char*)((char*)data+data_index), field_length);
            data_index += field_length;
            break;
        case AGENT_INFORMATION_TAG_SYSTEM:
            info.system= string((char*)((char*)data+data_index), field_length);
            data_index += field_length;
            break;

        case AGENT_INFORMATION_TAG_RELEASE:
            info.release= string((char*)((char*)data+data_index), field_length);
            data_index += field_length;
            break;
        case AGENT_INFORMATION_TAG_VERSION:
            info.version= string((char*)((char*)data+data_index), field_length);
            data_index += field_length;
            break;

        case AGENT_INFORMATION_TAG_CPU:
            info.cpu= string((char*)((char*)data+data_index), field_length);
            data_index += field_length;
            break;

        case AGENT_INFORMATION_TAG_AGENT_VERSION:
            info.agent_version= string((char*)((char*)data+data_index), field_length);
            data_index += field_length;
            break;
        }
    }
    Log::Info("Agent Information %d: ", fd);
    // update agent information, update status in frontend
    if(!ActiveAgentsManager::Instance()->UpdateAgentName(fd, info.hostname))
    	{
    	 // get ip
    	 AgentInfo *agent_info = ActiveAgentsManager::Instance()->FindByFD(fd);
         WebInterfaces::UpdateMachineInfoStatusIdle(info, agent_info->ip);
    	}
    Log::Info(info.hostname + info.system + info.release + info.version + info.cpu + info.agent_version);
    delete [] (char*)data;
    return 0;
}
// processing the agent response
void *ProcessingResponse(void *param)
{
    AgentResponseHead*rsp = (AgentResponseHead*)((char*)param+4);
    // Log::Debug("Receive response %d length, type %d,  id %d", rsp->length, rsp->type, rsp->id);
    // get a message from the message list
    if(rsp->type == RESPONSE_COMMAND_LOG)
    {
        AgentResponseLog *rsp_log = (AgentResponseLog*)((char*)param+4);
        TaskRunManager::Instance()->TaskRunLog(rsp_log);
    }
    else if(rsp->type == RESPONSE_COMMAND_START)
    {
        TaskRunManager::Instance()->TaskRunStart((AgentResponseStart *)((char*)param+4));
    }
    else if(rsp->type == RESPONSE_COMMAND_RESULT)
    {
        TaskRunManager::Instance()->TaskRunCompleted((AgentResponseResult *)((char*)param+4));
    }
    delete [] (char*)param;
    return 0;
}

void DispatchResponse(char *data)
{
    AgentResponseHead*rsp = (AgentResponseHead*)(data+4);
    // Thread id 0 processing the heart beat
    // 1-n process the other response
    switch(rsp->type)
    {
    case RESPONSE_HEAETBEAT:
        g_agent_response_threads->AddWork(0, HeartBeatProcessing, data);
        break;
    case AGENT_INFORMATION:
        g_agent_response_threads->AddWork(0, AgentInformationProcessing, data);
        break;
    default:
        {
            unsigned int command_id = (rsp->id);
            unsigned int index= command_id % (g_agent_response_threads->GetThreadNumber() - 1) + 1;
            g_agent_response_threads->AddWork(index , ProcessingResponse, data);
        }
        break;
    }


}

FrontCommandProcessorThread::FrontCommandProcessorThread()
{}

void FrontCommandProcessorThread::Run(void)
{
    Log::Debug("FrontCommandProcessorThread: start now thread id %ld", pthread_self() );

    DIR* pdir;
    struct dirent* pdirent;

    pdir=opendir(g_config->monitor_path.c_str());
    if(pdir==NULL)
    {       
        Log::Error("Command file dir: %s is not created", g_config->monitor_path.c_str());
        exit(-1);
    }
    closedir(pdir);
    while(!IsRequestStop())
    {
        pdir = opendir(g_config->monitor_path.c_str());
        if(pdir == NULL)
        {
            Log::Error("Command file dir: %s is not created", g_config->monitor_path.c_str());
        }
        else
        {
            for(pdirent=readdir(pdir); pdirent!=NULL; pdirent=readdir(pdir))
            {
                if(strcmp(pdirent->d_name,".")==0||strcmp(pdirent->d_name,"..")==0) 
                    continue;
                // get a file
                // file abs name is g_config->monitor_path + pdirent->d_name
                string filename = g_config->monitor_path + string(pdirent->d_name);
                Log::Info("There is command file: " + filename);
                // open the file
                ifstream file(filename.c_str(),  ifstream::in);
                if (file.fail())
                {
                    Log::Error("Read int file error with filename: " + filename);
                    continue;
                }

                string requestStr = "";
                string temp;
                while (getline(file, temp))
                    requestStr += temp;
                
                file.close();

                Log::Debug("requestStr:\n" + requestStr);
                if(requestStr.length() == 0)
                {
                    Log::Error("Command error, there is no content in the command file" + filename);
                    continue;
                }

                CommandProcessor processor(requestStr);
                if(processor.IsValidateCommand())
                {
                    processor.ProcessingCommand();
                    // delete the file
                    unlink(filename.c_str());
                }
                else
                {
                    Log::Error("Command error");
                }
            }
        closedir(pdir);
        }
        usleep(1);
    }//End of while 
    Log::Debug("FrontCommandProcessorThread:run exit now");
}

TimerThread::TimerThread()
{

}

void TimerThread::Run(void)
{
    Log::Debug("TimerThread: start now id %ld", pthread_self());

    while (!IsRequestStop())
    {
        int task_id = g_timer_task_manager->GetNextRunTask();
        while(task_id != -1)
        {
            WebInterfaces::StartTimerTask(task_id);
            task_id = g_timer_task_manager->GetNextRunTask();
        }
        sleep(1);
    }//End of while

    Log::Debug("TimerThread:run exit now");
}


