/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef SEND_PACKET_H
#define SEND_PACKET_H
#include <string>
struct AgentInfo;
int SendPacket(AgentInfo *info, const char *data, int datalength);
#endif
