/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef SYSTEM_PERFORMANCE_H
#define SYSTEM_PERFORMANCE_H
int GetSystemDiskIOInfo(SystemPerformanceInfo *pSystemPerf);
int GetDiskSpaceInfo(SystemPerformanceInfo *pSystemPerf);
int GetCPUUtilization(SystemPerformanceInfo *pSystemPerf);
int GetMemoryInfo(SystemPerformanceInfo *pSystemPerf);
int GetNetworkPerfInfo(SystemPerformanceInfo *pSystemPerf);
int GetLoadInfo(SystemPerformanceInfo *pSystemPerf);
#endif
