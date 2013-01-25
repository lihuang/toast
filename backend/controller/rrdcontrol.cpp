/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include <ctype.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <rrd.h>
#include <errno.h>
#include <time.h>
#include <string>
#include "systemconfig.h"
#include "../log/Log.h"
#include "../agentcmdrsp/agentcmdrsp.h"
using namespace std;

// install following packets
// sudo yum install rrdtool -b current
// sudo yum install monitor-rrdtool-devel -b current

int CreateMemoryRRD(const string &hostname)
{
    char name[64];   
    char *argv[128];
    int argc = 0;
    char start[64];
    char step[16];
    char dstotal[64];
    char dsfree[64];
    sprintf(name, "%s/%s.memory.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    argv[argc++] = "dummy";
    argv[argc++] = name;
    argv[argc++] = "--start";
    sprintf(start, "%ld", time(0));
    argv[argc++] = start;
    argv[argc++] = "--step";
    sprintf(step, "%d", HBTIMEOUT);
    argv[argc++] = step;
    //int totalMemory;      // K bytes
	//int freeMemory;       // K bytes
    sprintf(dstotal, "DS:total:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(dsfree, "DS:free:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    argv[argc++] = dstotal;
    argv[argc++] = dsfree;
    argv[argc++] = "RRA:AVERAGE:0.5:1:14400";
    argv[argc++] = "RRA:MIN:0.5:1:14400";
    argv[argc++] = "RRA:MAX:0.5:1:14400";
    argv[argc++] = "RRA:AVERAGE:0.5:5:35040";
    argv[argc++] = "RRA:MIN:0.5:5:35040";
    argv[argc++] = "RRA:MAX:0.5:5:35040";
    rrd_clear_error();
    rrd_create(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("create rrd %s error: %s", name, rrd_get_error());
        return -1;
    }
    return 0;
}
int UpdateMemoryRRD(const string &hostname, int total, int free)
{
    char name[64];
    char *argv[128];
    int argc = 0;
    struct stat st;
    sprintf(name, "%s/%s.memory.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    if(stat(name, &st))  // check if there is the rrd file, if not create it
    {
        if(CreateMemoryRRD(hostname))
            return -1;
    }
    argv[argc++] = "dummy";   // only for add a parameter other wish rrd_update will error
    argv[argc++] = name;
    char ds[64];
    sprintf(ds, "N:%d:%d", total, free);
    argv[argc++] = ds;
    // update the rrd file
    rrd_clear_error();
    rrd_update(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("update rrd %s failed: %s\n", name, rrd_get_error());
        return -1;
    } 
    return 0;
}
int CreateCPURRD(const string &hostname)
{
    char name[64];   
    char *argv[128];
    int argc = 0;
    char start[64];
    char dsidle[64];
    char dssystem[64];
    char dsuser[64];
    char step[16];
    sprintf(name, "%s/%s.cpu.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    argv[argc++] = "dummy";
    argv[argc++] = name;
    argv[argc++] = "--start";
    sprintf(start, "%ld", time(0));
    argv[argc++] = start;
    argv[argc++] = "--step";
    sprintf(step, "%d", HBTIMEOUT);
    argv[argc++] = step;

    sprintf(dsidle, "DS:idle:GAUGE:%d:0:100", HBTIMEOUT*2);
    sprintf(dssystem, "DS:system:GAUGE:%d:0:100", HBTIMEOUT*2);
    sprintf(dsuser, "DS:user:GAUGE:%d:0:100", HBTIMEOUT*2);
    argv[argc++] = dsidle;
    argv[argc++] = dssystem;
    argv[argc++] = dsuser;
    argv[argc++] = "RRA:AVERAGE:0.5:1:14400";
    argv[argc++] = "RRA:MIN:0.5:1:14400";
    argv[argc++] = "RRA:MAX:0.5:1:14400";
    argv[argc++] = "RRA:AVERAGE:0.5:5:35040";
    argv[argc++] = "RRA:MIN:0.5:5:35040";
    argv[argc++] = "RRA:MAX:0.5:5:35040";
    rrd_clear_error();
    rrd_create(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("create rrd %s error: %s", name, rrd_get_error());
        return -1;
    }
    return 0;
}
int UpdateCPURRD(const string &hostname, int idle, int system, int user)
{
    char name[64];
    char *argv[128];
    int argc = 0;
    struct stat st;
    sprintf(name, "%s/%s.cpu.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    if(stat(name, &st))  // check if there is the rrd file, if not create it
    {
        if(CreateCPURRD(hostname))
            return -1;
    }
    argv[argc++] = "dummy";   // only for add a parameter other wish rrd_update will error
    argv[argc++] = name;
    char ds[64];
    sprintf(ds, "N:%d:%d:%d", idle, system, user);
    argv[argc++] = ds;
    // update the rrd file
    rrd_clear_error();
    rrd_update(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("update rrd %s failed: %s\n", name, rrd_get_error());
        return -1;
    } 
    return 0;
}
int CreateDiskRRD(const string &hostname)
{
    char name[64];   
    char *argv[128];
    int argc = 0;
    char start[64];
    char step[16];
    char dstotal[64];
    char dsfree[64];
    char dsread[64];
    char dswrite[64];
    sprintf(name, "%s/%s.disk.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    argv[argc++] = "dummy";
    argv[argc++] = name;
    argv[argc++] = "--start";
    sprintf(start, "%ld", time(0));
    argv[argc++] = start;
    argv[argc++] = "--step";
    sprintf(step, "%d", HBTIMEOUT);
    argv[argc++] = step;

    sprintf(dstotal, "DS:total:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(dsfree, "DS:free:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(dsread, "DS:read:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(dswrite, "DS:write:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    argv[argc++] = dstotal;
    argv[argc++] = dsfree;
    argv[argc++] = dsread;
    argv[argc++] = dswrite;
    argv[argc++] = "RRA:AVERAGE:0.5:1:14400";
    argv[argc++] = "RRA:MIN:0.5:1:14400";
    argv[argc++] = "RRA:MAX:0.5:1:14400";
    argv[argc++] = "RRA:AVERAGE:0.5:5:35040";
    argv[argc++] = "RRA:MIN:0.5:5:35040";
    argv[argc++] = "RRA:MAX:0.5:5:35040";
    rrd_clear_error();
    rrd_create(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("create rrd %s error: %s", name, rrd_get_error());
        return -1;
    }
    return 0;
}
int UpdateDiskRRD(const string &hostname, int total, int free, int read, int write)
{
    char name[64];
    char *argv[128];
    int argc = 0;
    struct stat st;
    sprintf(name, "%s/%s.disk.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    if(stat(name, &st))  // check if there is the rrd file, if not create it
    {
        if(CreateDiskRRD(hostname))
            return -1;
    }
    argv[argc++] = "dummy";   // only for add a parameter other wish rrd_update will error
    argv[argc++] = name;
    char ds[64];
    sprintf(ds, "N:%d:%d:%d:%d", total, free, read, write);      //read write / hb  HBTIMEOUT/
    argv[argc++] = ds;
    // update the rrd file
    rrd_clear_error();
    rrd_update(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("update rrd %s failed: %s\n", name, rrd_get_error());
        return -1;
    } 
    return 0;
}
int CreateNetworkRRD(const string &hostname)
{
    char name[64];   
    char *argv[128];
    int argc = 0;
    char start[64];
    char step[16];
    char inbytes[64];
    char outbytes[64];
    char inpackets[64];
    char outpackets[64];

    sprintf(name, "%s/%s.network.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    argv[argc++] = "dummy";
    argv[argc++] = name;
    argv[argc++] = "--start";
    sprintf(start, "%ld", time(0));
    argv[argc++] = start;
    argv[argc++] = "--step";
    sprintf(step, "%d", HBTIMEOUT);
    argv[argc++] = step;
    sprintf(inbytes, "DS:inbytes:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(outbytes, "DS:outbytes:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(inpackets, "DS:inpackets:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    sprintf(outpackets, "DS:outpackets:GAUGE:%d:0:4294967296", HBTIMEOUT*2);
    argv[argc++] = inbytes;
    argv[argc++] = outbytes;
    argv[argc++] = inpackets;
    argv[argc++] = outpackets;
    argv[argc++] = "RRA:AVERAGE:0.5:1:14400";
    argv[argc++] = "RRA:MIN:0.5:1:14400";
    argv[argc++] = "RRA:MAX:0.5:1:14400";
    argv[argc++] = "RRA:AVERAGE:0.5:5:35040";
    argv[argc++] = "RRA:MIN:0.5:5:35040";
    argv[argc++] = "RRA:MAX:0.5:5:35040";
    rrd_clear_error();
    rrd_create(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("create rrd %s error: %s", name, rrd_get_error());
        return -1;
    }
    return 0;
}
int UpdateNetworkRRD(const string &hostname, int inbytes, int outbytes, int inpackets, int outpackets)
{
    char name[64];
    char *argv[128];
    int argc = 0;
    struct stat st;
    sprintf(name, "%s/%s.network.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    if(stat(name, &st))  // check if there is the rrd file, if not create it
    {
        if(CreateNetworkRRD(hostname))
            return -1;
    }
    argv[argc++] = "dummy";   // only for add a parameter other wish rrd_update will error
    argv[argc++] = name;
    char ds[64];
    sprintf(ds, "N:%d:%d:%d:%d", inbytes, outbytes, inpackets, outpackets);   // divide with hbeat for btyes/s HBTIMEOUT
    argv[argc++] = ds;
    // update the rrd file
    rrd_clear_error();
    rrd_update(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("update rrd %s failed: %s\n", name, rrd_get_error());
        return -1;
    } 
    return 0;
}

int CreateLoadRRD(const string &hostname)
{
    char name[64];   
    char *argv[128];
    int argc = 0;
    char start[64];
    char dsone[64];
    char dsfive[64];
    char dsfifteen[64];
    char step[16];
    sprintf(name, "%s/%s.load.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    argv[argc++] = "dummy";
    argv[argc++] = name;
    argv[argc++] = "--start";
    sprintf(start, "%ld", time(0));
    argv[argc++] = start;
    argv[argc++] = "--step";
    sprintf(step, "%d", HBTIMEOUT);
    argv[argc++] = step;

    sprintf(dsone, "DS:one_min:GAUGE:%d:0:100", HBTIMEOUT*2);
    sprintf(dsfive, "DS:five_min:GAUGE:%d:0:100", HBTIMEOUT*2);
    sprintf(dsfifteen, "DS:fifteen_min:GAUGE:%d:0:100", HBTIMEOUT*2);
    argv[argc++] = dsone;
    argv[argc++] = dsfive;
    argv[argc++] = dsfifteen;
    argv[argc++] = "RRA:AVERAGE:0.5:1:14400";
    argv[argc++] = "RRA:AVERAGE:0.5:6:14400";
    argv[argc++] = "RRA:AVERAGE:0.5:24:21600";
    argv[argc++] = "RRA:MAX:0.5:1:14400";
    argv[argc++] = "RRA:MAX:0.5:6:14400";
    argv[argc++] = "RRA:MAX:0.5:24:21600";

    rrd_clear_error();
    rrd_create(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("create rrd %s error: %s", name, rrd_get_error());
        return -1;
    }
    return 0;
}
// parameter = load * 100
int UpdateLoadRRD(const string &hostname, int one_minite, int five_minute, int fifteen_minute)
{
    char name[64];
    char *argv[128];
    int argc = 0;
    struct stat st;
    sprintf(name, "%s/%s.load.rrd", g_config->rrd_path.c_str(), hostname.c_str());
    if(stat(name, &st))  // check if there is the rrd file, if not create it
    {
        if(CreateLoadRRD(hostname))
            return -1;
    }
    argv[argc++] = "dummy";   // only for add a parameter other wish rrd_update will error
    argv[argc++] = name;
    char ds[64];
    sprintf(ds, "N:%f:%f:%f", one_minite/100.0, five_minute/100.0, fifteen_minute/100.0);
    argv[argc++] = ds;
    // update the rrd file
    rrd_clear_error();
    rrd_update(argc, argv);
    if(rrd_test_error())
    {
        Log::Error("update rrd %s failed: %s\n", name, rrd_get_error());
        return -1;
    } 
    return 0;
}

//int main(int argc, char **argv)
//{
//    for(int i = 0; i < 720; i++)
//        UpdateCPURRD(1, 50, 30, 20);
//    return 0;
//}
