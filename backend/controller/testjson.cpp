/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <iostream>
#include <string>
#include "../include/json/json.h"

using namespace std;
int main(int argc, char **argv)
{
    string jsonstr = "{\"1\":\"0 0 * * *\",\"22\":\"0 1 * * *\"}";
    Json::Reader reader;
    Json::Value root;
    bool parsingSuccessful = false;
    parsingSuccessful = reader.parse(jsonstr, root);
    Json::Value::Members mem = root.getMemberNames();
    for(Json::Value::Members::iterator mem_iter = mem.begin(); mem_iter != mem.end(); mem_iter++)
    {
        // first convert the mem to id which is int
        int id;
        sscanf((*mem_iter).c_str(), "%d", &id); 
      cout << "Task id: " << id << " time string: " << root[*mem_iter].asString() << endl;
    }

    return 0;
}
