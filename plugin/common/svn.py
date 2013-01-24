#!/usr/local/bin/python
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# svn
#   This script provides several methods of svn,such as 'svn co','svn diff','svn update' and so on

import subprocess
import sys
import os

debug = 0

class SVN:
    def __init__(self, svnurl = '', workCopy = '', \
                 account = '', password= '', isAuthCache = ''):
	if debug == 1:
	    print "This is __init__ method in class SVN..."
	self.workCopy = workCopy      
        self.svnurl = svnurl  
        self.account = account    #account for svn
        self.password = password  #password for svn
        self.isAuthCache = isAuthCache
    
    '''
    create svn command
    input:
        subcmd: subcommand of svn
        source: svnurl for operating
        target: target for svn checkout/update
        account: access account for svn
        password: access password for svn
        isAuthCache:do not cache authentication information if isAuthCache not true
        isInteractive:do not interactive promting if isInteractive is not true
    output:
        the svn command that could be excuted by subprocess.Popen
    '''
    def CreateCMD(self, subcmd, source, target, account, password,\
                   isAuthCache = False,isInteractive=False):
        if subcmd == 'up':
            cmd = ['svn', subcmd]
        else:
            cmd = ['svn', subcmd, source]
        if len(target) > 0:
            cmd.append(target)
        if len(account) < 1:
            namePara = []
        else:
            namePara = ['--username', account]
        if len(password) < 1:
            passwdPara = []
        else:
            passwdPara = ['--password', password]
        if isAuthCache:
            authCache = []
        else:
            authCache = ['--no-auth-cache']           
        if isInteractive:
            interactive = []
        else:
            interactive = ['--non-interactive']
        cmd = cmd + namePara + passwdPara + authCache + interactive
        if debug == 1 :
            print "This is in CreateCMD method in SVN class"
        return cmd

    '''
    create svn diff command
    '''
    def SvnDiffCmd(self,svnurl,account,password,isAuthCache,isInteractive):
        log_cmd = self.CreateCMD("log",svnurl,"",account,password,isAuthCache,isInteractive)+['-l 2']
        stdout = ''
        stderr = ''
        versions=[]
        p = subprocess.Popen(log_cmd, stderr=subprocess.STDOUT,\
                               stdout=subprocess.PIPE, close_fds=True)
        import re
        pattern = re.compile("r(\d+)")
        while True:
            line = p.stdout.readline()
            if not line:
                break
            match = pattern.match(line)
            if match:
                versions.append(match.group(1))
            stdout += line
            
        if p.wait() != 0:
            print "stdout: "+stdout
            sys.exit(1);
         
        version = []
        diff_cmd = ''
        if 2 == len(versions):
            version.append(":".join(versions))
            diff_cmd = self.CreateCMD("diff",svnurl,"",account,password,isAuthCache,isInteractive)+['-r '] +version
        return " ".join(diff_cmd)
        
    '''
    excute some svn commands,then return the result using a tuple.
    '''
    def SvnOperate(self,cmd,svnurl,workCopy,account,password,isAuthCache,isInteractive):
        stdout = ''
        stderr = ''
        cmd = self.CreateCMD(cmd, svnurl, workCopy, account, password,\
                               isAuthCache,isInteractive)
        print cmd
        p = subprocess.Popen(cmd, stderr=subprocess.STDOUT,\
                               stdout=subprocess.PIPE, close_fds=True)
        #stdout,stderr = p.communicate()
        while True:
            line = p.stdout.readline()
            if not line:
                break
            stdout += line
        if p.wait() != 0:
            print "stdout: "+stdout
            sys.exit(1);
        if debug == 1:
            if 0 < len(stdout):
                print 'stdout-------------------------------------'
                print stdout
            elif 0 < len(stderr):
                print 'stderr-------------------------------------'
                print stderr
            else:
                print 'nothing?'
        return (stdout,stderr)     
    	
    '''
    just for svn co
    '''
    def CheckOut(self,svnurl, workCopy, account, password, isAuthCache,isInteractive):
        if os.path.isdir(workCopy) == False:
            print workCopy + ' does not exist!!! The tool will exit.'
            sys.exit(1)
        stdout = ''
        stderr = ''
        cmd = self.CreateCMD("co", svnurl, workCopy, account, password,\
                               isAuthCache,isInteractive)
        print cmd
        p = subprocess.Popen(cmd, stderr=subprocess.STDOUT,\
                               stdout=subprocess.PIPE, close_fds=True)
        #stdout,stderr = p.communicate()
        while True:
            line = p.stdout.readline()
            if not line:
                break
            stdout += line
        if p.wait() != 0:
            print "stdout: "+stdout
            sys.exit(1);
        if debug == 1:
            if 0 < len(stdout):
                print 'stdout-------------------------------------'
                print stdout
            elif 0 < len(stderr):
                print 'stderr-------------------------------------'
                print stderr
            else:
                print 'nothing?'
        return (stdout,stderr)     
    
    '''
    svn update 
    '''
    def Update(self, workCopy, account, password, isAuthCache,isInteractive):
        if os.path.isdir(workCopy) == False:
            print workCopy + ' does not exist!!! The tool will exit.'
            sys.exit(1)
        stdout = ''
        stderr = ''
        cmd = self.CreateCMD('up', '', workCopy, account, password,\
                               isAuthCache,isInteractive)
        print cmd
        p = subprocess.Popen(cmd, stderr=subprocess.STDOUT,\
                               stdout=subprocess.PIPE, close_fds=True)
        #stdout,stderr = p.communicate()
        while True:
            line = p.stdout.readline()
            if not line:
                break
            stdout += line
        if p.wait() != 0:
            print "stdout: "+stdout
            sys.exit(1);
        if debug == 1:
            if 0 < len(stdout):
                print 'stdout-------------------------------------'
                print stdout
            elif 0 < len(stderr):
                print 'stderr-------------------------------------'
                print stderr
            else:
                print 'nothing?'
        return (stdout,stderr)     

    '''
    svn log
    '''
    def Svnlog(self, workCopy, account, password):
        if os.path.isdir(workCopy) == False:
            print workCopy + ' does NOT exist!!!'
            return False
        cmd = ['svn', 'log', '--xml', '-v', '-r', 'COMMITTED', workCopy, '--username', account, '--password', password,'--no-auth-cache','--non-interactive']
        print "=========================inside svnlog method================"
        p = subprocess.Popen(cmd, stderr=subprocess.PIPE,\
                               stdout=subprocess.PIPE, close_fds=True)
        stdout,stderr = p.communicate()

        if debug == 1:
            if 0 < len(stdout):
                print 'stdout-------------------------------------'
                print stdout
            elif 0 < len(stderr):
                print 'stderr-------------------------------------'
                print stderr
            else:
                print 'nothing?'
        return (stdout,stderr)     
