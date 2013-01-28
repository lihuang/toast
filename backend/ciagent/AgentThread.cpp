/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
#ifdef WIN32
#include <windows.h>
#include <tchar.h>
#include <stdio.h>
#include <strsafe.h>
#else
#include <iostream>
#include <fstream>
#include <errno.h>
#include <limits.h>
#include <fcntl.h>
#include <sys/wait.h>
#include <sys/types.h>
#include <dirent.h>
#include <unistd.h>
#include <stdio.h>
#include <sys/stat.h>
#include <string.h>
#include <vector>
#include <signal.h>
#include <set>
#include <pwd.h> //for getpwnam
#include <sys/utsname.h> // for uname
#endif
#include "AgentThread.h"
#include "../log/Log.h"
#include "../config/SimpleConfig.h"
//#include "../util/StringUtil.h"
#include "../pty/toastpopen.h"
//#include "AgentEngine.h"
#include "../agentcmdrsp/agentcmdrsp.h"
#include "../sync/mutex.h"
#include "systemperformanceinfo.h"
#include "citaskmanager.h"
#ifdef WIN32
#pragma comment(lib, "User32.lib")
typedef void (WINAPI *PGNSI)(LPSYSTEM_INFO);
typedef BOOL (WINAPI *PGPI)(DWORD, DWORD, DWORD, DWORD, PDWORD);
#define BUFSIZE 256
#endif
#define ISNUM(c)   (((c)>= '0') && ((c)<='9'))
extern int SendPacket(const char *data, int datalength);
// this map store the command that is running, for cancel run search the command and cancel
Mutex *g_prunning_commands_mutex;
Mutex *g_pty_fork_mutex;
#ifdef WIN32
map<int, HANDLE> g_running_commands;
#else
map<int, pid_t> g_running_commands;
#endif

using namespace std;
using namespace toast;
extern CITaskManager *g_ci_task_manager;
extern int   CIInterval;
void ControlCI(char *buf);

int AgentThreadDataInit()
{
    g_prunning_commands_mutex = new (std::nothrow)Mutex; 
    g_pty_fork_mutex = new (std::nothrow)Mutex;
    return 0;
}
void CancelAllCommands()
{
    g_prunning_commands_mutex->acquire();
#ifdef WIN32
    map<int, HANDLE>::iterator iter = g_running_commands.begin();
    while(iter != g_running_commands.end())
    {
        Log::Info("Kill the process %d, which processing command %d", iter->first, iter->second);
        if(TerminateProcess(iter->second,  -1) == 0)
            Log::Debug("Failed to stop %d\n",iter->second);
        iter++;
    }
    g_running_commands.clear();
#else
    map<int,pid_t>::iterator iter = g_running_commands.begin();
    while(iter != g_running_commands.end())
    {
        Log::Info("Kill the process %d, which processing command %d", iter->first, iter->second);
        if(kill(-iter->second,SIGKILL) == -1)
            Log::Debug("Failed to stop %d\n",iter->second);
        iter++;
    }
    g_running_commands.clear();
#endif
    g_prunning_commands_mutex->release();
}


