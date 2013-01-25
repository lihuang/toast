/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <sys/statvfs.h>
#include <mntent.h>
#include <iostream>
#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <sys/ioctl.h> // for ioctl
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <linux/fs.h>  // for BLKSSZGET
#include <map>
#include <string>
#include <unistd.h>
#include <errno.h>
#include "../agentcmdrsp/agentcmdrsp.h"
#include "../log/Log.h"
#define DISK_STATS         "/proc/diskstats"
#define DISK_PARTITIONS    "/proc/partitions"
#define PROC_STAT          "/proc/stat"
#define PROC_MEMINFO       "/proc/meminfo"
#define PROC_NET_DEV       "/proc/net/dev"
#define PROC_LOAD           "/proc/loadavg"
//#include <fcntl.h>
using namespace std;
//
int GetLoadInfo(SystemPerformanceInfo *pSystemPerf)
{
    int load1mh = 0;
	int load1ml = 0;
	int load5mh =0;
	int load5ml= 0;
	int load15mh=0;
	int load15ml = 0;
        FILE* fp = 0;
	if ((fp = fopen(PROC_LOAD, "r")) == NULL) 
		{
			       Log::Error("Open /proc/loadavg failed");
		return -1;	
		}
   	fscanf(fp, "%d.%d %d.%d %d.%d ", &load1mh, &load1ml, &load5mh, &load5ml, &load15mh, &load15ml);
	pSystemPerf->load1min = load1mh * 100 + load1ml;
	pSystemPerf->load5min = load5mh * 100 + load5ml;
	pSystemPerf->load15min = load15mh * 100 + load15ml;
        fclose(fp);
	return 0;
	
}

