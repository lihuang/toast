/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#ifdef WIN32
#include "toastpopen.h"
#include "../log/Log.h"
bool ToastPopen::TerminateChild()
{
    return TerminateProcess(m_hChildProcess, -1);
}
void ToastPopen::GetChildOutput()
{
    return;
}
bool ToastPopen::PopenCreateProcess(HANDLE hChildStdIn,
    HANDLE hChildStdOut,
    HANDLE hChildStdErr,
    const char *cmd)
{
    PROCESS_INFORMATION pi;
    STARTUPINFO si;
    DWORD dwProcessFlags = CREATE_NEW_CONSOLE;
    // Set up the start up info struct.
    ZeroMemory(&si,sizeof(STARTUPINFO));
    si.cb = sizeof(STARTUPINFO);
    si.dwFlags = STARTF_USESTDHANDLES | STARTF_USESHOWWINDOW;
    si.hStdOutput = hChildStdOut;
    si.hStdInput  = hChildStdIn;
    si.hStdError  = hChildStdErr;
    // Use this if you want to hide the child:
    si.wShowWindow = SW_SHOWDEFAULT; //SW_MINIMIZE; //SW_SHOWDEFAULT; //SW_HIDE;
    char *command = new (std::nothrow)char[strlen(cmd) + 1];
    sprintf(command, "%s",cmd); 
    if (!CreateProcess(NULL,command,NULL,NULL,TRUE,
        CREATE_NEW_CONSOLE,NULL,NULL,&si,&pi))
        Log::Error("CreateProcess");
    else
    {

        // Set global child process handle to cause threads to exit.
        m_hChildProcess = pi.hProcess;
        Log::Info("Child PID %d", pi.dwProcessId);
        // Close any unnecessary handles.
        if (!CloseHandle(pi.hThread)) 
            Log::Error("CloseHandle");
        delete [] command;
        return true;
    }
    delete [] command;
    return false;
}
int ToastPopen::Popen(const char *user, const char *cmdstring, HANDLE *h_output)
{
    HANDLE hOutputReadTmp,hOutputWrite;
    HANDLE hInputWriteTmp,hInputRead;
    HANDLE hErrorWrite;
    SECURITY_ATTRIBUTES sa;


    // Set up the security attributes struct.
    sa.nLength= sizeof(SECURITY_ATTRIBUTES);
    sa.lpSecurityDescriptor = NULL;
    sa.bInheritHandle = TRUE;


    // Create the child output pipe.
    // Parent read and child output pipe
    if (!CreatePipe(&hOutputReadTmp,&hOutputWrite,&sa,0))
    {
        Log::Error("CreatePipe");
        return -1;
    }


    // Create a duplicate of the output write handle for the std error
    // write handle. This is necessary in case the child application
    // closes one of its std output handles.
    if (!DuplicateHandle(GetCurrentProcess(),hOutputWrite,
        GetCurrentProcess(),&hErrorWrite,0,
        TRUE,DUPLICATE_SAME_ACCESS))
    {
        Log::Error("DuplicateHandle");
        return -1;
    }
    // Create the child input pipe.
    if (!CreatePipe(&hInputRead,&hInputWriteTmp,&sa,0))
    {
        Log::Error("CreatePipe");
        return -1;
    }
    // Create new output read handle and the input write handles. Set
    // the Properties to FALSE. Otherwise, the child inherits the
    // properties and, as a result, non-closeable handles to the pipes
    // are created.
    if (!DuplicateHandle(GetCurrentProcess(),hOutputReadTmp,
        GetCurrentProcess(),
        &m_hOutputRead, // Address of new handle.
        0,FALSE, // Make it uninheritable.
        DUPLICATE_SAME_ACCESS))
    {
        Log::Error("DuplicateHandle");
        return -1;
    }

    if (!DuplicateHandle(GetCurrentProcess(),hInputWriteTmp,
        GetCurrentProcess(),
        &m_hInputWrite, // Address of new handle.
        0,FALSE, // Make it uninheritable.
        DUPLICATE_SAME_ACCESS))
    {
        Log::Error("DuplicateHandle");
        return -1;
    }
    // Close inheritable copies of the handles you do not want to be
    // inherited.
    if (!CloseHandle(hOutputReadTmp))
    {
        Log::Error("CloseHandle");
        return -1;
    }
    if (!CloseHandle(hInputWriteTmp)) 
    {
        Log::Error("CloseHandle");
        return -1;
    }

    if (!PopenCreateProcess(
        hInputRead,
        hOutputWrite,
        hErrorWrite,cmdstring))
    {
        Log::Error("PopenCreateProcess");
        return -1;
    }

    // Close pipe handles (do not continue to modify the parent).
    // You need to make sure that no handles to the write end of the
    // output pipe are maintained in this process or else the pipe will
    // not close when the child process exits and the ReadFile will hang.
    if (!CloseHandle(hOutputWrite)) 
    {
        Log::Error("CloseHandle");
        return -1;
    }
    if (!CloseHandle(hInputRead )) 
    {
        Log::Error("CloseHandle");
        return -1;
    }
    if (!CloseHandle(hErrorWrite))
    {
        Log::Error("CloseHandle");
        return -1;
    }
    *h_output = m_hOutputRead;
    return 0;
}


