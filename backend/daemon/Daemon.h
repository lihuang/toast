/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef DAEMON_H
#define DAEMON_H

#include <string>

class Daemon
{
public:	
	static Daemon* Instance(std::string config = "Toast.conf");
	void StartDaemon(int argc, char** argv, const std::string& processName, int delay);
	bool IsStop(void);
        std::string config;

private:
        Daemon():m_flag(0){}
	void BeginDaemon(void);
	void ProcessControl(int argc, char** argv, const std::string& processName, int delay);
	int m_flag;
        static Daemon* instance;
};
#endif
