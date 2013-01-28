/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include "tcpsocket.h"
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <sys/uio.h>
#include <unistd.h>
#include <fcntl.h>
#include <netinet/tcp.h>
#include <string.h>
#include <new>

// Create a new socket and return the socket fd
TCPSocket* TCPSocket::CreateSocket(unsigned int addr, unsigned short port)
{
    TCPSocket *sock = new (std::nothrow)TCPSocket();
    if(!sock)
        return sock;

    if(sock->Socket() == -1)
    {
        //log create socket error and return -1
        delete sock;
        return NULL;
    }
    sock->SetReuseAddr();
    return sock;
}
TCPSocket *TCPSocket::CreateStreamListenSocket(unsigned int addr, unsigned short port, int backlog)
{
    TCPSocket *sock = new (std::nothrow)TCPSocket();
    if(!sock)
        return sock;

    if(sock->Socket() == -1)
    {
        //log create socket error and return -1
        delete sock;
        return NULL;
    }
    sock->SetReuseAddr();
    sock->SetNoDelay();
    sock->SetKeepalive();
    sock->SetNonblock();
    sock->SetNoLinger();

    struct sockaddr_in servaddr;
    memset(&servaddr, 0, sizeof(servaddr));
    servaddr.sin_family = AF_INET;
    servaddr.sin_addr.s_addr = htonl(addr);
    servaddr.sin_port = htons(port);
    if(sock->Bind((struct sockaddr*)&servaddr) == -1)
    {
        //log bind error and return 
        delete sock;
        return NULL;
    }
    if(sock->Listen(backlog) == -1)
    {
        delete sock;
        return NULL;
    }
    return sock;
}
int TCPSocket::Socket()
{
    m_socket_fd = ::socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
    return m_socket_fd;
}
int TCPSocket::Bind(struct sockaddr *addr)
{
    if(IsCreated() >= 0)
    {
        return ::bind(m_socket_fd, addr, sizeof(*addr));
    }
    else
        return -1;
}
int TCPSocket::Listen(int backlog)
{
    return ::listen(m_socket_fd, backlog);
}
int TCPSocket::Accept(struct sockaddr *remote_addr)
{
    socklen_t len = sizeof(sockaddr);
    return ::accept(m_socket_fd, remote_addr, &len);
}
int TCPSocket::Connect(struct sockaddr *addr)
{
    if(0 == ::connect(m_socket_fd, addr, sizeof(*addr)))
    {
        m_is_connected = true;
        return 0;
    }
    else
    {
        return -1;
    }
}
int TCPSocket::SetReuseAddr()
{
    int resuse_addr = 1;
    return setsockopt(m_socket_fd, SOL_SOCKET, SO_REUSEADDR, (const void *)&resuse_addr, sizeof(resuse_addr));
}
int TCPSocket::SetNoDelay()
{
    int tcp_nodelay = 1;
    return setsockopt(m_socket_fd, IPPROTO_TCP, TCP_NODELAY, (const void *) &tcp_nodelay, sizeof(int));
}
int TCPSocket::SetKeepalive()
{
    int keepalive = 1;
    return setsockopt(m_socket_fd, SOL_SOCKET, SO_KEEPALIVE, (const void *) &keepalive, sizeof(keepalive));
}
int TCPSocket::SetSendBufferSize(int buf_size)
{
    return setsockopt(m_socket_fd, SOL_SOCKET, SO_SNDBUF, &buf_size, sizeof(buf_size));
}
int TCPSocket::SetRecviveBufferSize(int buf_size)
{
    return setsockopt(m_socket_fd, SOL_SOCKET, SO_RCVBUF, &buf_size, sizeof(buf_size));
}
int TCPSocket::SetNonblock() 
{
    int opt = fcntl(m_socket_fd, F_GETFL, 0);
    return fcntl(m_socket_fd, F_SETFL, opt|O_NONBLOCK);
}
int TCPSocket::SetNoLinger()
{
	struct linger l;
	l.l_onoff = 1;
	l.l_linger = 0;
	return setsockopt(m_socket_fd, SOL_SOCKET, SO_LINGER, (void*)&l, sizeof(l));
}