int ToastPopen::Pclose()
{
    DWORD exit_code;
    int result;
    // Tell the thread to exit and wait for thread to die.
    if (!CloseHandle(m_hOutputRead)) Log::Error("CloseHandle");
    if (!CloseHandle(m_hInputWrite)) Log::Error("CloseHandle");

    if (WaitForSingleObject(m_hChildProcess,INFINITE) != WAIT_FAILED)
    {
        GetExitCodeProcess(m_hChildProcess, &exit_code);
        result = exit_code;
    }
    else
    {
        errno = GetLastError();
        result = -1;
    }
    /* Free up the native handle at this point */
    CloseHandle(m_hChildProcess);
    return result;
}

#else
#include "toastpopen.h"
#include <errno.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <limits.h>
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string>
#include <pwd.h> //for getpwnam
#include <grp.h>
#include "toastpty.h"
#include "../log/Log.h"
#define DEFAULT_OPEN_MAX 1024
int ToastPopen::m_maxfd = 0;
pid_t *ToastPopen::m_opened_fds = NULL;
long ToastPopen::open_max()
{
    long openmax;

    if((openmax = sysconf(_SC_OPEN_MAX)) < 0)
    {
        openmax = DEFAULT_OPEN_MAX;
    }
    return openmax;
}
ToastPopen::ToastPopen()
{
    m_childpid = -1;
    m_in       = NULL;
    m_out      = NULL;
    m_err      = NULL;
    m_master_fd = -1;
}
pid_t ToastPopen::PopenPTY(const char *user, const char *cmdstring, int *master_fd)
{
    char pty_name[1024];
    char unused[4];
    pid_t child_pid;
    int sync_pipe[2];
    int slave_fd;
    if (m_opened_fds == NULL)
    { 
        m_maxfd = open_max();
        if ((m_opened_fds = new (std::nothrow)int[m_maxfd]) == NULL)  //new error
            return -1;
        for(int i = 0; i < m_maxfd; i++)
        {
            m_opened_fds[i] = 0;
        }
    }
    // get the user info form  getpwnam 
    struct passwd *pwd;
    pwd = getpwnam(user);
    if(pwd == NULL)
    {
        return -1;    
    }
    *master_fd = OpenPtyMaster(pty_name, sizeof(pty_name));
    if(*master_fd == -1)
    {
        return -1;
    }
    if (pipe(sync_pipe) == -1)
    {
        return -1;
    }
    child_pid = fork();
    switch(child_pid)
    {
    case -1:  // error
        {
            close(sync_pipe[0]);
            return -1;
        }
        break;
    case 0: // child
        {
            for (int i = 0; i < m_maxfd; i++)
                if (m_opened_fds[i] > 0)
                    close(i);

            if (close(sync_pipe[0]) == -1)  // child close the read end
            {
                close(sync_pipe[1]);
                _exit(1);
            }
            if (setsid() == -1)  
            {
                close(sync_pipe[1]);
                _exit(1);
            }
	     close(*master_fd);   
            slave_fd = open(pty_name, O_RDWR);     
            if (slave_fd == -1)
            {
                close(sync_pipe[1]);
                _exit(1);
            }

            if (dup2(slave_fd, STDIN_FILENO) != STDIN_FILENO)
            {
                close(sync_pipe[1]);
                _exit(1);
            }
            if (dup2(slave_fd, STDOUT_FILENO) != STDOUT_FILENO)
            {
                close(sync_pipe[1]);
                _exit(1);
            }
            if (dup2(slave_fd, STDERR_FILENO) != STDERR_FILENO)
            {
                close(sync_pipe[1]);
                _exit(1);
            }

            if (slave_fd > STDERR_FILENO)       
                close(slave_fd);                
            if(-1 == SetPtyUserEnvironment(pwd, pty_name))
            {
                close(sync_pipe[1]);
                _exit(126);
            }
            if (close(sync_pipe[1]) == -1) // close the write end, release the parent
            {
                _exit(125);
            }
            if(pwd->pw_shell)
            {
                execl(pwd->pw_shell, "--login", "-c", cmdstring, (char *) 0);
            }
            else
            {
                execl("/bin/sh", "--login", "-c", cmdstring, (char *) 0);
            }
            _exit(127);

        }
        break;
    default: // parent
        {
            if (close(sync_pipe[1]) == -1) // parent close the write end
            {
                close(sync_pipe[0]);
                return -1;
            }

            if (read(sync_pipe[0], unused, 1) != 0)
            {
                Log::Error("read parent child sync pipe error ");
            }
            close(sync_pipe[0]);
            m_childpid = child_pid;
            m_opened_fds[*master_fd] = 1;
            m_master_fd = *master_fd;
        }
    }	
    return m_childpid;
}


