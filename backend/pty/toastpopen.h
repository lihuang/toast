/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */



#ifndef TOASTPOPEN_H
#define	TOASTPOPEN_H
#ifdef WIN32
#include<Windows.h>
// The code is based on KB: Q190351 */
// http://support.microsoft.com/kb/190351
class ToastPopen
{
public:
    int Popen(const char *user, const char *cmdstring, HANDLE *h_output);
    int Pclose();
    void GetChildOutput();
    bool TerminateChild();
    HANDLE GetSubProcessID(){return m_hChildProcess;}
 public:
    bool PopenCreateProcess(HANDLE hChildStdOut, HANDLE hChildStdIn,
        HANDLE hChildStdErr, const char *cmd);
    HANDLE m_hOutputRead;
    HANDLE m_hInputWrite;
    HANDLE m_hChildProcess;
};
#else
#include <pthread.h>
#include <stdio.h>
#include <string>
class ToastPopen
{
public:
    ToastPopen();
     //tradition popen
     pid_t Popen(const char *user, const char *cmdstring, FILE** in, FILE **out, FILE **err);
     //popen and new a process group, the new process is the new group leader.
     pid_t PopenAndNewGroup(const char *user, const char *cmdstring, FILE** in, FILE **out, FILE **err);
     pid_t PopenPTY(const char *user, const char *cmdstring, int *master_fd);
     int Pclose();
     //input is pclose return code
     int GetReturnCode(int stat, int *ReturnCode);
     int GetCommandOutput(std::string &outResult, std::string &errResult, FILE *fp_stdout, FILE *fp_stderr, int nSeconds);
     pid_t GetSubProcessID()
     {
         return m_childpid;
     }
private:
    int SetUserEnvironment(const char *user);
    ToastPopen(const ToastPopen &);
    const ToastPopen &operator = (const ToastPopen &);
    pid_t m_childpid;    //this object's child pid
    FILE *m_in;
    FILE *m_out;
    FILE *m_err;
    int m_master_fd;   //the master fd of fork pty
    static long open_max();
    static int *m_opened_fds;
    static int m_maxfd;
 };
#endif
#endif	/* TOASTPOPEN_H */

