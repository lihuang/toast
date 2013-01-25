/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "Log.h"
#include<iostream>
using namespace std;
int main()
{
    Log::Init("AgentDaemon.conf");
    Log::Info("test");
    Log::Debug("Debug");
    Log::Error("Error");

    
	Log::Notice("Notice");
	Log::Warn("Warn");
	Log::Crit("Crit");
	Log::Fatal("Fatal");
    return 0;
}
