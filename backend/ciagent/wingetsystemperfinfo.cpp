#include <windows.h>
#include <iostream>
#include <InitGuid.h>
#include <SetupAPI.h>
#include <Iphlpapi.h>
#include "../agentcmdrsp/agentcmdrsp.h"
#include "../log/Log.h"
// most function are not thread safe, most function has static variable, 
// since these function only used by heartbeat thread
// need setupapi.lib and Iphlpapi.lib
using namespace std;
#define MALLOC(x) HeapAlloc(GetProcessHeap(), 0, (x))
#define FREE(x) HeapFree(GetProcessHeap(), 0, (x))
#define DIV 1024

// Specify the width of the field in which to print the numbers. 
// The asterisk in the format specifier "%*I64d" takes an integer 
// argument and uses it to pad and right justify the number.
#define WIDTH 7
#define MAX_DISK_DEVICES            16

struct NetworkUtilization
{
	int prevInBytes;
	int prevOutBytes;
	int prevInPackets;
	int prevOutPackets;
	int inBytes;
	int outBytes;
	int inPackets;
	int outPackets;
};
int GetMemoryInfo(SystemPerformanceInfo *pSystemPerf)
{
	MEMORYSTATUSEX statex;
	statex.dwLength = sizeof (statex);
	if(GlobalMemoryStatusEx (&statex))
	{
		pSystemPerf->totalMemory = statex.ullTotalPhys/DIV;
		pSystemPerf->freeMemory  = statex.ullAvailPhys/DIV;
		return 0;
	}
	else
	{
		Log::Error("GlobalMemoryStatusEx() Error: %d", GetLastError());
		return -1;
	}
}
int GetNetworkPerfInfo(SystemPerformanceInfo *pSystemPerf)
{
	static NetworkUtilization *sp_internel_buffer = NULL;
	// Declare and initialize variables.
	DWORD dwSize = 0;
	DWORD dwRetVal = 0;
	int i;
	/* variables used for GetIfTable and GetIfEntry */
	MIB_IFTABLE *pIfTable;
	MIB_IFROW *pIfRow;

	// Allocate memory for our pointers.
	pIfTable = (MIB_IFTABLE *) MALLOC(sizeof(MIB_IFTABLE));
	if (pIfTable == NULL) 
	{
		// log error return;
		return -1;
	}
	// Make an initial call to GetIfTable to get the
	// necessary size into dwSize
	dwSize = sizeof (MIB_IFTABLE);
	if (GetIfTable(pIfTable, &dwSize, FALSE) == ERROR_INSUFFICIENT_BUFFER) 
	{
		FREE(pIfTable);
		pIfTable = (MIB_IFTABLE *) MALLOC(dwSize);
		if (pIfTable == NULL) 
		{
			Log::Error("Error allocating memory needed to call GetIfTable\n");
			return -1;
		}
	}
	// Make a second call to GetIfTable to get the actual data we want.
	if ((dwRetVal = GetIfTable(pIfTable, &dwSize, FALSE)) == NO_ERROR) 
	{
		if(sp_internel_buffer == NULL)
		{
			sp_internel_buffer = (NetworkUtilization *) MALLOC(sizeof(NetworkUtilization)* pIfTable->dwNumEntries);
            if(!sp_internel_buffer)                
            {
                Log::Error("Alloc Iftable error");
			    return -1;
            }
			memset(sp_internel_buffer, 0, sizeof(NetworkUtilization)* pIfTable->dwNumEntries);
		}

		for (i = 0; i < (int) pIfTable->dwNumEntries; i++) 
		{
			pIfRow = (MIB_IFROW *) & pIfTable->table[i];
			// get in bytes;
			if(sp_internel_buffer[i].prevInBytes != 0)
			{
				sp_internel_buffer[i].inBytes = (sp_internel_buffer[i].prevInBytes > pIfRow->dwInOctets) 
					?0xFFFFFFFF - sp_internel_buffer[i].prevInBytes + pIfRow->dwInOctets
					:pIfRow->dwInOctets - sp_internel_buffer[i].prevInBytes;
			}
			sp_internel_buffer[i].prevInBytes = pIfRow->dwInOctets;
			// out bytes
			if(sp_internel_buffer[i].prevOutBytes != 0)
			{
				sp_internel_buffer[i].outBytes = (sp_internel_buffer[i].prevOutBytes > pIfRow->dwOutOctets) 
					?0xFFFFFFFF - sp_internel_buffer[i].prevOutBytes + pIfRow->dwOutOctets
					:pIfRow->dwOutOctets - sp_internel_buffer[i].prevOutBytes;
			}

			sp_internel_buffer[i].prevOutBytes = pIfRow->dwOutOctets;
			// get in packets;
			if(sp_internel_buffer[i].prevInPackets != 0)
			{
				sp_internel_buffer[i].inPackets = (sp_internel_buffer[i].prevInPackets > pIfRow->dwInUcastPkts) 
					?0xFFFFFFFF - sp_internel_buffer[i].prevInPackets + pIfRow->dwInUcastPkts
					:pIfRow->dwInUcastPkts - sp_internel_buffer[i].prevInPackets;
			}
			sp_internel_buffer[i].prevInPackets = pIfRow->dwInUcastPkts;
			// out packets
			if(sp_internel_buffer[i].prevOutPackets != 0)
			{
				sp_internel_buffer[i].outPackets = (sp_internel_buffer[i].prevOutPackets > pIfRow->dwOutUcastPkts) 
					?0xFFFFFFFF - sp_internel_buffer[i].prevOutPackets + pIfRow->dwOutUcastPkts
					:pIfRow->dwOutUcastPkts - sp_internel_buffer[i].prevOutPackets;
			}
			sp_internel_buffer[i].prevOutPackets = pIfRow->dwOutUcastPkts;

			pSystemPerf->inBytes += (int)(sp_internel_buffer[i].inBytes*1.0/HBTIMEOUT);
			pSystemPerf->outBytes += (int)(sp_internel_buffer[i].outBytes*1.0/HBTIMEOUT);
			pSystemPerf->inPackets += (int)(sp_internel_buffer[i].inPackets*1.0/HBTIMEOUT);
			pSystemPerf->outPackets += (int)(sp_internel_buffer[i].outPackets*1.0/HBTIMEOUT);
		}
	} 
	else 
	{
		if (pIfTable != NULL) 
		{
			FREE(pIfTable);
			pIfTable = NULL;
		}  
        Log::Error("GetIfTable failed");
		return -1;
	}
	if (pIfTable != NULL) 
	{
		FREE(pIfTable);
		pIfTable = NULL;
	}
	return 0;
}
int GetCPUUtilization(SystemPerformanceInfo *pSystemPerf)
{
	//Contains a 64-bit value representing the number of 100-nanosecond intervals since January 1, 1601 (UTC).
	static FILETIME prevIdleTime = {0,0};
	static FILETIME prevKernelTime = {0, 0};
	static FILETIME prevUserTime = {0, 0};
	FILETIME idleTime = {0,0};
	FILETIME kernelTime = {0, 0};
	FILETIME userTime = {0, 0};
	long long tmp;
	if(GetSystemTimes(&idleTime, &kernelTime, &userTime))  // get time success
	{
		if(prevIdleTime.dwLowDateTime || prevIdleTime.dwHighDateTime)
		{
			tmp = (idleTime.dwHighDateTime - prevIdleTime.dwHighDateTime);
			tmp = tmp << 32;
			tmp += idleTime.dwLowDateTime - prevIdleTime.dwLowDateTime;
			tmp = tmp / 10000;
			pSystemPerf->idleTime = tmp;
			prevIdleTime = idleTime;
		}
		else
		{
			prevIdleTime = idleTime;
			pSystemPerf->idleTime = 0;
		}
		if(prevKernelTime.dwLowDateTime || prevKernelTime.dwHighDateTime)
		{
			tmp = (kernelTime.dwHighDateTime - prevKernelTime.dwHighDateTime);
			tmp = tmp << 32;
			tmp += kernelTime.dwLowDateTime - prevKernelTime.dwLowDateTime;
			tmp = tmp / 10000;
			pSystemPerf->systemTime = tmp;
			prevKernelTime = kernelTime;
		}
		else
		{
			prevKernelTime = kernelTime;
			pSystemPerf->systemTime = 0;
		}
		if(prevUserTime.dwLowDateTime || prevUserTime.dwHighDateTime)
		{
			tmp = (userTime.dwHighDateTime - prevUserTime.dwHighDateTime);
			tmp = tmp << 32;
			tmp += userTime.dwLowDateTime - prevUserTime.dwLowDateTime;
			tmp = tmp / 10000;
			pSystemPerf->userTime = tmp;
			prevUserTime = userTime;
		}
		else
		{
			pSystemPerf->userTime = 0;
			prevUserTime = userTime;
		}
		return 0;
	}
	else
	{
		Log::Error("Get cpu performance failed %d", GetLastError());
		return -1;
	}
}
int GetDiskSpaceInfo(SystemPerformanceInfo *pSystemPerf)
{
	DWORD diskNumber = 0;
	DWORD diskDrivers;
	if(!(diskDrivers = GetLogicalDrives()))
	{
        Log::Error("Get device driver error");
		return -1;
	}
	char diskName[16] = {'A',':'};
	for(int i = 0; i < 26; i++)
	{
		int bitMask = 0x01;
		bitMask = bitMask << i;
		if(diskDrivers & bitMask)    // there is a bit
		{
			diskName[0] = 'A' + i;
			UINT diskType = GetDriveType(diskName);
			if(diskType == DRIVE_FIXED)
			{
				unsigned _int64  totalBytes;
				unsigned _int64  freeBytes;
				if(GetDiskFreeSpaceEx(diskName, NULL, (PULARGE_INTEGER)&totalBytes, (PULARGE_INTEGER)&freeBytes))
				{
					pSystemPerf->totalDiskSpace += (float)totalBytes/1024/1024;
					pSystemPerf->freeDiskSpace  += (float)freeBytes/1024/1024;
				}
			}
		}
	} 
	return 0;
}

