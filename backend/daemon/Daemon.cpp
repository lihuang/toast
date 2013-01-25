/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include <stdlib.h>
#include "UnixProcessController.h"
#include "Daemon.h"
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
//#include "Log.h"

using namespace std;

Daemon* Daemon::instance = NULL;
Daemon* Daemon::Instance(string config)
{
        if (NULL == instance)
        {
            instance = new (std::nothrow)Daemon;
            instance->config = config;
        }
	return instance;
}

//进程变为Daemon，并加入进程控制的功能
void Daemon::StartDaemon(int argc, char** argv, const std::string& processName, int delay)
{
	BeginDaemon();
	ProcessControl(argc,argv,processName,delay);
}
void Daemon::BeginDaemon()
{
    int  fd;

    switch (fork()) {
    case -1:
       exit(-1);
    case 0:
        break;

    default:
        exit(0);
    }

    if (setsid() == -1) 
    {
        exit(2);
    }

    umask(0);

    fd = open("/dev/null", O_RDWR);
    dup2(fd, STDIN_FILENO) ;
    dup2(fd, STDOUT_FILENO) ;
    if (fd > STDERR_FILENO) 
    	{
    	 close(fd);
    	}
}

//变为Daemon
/*
void Daemon::BeginDaemon(void)
{
	if(fork()!=0) exit(0);
	setsid();
	umask(0);
	if(fork()!=0) exit(0);     
}
*/
//加入进程控制功能
void Daemon::ProcessControl(int argc,char** argv,
		const string& processName,int delay)
{
	string fileName=processName+".pid";

	UnixProcessController procCtrl(argv[0],fileName.c_str(),delay,NULL);
	procCtrl.SetRestartDelay(delay);

	if(!UnixProcessController::SignalToFlag(SIGUSR1,&m_flag))
	{
	//	Log::Error("Daemon::ProcessControl: SignalToFlag fail.");
	}
        procCtrl.ProcessControl(argc, argv, config);

	//if(argc>2)
	//	procCtrl.ShowUsage(argv[0]);
	//else
	//{
	//	if(argc==1)
	//		procCtrl.ProcessControl("Start");
	//	else
	//	{
	//		if(!procCtrl.ProcessControl(argv[1]))
	//			procCtrl.ShowUsage(argv[0]);
	//	}
	//}

	if(UnixProcessController::WritePIDToFile(fileName.c_str())==0)
	{
		//Log::Error("Daemon::ProcessControl: Can't write pid!");
		exit(1);
	}

}

//提供给调用进程来判断是否需要退出运行
bool Daemon::IsStop(void)
{
	return m_flag==1;
}