void *ProcessingCommand(void *param)
{
    char *cmd = (char *)((char*)param+4);   // skip the id
    CommandHead *head = (CommandHead*)cmd;
    if(head->type == COMMAND_CI)
    	{
    	     ControlCI(cmd);
    	}
	else
		{
	
    CommandProcess command_process(cmd);
    command_process.ProcessCommand();
		}
    delete [] (char*)param;    // free the command memory, new at ReceiveData
    return NULL;
}
void ControlCI(char *buf)
{

    CICommand *cmd = (CICommand*)(buf);
    
    string url = string(cmd->data, cmd->urlLength);
    if(cmd->subType == 1) // add
    	{
    		 Log::Info("Add continuous intergration task ID: %d, URL: %s", cmd->taskid,  url.c_str());

	g_ci_task_manager->Insert_Monitors_Task(cmd->taskid, url, 0);

    	}
	else if(cmd->subType == 2)
		{
		    Log::Info("Update continuous intergration task ID: %d, URL: %s", cmd->taskid, url.c_str());

	g_ci_task_manager->Insert_Monitors_Task(cmd->taskid, url, 0);

		}
	else if(cmd->subType == 3)
		{
		  	 Log::Info("Delete continuous intergration task ID: %d,  URL: %s", cmd->taskid, url.c_str());

	 g_ci_task_manager->Delete_Monitors_Task(cmd->taskid);
		}
	else 
		{
		Log::Error("Receive invalidate CI command subtype %d", cmd->subType);
		}
}
int CommandProcess::ProcessCommand()
{
    switch(m_agent_command->type)
    {
    case COMMAND_CANCEL:
        CancelCommandRun();
        break;
    case COMMAND_RUN:
        RunCommand();
        break;
    default:
        SendCommandResultMsg(COMMAND_COMPLETED, -1, "Can't find the command type");
        Log::Debug("Command type can't find");
        break;
    }
    return 0;
}
CommandProcess::CommandProcess(char *command_buf)
{
    m_agent_command = (AgentCommand*)command_buf;
    m_account              = string(m_agent_command->data, m_agent_command->account_length);
    m_command           = string(m_agent_command->data + m_agent_command->account_length, m_agent_command->command_length);

    Log::Debug("Receive command id %d, type %d, timeout %d", m_agent_command->id, m_agent_command->type, m_agent_command->timeout);
    Log::Debug("account length %d, command length %d", m_agent_command->account_length, m_agent_command->command_length);
    Log::Debug("account: " + m_account + " command: " + m_command);
}
// 0 success
// 1 timeout
// 2 error
#ifdef WIN32
int CommandProcess::GetOutputSendToController(HANDLE h_Output)
{
    CHAR lpBuffer[8192];
    DWORD nBytesRead;
    HANDLE hEvent = NULL;
    OVERLAPPED oOverlap;
    DWORD cbRet;
    BOOL bResult;
    int result = 0;
    AgentResponseLog *rsp = (AgentResponseLog*)lpBuffer;
    rsp->head.type = RESPONSE_COMMAND_LOG;
    rsp->head.id = m_agent_command->id;

    hEvent = CreateEvent( 
        NULL,    // default security attribute 
        TRUE,    // manual-reset event 
        FALSE,    // initial state = signaled 
        NULL);   // unnamed event object 
    if(hEvent == NULL)
    {
        Log::Error( "Create event failed: %d " ,  GetLastError());;
        return 2;
    }
    ZeroMemory(&oOverlap, sizeof(OVERLAPPED));
    oOverlap.hEvent = hEvent;
    bResult = ReadFile(h_Output, rsp->data, sizeof(lpBuffer) - 12, &nBytesRead, &oOverlap);
    if(!bResult)
    {
        if(GetLastError() == ERROR_BROKEN_PIPE) 
        { 
            Log::Error( "End of file ");
            goto Cleanup;
        }  
    }
    else
    {
        rsp->head.length = nBytesRead + sizeof(AgentResponseHead) + 4;
        // there are data read send to controller now
        SendPacket(lpBuffer, rsp->head.length);
    }
    int timeout = INFINITE;
    if (m_agent_command->timeout != 0)
        timeout = m_agent_command->timeout * 1000 * 60;
    ULONGLONG start_time = GetTickCount();
    ULONGLONG end_time = start_time;
    while(1)
    {
        if(m_agent_command->timeout != 0 && timeout != 0)
        {
            end_time = GetTickCount();
            if(end_time > start_time)
                {
                    timeout = timeout - end_time + start_time;
                }
                else
                {
                    timeout = timeout - end_time - (0xffffffff - start_time);
                }
            start_time = end_time;
        }
        if(timeout <= 0)
        {
            result = 1;
            goto Cleanup;
        }

        DWORD dwWait = WaitForSingleObjectEx(hEvent, timeout, TRUE);
        ResetEvent(hEvent);
        switch (dwWait) 
        { 
            // The wait conditions are satisfied by a completed connect operation. 
        case WAIT_OBJECT_0: 
            bResult = GetOverlappedResult(h_Output, &oOverlap, &cbRet, FALSE);  
            if(!bResult)
            {
                if(GetLastError() == ERROR_BROKEN_PIPE)
                { 
                    // TO_DO: Handle an end of file
                    Log::Error( "GetOverlappedResult found EOF\n" );
                    goto Cleanup;
                } 
            }
            else
            {
                if (!ReadFile(h_Output, rsp->data, sizeof(lpBuffer) - 12, &nBytesRead,&oOverlap) || !nBytesRead)
                {
                    if (GetLastError() == ERROR_BROKEN_PIPE)
                    {
                        Log::Info("PIPE CLOSED");
                        goto Cleanup;
                    }
                    else
                    {
                        Log::Error("ReadFile error"); // Something bad happened.
                        goto Cleanup;
                    }
                }
                else
                {
                    rsp->head.length = nBytesRead + sizeof(AgentResponseHead) + 4;
                    // there are data read send to controller now
                    Log::Info("Send data to server");
                    SendPacket(lpBuffer, rsp->head.length);
                }
            }
            break; 

        case WAIT_IO_COMPLETION: 
            Log::Info("IO COMPLETION");
            goto Cleanup;
            // An error occurred in the wait function. 
        case WAIT_TIMEOUT:
            result = 1;
            Log::Error("Command timeout" );
            goto Cleanup;
        default: 
            {
                Log::Error("WaitForSingleObjectEx Error: (%d)", GetLastError()); 
                goto Cleanup;
            }
        } 
    }
Cleanup:
    CloseHandle(hEvent);
    return result;
}
#else
int CommandProcess::GetOutputSendToController(int fd_out)
{
    char buf[1024 * 10]; //10k
    AgentResponseLog *rsp = (AgentResponseLog*)buf;
    int n_fds = 0;
    int max_fd = 0;
    rsp->head.type = RESPONSE_COMMAND_LOG;
    rsp->head.id = m_agent_command->id;
    fd_set readset;
    FD_ZERO(&readset);
    Log::Debug("GetCommandOutput fd %d", fd_out);
    FD_SET(fd_out, &readset);
    max_fd = fd_out + 1;
    n_fds++;
    struct timeval TimeOut;
    int selectResult;
    if (m_agent_command->timeout != 0)
    {
        TimeOut.tv_sec = m_agent_command->timeout * 60;
        TimeOut.tv_usec = 0;
    }
    fd_set new_readset;
    while (1)
    {
        new_readset = readset;
        if (m_agent_command->timeout == 0)
        {
            selectResult = select(max_fd, &new_readset, NULL, NULL, NULL);
        }
        else
        {
            selectResult = select(max_fd, &new_readset, NULL, NULL, &TimeOut);
        }
        if (selectResult == 0)
        {
            return 1;     // timeouted
        }
        else if (selectResult == -1)
        {
            //there is error
            Log::Error("Select error \t");
            break;
        }
        else
        {
                if (FD_ISSET(fd_out, &new_readset))
                {
                    int byte_reads = read(fd_out, rsp->data, sizeof (buf) -12);
                    if (byte_reads <= 0) //endof file
                    {
                        FD_CLR(fd_out, &readset);
                        n_fds--;
                    }
                    else
                    {
                        rsp->head.length = byte_reads + sizeof(AgentResponseHead) + 4;
                        // there are data read send to controller now
                        SendPacket(buf, rsp->head.length);
                    }
            }
            if (0 == n_fds)
            {
                break;
            }
        }
    }
    return 0;
}
#endif
int CommandProcess::SendCommandStartRunMsg()
{
    Log::Info("Send command %d starting message", m_agent_command->id);
    AgentResponseStart rsp;
    rsp.head.length = sizeof(rsp);
    rsp.head.type   = RESPONSE_COMMAND_START;
    rsp.head.id       = m_agent_command->id;
    return SendPacket((char*)&rsp, sizeof(rsp));
}
int CommandProcess::SendCommandResultMsg(int result, int return_code, const string &result_str)
{
    Log::Info("Send command %d result: %d, return code: %d result string: %s", m_agent_command->id, result, return_code, result_str.c_str());
    char *buf = new (std::nothrow)char[RESPONSE_RESULT_HEAD_LENGTH + result_str.length() + 1];
    if(!buf)
    {
        Log::Error("Out of memory at SendCommandResultMsg");
        return -1;
    }
    AgentResponseResult *rsp = (AgentResponseResult*)buf;
    rsp->head.length = RESPONSE_RESULT_HEAD_LENGTH + result_str.length();
    rsp->head.type   = RESPONSE_COMMAND_RESULT;
    rsp->head.id       = m_agent_command->id;
    rsp->result = result;
    rsp->return_code = return_code;
    strncpy(rsp->data, result_str.c_str(), result_str.length());
    int res = SendPacket(buf, rsp->head.length);
    delete [] buf;
    return res;
}
// cancel command doesn't send response to the controller
// if there is command running, than kill it, otherwise nothing to do
int CommandProcess::CancelCommandRun()
{
    g_prunning_commands_mutex->acquire();
#ifdef WIN32
    map<int, HANDLE>::iterator iter;
    iter = g_running_commands.find(m_agent_command->id);
    if(iter != g_running_commands.end())
    {
        if(TerminateProcess(iter->second,  -1) == 0)
        {
            Log::Error("Cancel the command %d, error %s", m_agent_command->id, strerror(errno));
        }
    }

#else
    //find the command 
    map<int, pid_t>::iterator iter;
    iter = g_running_commands.find(m_agent_command->id);
    if(iter != g_running_commands.end())
    {
        if(kill(-g_running_commands[m_agent_command->id], SIGKILL) == -1)
        {
            Log::Error("Cancel the command %d, error %s", m_agent_command->id, strerror(errno));
        }
	Log::Info("Cancel command %d run", m_agent_command->id);
    }
#endif
    g_prunning_commands_mutex->release();
    return 0;
}
// -1 error, result_str is the error reason
// 0 success
int CommandProcess::RunCommand()
{
    string result_str;
    if (StringUtil::Trim(m_command) == "")
    {
        result_str = "Command is null, command not run!";
        SendCommandResultMsg(COMMAND_COMPLETED, -1, result_str);
        return -1;
    }
#ifdef WIN32
    ToastPopen tPopen;
    HANDLE output;
    g_pty_fork_mutex->acquire();
    if(-1 == tPopen.Popen(m_account.c_str(), m_command.c_str(), &output))
    {
        result_str = "Command invalidate or error, run command failed";
        SendCommandResultMsg(COMMAND_COMPLETED, -1, result_str);
	    g_pty_fork_mutex->release();
        return -1;
    }
    g_pty_fork_mutex->release();
#else
    ToastPopen tPopen;
    struct passwd *pwd;
    pwd = getpwnam(m_account.c_str());
    if(pwd == NULL)
    {
        result_str = "There is no user " + m_account + " command not run";
        SendCommandResultMsg(COMMAND_COMPLETED, -1, result_str);
        return -1;
    }
    int master_fd;
    g_pty_fork_mutex->acquire();
    if(-1 == tPopen.PopenPTY(m_account.c_str(), m_command.c_str(), &master_fd))
    {
        result_str = "Command invalidate or error, run command failed";
        SendCommandResultMsg(COMMAND_COMPLETED, -1, result_str);
	g_pty_fork_mutex->release();
        return -1;
    }
    g_pty_fork_mutex->release();
#endif
    g_prunning_commands_mutex->acquire();
    g_running_commands[m_agent_command->id] = tPopen.GetSubProcessID();
    g_prunning_commands_mutex->release();
    // inform the controller this command start run
    SendCommandStartRunMsg();
#ifdef WIN32
    int is_timeout = GetOutputSendToController(output);
    Log::Debug("Getoutputsendtocontroller return %d", is_timeout);
    if(1 == is_timeout)
    {
        tPopen.TerminateChild();
    }
#else
    Log::Debug("Command %d, processing processid:  %d, ttyfd: %d", m_agent_command->id, tPopen.GetSubProcessID(), master_fd);
    int is_timeout = GetOutputSendToController(master_fd);
    if(1 == is_timeout)   //  the command is timeout
    {
        Log::Info("Command %d run timeout, kill the process %d", m_agent_command->id, tPopen.GetSubProcessID());
        kill(-tPopen.GetSubProcessID(), SIGKILL);
    }
#endif

    int res = tPopen.Pclose();
    g_prunning_commands_mutex->acquire();
    g_running_commands.erase(m_agent_command->id);
    g_prunning_commands_mutex->release();
    if(is_timeout)
    {
        SendCommandResultMsg(COMMAND_TIMEOUT, res, result_str);
    }
#ifdef WIN32
    else if(res == -1)      // there may be bug, for task that return -1 will be canceled. see TerminateProcess 
    	{
    	SendCommandResultMsg(COMMAND_CANCELED, res, result_str);
    	}
#else
    else if(res == SIGKILL) //SIGKILL
    {
        SendCommandResultMsg(COMMAND_CANCELED, res, result_str);
    }
#endif
    else
    {
        SendCommandResultMsg(COMMAND_COMPLETED, res, result_str);
    }
    Log::Debug("Command %d , result status is: %d ", m_agent_command->id, res);
    return res;
}
HBThread::HBThread()
{
}
void HBThread::Run(void)
{
    char buf[1024];
    while (!IsRequestStop())
    {
            Log::Debug("Send heartbeat");

		SystemPerformanceInfo perf;
		memset((void*)&perf, 0, sizeof(SystemPerformanceInfo));
		GetMemoryInfo(&perf);
		GetNetworkPerfInfo(&perf);
		GetCPUUtilization(&perf);
		GetDiskSpaceInfo(&perf);
		GetSystemDiskIOInfo(&perf);
#ifndef WIN32
		GetLoadInfo(&perf);
#endif

        AgentHeartBeat *rsp = (AgentHeartBeat*)buf;
        rsp->head.length = sizeof(AgentResponseHead)+ sizeof(SystemPerformanceInfo);
        rsp->head.type    = RESPONSE_HEAETBEAT;
	memcpy(rsp->data, (void*)&perf, sizeof(SystemPerformanceInfo));
      //  strncpy(rsp->data, hb.c_str(), hb.length());
       SendPacket(buf, rsp->head.length);
#ifdef WIN32
        Sleep(HBTIMEOUT*1000);
#else
        sleep(HBTIMEOUT);
#endif
    }//End of while
}


CIThread::CIThread()
{
}
void CIThread::Run(void)
{
    char buf[1024];
    while (!IsRequestStop())
    {
        sleep(::CIInterval);
        g_ci_task_manager->CheckChanged();
    }//End of while
}



