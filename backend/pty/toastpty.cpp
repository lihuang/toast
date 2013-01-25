/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include <grp.h>
#include <pwd.h> //for getpwnam
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <termios.h>  //for posix_openpt
#include <stdlib.h>
#include <fcntl.h>
#include <string>
#include <errno.h>
#include <unistd.h>
#include <string.h>
int OpenPtyMaster(char *slaveName, size_t snLen)
{
    int master_fd, saved_errno;
    char *p;

    master_fd = posix_openpt(O_RDWR | O_NOCTTY);  //open a pty master, and O_NOCTTY make it not the caller's control tty
    if (master_fd == -1)
        return -1;

    if (grantpt(master_fd) == -1) //set authority
    {
        saved_errno = errno;
        close(master_fd);
        errno = saved_errno;
        return -1;
    }

    if (unlockpt(master_fd) == -1)
    {
        saved_errno = errno;
        close(master_fd);
        errno = saved_errno;
        return -1;
    }

    p = ptsname(master_fd);  //get the slave name.
    if (p == NULL)
    {
        saved_errno = errno;
        close(master_fd);
        errno = saved_errno;
        return -1;
    }

    if (strlen(p) < snLen)
    {
        strncpy(slaveName, p, snLen);
    }
    else
    {
        close(master_fd);
        errno = EOVERFLOW;
        return -1;
    }

    return master_fd;
}


#define MAX_SNAME 1024                 

pid_t ToastPtyFork(int *masterFd, char *slaveName, size_t snLen,
    const struct termios *slaveTermios, const struct winsize *slaveWS)
{
    int mfd, slave_fd, saved_errno;
    pid_t childPid;
    char slname[MAX_SNAME];

    mfd = OpenPtyMaster(slname, MAX_SNAME);
    if (mfd == -1)
        return -1;

    if (slaveName != NULL)
    {           
        if (strlen(slname) < snLen)
        {
            strncpy(slaveName, slname, snLen);

        }
        else
        {                        
            close(mfd);
            errno = EOVERFLOW;
            return -1;
        }
    }

    childPid = fork();

    if (childPid == -1)
    {              
        saved_errno = errno;            
        close(mfd);                     
        errno = saved_errno;
        return -1;
    }

    if (childPid != 0)
    {                
        *masterFd = mfd;                
        return childPid;                
    }

    //child process    

    if (setsid() == -1)  
    {
        _exit(1);
    }

    close(mfd);                        

    slave_fd = open(slname, O_RDWR);     
    if (slave_fd == -1)
        _exit(1);

    if (slaveTermios != NULL)           
        if (tcsetattr(slave_fd, TCSANOW, slaveTermios) == -1)
            _exit(1);

    if (slaveWS != NULL)                
        if (ioctl(slave_fd, TIOCSWINSZ, slaveWS) == -1)
            _exit(1);


    if (dup2(slave_fd, STDIN_FILENO) != STDIN_FILENO)
        _exit(1);
    if (dup2(slave_fd, STDOUT_FILENO) != STDOUT_FILENO)
        _exit(1);
    if (dup2(slave_fd, STDERR_FILENO) != STDERR_FILENO)
        _exit(1);

    if (slave_fd > STDERR_FILENO)       
        close(slave_fd);                

    return 0;                          
}
int SetPtyUserEnvironment(struct passwd *pwd, const char *pty_name)
{
    std::string path_local_bin = "/usr/local/bin";
    // The squence of the code is important
    {
        //set basic environment
        setenv("USER", pwd->pw_name, 1);
        setenv("LOGNAME", pwd->pw_name, 1);
        setenv("HOME", pwd->pw_dir, 1);

        std::string path_org = getenv("PATH");
        if(std::string::npos == path_org.find(path_local_bin))
        {
            path_org = path_local_bin + ":" + path_org;
        }
        setenv("PATH",path_org.c_str(), 1);      //getenv("PATH")
        setenv("SHELL", pwd->pw_shell, 1);

        //change the current directory
        if(pwd->pw_dir != NULL)
            chdir(pwd->pw_dir);
        // follow step as login
        //change the pty owner to user
        struct group *grp;
        gid_t gid;
        mode_t mode;
        struct stat ptyst;
        // linux slave PTY is owned by group tty.
        grp = getgrnam("tty");
        if(grp)
        {
            gid = grp->gr_gid;
            mode = S_IRUSR | S_IWUSR | S_IWGRP;
        }
        else
        {
            gid = pwd->pw_gid;
            mode = S_IRUSR | S_IWUSR | S_IWGRP | S_IWOTH;
        }

        if (stat(pty_name, &ptyst))
            return -1;
        //change owner
        if (ptyst.st_uid != pwd->pw_uid || ptyst.st_gid != gid)
        {
            if (chown(pty_name, pwd->pw_uid, gid) < 0)
            {
                return -1;
            }
        }
        // Modify the access mode
        if ((ptyst.st_mode & (S_IRWXU|S_IRWXG|S_IRWXO)) != mode)
        {
            if (chmod(pty_name, mode) < 0)
            {
                return -1;
            }
        }
        setgid(pwd->pw_gid);
        initgroups(pwd->pw_name, pwd->pw_gid);
        setuid(pwd->pw_uid);
    }
    return 0;
}
/*
int SetPtyUserEnvironment(const char *user, const char *pty_name)
{
std::string path_local_bin = "/usr/local/bin";
// The squence of the code is important
{
struct passwd *pwd;
pwd = getpwnam(user);

if(pwd == NULL)
return -1;

//set basic environment
setenv("USER", pwd->pw_name, 1);
setenv("LOGNAME", pwd->pw_name, 1);
setenv("HOME", pwd->pw_dir, 1);

std::string path_org = getenv("PATH");
if(std::string::npos == path_org.find(path_local_bin))
{
path_org = path_local_bin + ":" + path_org;
}
setenv("PATH",path_org.c_str(), 1);      //getenv("PATH")
setenv("SHELL", pwd->pw_shell, 1);

//change the current directory
if(pwd->pw_dir != NULL)
chdir(pwd->pw_dir);
// follow step as login
//change the pty owner to user
struct group *grp;
gid_t gid;
mode_t mode;
struct stat ptyst;
// linux slave PTY is owned by group tty.
grp = getgrnam("tty");
if(grp)
{
gid = grp->gr_gid;
mode = S_IRUSR | S_IWUSR | S_IWGRP;
}
else
{
gid = pwd->pw_gid;
mode = S_IRUSR | S_IWUSR | S_IWGRP | S_IWOTH;
}

if (stat(pty_name, &ptyst))
return -1;
//change owner
if (ptyst.st_uid != pwd->pw_uid || ptyst.st_gid != gid)
{
if (chown(pty_name, pwd->pw_uid, gid) < 0)
{
return -1;
}
}
// Modify the access mode
if ((ptyst.st_mode & (S_IRWXU|S_IRWXG|S_IRWXO)) != mode)
{
if (chmod(pty_name, mode) < 0)
{
return -1;
}
}
setgid(pwd->pw_gid);
initgroups(user, pwd->pw_gid);
setuid(pwd->pw_uid);
}
return 0;
}*/


