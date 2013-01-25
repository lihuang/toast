/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef RRDCONTROL_H
#define RRDCONTROL_H
int UpdateMemoryRRD(const string &hostname, int total, int free);
int UpdateCPURRD(const string &hostname, int idle, int system, int user);
int UpdateDiskRRD(const string &hostname, int total, int free, int read, int write);
int UpdateNetworkRRD(const string &hostname, int inbytes, int outbytes, int inpackets, int outpackets);
int UpdateLoadRRD(const string &hostname, int one_minite, int five_minute, int fifteen_minute);
#endif