// Inter-|   Receive                                                |  Transmit
// face |bytes    packets errs drop fifo frame compressed multicast|bytes    packets errs drop fifo colls carrier compressed
//    lo:     480       8    0    0    0     0          0         0      480       8    0    0    0     0       0          0
//  eth0:  186481    1047    0    0    0     0          0         0    27413     261    0    0    0     0       0          0
//virbr0:       0       0    0    0    0     0          0         0     2421      26    0    0    0     0       0          0
int GetNetworkPerfInfo(SystemPerformanceInfo *pSystemPerf)
{
	// PROC_NET_DEV
	static unsigned long long prevTotalInBytes = 0;
	static unsigned long long prevTotalOutBytes = 0;
	static unsigned long long prevTotalInPackets = 0;
	static unsigned long long prevTotalOutPackets = 0;
	unsigned long long inBytes = 0;
	unsigned long long outBytes = 0;
	unsigned long long inPackets = 0;
	unsigned long long outPackets = 0;
	unsigned long long totalInBytes = 0;
	unsigned long long totalOutBytes = 0;
	unsigned long long totalInPackets = 0;
	unsigned long long totalOutPackets = 0;
	char line[1024];
	FILE *fp_net = fopen(PROC_NET_DEV, "r");
	if(fp_net == NULL)
	{
	       Log::Error("Open /proc/dev/net failed");
		// log error
		return -1;
	}
	// skip first tow line
	fgets(line, 1024, fp_net);
	fgets(line, 1024, fp_net);
	while (fgets(line, 1024, fp_net) != NULL) 
	{
		if(strstr(line, "lo") || strstr(line, "bond"))
		{
			continue;
		}
		char *p = strchr(line, ':');

		sscanf(p + 1, "%llu %llu %*u %*u %*u %*u %*u %*u %llu %llu %*u %*u %*u %*u %*u %*u",
			&inBytes, &inPackets, &outBytes, &outPackets);
		totalInBytes += inBytes;
		totalInPackets += inPackets;
		totalOutBytes += outBytes;
		totalOutPackets += outPackets;
	}
	if(prevTotalInBytes != 0)
	{
		pSystemPerf->inBytes = (int)((totalInBytes - prevTotalInBytes) * 1.0 /HBTIMEOUT);		
	}
	if(prevTotalInPackets != 0)
	{
		pSystemPerf->inPackets = (int)((totalInPackets - prevTotalInPackets)*1.0/HBTIMEOUT);
	}
	if(prevTotalOutBytes != 0)
	{
		pSystemPerf->outBytes = (int)((totalOutBytes - prevTotalOutBytes)*1.0 /HBTIMEOUT);
	}
	if(prevTotalOutPackets != 0)
	{
		pSystemPerf->outPackets = (int)((totalOutPackets - prevTotalOutPackets)*1.0/HBTIMEOUT);
	}
	prevTotalInBytes = totalInBytes;
	prevTotalOutBytes = totalOutBytes;
	prevTotalInPackets = totalInPackets;
	prevTotalOutPackets = totalOutPackets;
	fclose(fp_net);
	return 0;
}
int GetMemoryInfo(SystemPerformanceInfo *pSystemPerf)
{
	//int totalMemory;      // K bytes
	//int freeMemory;       // K bytes
	unsigned long c_total;
	unsigned long c_free;
	char line[4096];
	FILE *fp_meminfo = fopen(PROC_MEMINFO, "r");
	if(fp_meminfo == NULL)
	{
		Log::Error("Open /proc/meminfo error");
		return -1;
	}
	while(fgets(line, 4096, fp_meminfo))
	{
		if(!strncmp(line, "MemTotal:", 9))
		{
			sscanf(line + 9, "%lu", &c_total);
			pSystemPerf->totalMemory = c_total;
		}
		else if(!strncmp(line, "MemFree:", 8))
		{
			sscanf(line + 8, "%lu", &c_free);
			pSystemPerf->freeMemory = c_free;
		}
	}
	fclose(fp_meminfo);
	return 0;
}
int GetCpuFeq()
{
	int feq;
	errno = 0;
	feq = sysconf(_SC_CLK_TCK);
	if((feq == -1) && (errno != 0))
	{
		feq = 100;
	}
	return feq;
}
int GetCPUUtilization(SystemPerformanceInfo *pSystemPerf)
{
	static unsigned long long prev_user = 0;
	static unsigned long long prev_sys = 0;
	static unsigned long long prev_nice = 0; // no use
	static unsigned long long prev_idle = 0;
	unsigned long long c_user = 0;
	unsigned long long c_sys = 0;
	unsigned long long c_nice = 0; // no use
	unsigned long long c_idle = 0;
	char line[4096];
	FILE *fp_stat = fopen(PROC_STAT, "r");
	if(fp_stat == NULL)
	{
		Log::Error("Open /proc/stat failed" );
		return -1;
	}
	while (fgets(line, 4096, fp_stat) != NULL) 
	{
		if (!strncmp(line, "cpu ", 4)) 
		{
			sscanf(line+5, "%llu %llu %llu %llu", &c_user, &c_nice, &c_sys, &c_idle);
			if(prev_user != 0)
			{
			      pSystemPerf->userTime = (prev_user < c_user) ? (c_user - prev_user):(prev_user - c_user);
			}
			if(prev_sys != 0)
			{
			
				pSystemPerf->systemTime = (prev_sys < c_sys) ? (c_sys - prev_sys) : (prev_sys - c_sys);
			}
			if(prev_idle != 0)
			{
				pSystemPerf->idleTime = (prev_idle < c_idle) ? (c_idle - prev_idle) : (prev_idle - c_idle);
			}
			prev_user = c_user;
			prev_sys = c_sys;
			prev_nice = c_nice;
			prev_idle = c_idle;
		}
	}
	fclose(fp_stat);
	return 0;
}
#define MOUNT_FILE           "/proc/mounts"  // "/etc/mtab"
int GetDiskSpaceInfo(SystemPerformanceInfo *pSystemPerf)
{
	//int totalDiskSpace;    // unit M bytes
	//int freeDiskSpace;     // M bytes
	// open the mount file
	FILE *mnt_file = setmntent(MOUNT_FILE, "r");
	if(mnt_file == NULL)
	{
		Log::Error("Open /porc/mounts failed");
		return -1;
	}

	// iterator the mount files
	struct mntent *mnt = NULL;
	while((mnt = getmntent(mnt_file)))
	{
		if(!strncmp(mnt->mnt_fsname, "/dev", 4))
		{
			struct statvfs svfs;
			if (statvfs(mnt->mnt_dir, &svfs)) 
			{
				continue;
			}
			pSystemPerf->totalDiskSpace += (int)((double)svfs.f_blocks * svfs.f_bsize / 1000000.0);
			pSystemPerf->freeDiskSpace += (int)((double)svfs.f_bavail * svfs.f_bsize /1000000.0);
		}
	}
	endmntent(mnt_file);
	return 0;
}
///proc/diskstats
// 8       0 sda 27457 26261 1074454 197975 5609 8659 110218 925701 0 138905 1123678
// major, minor name
/*
Field  1 -- # of reads completed 27457
This is the total number of reads completed successfully.
Field  2 -- # of reads merged, field 6 -- # of writes merged
Reads and writes which are adjacent to each other may be merged for
efficiency.  Thus two 4K reads may become one 8K read before it is
ultimately handed to the disk, and so it will be counted (and queued)
as only one I/O.  This field lets you know how often this was done.
Field  3 -- # of sectors read  1074454
This is the total number of sectors read successfully.
Field  4 -- # of milliseconds spent reading
This is the total number of milliseconds spent by all reads (as
measured from __make_request() to end_that_request_last()).
Field  5 -- # of writes completed
This is the total number of writes completed successfully.
Field  7 -- # of sectors written  110218
This is the total number of sectors written successfully.
Field  8 -- # of milliseconds spent writing
This is the total number of milliseconds spent by all writes (as
measured from __make_request() to end_that_request_last()).
Field  9 -- # of I/Os currently in progress
The only field that should go to zero. Incremented as requests are
given to appropriate struct request_queue and decremented as they finish.
Field 10 -- # of milliseconds spent doing I/Os
This field increases so long as field 9 is nonzero.
Field 11 -- weighted # of milliseconds spent doing I/Os
This field is incremented at each I/O start, I/O completion, I/O
merge, or read of these stats by the number of I/Os in progress
(field 9) times the number of milliseconds spent doing I/O since the
last update of this field.  This can provide an easy measure of both
I/O completion time and the backlog that may be accumulating.*/
struct diskIOInfo {
	unsigned int major;	/* Device major number */
	unsigned int minor;	/* Device minor number */
	char name[32];
	unsigned long long read_completed;	
	unsigned long long read_merged;	
	unsigned long long read_sectors;
	unsigned long long read_time;	
	unsigned long long write_completed;
	unsigned long long write_merged;	
	unsigned long long write_sectors; 
	unsigned long long write_time;
	unsigned long long spend_io;
	unsigned long long weighted_time;
};
int GetSectorSize(const char *dev_name)
{
	int fd; 
	unsigned long size; 
	char fileName[128] = "/dev/";
	strcat(fileName, dev_name);
	fd = open(fileName, O_RDONLY); 
	if(fd == -1)
	{
	       Log::Warn("Open /dev/%s failed", dev_name);
		return -1;
	}

	if(ioctl(fd, BLKSSZGET, &size) == -1)  // get sector number error return default num
	{
		size = 512;
	}
	close(fd);
	return size;
}
int GetPartitionsNumber()
{
	FILE *disk_partitons_file = fopen(DISK_STATS, "r");
	if(disk_partitons_file == NULL)
	{
		return -1;
	}
	const char *fmt = "%4d %4d";
	char tmp_buf[256];
	int major, minor;
	int part_num = 0;
	while(fgets(tmp_buf, sizeof(tmp_buf), disk_partitons_file))
	{
		int items = sscanf(tmp_buf, fmt, &major, &minor);
		if(items != 2)
			continue;		
		part_num++;
	}
	fclose(disk_partitons_file);
	return part_num;
}
int GetPartitions(diskIOInfo *disk_info)
{
	FILE *disk_partitons_file = fopen(DISK_STATS, "r");
	if(disk_partitons_file == NULL)
	{
	       Log::Error("Open disk stat failed");
		return -1;
	}
	const char *fmt = "%4d %4d";
	char tmp_buf[256];
	int major, minor;
	int part_num = 0;
	while(fgets(tmp_buf, sizeof(tmp_buf), disk_partitons_file))
	{
		int items = sscanf(tmp_buf, fmt, &major, &minor);
		if(items != 2)
			continue;
		disk_info[part_num].major = major;
		disk_info[part_num].minor = minor;
		part_num++;
	}
	fclose(disk_partitons_file);
	return part_num++;
}

