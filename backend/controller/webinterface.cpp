/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include <string>
#include <stdio.h>
#include "../include/json/json.h"
#include "../log/Log.h"
#include "../include/curl/curl.h"
#include "systemconfig.h"
#include "webinterface.h"
#include "timertaskmanager.h"
#include "webstatus.h"
#include "agentlist.h"
using namespace std;

#define MAX_RESPONSE_LENGTH                1024*1024*16           // web response max length 16M
extern TimerTaskManager *g_timer_task_manager;

//status 机器状态：0 idle; 1 running; 2 down 
int WebInterfaces::SetAllAgentToDown()
{
    string url = g_config->root_url + g_config->update_all_agent_url;
    string post_Info = "status=2";
    string result;
    return CurlPost(url, post_Info, &result);
}
//status 机器状态：0 idle; 1 running; 2 down 
int WebInterfaces::SetAllRunToComplete()
{
    string url = g_config->root_url + g_config->update_all_run_url;
    string post_Info = "status=400";             //WEB_STATUS_ABORTED
    string result;
    return CurlPost(url, post_Info, &result);
}


// curl_url = http://v132194.sqa.cm4.tbsite.net/toast/api/runtaskbyid?id=TASKID&autorun=1&user=TOAST&desc=DESC
void WebInterfaces::StartTimerTask(int task_id)
{
    string url = g_config->root_url + g_config->run_timer_task_url;
    char buf[64];
    sprintf(buf, "%d", task_id);
    string post_str = "id=" + string(buf) + "&autorun=1" + "&user=TOAST";
    string returnStr;
    if(CurlPost(url, post_str, &returnStr))
    {
        Log::Error("start timer task error %d ", task_id);
    }
}

// http://toast.corp.taobao.com/job/getallruntime 
// return timer task number
//  {"1":"0 0 * * *","22":"0 1 * * *"}
int WebInterfaces::GetTimerTaskList()
{
    string url = g_config->root_url + g_config->task_list_url;
    string task_lists_str;
    int post_result = CurlPost(url, "", &task_lists_str);

    int timer_task_counter = 0;
    if(post_result == CURLE_OK)
    {
        Json::Reader reader;
        Json::Value root;
        bool parseresult = false;
        try
        {
            parseresult = reader.parse(task_lists_str, root);
            if(parseresult && !root.empty() )
            {

                Json::Value::Members mem = root.getMemberNames();
                for(Json::Value::Members::iterator mem_iter = mem.begin(); mem_iter != mem.end(); mem_iter++)
                {
                    // first convert the mem to id which is int
                    int id;
                    sscanf((*mem_iter).c_str(), "%d", &id); 
                    // second get the timestring
                    string timestring = root[*mem_iter].asString();
                    g_timer_task_manager->Insert(id, timestring);
                    timer_task_counter++;
                }
            }
            else
            {
                Log::Error("JSON parse error or no object in the json");
            }

        }
        catch(...)
        {
            Log::Error("Get time task list exception");
        }
    }
    else
    {
        Log::Error("Get time task list error, curl error %d", post_result);
    }
    return timer_task_counter;
}

// http://toast.corp.taobao.com/machine/updatemachine
// name 机器名
// status 机器状态：0 idle; 1 running; 2 down
void WebInterfaces::UpdateMachineStatus(const string &name, int status)
{
    char buf[64];
    sprintf(buf, "hostname=%s&status=%d",  name.c_str(),  status);
    string url = g_config->root_url + g_config->update_agent_url;
    string result;
    int res = CurlPost(url, buf, &result);
    if (res != CURLE_OK)
    {
        Log::Error("Update %s status %d failed with machine name:\t", name.c_str(), status);
    }
    else
    {
        Log::Info("Update %s with status %d success\t",  name.c_str(), status);
    }
}

// http://toast.corp.taobao.com/machine/updatemachine
/*
status 机器状态：0 idle; 1 running; 2 down
version Agent版本号
type OS类型：0 linux; 1 windows
hostname 机器名
ip 机器IP地址
os 操作系统信息
cpu CPU核数
*/
void WebInterfaces::UpdateMachineInfoStatusIdle(const AgentSystemInfo &info, const string &ip)
{
    char buf[128];
    if(info.system== "Windows")
    	{
    	    sprintf(buf, "type=%d",  1);
    	}
    else
    	{
    	    sprintf(buf, "type=%d",  0);
    	}
    string post_content = string(buf) + "&hostname=" + info.hostname+ "&status=0" + "&ip=" + ip + "&version=" + info.agent_version 
		+ "&cpu=" + info.cpu + "&os=" + info.release + " OSVersion " + info.version; 

    string url = g_config->root_url + g_config->update_agent_url;
    string result;
    int res = CurlPost(url, post_content, &result);
    
    if (res != CURLE_OK)
    {
        Log::Error("Update Machine  %s information failed!\t",  info.hostname.c_str());
    }
    else
    {
        Log::Info("Update Machine %s information successfully!\t", info.hostname.c_str());
    }
}
// http://toast.corp.taobao.com/run/updaterun
//id 运行命令ID
// status 运行状态：0 waitting; 1 running; 2 complete; 3 canceled; 4 timeout; 5 abort; 10 canceling
// return_value命令返回值
// desc_info 描述信息
int WebInterfaces::UpdateTaskInfo(int run_id, int status, int return_value, const string &info)
{
    char buf[128];
    sprintf(buf, "id=%d&status=%d&return_value=%d", run_id, status, return_value);
    string post_msg = string(buf) + "&desc_info=" + info;
    string server_response;
    string post_url = g_config->root_url + g_config->update_run_url;
    int post_result = CurlPost(post_url, post_msg, &server_response);

    return post_result;
}

// function write_calback, this is called by libcurl get data form server
// CURLOPT_WRITEFUNCTION  CURLOPT_WRITEDATA
size_t WebInterfaces::CurlWriteCallback( char *ptr, size_t size, size_t nmemb, void *server_return)
{
    size_t len = size*nmemb;

    if (server_return)
    	{
    ( (string*)server_return)->append(ptr, len);
    	}
    return len;
}
int WebInterfaces::CurlPost(const string& url, const string& post_msg, string *returnString)
{
    CURL *curl;
    CURLcode res;
    Log::Debug("URL: " + url);
    Log::Debug("Content: " + post_msg);
    curl = curl_easy_init();
    if(curl) 
    {
       try
       	{
        curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);   // ref libcurl manual
        curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
        curl_easy_setopt(curl, CURLOPT_POST, 1);
        curl_easy_setopt(curl, CURLOPT_POSTFIELDS, post_msg.c_str());
        curl_easy_setopt(curl, CURLOPT_POSTFIELDSIZE, post_msg.length());
        curl_easy_setopt(curl, CURLOPT_TIMEOUT, 120);
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, CurlWriteCallback);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, returnString);

        res = curl_easy_perform(curl);
        curl_easy_cleanup(curl);

        if(res != CURLE_OK)  // CURLE_OK == 0
        {
            Log::Error("Post %s error, curl_easy_perform return %d", post_msg.c_str(), res);
        }
	else
		{
		Log::Info("Curl result: " + *returnString);
		}
       	}
	 catch(...)
	 	{
	 	Log::Error("Curl exception");
	 	}
        return res;
    }
    else
    {
        Log::Error("curl_easy_init return NULL");
        return -1;
    }
}
