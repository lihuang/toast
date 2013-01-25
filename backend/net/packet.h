/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef PACKET_H
#define PACKET_H
typedef unsigned int uint32;
#define HEADER_LENGTH   8
typedef struct
{
    uint32 length;    // length of the packet, doesn't include length field.
    uint32 number;       // number of the packet,
    char    data[1];    // date of the packet.
}Packet;
#endif