int GetSystemDiskIOInfo(SystemPerformanceInfo *pSystemPerf)
{
	HDEVINFO hDevInfoSet;
	SP_DEVICE_INTERFACE_DATA ifData;
	PSP_DEVICE_INTERFACE_DETAIL_DATA pDetail = NULL;
	DWORD nCount;
	BOOL result;
	static LARGE_INTEGER  pervBytesRead[MAX_DISK_DEVICES] = { 0 };
	static LARGE_INTEGER  prevBytesWritten[MAX_DISK_DEVICES] = {0};

	// get handle to device information set
	hDevInfoSet = SetupDiGetClassDevs(const_cast<LPGUID>(&GUID_DEVINTERFACE_DISK), NULL, NULL, DIGCF_PRESENT|DIGCF_DEVICEINTERFACE);
	if(hDevInfoSet == INVALID_HANDLE_VALUE)
	{
		Log::Error("IOCTL_STORAGE_GET_DEVICE_NUMBER Error: %ld\n", GetLastError());
		return -1;
	}
	result = TRUE;
	nCount = 0;
	while(result)
	{
		ifData.cbSize = sizeof(ifData);
		result = SetupDiEnumDeviceInterfaces(hDevInfoSet, NULL, const_cast<LPGUID>(&GUID_DEVINTERFACE_DISK), nCount, &ifData);
		if(result)
		{
			DWORD requiredSize;
			// first get the DeviceInterfaceDetailData length
			result =SetupDiGetDeviceInterfaceDetail(hDevInfoSet, &ifData, NULL, 0, &requiredSize, NULL);
			SetLastError(0);
			pDetail = (PSP_DEVICE_INTERFACE_DETAIL_DATA)MALLOC(requiredSize);
			if(pDetail == NULL)
			{
				Log::Error("There is no memory %ld\n", GetLastError());
				SetupDiDestroyDeviceInfoList(hDevInfoSet);
				return -1;
			}
			pDetail->cbSize = sizeof(SP_DEVICE_INTERFACE_DETAIL_DATA);
			result = SetupDiGetDeviceInterfaceDetail(hDevInfoSet, &ifData, pDetail, requiredSize, NULL, NULL);
			if(result)  // get the device path
			{
				HANDLE hDevice = CreateFile(pDetail->DevicePath, GENERIC_READ, FILE_SHARE_READ, NULL, OPEN_EXISTING, 0, NULL);
				if(hDevice == INVALID_HANDLE_VALUE) 
				{
					FREE(pDetail);
					Log::Error("CreateFile() Error: %ld\n", GetLastError());
					SetupDiDestroyDeviceInfoList(hDevInfoSet);
					return -1;
				}
				DISK_PERFORMANCE diskPerf;
				DWORD readed;
				if(DeviceIoControl(hDevice, IOCTL_DISK_PERFORMANCE, NULL, 0, (LPVOID)&diskPerf, sizeof(diskPerf), &readed, NULL))
				{
					if(pervBytesRead[nCount].QuadPart)
					{
						pSystemPerf->diskRead += diskPerf.BytesRead.QuadPart - pervBytesRead[nCount].QuadPart;
					}
					pervBytesRead[nCount].QuadPart = diskPerf.BytesRead.QuadPart;

					if(prevBytesWritten[nCount].QuadPart)
					{
						pSystemPerf->diskWrite += diskPerf.BytesWritten.QuadPart - prevBytesWritten[nCount].QuadPart;
					}
					prevBytesWritten[nCount].QuadPart = diskPerf.BytesWritten.QuadPart;
				}
				CloseHandle(hDevice);
				nCount++;
			}
			FREE(pDetail);
		}
	}
	pSystemPerf->diskRead = (int)(pSystemPerf->diskRead * 1.0 / HBTIMEOUT);
	pSystemPerf->diskWrite = (int)(pSystemPerf->diskWrite*1.0/HBTIMEOUT);
	SetupDiDestroyDeviceInfoList(hDevInfoSet);
	return 0;
}
//int _tmain(int argc, _TCHAR* argv[])
//{
//    SystemPerformanceInfo perf;
//
//    for(int i = 0; i < 1000000000; i++)
//    {
//        memset((void*)&perf, 0, sizeof(SystemPerformanceInfo));
//        GetMemoryInfo(&perf);
//        GetNetworkPerfInfo(&perf);
//        GetCPUUtilization(&perf);
//        GetDiskSpaceInfo(&perf);
//        GetSystemDiskIOInfo(&perf);
//        cout << "System performance info: " << endl;
//        cout << "userTime: " << perf.userTime << " (ms)" << endl;
//        cout << "idleTime: " << perf.idleTime << " (ms)" << endl;
//        cout << "systemTime: " << perf.systemTime << " (ms)" << endl;
//        cout << "totalMemory: " << perf.totalMemory << " (Kbytes)" << endl;
//        cout << "freeMemory: " << perf.freeMemory << " (Kbytes)" << endl;
//        cout << "totalDiskSpace: " << perf.totalDiskSpace << " (Mbytes)" << endl;
//        cout << "freeDiskSpace: " << perf.freeDiskSpace << " (Mbytes)" << endl;
//        cout << "diskRead: " << perf.diskRead << " (Bytes)" << endl;
//        cout << "diskWrite: " << perf.diskWrite << " (Bytes)" << endl;
//        cout << "inBytes: " << perf.inBytes << " (Bytes)" << endl;
//        cout << "outBytes: " << perf.outBytes << " (Bytes)" << endl;
//        cout << "inPackets: " << perf.inPackets << endl;
//        cout << "outPackets: " << perf.outPackets << endl;
//    }
//    system("pause");
//    return 0;
//}

