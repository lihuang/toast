#!/usr/bin/env python

#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#   
# command
#   This script  provides some common operation for other script

import os
import subprocess
import sys


debug = 0
filename = './log.txt'
def SetFileName(file):
    global filename
    filename = file
    create_file = open(filename,'w')
    create_file.close()
    

def WriteFile(contents):
    if filename != '':
        output = open(filename,'a')
        try:
            output.write(contents)
            output.write('\n')
        finally:
            output.close()

def ReadFile():
    if filename != '':
        input = open(filename,'r')
        try:
            print input.read()
        finally:
            input.close()

def GetLogs():
	logs = ""
	if filename != '':
		input = open(filename,'r')
		try:
			logs = input.readlines()	
		finally:
			input.close()
	return logs

def DoCmd(cmd,password):
    """
    input: cmd, the command needed to run
    output: (stdout, stderr), a tuple include stdout and stderr
    """
    WriteFile(cmd)
    try:
        if password !='':
            cmd = "sudo -k;echo "+password+" |sudo -S -u root "+cmd
        p = subprocess.Popen(cmd, bufsize=4096, shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, close_fds=True)
        outputs=''
        while True:
            line = p.stdout.read(4096)
            outputs=line+outputs
	
            if not line:
                break
            sys.stdout.write(line)
            WriteFile(line)
        if p.wait() == 0:
            print cmd + ' process execute ok'
                    #print (p.returncode,outputs)
        return (p.returncode,outputs)
    except:
        print "we meet an exception while invoke command: " + cmd
        sys.exit(2) 
"""
DoCmd1 return both stdout and stderr,but not immedeately
"""
def DoCmd1(cmd):
    WriteFile(cmd)
    res = ''
    try:
        p = subprocess.Popen(cmd, bufsize=4096, shell=True,stdout = subprocess.PIPE,stderr = subprocess.PIPE,close_fds = True)
        stdout,stderr = p.communicate()
        if not stderr == '':
            print 'Stderr: '+stderr

        WriteFile(stderr)
        WriteFile(stdout)
        res = 'Stderr: '+stderr+'\n'+'Stdout: '+stdout
    except:
        print "we meet an exception while invoke command: "+cmd
    finally:
	#print res
       return p.returncode,res

def yum(packages, password, command = 'install', branch = 'test'):
    """
    input: packages, a list contains all the packages needed to do the 'command'
           password, sudo's password
           command, is one of install, update, remove | erase(default is install)
           branch, specifies the branch on YUM server, is one of stable|current|test|dailybuild(default is test)
    """
    if not isinstance(packages, (tuple, list)):
        raise Exception, 'parameter packages should be a tuple or a list.'
    if command not in ('install', 'update', 'remove', 'erase'):
        raise Exception, 'parameter command should be one of install, update, remove | erase.'
    if branch not in ('stable', 'current', 'test', 'dailybuild', ''):
        raise Exception, 'parameter branch should be one of stable|current|test|dailybuild.'

    res = ['',''];

    for package in packages:
        if not package.strip():
            continue
        if command in('remove','erase'):

            cmd = 'yum info  '+package+' |grep Repo'
            status,output = DoCmd(cmd,password)
            if status == 0:
                if output.find("installed") != -1:
                        cmd = 'yum remove '+package+ ' -y'
                        DoCmd(cmd,password)
                else:
                    raise Exception,'The '+package+' has not been installed yet'
                
        else:
            cmd = 'yum '+ command+ ' '+package+ ' -y'
            if branch !='':
                cmd += ' -b '+branch
            if filename != '':
                cmd += ' >> '+filename
                status,output= DoCmd(cmd,password)
                res[0] =status;
                res[1] +=output;
    return res
    

def rpm(packages, password, command = 'install'):
    """
    input: packages, a list contains all the packages needed to do the 'command'
           password, sudo's password
           command, is one of install, remove | erase(default is install)
    """
    if not isinstance(packages, (tuple, list)):
        raise Exception, 'parameter packages should be a tuple or a list.'
    if command not in ('install', 'remove', 'erase'):
        raise Exception, 'parameter command should be one of install, remove | erase.'
    for package in packages:
        if not package.strip():
            continue
        p, fullname = os.path.split(package)
        filename, suffix = os.path.splitext(fullname)
        if command == 'install':
            if os.path.exists('/tmp/' + fullname):
                DoCmd('rm -f /tmp/' + fullname,password)
            DoCmd('wget ' + package + ' -P /tmp',password) 
            sudo('rpm -ivh /tmp/' + fullname, 'root', password)
            DoCmd('rm -f /tmp/' + fullname,password)
        elif command in ('remove', 'erase'):
            sudo('rpm -e ' + filename, 'root', password)

def service(server, password, command = 'start'):
    """
    start or stop the specified service as root (service xxx start|stop|restart)
    input: server, the service needed to do the 'command'
           password, the svn password
           command, is one of start|stop|restart(default is start)
    output: (stdout, stderr), a tuple include stdout and stderr
    """
    if command not in ('start', 'stop', 'restart'):
        raise Exception, 'parameter command should be one of start|stop|restart.'
    return sudo('service ' + server + ' ' + command, 'root', password)

def ValidateExist(path):
    if(os.path.exists(path) == False):
        raise Exception,path+" can't be found,please check it."

def conf_change(configFile, changeDict):
    """
    input: configFile, the config file that need to be changed
           changeDist, a dict contain the old value and new value
                       like {oldValue:newValue, ...}
    output: (stdout, stderr), a tuple include stdout and stderr
    """
    if not isinstance(changeDict, dict):
        raise Exception, 'parameter changeDist must be a dict like {oldValue:newValue, ...} .'
    ValidateExist(configFile)
    tempConf = open(configFile, 'r')
    confStr = tempConf.read()
    tempConf.close()

    for key in changeDict:
        confStr = confStr.replace(key, changeDict[key])

    tempConf = open(configFile, 'w')
    tempConf.write(confStr)
    tempConf.close()

