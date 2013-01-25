/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/
#ifndef _AGENTENGINE_H
#define _AGENTENGINE_H


#include <string>
#include <vector>
#include "../log/Log.h"
#include "../config/SimpleConfig.h"
namespace toast
{
using namespace std;

extern  string serverhost;
 extern  string serverport;
bool InitConfigFile(string filePath);

class AgentEngine
{
    public:
        static AgentEngine* instance();
        void run();
	 void stop();

    private:
        static AgentEngine* m_instance;

        AgentEngine();
        ~AgentEngine();
        bool LoadConfig(const std::string& filePath);

};

}

#endif
