/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef MY_SOCKET_H
#define MY_SOCKET_H
#include<unistd.h>
class TCPSocket
{
public:
    // Create a new socket and return the socket fd
    static TCPSocket* CreateSocket(unsigned int addr, unsigned short port);
    static TCPSocket *CreateStreamListenSocket(unsigned int addr, unsigned short port, int backlog);
    int Accept(struct sockaddr *remote_addr);
    int SetReuseAddr();
    int SetNoDelay();
	int SetKeepalive();
	int SetRecviveBufferSize(int buf_size);
	int SetSendBufferSize(int buf_size);
	int SetNonblock();
	int SetNoLinger();

    int Connect(struct sockaddr *addr);
	int GetFd()
	{
		return m_socket_fd;
	}
	bool IsConnected(){return m_is_connected;}
    ~TCPSocket()
    {
        close(m_socket_fd);
        m_socket_fd = -1;
    }
private:
    TCPSocket():m_socket_fd(-1),m_is_connected(false){}
    TCPSocket(const TCPSocket &);
    TCPSocket & operator=(const TCPSocket &);
    int Socket();
	int Bind(struct sockaddr *addr);
	int Listen(int backlog);
	bool IsCreated(){return m_socket_fd == -1;}
	int m_socket_fd;
	bool m_is_connected;
};
#endif

