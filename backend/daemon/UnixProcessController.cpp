/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef _WIN32

#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <getopt.h>

#include "UnixProcessController.h"
using namespace std;
// _SIG_MAXSIG  bsd has it
// __SIGRTMAX linux has it

#ifndef _SIG_MAXSIG
#define _SIG_MAXSIG __SIGRTMAX
#endif
static int * m_SignalFlagMap[_SIG_MAXSIG+1];

UnixProcessController::UnixProcessController(const char * argv0, const char * PidFileName,
			int Timeout, void (* VersionFunction)() = 0)
{
	m_ProcessName = argv0;
	m_Message = NULL;
	m_Timeout = Timeout;
	m_PrintOutVersion=VersionFunction;
	if (m_Timeout<=0)
		m_Timeout = 10;  // set the default timeout to 10 seconds.

    // Default restart delay is 5 seconds
    m_RestartDelay = 5;

	FILE * fp;
	if ((fp=fopen(PidFileName,"r"))==NULL) 
	{
		this->m_Pid = 0;
	}
	else 
	{
		char TmpStr[20];
		if (fgets(TmpStr,sizeof(TmpStr)-1,fp)) 
		{
			m_Pid = atol(TmpStr);
		}
		else 
		{
			m_Pid = 0;
		}
		fclose(fp);

	}

}

inline bool UnixProcessController::OldProcessExists()
{
	if (m_Pid<=0) 
	{
		return false;
	}
	else 
	{
		return ( !kill(m_Pid,0) );
	}
}



void UnixProcessController::PrintOutMessage()
{
	if (m_Message && strlen(m_Message)>0) 
	{
		fprintf(stderr,"%s: %s\n",m_ProcessName,m_Message);
	}
}

void UnixProcessController::SetErrorMessage(const char * Message)
{
	m_Message = Message;
}



void UnixProcessController::Exit(ExitType Quit)
{
	PrintOutMessage();
	if(Quit==EXITNORMALLY)
		exit(0);
	else if(Quit==EXITABNORMALLY)
		exit(1);

	return ;
}

void UnixProcessController::ShowUsage(const char * Processname)
{
	fprintf(stderr,"\nUsage: %s [-s|--start] [-e|--stop] [-r|--restart] [-v|--version] [-c arg|--config arg]\n", Processname);
	Exit(EXITNORMALLY);
}

bool UnixProcessController::KissOldProcess()
{
	if (m_Pid<=0) {
		return true;
	}

	return ( !kill(m_Pid, SIGUSR1) );
}

bool UnixProcessController::ProcessControl(int argc, char* const argv[], string& config)
{
    int c;
    int option_index = 0;
    static struct option long_options[] = {
        {"start", 0, 0, 's'},
        {"stop", 0, 0, 'e'},
        {"restart", 0, 0, 'r'},
        {"version", 0, 0, 'v'},
        {"config", 1, 0, 'c'},
        {0, 0, 0, 0}
    };

    if (argc == 1)
    {
        Exit(Start());
        return true;
    }

    int command = -1; //0:stop; 1:start; 2:restart
    while ((c = getopt_long (argc, argv, "servc:", long_options, &option_index)) != -1)
    {
        switch (c) {
            case 's':
                command = 1; 
                break;

            case 'e':
                command = 0;
                break; 

            case 'r':
                command = 2;
                break;

            case 'v':
                Exit(ShowVersion());
                return true;

            case 'c':
                config = optarg;
                if(command == -1)
                    command = 1;
                break;

            case '?':
                ShowUsage(argv[0]);
                return false;

            default:
                ShowUsage(argv[0]);
                return false;
        }
    }
    if (optind < argc) 
    {
        fprintf(stderr, "non-option ARGV-elements: ");
        while (optind < argc)
            fprintf(stderr, "%s ", argv[optind++]);
        fprintf(stderr, "\n");

        ShowUsage(argv[0]);
        return false;
    }   

    switch(command)
    {
        case 0:
            Exit(Stop());
            break;
        case 1:
            Exit(Start());
            break;
        case 2:
            Exit(Restart());
            break;
        default:
            return false;
    }
    return true;
}

UnixProcessController::ExitType UnixProcessController::Start()
{
	if (OldProcessExists()) 
	{
		SetErrorMessage("Another process is running, please stop it at first.");
		return EXITABNORMALLY;
	}
	SetErrorMessage("Starting ...");
	return CONTINUERUNNING;
}

UnixProcessController::ExitType UnixProcessController::Stop()
{
	if (OldProcessExists() && !KissOldProcess()) 
	{
		SetErrorMessage("Can not stop the old process.");
		return EXITABNORMALLY;
	}

	while (m_Timeout>0 && OldProcessExists() ) 
	{
		fprintf(stderr,"\rWaiting for old process to quit. Timeout: %4d secs.   ", m_Timeout);
		--m_Timeout;
		sleep(1);
	}
	fprintf(stderr,"\n");

	if (OldProcessExists()) 
	{
		SetErrorMessage("Can not stop the old process.");
		return EXITABNORMALLY;
	}

	SetErrorMessage("Old process has been stopped.");
	return EXITNORMALLY;

}

UnixProcessController::ExitType UnixProcessController::Restart()
{
	if (Stop()==EXITABNORMALLY) 
	{
		return EXITABNORMALLY;
	}
	/*
	PrintOutMessage();

    if (m_RestartDelay > 0)
    {
        char msg[100];
        sprintf(msg, "Waiting %d seconds for process stop to complete...", m_RestartDelay);
        SetErrorMessage(msg);
        PrintOutMessage();
        sleep(m_RestartDelay);
    }
	*/
	SetErrorMessage("Restarting ...");
	return CONTINUERUNNING;
}

UnixProcessController::ExitType UnixProcessController::ShowVersion()
{
	if (m_PrintOutVersion)
		(*m_PrintOutVersion)();
	else
		fprintf(stderr,"Unkown version.\n");
	return EXITNORMALLY;
}


int UnixProcessController::WritePIDToFile(const char * pidFileName)
{
	FILE * fp = fopen(pidFileName, "w");
	
	if (fp == NULL)
		return 0;
	else
	{
		fprintf(fp,"%d",getpid());
		fclose(fp);

		return 1;
	}
}

int UnixProcessController::ReadPIDFromFile(const char * pidFileName)
{
	FILE * fp;
	int pid;
	if ((fp=fopen(pidFileName,"r"))==NULL) 
	{
		pid = 0;
	}
	else 
	{
		char TmpStr[20];
		if (fgets(TmpStr,sizeof(TmpStr)-1,fp)) 
		{
			pid = atol(TmpStr);
		}
		else 
		{
			pid = 0;
		}
		fclose(fp);

	}

	return pid;
}

bool UnixProcessController::IgnoreSignal(int sig)
{
	return (signal(sig,SIG_IGN)!=SIG_ERR);
}

static void SignalToFlagHandler(int sig)
{
	int * p = m_SignalFlagMap[sig];
	if (!p) return;
	++(*p);
}

bool UnixProcessController::SignalToFlag(int sig, int * pFlag)
{
	m_SignalFlagMap[sig]=pFlag;
	return (signal(sig,SignalToFlagHandler)!=SIG_ERR);
}
#endif