int GetSystemDiskIOInfo(SystemPerformanceInfo *pSystemPerf)
{
	unsigned long long total_read_bytes = 0;
	unsigned long long total_write_bytes = 0;
	static diskIOInfo prevInfo[128] = {{0}};
	static int stored_partition_num = 0;

	if(stored_partition_num == 0)
	{
		stored_partition_num = GetPartitionsNumber();
		GetPartitions(prevInfo);
	}

	FILE *disk_stat_file = fopen(DISK_STATS, "r");
	if(disk_stat_file == NULL)
	{
		// log error   1       0 ram0 0 0 0 0 0 0 0 0 0 0 0
		Log::Error("Open /proc/diskstats failed");
		return -1;
	}
	const char *fmt = "%4d %4d %s %u %u %llu %u %u %u %llu %u %*u %u %u";
	char tmp_buf[256];
	while(fgets(tmp_buf, sizeof(tmp_buf), disk_stat_file))
	{
		diskIOInfo diskInfo;
		sscanf(tmp_buf, fmt, &diskInfo.major, &diskInfo.minor, &diskInfo.name,
			&diskInfo.read_completed, &diskInfo.read_merged, &diskInfo.read_sectors, &diskInfo.read_time,
			&diskInfo.write_completed, &diskInfo.write_merged, &diskInfo.write_sectors, &diskInfo.write_time,
			&diskInfo.spend_io, &diskInfo.weighted_time);
		if(diskInfo.read_sectors != 0)
		{
			for(int i = 0; i < 128; i++)
			{
				if(prevInfo[i].major == diskInfo.major && prevInfo[i].minor == diskInfo.minor)
				{
					int sector_size = GetSectorSize(diskInfo.name);
					if(sector_size == -1)
						break;
					if(prevInfo[i].read_sectors != 0)
					{
						total_read_bytes += (diskInfo.read_sectors - prevInfo[i].read_sectors) * sector_size;
					}

					if(prevInfo[i].write_sectors != 0)
					{
						total_write_bytes += (diskInfo.write_sectors - prevInfo[i].write_sectors) *sector_size;
					}
					prevInfo[i] = diskInfo;
				}
			}
		}
	}
	pSystemPerf->diskRead = (int)(total_read_bytes* 1.0 /HBTIMEOUT);
	pSystemPerf->diskWrite = (int)(total_write_bytes*1.0/HBTIMEOUT);
	fclose(disk_stat_file);
	return 0;
}

