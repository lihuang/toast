/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef BUFFER_H
#define BUFFER_H
#include "packet.h"
#include <list>
#include <string>
using namespace std;
struct SendBuffer
{
    volatile uint32 number;                 // send packet number
    std::list<char *> send_list;
    int current_send_length;       //the current send data length, include length field
    int current_send_pointer;      // record the current send pointer
    char *current_send_data;       // courrent send data buffer
    SendBuffer()
    {
        number = 0;
        current_send_pointer = 0;    // record the current send pointer
        current_send_data = NULL;  // courrent send data buffer
        current_send_length = 0;
    }
    ~SendBuffer()
    {
        delete [] current_send_data;
        list<char *>::iterator iter = send_list.begin();
        while(iter != send_list.end())
        {
            delete[] *iter;                   // delete the send_list elements
            iter++;
        }
        send_list.clear();
    }
};

struct ReceiveBuffer
{
    uint32 length;         // length of the data, not include length field
    uint32 number;
    char header_buffer[2*4];  // the header buffer,
    uint32 header_pointer;    // the header buffer pointer
    uint32 data_pointer;   // packet data pointer
    char *data_buffer;        // packet data buffer
    ReceiveBuffer()
    {
        length = 0;
        number = 0;
        header_pointer = 0;
        data_pointer = 0;
        data_buffer = NULL;
    }
    ~ReceiveBuffer()
    {
        delete [] data_buffer;
    }
};
#endif

