/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef AGENTCMDRSP_H
#define AGENTCMDRSP_H
#include <string>
typedef unsigned int uint32;

#define HBTIMEOUT            180UL    // seconds


#define COMMAND_RUN          0x01                   // run a command
#define COMMAND_CANCEL       0x02                // cancel a running command
#define COMMAND_GET_INFO    0x03              // get the agent information, such as agent type, machine name etc...
#define COMMAND_GET_STATUS    0x04         // get the agent status information, such as send receive packet numbers, running commands etc.
#define COMMAND_CI                      0x100
#define RESPONSE_COMMAND_START       0x01
#define RESPONSE_COMMAND_LOG         0x02
#define RESPONSE_COMMAND_RESULT      0x03
#define RESPONSE_HEAETBEAT   0x04

// agent information is TLV struct
#define AGENT_INFORMATION     0x100         // agent send information
#define AGENT_INFORMATION_TAG_HOSTNAME 0x01
#define AGENT_INFORMATION_TAG_SYSTEM      0x02
#define AGENT_INFORMATION_TAG_RELEASE    0x03
#define AGENT_INFORMATION_TAG_VERSION    0x04
#define AGENT_INFORMATION_TAG_CPU            0x05
#define AGENT_INFORMATION_TAG_AGENT_VERSION 0x06

#define RESPONSE_RESULT_HEAD_LENGTH     20
#define AGENT_COMMAND_HEAD_LENGTH  24    // not include data
enum COMMAND_STATUS
{
    COMMAND_WAITING   = 0,
    COMMAND_RUNNING   = 1,
    COMMAND_COMPLETED = 2,
    COMMAND_CANCELED  = 3,
    COMMAND_TIMEOUT   = 4
};

/*
{ "TestType":"CI", "RunID":"0",
"Commands": 
[{ "TestCommand":"Add", #[Add|Del] "AppendInfo": "{\"TaskID\":\"1\",\"Time\":\"1\", \"SVN\":\"url\",}" }]}
*/
struct CommandHead
{
    uint32 length;    // total length of the message, include length field
    uint32 type;      // typeof comand
};
struct CICommand
{
    uint32 length;   // total length of the message, include length field
    uint32 type;      // command type COMMAND_CI
    uint32 subType;  // add 1, update, 2, update, 3, del
    uint32 taskid;      // if the code changed, which task invoke
    uint32 urlLength;   // the specificed url
    char    data[1];      // urlstring
};
struct AgentCommand 
{
    uint32 length;          // total length of the message, include length field
    uint32 type;            // command or cancel run
    uint32 id;              // command id
    uint32 timeout;         // command timeout value
    uint32 account_length;      // run account  root or other
    uint32 command_length;   // command length
    char   data[1];            // data include user and command, first user second command, userlength is command pointer start
};
struct AgentResponsePacket
{
    uint32 agent_id;
    char data[1];    
};
struct AgentResponseHead
{
    uint32 length; // length of the response, include the header
    uint32 type;
    uint32  id;    // response command id, some packet is unused such as heart beat and info
};
struct AgentResponseStart
{
    AgentResponseHead head;
};
struct AgentResponseResult
{
    AgentResponseHead head;
    int      result;   // see COMMAND_RESULT
    int      return_code;
    char   data[1];       // the command or other thing      
};
struct AgentHeartBeat
{
    AgentResponseHead head;   // id is 0xffffffff
    char data[1];
};
struct AgentReportInfo
{
    AgentResponseHead head;   // id is 0xffffffff
    char data[1];
};
struct AgentResponseLog
{
    AgentResponseHead head;
    char data[1];
};
struct AgentSystemInfo
	{    
	std::string hostname;  //    
	std::string system;   // operator system linux /windows    
	std::string release;    
	std::string version;    
	std::string cpu;    
	std::string agent_version;
	};
struct SystemPerformanceInfo
{
	int userTime;         // ms
	int systemTime;       // ms
	int idleTime;         // ms
	int totalMemory;      // K bytes
	int freeMemory;       // K bytes
	int totalDiskSpace;    // unit M bytes
	int freeDiskSpace;     // M bytes
	int diskRead;          // byte
	int diskWrite;         // byte
	int inBytes;           // tyte network
	int outBytes;          // byte
	int inPackets;         // number
	int outPackets;        // number
	int load1min;
	int load5min;
	int load15min;
};
#endif

