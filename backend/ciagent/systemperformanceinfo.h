#ifndef SYSTEM_PERFORMANCE_H
#define SYSTEM_PERFORMANCE_H
int GetSystemDiskIOInfo(SystemPerformanceInfo *pSystemPerf);
int GetDiskSpaceInfo(SystemPerformanceInfo *pSystemPerf);
int GetCPUUtilization(SystemPerformanceInfo *pSystemPerf);
int GetMemoryInfo(SystemPerformanceInfo *pSystemPerf);
int GetNetworkPerfInfo(SystemPerformanceInfo *pSystemPerf);
int GetLoadInfo(SystemPerformanceInfo *pSystemPerf);
#endif