/*
* If you want to get stdout and stderr from the popened process call function as below
* FILE *output;
* FILE *errOutput
* pid_t subProcess_PID = t.Popen(cmdstring, NULL, &output, &errOutpur), if error return -1
*
*cmd string is the command
* in : in out parameter that need to input something to subporcess, if doesn't want to input
* pass NULL
* out: get subprocess's stdout, if doesn't need pass NULL
* err: get subprocess's stderr, if doesn't need pass NULL
*/
pid_t ToastPopen::Popen(const char *user, const char *cmdstring, FILE** in, FILE **out, FILE **err)
{
    int pfdin[2];
    int pfdout[2];
    int pfderr[2];
    pid_t pid;

    if(!in && !out && !err)
    {
        errno = EINVAL;
        return -1;     // failed
    }

    if (m_opened_fds == NULL)
    { 
        m_maxfd = open_max();
        if ((m_opened_fds = new (std::nothrow)int[m_maxfd]) == NULL)  //new error
            return -1;
        for(int i = 0; i < m_maxfd; i++)
        {
            m_opened_fds[i] = 0;
        }
    }
    if(in)
    {
        if (pipe(pfdin) < 0)
            return -1; /* errno set by pipe() */
    }
    if(out)
    {
        if(pipe(pfdout) < 0)
        {
            if(in)
            {
                close(pfdin[0]);
                close(pfdin[1]);
            }
            return -1;
        }
    }
    if(err)
    {
        if(pipe(pfderr) < 0)
        {
            if(in)
            {
                close(pfdin[0]);
                close(pfdin[1]);
            }
            if(out)
            {
                close(pfdout[0]);
                close(pfdout[1]);
            }
            return -1;
        }
    }
    if ((pid = fork()) < 0)
    {
        if(in)
        {
            close(pfdin[0]);
            close(pfdin[1]);
        }
        if(out)
        {
            close(pfdout[0]);
            close(pfdout[1]);
        }

        if(err)
        {
            close(pfderr[0]);
            close(pfderr[1]);
        }

        return -1; /* errno set by fork() */
    }
    else if (pid == 0)
    { /* child */

        if(in)
        {
            close(pfdin[1]);
            if(pfdin[0] != STDIN_FILENO)
            {
                dup2(pfdin[0], STDIN_FILENO);
                close(pfdin[0]);
            }
        }
        if(out)
        {
            close(pfdout[0]);
            if(pfdout[1] != STDOUT_FILENO)
            {
                dup2(pfdout[1], STDOUT_FILENO);
                close(pfdout[1]);
            }
        }

        if(err)
        {
            close(pfderr[0]);
            if(pfderr[1] != STDERR_FILENO)
            {
                dup2(pfderr[1], STDERR_FILENO);
                close(pfderr[1]);
            }
        }

        // The popen() function shall ensure that any streams from previous popen() calls that
        // remain open in the parent process are closed in the new child process.
        for (int i = 0; i < m_maxfd; i++)
            if (m_opened_fds[i] > 0)
                close(i);

        SetUserEnvironment(user);
        struct passwd *pwd;
        pwd = getpwnam(user);
        if(pwd == NULL)
            return -1;
        if(pwd->pw_shell)
        {
            execl(pwd->pw_shell, "--login", "-c", cmdstring, (char *) 0);
        }
        else
        {
            execl("/bin/sh", "--login", "-c", cmdstring, (char *) 0);
        }
        _exit(127);
    }
    /* parent continues... */
    m_childpid = pid;
    char type = 'w';

    if(in)
    {
        close(pfdin[0]);
        type = 'w';
        *in = fdopen(pfdin[1], &type);
        m_in = *in;
        m_opened_fds[fileno(m_in)] = 1;
    }
    if(out)
    {
        close(pfdout[1]);
        type = 'r';
        *out = fdopen(pfdout[0], &type);
        m_out = *out;
        m_opened_fds[fileno(m_out)] = 1;
    }

    if(err)
    {
        close(pfderr[1]);
        type = 'r';
        *err = fdopen(pfderr[0], &type);
        m_err = *err;
        m_opened_fds[fileno(m_err)] = 1;
    }
    return pid;
}
int ToastPopen::SetUserEnvironment(const char *user)
{
    if(!(user && user[0] != '\0'))
        return 1;
    // if(strcmp(user,"root"))
    {
        struct passwd *pwd;
        pwd = getpwnam(user);

        if(pwd == NULL)
            return -1;
        setenv("USER", pwd->pw_name, 1);
        setenv("LOGNAME", pwd->pw_name, 1);
        setenv("HOME", pwd->pw_dir, 1);
        setenv("PATH", getenv("PATH"), 1);
        setenv("SHELL", pwd->pw_shell, 1);

        //change the current directory
        if(pwd->pw_dir != NULL)
            chdir(pwd->pw_dir);
        setgid(pwd->pw_gid);
        initgroups(user, pwd->pw_gid);
        setuid(pwd->pw_uid);
    }
    return 0;
}
pid_t ToastPopen::PopenAndNewGroup(const char *user, const char *cmdstring, FILE** in, FILE **out, FILE **err)
{
    int pfdin[2];
    int pfdout[2];
    int pfderr[2];
    pid_t pid;

    if(!in && !out && !err)
    {
        errno = EINVAL;
        return -1;     // failed
    }

    if (m_opened_fds == NULL)
    { /* first time through */
        /* allocate zeroed out array for child pids */
        m_maxfd = open_max();
        if ((m_opened_fds = new (std::nothrow)int[m_maxfd]) == NULL)  //new error
            return -1;
        for(int i = 0; i < m_maxfd; i++)
        {
            m_opened_fds[i] = 0;
        }
    }
    if(in)
    {
        if (pipe(pfdin) < 0)
            return -1; /* errno set by pipe() */
    }
    if(out)
    {
        if(pipe(pfdout) < 0)
        {
            if(in)
            {
                close(pfdin[0]);
                close(pfdin[1]);
            }
            return -1;
        }
    }
    if(err)
    {
        if(pipe(pfderr) < 0)
        {
            if(in)
            {
                close(pfdin[0]);
                close(pfdin[1]);
            }
            if(out)
            {
                close(pfdout[0]);
                close(pfdout[1]);
            }
            return -1;
        }
    }
    if ((pid = fork()) < 0)
    {
        if(in)
        {
            close(pfdin[0]);
            close(pfdin[1]);
        }
        if(out)
        {
            close(pfdout[0]);
            close(pfdout[1]);
        }

        if(err)
        {
            close(pfderr[0]);
            close(pfderr[1]);
        }

        return -1; /* errno set by fork() */
    }
    else if (pid == 0)
    { /* child */

        if(in)
        {
            close(pfdin[1]);
            if(pfdin[0] != STDIN_FILENO)
            {
                dup2(pfdin[0], STDIN_FILENO);
                close(pfdin[0]);
            }
        }
        if(out)
        {
            close(pfdout[0]);
            if(pfdout[1] != STDOUT_FILENO)
            {
                dup2(pfdout[1], STDOUT_FILENO);
                close(pfdout[1]);
            }
        }

        if(err)
        {
            close(pfderr[0]);
            if(pfderr[1] != STDERR_FILENO)
            {
                dup2(pfderr[1], STDERR_FILENO);
                close(pfderr[1]);
            }
        }

        // The popen() function shall ensure that any streams from previous popen() calls that
        // remain open in the parent process are closed in the new child process.
        for (int i = 0; i < m_maxfd; i++)
            if (m_opened_fds[i] > 0)
                close(i);

        setpgid(0, 0);
        SetUserEnvironment(user);
        struct passwd *pwd;
        pwd = getpwnam(user);
        if(pwd == NULL)
            return -1;
        if(pwd->pw_shell)
        {
            execl(pwd->pw_shell, "--login", "-c", cmdstring, (char *) 0);
        }
        else
        {
            execl("/bin/sh", "--login", "-c", cmdstring, (char *) 0);
        }
        _exit(127);
    }
    /* parent continues... */
    m_childpid = pid;
    char type = 'w';

    if(in)
    {
        close(pfdin[0]);
        type = 'w';
        *in = fdopen(pfdin[1], &type);
        m_in = *in;
        m_opened_fds[fileno(m_in)] = 1;
    }
    if(out)
    {
        close(pfdout[1]);
        type = 'r';
        *out = fdopen(pfdout[0], &type);
        m_out = *out;
        m_opened_fds[fileno(m_out)] = 1;
    }

    if(err)
    {
        close(pfderr[1]);
        type = 'r';
        *err = fdopen(pfderr[0], &type);
        m_err = *err;
        m_opened_fds[fileno(m_err)] = 1;
    }

    return pid;
}
int ToastPopen::Pclose()
{
    int stat;

    if (m_childpid == -1)
    {
        errno = EINVAL;
        return (-1); /* popen() has never been called */
    }

    if(m_in != NULL)
    {
        m_opened_fds[fileno(m_in)] = 0;
        fclose(m_in);
        m_in = NULL;
    }

    if(m_out != NULL)
    {
        m_opened_fds[fileno(m_out)] = 0;
        fclose(m_out);
        m_in = NULL;
    }

    if(m_err != NULL)
    {
        m_opened_fds[fileno(m_err)] = 0;
        fclose(m_err);
        m_err = NULL;
    }
    if(m_master_fd != -1)
    {
        m_opened_fds[m_master_fd] = 0;
        close(m_master_fd);
    }
    while(waitpid(m_childpid, &stat, 0) < 0)
    {
        if(errno != EINTR)
        {
            m_childpid = -1;
            return (-1);
        }
    }
    m_childpid = -1;
    return (stat); /* return child's termination status */
}
// 0: the sub process normal closed. ReturnCode is the return code
// 1: the sub process abnormal closed. ReturnCode is the signal number
// 2: other ReturnCode unchange
int ToastPopen::GetReturnCode(int Stat, int *ReturnCode)
{
    if(WIFEXITED(Stat))
    {
        *ReturnCode = WEXITSTATUS(Stat);
        return 0;
    }
    else if(WIFSIGNALED(Stat))
    {
        *ReturnCode = WTERMSIG(Stat);
        return 1;
    }
    else 
    {
        return 2;
    }
}
// return 0 success
// return 2 timeout
// return 1 select error
int ToastPopen::GetCommandOutput(std::string &outResult, std::string &errResult, FILE *fp_stdout, FILE *fp_stderr, int nSeconds)
{

    char outputbuf[1024 * 10]; //10k
    int fd_out = -1;
    int fd_err = -1;
    int max_fd = 0;
    int n_fds = 0;
    fd_set readset;
    FD_ZERO(&readset);
    if (fp_stdout)
    {
        fd_out = fileno(fp_stdout);
        FD_SET(fd_out, &readset);
        max_fd = fd_out + 1;
        n_fds++;
    }
    if (fp_stderr)
    {
        fd_err = fileno(fp_stderr);
        FD_SET(fd_err, &readset);
        max_fd = (fd_out > fd_err) ? (fd_out + 1) : (fd_err + 1);
        n_fds++;
    }
    int selectResult;
    struct timeval TimeOut;
    if (nSeconds != 0)
    {
        TimeOut.tv_sec = nSeconds;
        TimeOut.tv_usec = 0;
    }

    fd_set new_readset;
    while (1)
    {
        new_readset = readset;
        if (nSeconds == 0)
        {
            selectResult = select(max_fd, &new_readset, NULL, NULL, NULL);
        }
        else
        {
            selectResult = select(max_fd, &new_readset, NULL, NULL, &TimeOut);
        }
        if (selectResult == 0)
        {
            //timeout
            return 2;
        }
        else if (selectResult == -1)
        {
            return 1;
        }
        else
        {
            for (int i = 0; i < max_fd; i++)
            {
                if (FD_ISSET(i, &new_readset))
                {

                    int byteReads = read(i, outputbuf, sizeof (outputbuf));
                    if (byteReads == 0) //endof file
                    {
                        FD_CLR(i, &readset);
                        n_fds--;
                    }
                    else
                    {
                        if (i == fd_out)
                        {
                            outResult.append(outputbuf, byteReads);
                        }
                        else if (i == fd_err)
                        {
                            errResult.append(outputbuf, byteReads);
                        }
                    }
                }
            }
            if (0 == n_fds)
            {
                break;
            }
        }
    }
    return 0;
}
#endif

