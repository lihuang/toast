/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifdef WIN32
#include <windows.h>
#include <tchar.h>
#include <stdio.h>
#include <strsafe.h>
#else
#include <errno.h>
#include <sys/socket.h>
#include <netdb.h>
#include <sys/types.h>
#include <dirent.h>
#include <unistd.h>
#include <stdio.h>
#include <sys/stat.h>
#include <string.h>
#include <vector>
#include <set>
#include <sys/utsname.h> // for uname
#endif
#include "../log/Log.h"
#include "../config/SimpleConfig.h"
#include "../pty/toastpopen.h"
#include "../agentcmdrsp/agentcmdrsp.h"
#include "version.h"

#ifdef WIN32
#pragma comment(lib, "User32.lib")
typedef void (WINAPI *PGNSI)(LPSYSTEM_INFO);
typedef BOOL (WINAPI *PGPI)(DWORD, DWORD, DWORD, DWORD, PDWORD);
#define BUFSIZE 256
#endif
extern int SendPacket(const char *data, int datalength);

#ifdef WIN32
// copy from msdn
BOOL GetOSDisplayString( LPTSTR pszOS)
{
    OSVERSIONINFOEX osvi;
    SYSTEM_INFO si;
    PGNSI pGNSI;
    PGPI pGPI;
    BOOL bOsVersionInfoEx;
    DWORD dwType;

    ZeroMemory(&si, sizeof(SYSTEM_INFO));
    ZeroMemory(&osvi, sizeof(OSVERSIONINFOEX));

    osvi.dwOSVersionInfoSize = sizeof(OSVERSIONINFOEX);
    bOsVersionInfoEx = GetVersionEx((OSVERSIONINFO*) &osvi);

    if( !(bOsVersionInfoEx = GetVersionEx ((OSVERSIONINFO *) &osvi)) )
        return 0;

    // Call GetNativeSystemInfo if supported or GetSystemInfo otherwise.
    pGNSI = (PGNSI) GetProcAddress(
        GetModuleHandle(TEXT("kernel32.dll")), 
        "GetNativeSystemInfo");
    if(NULL != pGNSI)
        pGNSI(&si);
    else GetSystemInfo(&si);

    if ( VER_PLATFORM_WIN32_NT==osvi.dwPlatformId && 
        osvi.dwMajorVersion > 4 )
    {
        StringCchCopy(pszOS, BUFSIZE, TEXT("Microsoft "));
        // Test for the specific product.
        if ( osvi.dwMajorVersion == 6 )
        {
            if( osvi.dwMinorVersion == 0 )
            {
                if( osvi.wProductType == VER_NT_WORKSTATION )
                    StringCchCat(pszOS, BUFSIZE, TEXT("Windows Vista "));
                else StringCchCat(pszOS, BUFSIZE, TEXT("Windows Server 2008 " ));
            }
            if ( osvi.dwMinorVersion == 1 )
            {
                if( osvi.wProductType == VER_NT_WORKSTATION )
                    StringCchCat(pszOS, BUFSIZE, TEXT("Windows 7 "));
                else StringCchCat(pszOS, BUFSIZE, TEXT("Windows Server 2008 R2 " ));
            }

            pGPI = (PGPI) GetProcAddress(
                GetModuleHandle(TEXT("kernel32.dll")), 
                "GetProductInfo");

            pGPI( osvi.dwMajorVersion, osvi.dwMinorVersion, 0, 0, &dwType);
            switch( dwType )
            {
            case PRODUCT_ULTIMATE:
                StringCchCat(pszOS, BUFSIZE, TEXT("Ultimate Edition" ));
                break;
            case PRODUCT_PROFESSIONAL:
                StringCchCat(pszOS, BUFSIZE, TEXT("Professional" ));
                break;
            case PRODUCT_HOME_PREMIUM:
                StringCchCat(pszOS, BUFSIZE, TEXT("Home Premium Edition" ));
                break;
            case PRODUCT_HOME_BASIC:
                StringCchCat(pszOS, BUFSIZE, TEXT("Home Basic Edition" ));
                break;
            case PRODUCT_ENTERPRISE:
                StringCchCat(pszOS, BUFSIZE, TEXT("Enterprise Edition" ));
                break;
            case PRODUCT_BUSINESS:
                StringCchCat(pszOS, BUFSIZE, TEXT("Business Edition" ));
                break;
            case PRODUCT_STARTER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Starter Edition" ));
                break;
            case PRODUCT_CLUSTER_SERVER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Cluster Server Edition" ));
                break;
            case PRODUCT_DATACENTER_SERVER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Datacenter Edition" ));
                break;
            case PRODUCT_DATACENTER_SERVER_CORE:
                StringCchCat(pszOS, BUFSIZE, TEXT("Datacenter Edition (core installation)" ));
                break;
            case PRODUCT_ENTERPRISE_SERVER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Enterprise Edition" ));
                break;
            case PRODUCT_ENTERPRISE_SERVER_CORE:
                StringCchCat(pszOS, BUFSIZE, TEXT("Enterprise Edition (core installation)" ));
                break;
            case PRODUCT_ENTERPRISE_SERVER_IA64:
                StringCchCat(pszOS, BUFSIZE, TEXT("Enterprise Edition for Itanium-based Systems" ));
                break;
            case PRODUCT_SMALLBUSINESS_SERVER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Small Business Server" ));
                break;
            case PRODUCT_SMALLBUSINESS_SERVER_PREMIUM:
                StringCchCat(pszOS, BUFSIZE, TEXT("Small Business Server Premium Edition" ));
                break;
            case PRODUCT_STANDARD_SERVER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Standard Edition" ));
                break;
            case PRODUCT_STANDARD_SERVER_CORE:
                StringCchCat(pszOS, BUFSIZE, TEXT("Standard Edition (core installation)" ));
                break;
            case PRODUCT_WEB_SERVER:
                StringCchCat(pszOS, BUFSIZE, TEXT("Web Server Edition" ));
                break;
            }
        }
        if ( osvi.dwMajorVersion == 5 && osvi.dwMinorVersion == 2 )
        {
            if( GetSystemMetrics(SM_SERVERR2) )
                StringCchCat(pszOS, BUFSIZE, TEXT( "Windows Server 2003 R2, "));
            else if ( osvi.wSuiteMask & VER_SUITE_STORAGE_SERVER )
                StringCchCat(pszOS, BUFSIZE, TEXT( "Windows Storage Server 2003"));
            else if ( osvi.wSuiteMask & VER_SUITE_WH_SERVER )
                StringCchCat(pszOS, BUFSIZE, TEXT( "Windows Home Server"));
            else if( osvi.wProductType == VER_NT_WORKSTATION &&
                si.wProcessorArchitecture==PROCESSOR_ARCHITECTURE_AMD64)
            {
                StringCchCat(pszOS, BUFSIZE, TEXT( "Windows XP Professional x64 Edition"));
            }
            else StringCchCat(pszOS, BUFSIZE, TEXT("Windows Server 2003, "));

            // Test for the server type.
            if ( osvi.wProductType != VER_NT_WORKSTATION )
            {
                if ( si.wProcessorArchitecture==PROCESSOR_ARCHITECTURE_IA64 )
                {
                    if( osvi.wSuiteMask & VER_SUITE_DATACENTER )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Datacenter Edition for Itanium-based Systems" ));
                    else if( osvi.wSuiteMask & VER_SUITE_ENTERPRISE )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Enterprise Edition for Itanium-based Systems" ));
                }

                else if ( si.wProcessorArchitecture==PROCESSOR_ARCHITECTURE_AMD64 )
                {
                    if( osvi.wSuiteMask & VER_SUITE_DATACENTER )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Datacenter x64 Edition" ));
                    else if( osvi.wSuiteMask & VER_SUITE_ENTERPRISE )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Enterprise x64 Edition" ));
                    else StringCchCat(pszOS, BUFSIZE, TEXT( "Standard x64 Edition" ));
                }

                else
                {
                    if ( osvi.wSuiteMask & VER_SUITE_COMPUTE_SERVER )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Compute Cluster Edition" ));
                    else if( osvi.wSuiteMask & VER_SUITE_DATACENTER )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Datacenter Edition" ));
                    else if( osvi.wSuiteMask & VER_SUITE_ENTERPRISE )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Enterprise Edition" ));
                    else if ( osvi.wSuiteMask & VER_SUITE_BLADE )
                        StringCchCat(pszOS, BUFSIZE, TEXT( "Web Edition" ));
                    else StringCchCat(pszOS, BUFSIZE, TEXT( "Standard Edition" ));
                }
            }
        }
        if ( osvi.dwMajorVersion == 5 && osvi.dwMinorVersion == 1 )
        {
            StringCchCat(pszOS, BUFSIZE, TEXT("Windows XP "));
            if( osvi.wSuiteMask & VER_SUITE_PERSONAL )
                StringCchCat(pszOS, BUFSIZE, TEXT( "Home Edition" ));
            else StringCchCat(pszOS, BUFSIZE, TEXT( "Professional" ));
        }

        if ( osvi.dwMajorVersion == 5 && osvi.dwMinorVersion == 0 )
        {
            StringCchCat(pszOS, BUFSIZE, TEXT("Windows 2000 "));

            if ( osvi.wProductType == VER_NT_WORKSTATION )
            {
                StringCchCat(pszOS, BUFSIZE, TEXT( "Professional" ));
            }
            else 
            {
                if( osvi.wSuiteMask & VER_SUITE_DATACENTER )
                    StringCchCat(pszOS, BUFSIZE, TEXT( "Datacenter Server" ));
                else if( osvi.wSuiteMask & VER_SUITE_ENTERPRISE )
                    StringCchCat(pszOS, BUFSIZE, TEXT( "Advanced Server" ));
                else StringCchCat(pszOS, BUFSIZE, TEXT( "Server" ));
            }
        }

        // Include service pack (if any) and build number.
        if( _tcslen(osvi.szCSDVersion) > 0 )
        {
            StringCchCat(pszOS, BUFSIZE, TEXT(" ") );
            StringCchCat(pszOS, BUFSIZE, osvi.szCSDVersion);
        }

        TCHAR buf[80];

        StringCchPrintf( buf, 80, TEXT(" (build %d)"), osvi.dwBuildNumber);
        StringCchCat(pszOS, BUFSIZE, buf);

        if ( osvi.dwMajorVersion >= 6 )
        {
            if ( si.wProcessorArchitecture==PROCESSOR_ARCHITECTURE_AMD64 )
                StringCchCat(pszOS, BUFSIZE, TEXT( ", 64-bit" ));
            else if (si.wProcessorArchitecture==PROCESSOR_ARCHITECTURE_INTEL )
                StringCchCat(pszOS, BUFSIZE, TEXT(", 32-bit"));
        }

        return TRUE; 
    }
    else
    {  
        return FALSE;
    }
}
void GetAgentInfo(AgentSystemInfo *info)
{

    SYSTEM_INFO siSysInfo;
    // get cpu info
    GetSystemInfo(&siSysInfo); 
    switch(siSysInfo.wProcessorArchitecture)
    {
    case PROCESSOR_ARCHITECTURE_AMD64:
        info->cpu = "x64";
        break;
    case PROCESSOR_ARCHITECTURE_IA64:
        info->cpu = "IA64";
        break;
    case PROCESSOR_ARCHITECTURE_INTEL:
        info->cpu = "x86";
        break;
    default:
        info->cpu = "Unknown";
        break;
    }
    // get dns hostname
    DWORD dwSize = 0;
    // first get the buffer size
    GetComputerNameEx(ComputerNameDnsFullyQualified, NULL, &dwSize);
    char *name_buf = new char[dwSize + 1];
    // second get the host name, dns full qualified
    if (!GetComputerNameEx(ComputerNameDnsFullyQualified, name_buf, &dwSize))
    {
        Log::Error("Get dns fully qualified name failed, exit now!");
	    exit(-1);
        info->hostname = "Unknown";
    }	
    else
    {
        info->hostname = string(name_buf, dwSize);
    }
    delete [] name_buf;
    info->system = "Windows";
    char buf[255];

    // get release and version
    OSVERSIONINFO osvi;
    ZeroMemory(&osvi, sizeof(OSVERSIONINFO));
    osvi.dwOSVersionInfoSize = sizeof(OSVERSIONINFO);
    GetVersionEx(&osvi);
    sprintf(buf, "%d.%d", osvi.dwMajorVersion, osvi.dwMinorVersion);
    info->release = buf;
    
    TCHAR szOS[BUFSIZE];
    if( GetOSDisplayString(szOS))
    {
        info->version = szOS;
    }
    else
    {
        info->version = "Unknown";
    }
    info->agent_version = AGENT_VERSION;
}
#else
/*

*/
char * GetCanonname(char*canonname, int canonname_lenth, char * hostname)
{
    struct addrinfo hint;
    struct addrinfo *result = NULL;

    memset(&hint, 0, sizeof(struct addrinfo));
    hint.ai_family = AF_UNSPEC;
    hint.ai_flags = AI_CANONNAME;
    int n;
    if((n=getaddrinfo(hostname, NULL, &hint, &result)) != 0)
    {
        Log::Error("getaddinfo error: %s", gai_strerror(n));
        return NULL;
    }
    strncpy(canonname, result->ai_canonname, canonname_lenth);
    freeaddrinfo(result);
    return canonname;
}
void GetAgentInfo(AgentSystemInfo *info)
{
    struct utsname uts;
    if (uname(&uts) == -1)
    {
       info->hostname      = "Unknown";
       info->system        = "Unknown";
       info->release       = "Unknown";
       info->version       = "Unknown";
       info->cpu           = "Unknown";
       info->agent_version = AGENT_VERSION;
    }
    else
    	{
    	char buf[512];
	if(!GetCanonname(buf, sizeof(buf), uts.nodename))
		{
    	     info->hostname = uts.nodename;
		}
	else
	{
	    info->hostname = buf;
	}
		
	info->system     = uts.sysname;
	info->release      = uts.release;
	info->version     = uts.version;
	info->cpu           = uts.machine;
	info->agent_version      = AGENT_VERSION;
    	}
}
#endif
void SendAgentInfo(AgentSystemInfo &info)
{
    char buf[1024];
    AgentReportInfo *rsp = (AgentReportInfo*)buf;
    
    rsp->head.length = sizeof(AgentResponseHead)+ 24 + 24
                               + info.hostname.length() 
                               + info.system.length()
                               + info.release.length()
                               + info.version.length()
                               + info.cpu.length()
                               + info.agent_version.length();
    rsp->head.type    = AGENT_INFORMATION;
    rsp->head.id        = 0;
    int data_index = 0;
    *((int*)(&rsp->data[data_index])) = AGENT_INFORMATION_TAG_HOSTNAME;
    data_index+=4;
    *((int*)(&rsp->data[data_index])) = info.hostname.length();
    data_index+=4;
    strncpy(&(rsp->data[data_index]), info.hostname.c_str(), info.hostname.length());
    data_index += info.hostname.length();
    
    *((int*)(&rsp->data[data_index])) = AGENT_INFORMATION_TAG_SYSTEM;
    data_index+=4;
    *((int*)(&rsp->data[data_index])) = info.system.length();
    data_index += 4;
    strncpy(&(rsp->data[data_index]), info.system.c_str(), info.system.length());
    data_index += info.system.length();
   
    *((int*)(&rsp->data[data_index])) = AGENT_INFORMATION_TAG_RELEASE;
    data_index+=4;
    *((int*)(&rsp->data[data_index])) = info.release.length();
    data_index += 4;
    strncpy(&(rsp->data[data_index]), info.release.c_str(), info.release.length());
    data_index += info.release.length();

    *((int*)(&rsp->data[data_index])) = AGENT_INFORMATION_TAG_VERSION;
    data_index+=4;
    *((int*)(&rsp->data[data_index])) = info.version.length();
    data_index += 4;
    strncpy(&(rsp->data[data_index]), info.version.c_str(), info.version.length());
    data_index += info.version.length();
  
    *((int*)(&rsp->data[data_index])) = AGENT_INFORMATION_TAG_CPU;
    data_index+=4;
    *((int*)(&rsp->data[data_index])) = info.cpu.length();
    data_index += 4;
    strncpy(&(rsp->data[data_index]), info.cpu.c_str(), info.cpu.length());
    data_index += info.cpu.length();
 
    *((int*)(&rsp->data[data_index])) = AGENT_INFORMATION_TAG_AGENT_VERSION;
    data_index+=4;
    *((int*)(&rsp->data[data_index])) = info.agent_version.length();
    data_index += 4;
    strncpy(&(rsp->data[data_index]), info.agent_version.c_str(), info.agent_version.length());
    data_index += info.agent_version.length();
    SendPacket(buf, rsp->head.length);
    Log::Info("Sending agent information to server");
}

