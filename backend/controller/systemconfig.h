/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef TOAST_SYATEM_CONFIG_H
#define TOAST_SYSTEM_CONFIG_H
#include <string>
namespace toast
{
struct SystemConfig
{
std::string monitor_path;
std::string rrd_path;
std::string curl_url;       // = http://v132194.sqa.cm4.tbsite.net/toast/api/runtaskbyid?id=TASKID&build=BUILD&user=TOAST&desc=DESC
int    num_response_process_threads;
//int    db_port;
std::string log_path;
//std::string db_host;
//std::string db_user;
//std::string db_password;
//std::string db_name;
std::string root_url;
std::string task_list_url;
std::string update_agent_url;
std::string update_all_agent_url;
std::string update_all_run_url;
std::string update_run_url;
std::string run_timer_task_url;
std::string CI_agent;
};  
}
extern toast::SystemConfig *g_config;
#endif

