/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <locale.h>
#include <string>
#include "ToastEngine.h"
#include "../daemon/Daemon.h"
#include "../log/Log.h"
using namespace std;
using namespace toast;


int main(int argc,char* argv[])
{
    setlocale(LC_ALL, "");
    Daemon::Instance("./controller.conf")->StartDaemon(argc, argv, argv[0], 30);

    Log::Init(Daemon::Instance()->config);
    Log::set_prog_name(argv[0]);
    Log::set_mod_name("");
    //set global string value from config file 
    Log::Info("start to ToastEngine::instance()->run()");
    ToastEngine::instance()->run();

    return 0;
}
