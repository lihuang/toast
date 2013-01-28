
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
#include "sendpacket.h"
#include "../net/packet.h"
#include "../net/buffer.h"
#include "../log/Log.h"
#include "agentlist.h"
#include "../poll/epollmanager.h"
using namespace std;
// send packet 
// 1. get the out buffer lock
// 2. add data to buffer 
// 3. set the epoll write event
// 4. free the out buffer lock
// send success 0. otherwise -1(because of agent is down ...)
extern EpollManager *g_epoll_manager;
int SendPacket(AgentInfo *info, const char *data, int datalength)
{
    // first alloc the out data buffer;
    char *buffer = new (std::nothrow)char[datalength + HEADER_LENGTH];
    if(!buffer) // out of memory
        return -1;
    Packet *packet = (Packet*)(buffer);
    packet->length = datalength + 4;
    for(int i=0; i<datalength; i++)
    {
        buffer[HEADER_LENGTH + i] = *data++;
    }

     if(info)
    {
        packet->number = info->send_buffer->number++;
        info->send_buffer->send_list.push_back(buffer);
        g_epoll_manager->AddWrite(info->fd);
        ActiveAgentsManager::Instance()->UnlockList();
        return 0;
    }
    else
    	{
    	Log::Error("No such agent %s", info->name.c_str());
    	}

    delete [] buffer;
    return -1;
}
