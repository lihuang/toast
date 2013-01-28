/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include "sendreceivedata.h"
#include "packet.h"
#include "buffer.h"
#include "../log/Log.h"
#ifdef WIN32
#include <winsock2.h>
#else
#include <fcntl.h>
#include <errno.h>
#include <sys/socket.h>
#include <unistd.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <sys/epoll.h>
typedef int SOCKET;
#endif
using namespace std;
// -1 error occure otherwise the byte of data send
static int SendData(SOCKET fd, SendBuffer *send_buffer)
{
    int num_write = 0;
    if(send_buffer->current_send_data != NULL)   //there is packet that not send completed
    {
        // send the 
        int remain_data_length = send_buffer->current_send_length - send_buffer->current_send_pointer;
        num_write = send(fd, send_buffer->current_send_data + send_buffer->current_send_pointer, remain_data_length, 0);
        if(num_write == SOCKET_ERROR)
        {
#ifdef WIN32
            if(WSAGetLastError() == WSAEWOULDBLOCK)
            {
                return 0;
            }
#else
            if(errno == EAGAIN)  //again the TCP/IP send buffer is full
            {
                return 0;
            }
#endif
            // there is some error occur
            return -1;
        }
        else if(num_write < remain_data_length)  
        {
            send_buffer->current_send_pointer += num_write;
        }
        else
        {
            //Log::Debug("Send packet %d length: %d fd: %d", send_buffer->number, send_buffer->current_send_length, fd);
            delete[] send_buffer->current_send_data;
            send_buffer->current_send_data    = NULL;
            send_buffer->current_send_length  = 0;
            send_buffer->current_send_pointer = 0;
        }        
    }
    return num_write;
}
// 0 need send another time(send buffer is full), 1 all data sendout,  -1 error
int SendDataInBuffer(SOCKET fd, SendBuffer *send_buffer)
{
    int num_send;
    if(send_buffer->current_send_data != NULL)   //there is packet that not send completed
    {
        // send the 
        int remain_data_length = send_buffer->current_send_length - send_buffer->current_send_pointer;
        num_send = SendData(fd, send_buffer);
        if(num_send == -1)
        {
            return -1; // there is some error occur
        }
        else if(remain_data_length != num_send)
        {
            return 0;
        }
    }
    while(!send_buffer->send_list.empty()) // send list not empty send
    {
        send_buffer->current_send_data = send_buffer->send_list.front();
        send_buffer->current_send_length = *(uint32*)(send_buffer->current_send_data) + 4;
        int need_send = send_buffer->current_send_length;
        send_buffer->current_send_pointer = 0;
        send_buffer->send_list.pop_front();
        num_send = SendData(fd, send_buffer);
        if(num_send == -1)
        {
            return -1; // there is some error occur
        }
        else if(need_send != num_send)
        {
            return 0;
        }
    }
    return 1;
}
// packet transfer up lyer is
// agent response data
// 0 end of file, the client is closed
// 1 the receive buffer is empty
// -1 error
// permeter peer, for server this is agent id, it's agent fd, for client is unused
int ReceiveDataToBuffer(SOCKET fd, int peer, ReceiveBuffer *receive_buffer, ReceivePacketCallBack cb)
{
    int num_reads = 0;
    int req_reads;
    while(1)   // read data until endof file or eagain
    {
        if(receive_buffer->header_pointer < 4)  // the length of the packet header field has not received
        {
            int req_reads = 4-receive_buffer->header_pointer;
            num_reads = recv(fd, &(receive_buffer->header_buffer[receive_buffer->header_pointer]), 4-receive_buffer->header_pointer, 0);
            if(num_reads == SOCKET_ERROR)
            {
#ifdef WIN32
                if(WSAGetLastError() == WSAEWOULDBLOCK)
                {
                    return 1;
                }
                else
                {
                    return -1;
                }
#else                
                if(errno == EAGAIN)   // Thrre is no data in the socket buffer.
                {
                    return 1;
                }
                else    // make sure process EINTR
                {
                    return -1;
                }
#endif 
            }
            else if(num_reads == 0)
            {
                return 0;
            }
            // check if the length field is completed or not
            receive_buffer->header_pointer += num_reads;
            if(receive_buffer->header_pointer == 4)   // length is completed
            {
                //new data buffer        *static_cast<uint32*>(receive_buffer->header_buffer)
                receive_buffer->length = *(uint32*)(receive_buffer->header_buffer);
                receive_buffer->data_buffer = new (std::nothrow)char[receive_buffer->length];  // 
                if(receive_buffer->data_buffer)
                {
                    *(int*)(receive_buffer->data_buffer) = peer;
                }
            }
            if(num_reads < req_reads)
            {
                return 1;
            }
        }
        else if(receive_buffer->header_pointer < HEADER_LENGTH)
        {
            req_reads = HEADER_LENGTH-receive_buffer->header_pointer;
            num_reads = recv(fd, &(receive_buffer->header_buffer[receive_buffer->header_pointer]), HEADER_LENGTH-receive_buffer->header_pointer, 0);
            if(num_reads == SOCKET_ERROR)
            {
#ifdef WIN32
                if(WSAGetLastError() == WSAEWOULDBLOCK)
                {
                    return 1;
                }
                else
                {
                    return -1;
                }
#else                        
                if(errno == EAGAIN)
                {
                    return 1;
                }
                else
                {
                    return -1;
                }
#endif
            }
            else if(num_reads == 0)
            {
                return 0;
            }
            // check if the number field is completed or not
            receive_buffer->header_pointer += num_reads;
            if(receive_buffer->header_pointer == HEADER_LENGTH)
            {
                receive_buffer->number = *(uint32*)(receive_buffer->header_buffer + 4);
            }
            if(num_reads < req_reads)
            {
                return 1;
            }
        }
        else
        {
            if(receive_buffer->data_buffer)
            {
                // otherwise recv it to the data buffer
                req_reads = receive_buffer->length- receive_buffer->data_pointer - 4;
                num_reads = recv(fd, &(receive_buffer->data_buffer[receive_buffer->data_pointer + 4]), receive_buffer->length- receive_buffer->data_pointer - 4, 0);
                if(num_reads == SOCKET_ERROR)
                {
#ifdef WIN32
                    if(WSAGetLastError() == WSAEWOULDBLOCK)
                    {
                        return 1;
                    }
                    else
                    {
                        return -1;
                    }
#else                            
                    if(errno == EAGAIN)
                    {
                        return 1;
                    }
                    else    // other error 
                    {
                        return -1;
                    }
#endif
                }
                else if(num_reads == 0)
                {
                    return 0;
                }

                // check if the length field is completed or not
                receive_buffer->data_pointer += num_reads;
                if(receive_buffer->data_pointer == receive_buffer->length - 4)
                {
                    // receive a packet, send it to receive packet queue and set new data pointer
                    // lack of send to receive buffer
                    // call the callback function
                    // check is the packet is not 0
                    if(receive_buffer->data_pointer)
                    {
                        //Log::Debug("Receive packet %d length %d from %d", receive_buffer->number, receive_buffer->length, peer);
                        cb(receive_buffer->data_buffer, receive_buffer->data_pointer);
                    }
                    else
                        delete[] receive_buffer->data_buffer;  //  deelet1 byte data

                    // delete[] receive_buffer->data_buffer;           decrement data copy
                    receive_buffer->data_buffer = NULL;
                    receive_buffer->data_pointer = 0;
                    receive_buffer->header_pointer = 0;
                }
                if(num_reads < req_reads)
                {
                    return 1;
                }
            }                      
            else// if the data buffer new failed, we drop the current packet data
            {
                char buf[4096];
                if((receive_buffer->length- receive_buffer->data_pointer - 4) >= 4096)
                {
                    req_reads = 4096;
                    num_reads = recv(fd, buf, 4096, 0);
                    if(num_reads == SOCKET_ERROR)
                    {
#ifdef WIN32
                        if(WSAGetLastError() == WSAEWOULDBLOCK)
                        {
                            return 1;
                        }
                        else
                        {
                            return -1;
                        }
#else        
                        if(errno == EAGAIN)
                        {
                            return 1;
                        }
                        else    // other error 
                        {
                            return -1;
                        }
#endif
                    }
                    else if(num_reads == 0)
                    {
                        return 0;
                    }
                    receive_buffer->data_pointer += num_reads;
                    if(num_reads < req_reads)
                        return 1;
                }
                else if(receive_buffer->length- receive_buffer->data_pointer - 4) // 0<data < 4096
                {
                    req_reads = receive_buffer->length- receive_buffer->data_pointer - 4;
                    num_reads = recv(fd, buf, receive_buffer->length- receive_buffer->data_pointer - 4, 0);
                    if(num_reads == SOCKET_ERROR)
                    {
#ifdef WIN32
                        if(WSAGetLastError() == WSAEWOULDBLOCK)
                        {
                            return 1;
                        }
                        else
                        {
                            return -1;
                        }
#else                                
                        if(errno == EAGAIN)
                        {
                            return 1;
                        }
                        else    // other error 
                        {
                            return -1;
                        }
#endif
                    }
                    else if(num_reads == 0)
                    {
                        return 0;
                    }
                    receive_buffer->data_pointer += num_reads;
                    if(num_reads < req_reads)
                    {
                        return 1;
                    }
                }      // all the data read out
                else
                {
                    Log::Error("Packet %d, length % droped, due to leck of memory", receive_buffer->number, receive_buffer->length - 4);
                    receive_buffer->data_buffer = NULL;
                    receive_buffer->data_pointer = 0;
                    receive_buffer->header_pointer = 0;
                }
            }
        }
    }
    return 1;   // normal can't go there usually return from EAGAIN
}
