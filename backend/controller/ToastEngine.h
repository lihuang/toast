/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#ifndef _TOASTENGINE_H
#define _TOASTENGINE_H
#include <vector>
#include <string>
#include "../thread/threadbase.h"
namespace toast
{
class ToastEngine
{
    public:
        static ToastEngine* instance();
        void run();

    private:
        static ToastEngine* m_instance;
        ToastEngine();
        ~ToastEngine();
        bool LoadConfig(const std::string& filePath);
	void Initlize();
        bool ReLoadConfig(const std::string& filePath);
        bool GetInfoFromConfig();
	typedef std::vector<Thread*> ThreadVector;
        ThreadVector m_OtherThreadPool; 
        //MonitorSVNThread *m_pSVNMonitorThread;
        void CreateFunctionalThreads();

        void StartSVNMonitorThread();

        void StartThreads(std::vector<Thread*>& pool);
        void KillThreads(std::vector<Thread*>& pool);
};

}
#endif

