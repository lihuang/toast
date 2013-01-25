/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef JSON_COMMAND_PROCESS_H
#define JSON_COMMAND_PROCESS_H
#include <string>
#include "../include/json/json.h"
class CommandProcessor
{
public:
    CommandProcessor(std::string &json_cmd_str);
    int IsValidateCommand();
    void ProcessingCommand();
private:
    CommandProcessor(const CommandProcessor&);
    CommandProcessor& operator=(const CommandProcessor&);
    void ParseTimerTask();
    void ParseRunCommand();
	void ParseCICommand();
    int SendCommandToAgent(int run_type, const std::string &account, 
    const std::string& command, const std::string &agent, int id, int timeout);
    int SendCICommand(std::string command, int task_id, const std::string &ci_url);

    std::string m_test_type;
    std::string m_run_id;
    Json::Value m_commands;
    int    m_validate_cmd;
};
#endif
