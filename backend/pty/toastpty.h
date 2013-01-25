/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */


#ifndef TOASTPTY_H
#define	TOASTPTY_H
int OpenPtyMaster(char *slaveName, size_t snLen);
pid_t ToastPtyFork(int *masterFd, char *slaveName, size_t snLen,
        const struct termios *slaveTermios, const struct winsize *slaveWS);
int SetPtyUserEnvironment(struct passwd *pwd, const char *pty_name);

#endif	/* TOASTPTY_H */

