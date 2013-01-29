/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "../log/Log.h"
#include "jsoncommandprocess.h"
#include "../agentcmdrsp/agentcmdrsp.h"
#include "taskjobmanager.h"
#include "agentlist.h"
#include "timertaskmanager.h"
#include "webinterface.h"
#include "sendpacket.h"
#include "systemconfig.h"
#include "webstatus.h"
using namespace std;
extern TimerTaskManager *g_timer_task_manager;

CommandProcessor::CommandProcessor(string &json_cmd_str)
{
	Json::Reader reader;
	Json::Value root;
	bool parseresult = false;
	m_validate_cmd = 0;
	try
	{
		parseresult = reader.parse(json_cmd_str, root);
		if(parseresult && !root.empty())
		{
			m_test_type = root["TestType"].asString();
			m_run_id = root["RunID"].asString();
			m_commands = root["Commands"];
			m_validate_cmd = 1;
		}
		else
		{
			Log::Error("JSON parse error or no command in the json");
		}
	}
	catch(...)
	{
		Log::Error("Get parse command exception");
	}
}
int CommandProcessor::IsValidateCommand()
{
	return m_validate_cmd;
}
void CommandProcessor::ProcessingCommand()
{
	if(m_test_type == "CI")      // ci command send it to the specific agent
	{
		ParseCICommand();
	}
	else if(m_test_type == "Regress" || m_test_type == "CancelRun")
	{
		ParseRunCommand();
	}
	else if(m_test_type == "TimerTask")
	{
		ParseTimerTask();
	}
	else
	{
		Log::Error("There are no test type: " + m_test_type);
	} 
}
/*
{ "TestType":"CI", "RunID":"0",
"Commands": [{ "TestCommand":"Add", #[Add|Del] "AppendInfo": "{\"TaskID\":\"1\",\"Time\":\"1\", \"SVN\":\"url\",}" }]}
*/
void CommandProcessor::ParseCICommand()
{
	try
	{
		if(!m_commands.empty())
		{
			for(int i = 0; i < m_commands.size(); i++)
			{
				string command = m_commands[i]["TestCommand"].asString();
				bool json_parse_result = false;
				string ci_task_str = m_commands[i]["AppendInfo"].asString();
				Json::Value ci_task_info;
				Json::Reader reader;
				json_parse_result = reader.parse(ci_task_str, ci_task_info);
				if(json_parse_result && !ci_task_info.empty())
				{
					string ci_task_id = ci_task_info["TaskID"].asString();
					string ci_url          = ci_task_info["SVN"].asString();
					if(ci_task_id.empty())
					{
						Log::Error("CI task id is empty!");
						continue;
					}
					int task_id = atoi(ci_task_id.c_str());
					if(command == "Add" || command == "Update" || command == "Del")
					{
						SendCICommand(command, task_id, ci_url);
					}
					else
					{
						Log::Error("Command invalidate");
					}	       
				}
				else
				{
					Log::Error("Json format error or no ci information");
				}

			}
		}
	}
	catch(...)
	{
		Log::Error("Parse command  exception");
	}
}
void CommandProcessor::ParseTimerTask()
{
	bool json_parse_result = false;
	try
	{
		if(!m_commands.empty())
		{
			for(int i = 0; i < m_commands.size(); i++)
			{
				string tast_cmd = m_commands[i]["TestCommand"].asString();
				string timer_task_str = m_commands[i]["AppendInfo"].asString();
				Json::Value timer_task_info;
				Json::Reader reader;
				json_parse_result = reader.parse(timer_task_str, timer_task_info);
				if(json_parse_result && !timer_task_info.empty())
				{
					string task_id_str = timer_task_info["TaskID"].asString();
					string run_time_str = timer_task_info["Time"].asString();
					if(task_id_str.empty())
					{
						Log::Error("TimerTask id is empty!");
						continue;
					}
					int task_id = atoi(task_id_str.c_str());
					if (tast_cmd == "Add" && !run_time_str.empty())
					{
						Log::Info("Add TimerTask ID: " + task_id_str + ", Time: " + run_time_str);
						g_timer_task_manager->Insert(task_id, run_time_str);
					}
					else if (tast_cmd == "Del")  //  && !run_time_str.empty() delete need not check time string
					{
						Log::Info("Delete TimerTask ID: " + task_id_str + ", Time: " + run_time_str);
						g_timer_task_manager->Delete(task_id);
					}
					else
					{
						Log::Error("TimerTask Error with TaskID: " + task_id_str + ", run time: " + run_time_str + ", Command: " + tast_cmd);
					}
				}
			}
		}
	}
	catch(...)
	{
		Log::Error("Parse command timer task exception");
	}
}