/*
int main(int argc, char* argv[])
{
	SystemPerformanceInfo perf;

	for(int i = 0; i < 10; i++)
	{
		memset((void*)&perf, 0, sizeof(SystemPerformanceInfo));
		SystemPerformanceInfo perf;
		GetMemoryInfo(&perf);
		GetNetworkPerfInfo(&perf);
		GetCPUUtilization(&perf);
		GetDiskSpaceInfo(&perf);
		GetSystemDiskIOInfo(&perf);
		cout << "System performance info: " << endl;
		cout << "userTime: " << perf.userTime << " (ms)" << endl;
		cout << "idleTime: " << perf.idleTime << " (ms)" << endl;
		cout << "systemTime: " << perf.systemTime << " (ms)" << endl;
		cout << "totalMemory: " << perf.totalMemory << " (Kbytes)" << endl;
		cout << "freeMemory: " << perf.freeMemory << " (Kbytes)" << endl;
		cout << "totalDiskSpace: " << perf.totalDiskSpace << " (Mbytes)" << endl;
		cout << "freeDiskSpace: " << perf.freeDiskSpace << " (Mbytes)" << endl;
		cout << "diskRead: " << perf.diskRead << " (Bytes)" << endl;
		cout << "diskWrite: " << perf.diskWrite << " (Bytes)" << endl;
		cout << "inBytes: " << perf.inBytes << " (Bytes)" << endl;
		cout << "outBytes: " << perf.outBytes << " (Bytes)" << endl;
		cout << "inPackets: " << perf.inPackets << endl;
		cout << "outPackets: " << perf.outPackets << endl;
		sleep(10);
	}
	return 0;
}*/

