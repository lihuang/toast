/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef _WIN32

#ifndef __UNIXPROCESSCONTROLLER_H__
#define __UNIXPROCESSCONTROLLER_H__

#include <iostream>
//#include <unistd.h>
//#include <sys/types.h>
#include <signal.h>

class UnixProcessController
{

public:
	typedef enum _ExitType {
		CONTINUERUNNING = 0,
		EXITNORMALLY ,
		EXITABNORMALLY
	} ExitType;

	UnixProcessController(const char * argv0, const char * PidFileName,
		int TimeOut, void (* VersionFunction)() );
	virtual ~UnixProcessController() { };

	bool ProcessControl(int argc, char* const argv[], std::string& config);

	virtual void ShowUsage(const char * Processname);

    void SetRestartDelay(int delay) { if (delay >= 0) m_RestartDelay = delay; }

	static bool IgnoreSignal(int Signal);
	static bool SignalToFlag(int Signal, int* pFlag);

	static int WritePIDToFile(const char * pidFileName);
	static int ReadPIDFromFile(const char * pidFileName);

protected:
	inline bool OldProcessExists();
	bool KissOldProcess();
	void Exit(ExitType Quit);

	void SetErrorMessage(const char * Message);

private:

	// 1 - continue running
	// 0 - exit normally
	// -1 - exit with error
	ExitType Stop();
	ExitType Restart();
	ExitType Start();
	ExitType ShowVersion();

	void PrintOutMessage();

	int m_Pid;
	int m_Timeout;
	const char * m_ProcessName;
	const char * m_Message;
	void  (* m_PrintOutVersion)();

    // Number of seconds to wait between stop and start when restarting
    int m_RestartDelay;

};


#endif

#endif