void CommandProcessor::ParseRunCommand()
{
	try
	{
		if(!m_commands.empty())
		{
			for(int i = 0; i < m_commands.size(); i++)
			{
				string account = m_commands[i]["Sudoer"].asString();
				string command = m_commands[i]["TestCommand"].asString();
				string agent_ip   = m_commands[i]["TestBox"].asString();
				int cmd_id;
				int timeout;
				string id_str = m_commands[i]["CommandID"].asString();
				sscanf(id_str.c_str(), "%d", &cmd_id);
				string timeout_str = m_commands[i]["Timeout"].asString();
				sscanf(timeout_str.c_str(), "%d",  &timeout);   
				if(m_test_type == "Regress")
					SendCommandToAgent(COMMAND_RUN, account, command, agent_ip, cmd_id, timeout);
				else
					SendCommandToAgent(COMMAND_CANCEL, account, command, agent_ip, cmd_id, timeout);
			}
		}
	}
	catch(...)
	{
		Log::Error("Parse command  exception");
	}
}

int CommandProcessor::SendCICommand(string command, int task_id, const string &ci_url)
{
	char *buf = new (std::nothrow)char[sizeof(CICommand) + ci_url.length() + 1];		
	if(!buf)
	{
		Log::Error("There is no memory allocate ci command");
		return -1;
	}
	CICommand *ci_cmd = (CICommand*)buf;
	if(command == "Add")
	{
		ci_cmd->subType = 1;
	}
	else if(command == "Update")
	{
		ci_cmd->subType = 2;
	}
	else if(command == "Del")
	{
		ci_cmd->subType = 3;
	}
	ci_cmd->type = COMMAND_CI;
	ci_cmd->taskid = task_id;
	ci_cmd->urlLength = ci_url.length();
	strcpy(ci_cmd->data, ci_url.c_str());
	ci_cmd->length = 20 + ci_url.length();
	ActiveAgentsManager::Instance()->LockList();
	AgentInfo *info = ActiveAgentsManager::Instance()->FindByName(g_config->CI_agent);
	 if(!info)   // no such agent
	{
	    Log::Error("There is no ci agent or agent is down");
	    ActiveAgentsManager::Instance()->UnlockList();			
       	    return -1;
	}
	SendPacket(info, buf,  ci_cmd->length);
	ActiveAgentsManager::Instance()->UnlockList();	

}
int CommandProcessor::SendCommandToAgent(int run_type, const string &account, 
	const string& command, const string &agent_ip, int id, int timeout)
{
	TaskRun *task = new (std::nothrow)TaskRun;
	if(!task)
	{
		Log::Error("There is no memory to allocate task");
		return -1;
	}
	task->account     = account;
	task->command     = command;
	task->id          = id;
	task->timeout     = timeout;
	task->agent = agent_ip;
	task->fail_action = 0;
	task->log_fd= -1;  // initlize the fd to invalidate fd

	// 1 Send task to the agent
	// 2 Insert the task to the task list.
	AgentCommand *agent_command;
	char *buf = new (std::nothrow)char[AGENT_COMMAND_HEAD_LENGTH + task->account.length() + task->command.length() + 1];
	if(!buf)
	{
		Log::Error("There is no memory allocate agent command");
		if(run_type != COMMAND_RUN)
			delete task;
		return -1;
	}
	strcpy(buf+AGENT_COMMAND_HEAD_LENGTH, task->account.c_str());
	strcat(buf+AGENT_COMMAND_HEAD_LENGTH, task->command.c_str());
	agent_command = (AgentCommand*)buf;
	agent_command->account_length = task->account.length();
	agent_command->id = task->id;
	agent_command->command_length = task->command.length();
	agent_command->length = AGENT_COMMAND_HEAD_LENGTH + agent_command->account_length + agent_command->command_length;
	agent_command->timeout = task->timeout;
	agent_command->type      = run_type;
	Log::Debug("Send command to agent id %d, type %d, timeout %d", agent_command->id, agent_command->type, agent_command->timeout);
	Log::Debug("command: " + task->command);
	ActiveAgentsManager::Instance()->LockList();
	AgentInfo *info = ActiveAgentsManager::Instance()->FindByIP(agent_ip);
	if(!info)   // no such agent
	{
		if(run_type == COMMAND_RUN)
		{
			Log::Info("Task %d not run due to agent down", id);
			WebInterfaces::UpdateTaskInfo(id, WEB_STATUS_AGENT_DOWN, 0, "Agent is Down, or no such agent");
		}
		else
		{
			Log::Error("Cancel failed, agent %s is down", agent_ip.c_str());
		}
	       ActiveAgentsManager::Instance()->UnlockList();			
       	return -1;
	}
    // only the run command need to insert to the running list,
	// cancel run has no response
	if(run_type == COMMAND_RUN)
	{
	    TaskRunManager::Instance() ->InsertTaskRun(task);
	}
	SendPacket(info, buf,  agent_command->length);
	ActiveAgentsManager::Instance()->UnlockList();	
 
	if(run_type != COMMAND_RUN)
	{
		delete task;
	}
	delete [] buf;
	return 0;
}

