#!/usr/bin/python
# Filename: svn.py
# -*- coding: utf-8 -*-

#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#

import os
import subprocess

class SVN:
    '''
    @summary: svn interface via system svn lib.
    '''
    def __init__(self, **argv):
        '''
        @param argv: the options of svn:
               svn_url: the svn url of code
               work_copy: where the code check out to
               account: the account of svn
               password: the password of svn
               is_auth_cahe: the option of svn
               is_interactive: the option of svn
        '''
        self.svn_url = ""
        if "svn_url" in argv.keys():
            self.svn_url = argv["svn_url"]

        self.work_copy = ""
        if "work_copy" in argv.keys():
            self.work_copy = argv["work_copy"]

        self.account = ""
        if "account" in argv.keys():
            self.account = argv["account"]

        self.password = ""
        if "password" in argv.keys():
            self.password = argv["password"]

        self.is_auth_cache = False
        if "is_auth_cache" in argv.keys():
            self.is_auth_cache = argv["is_auth_cache"]

        self.is_interactive = False
        if "is_interactive" in argv.keys():
            self.is_interactive = argv["is_interactive"]

    def create_cmd(self, sub_cmd, **argv):
        '''
        @summary: create command via parameters or self's variable
        @param sub_cmd: the command of svn
        @param argv: the options of svn
        '''
        svn_url = self.svn_url
        if "svn_url" in argv.keys():
            svn_url = argv["svn_url"]

        work_copy = self.work_copy
        if "work_copy" in argv.keys():
            work_copy = argv["work_copy"]

        account = self.account
        if "account" in argv.keys():
            account = argv["account"]

        password = self.password
        if "password" in argv.keys():
            password = argv["password"]

        is_auth_cache = self.is_auth_cache
        if "is_auth_cache" in argv.keys():
            is_auth_cache = argv["is_auth_cache"]

        is_interactive = self.is_interactive
        if "is_interactive" in argv.keys():
            is_interactive = argv["is_interactive"]

        if isinstance(sub_cmd, basestring):
            sub_cmd = [sub_cmd]
        cmd = ["svn"] + sub_cmd
        if 0 < len(svn_url):
            cmd += [svn_url]
        if 0 < len(work_copy):
            cmd += [work_copy]
            if "check_work_copy" in argv.keys() and argv['check_work_copy']:
                self.validate_exist(work_copy, True)
        if 1 < len(account):
            cmd += ["--username", account]
        if 1 < len(password):
            cmd += ["--password", password]
        if not is_auth_cache:
            cmd += ["--no-auth-cache"]
        if not is_interactive:
            cmd += ["--non-interactive"]
        return cmd

    def svn_operator(self, cmd, **argv):
        '''
        @summary: execute svn operator.
        @param cmd: the svn command to be executed
        @param argv: the options of svn.
        '''
        stdout = ''
        stderr = ''
        cmd = self.create_cmd(cmd, **argv)
        print cmd
        p = subprocess.Popen(cmd, stdout=subprocess.PIPE,\
            stderr=subprocess.STDOUT, close_fds=True)
        while True:
            line = p.stdout.readline()
            if not line:
                break
            stdout += line
        if p.wait() != 0:
            print "stdout: " + stdout
            exit(1)
        return (stdout, stderr)

    def validate_exist(self, path, is_dir=False):
        '''
        @summary: validate file or floder is exists.
        @param path: the path to be validated
        @param is_dir: is the path directory
        '''
        if False == os.path.exists(path):
            print "`" + path + "` can't be found,",
            print "please check it and this program will exit now!"
            exit(1)
        if is_dir and False == os.path.isdir(path):
            print "`" + path + "` is not a directory,",
            print "please check it and this program will exit now!"
            exit(1)

    def check_out(self, **argv):
        '''
        @summary: the check out operation, one of the svn operations.
        @param argv: the options of svn
        '''
        return self.svn_operator("co", **argv)

    def export(self, **argv):
        '''
        @summary: the export operation, one of the svn operations.
        @param argv: the options of svn
        '''
        return self.svn_operator("export", **argv)

    def update(self, **argv):
        '''
        @summary: the svn operation, one of the svn operations.
        @param argv: the options of svn
        '''
        argv['svn_url'] = ''  # do not use svn_url for svn up
        argv['check_work_copy'] = True
        return self.svn_operator("up", **argv)

    def log(self, **argv):
        argv['svn_url'] = ''  # do not use svn_url for svn log
        cmd = ['log', '--xml', '-v', '-r', 'COMMITTED']
        cmd = self.create_cmd(cmd, **argv)
        print cmd
        print "================inside svnlog method================"
        p = subprocess.Popen(cmd, stdout=subprocess.PIPE,\
            stderr=subprocess.PIPE, close_fds=True)
        stdout, stderr = p.communicate()
        return (stdout, stderr)

if __name__ == "__main__":
    svn = SVN(svn_url="http://xxx.xxx.xxx")
    print svn.create_cmd("ci")
    svn.validate_exist("/xxx/xxx", False)
    svn.log(work_copy="/var/www/toast")
