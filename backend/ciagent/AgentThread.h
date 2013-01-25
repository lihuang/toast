/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef AGENTTHREAD_H
#define AGENTTHREAD_H

#include "../thread/threadbase.h"
#include "../agentcmdrsp/agentcmdrsp.h"
#include<string>
void CancelAllCommands();
namespace toast
{

class CommandProcess
{
    public:
        CommandProcess(char *command_str);
	 int  ProcessCommand();
    private:
		// no copy and =
	 CommandProcess(const CommandProcess&);
           CommandProcess& operator=(const CommandProcess&);
          int CancelCommandRun();
	   int RunCommand();
#ifdef WIN32
       int GetOutputSendToController(HANDLE fd_out);
#else
         int GetOutputSendToController(int fd_out);
#endif
	  int SendCommandStartRunMsg();
	 int SendCommandResultMsg(int result, int return_code, const std::string &result_str);

    private:
   	// command threads
   	AgentCommand *m_agent_command;
	std::string m_command;
	std::string m_account;
};
class HBThread:
    public Thread
{
    public:
        HBThread();
        void Run(void);
};
class CIThread:
    public Thread
{
    public:
        CIThread();
        void Run(void);
};
}
#endif

