/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef AGENTCONNECT_H
#define AGENTCONNECT_H
#ifdef WIN32
#include <winsock2.h>
#else
typedef int SOCKET;
#endif
class NetThread:
    public Thread
{
public:
    NetThread();
    void Run(void);
private:
    SOCKET ConnectToServer(const char *host, const char *serv);
};
#endif
