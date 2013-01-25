/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef SERVEREVENTPROCESSING_H
#define SERVEREVENTPROCESSING_H
class EpollManager;
class TCPSocket;
int SendProcessing(int fd);
int CloseProcessing(EpollManager *epoll_manager, int fd);
int ReceiveProcessing(EpollManager *epoll_manager, int fd);
int NewConnection(EpollManager *epoll_manager, TCPSocket *listen_socket, unsigned int events);
#endif

