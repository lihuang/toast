/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef CONNECTIONMANAGER_H
#define CONNECTIONMANAGER_H
#include "../thread/threadbase.h"
class CommThread:public Thread
    {
    public:
        CommThread();
    private:
        void Run(void);
    };
#endif

