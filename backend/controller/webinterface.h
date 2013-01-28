/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#ifndef WEBINTERFACE_H
#define WEBINTERFACE_H
#include <string>
#include <map>
struct AgentSystemInfo;
class WebInterfaces
{
public:
   static int SetAllAgentToDown();
    static int SetAllRunToComplete();
    // http://toast.corp.taobao.com/job/getallruntime 
    // return timer task number
    //  {"1":"0 0 * * *","22":"0 1 * * *"}
    static int GetTimerTaskList();
 
       // http://toast.corp.taobao.com/machine/updatemachine
    // name 机器名
    // status 机器状态：0 idle; 1 running; 2 down
    // version Agent版本号
    // type OS类型：0 linux; 1 windows
    // desc_info 机器信息，包括硬件信息，OS信息等 
    
    static void UpdateMachineStatus(const std::string &name, int status);

    static void UpdateMachineInfoStatusIdle(const AgentSystemInfo& info, const std::string &other_info);
    
    // http://toast.corp.taobao.com/run/updaterun
    //id 运行命令ID
    // status 运行状态：0 waitting; 1 running; 2 complete; 3 canceled; 4 timeout; 5 abort; 10 canceling
    // return_value命令返回值
    // desc_info 描述信息
    static int UpdateTaskInfo(int run_id, int status, int return_value, const std::string &info);
    static void StartTimerTask(int task_id);
   
private:
    // function write_calback, this is called by libcurl get data form server
    // CURLOPT_WRITEFUNCTION  CURLOPT_WRITEDATA
    static size_t CurlWriteCallback( char *ptr, size_t size, size_t nmemb, void *server_return);
  
    static int CurlPost(const std::string& url, const std::string& post_msg, std::string *returnString);
 };

#endif

