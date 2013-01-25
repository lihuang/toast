/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef SENDRECEIVEDATA_H
#define SENDRECEIVEDATA_H
#ifdef WIN32
#include<WinSock2.h>
#else
#define SOCKET_ERROR    -1
typedef  int SOCKET;

#endif
struct SendBuffer;
struct ReceiveBuffer;
// call back function call when there is a packet received
typedef void (*ReceivePacketCallBack)(char *data, int datalength);
int SendDataInBuffer(SOCKET fd, SendBuffer *send_buffer);
int ReceiveDataToBuffer(SOCKET fd, int peer, ReceiveBuffer *receive_buffer, ReceivePacketCallBack cb);

#endif
