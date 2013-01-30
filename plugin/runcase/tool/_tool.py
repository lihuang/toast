#!/usr/bin/python
# Filename: tool.py
# -*- coding: utf-8 -*-

#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#

import ConfigParser
import os
import sys


class Tool:
    '''
    @summary: Base class of test tool
    '''
    def __init__(self, url, case_id, fn=""):
        '''
        @param url: the svn url of code
        @param case_id: the case's id in TOAST
        @param fn: the function name of case
        '''
        self.url = url
        self.case_id = case_id
        self.fn = fn

    def get_conf(self, prefix, key, conf=''):
        '''
        @summary: get the config value by the key
        @param key: the config key
        @return: the config value
        '''
        if "" == conf:
            conf = os.path.join(os.path.dirname(__file__), self.__class__.__name__.lower() + ".conf")
        self.validate_exist(conf)
        cfgParser = ConfigParser.ConfigParser()
        cfgParser.read(conf)
        return cfgParser.get(prefix, key)

    def validate_exist(self, path):
        if not os.path.exists(path):
            print "`" + path + "` can't be found,",
            print "please check config file and this program will exit now!"
            exit(1)

    def before_run(self):  # add lib to path
        svn_url = self.url
        conf = os.path.join(os.path.dirname(__file__), "../run_case.conf")
        work_copy = self.get_conf("runcase", "basepath", conf)
        work_copy = os.path.join(work_copy, svn_url.split("/")[-1])
        self.work_copy = work_copy
        account = self.get_conf("svn", "account", conf)
        password = self.get_conf("svn", "password", conf)
        lib = os.path.join(os.path.dirname(__file__), "../lib")
        sys.path.append(lib)
        import svn as libsvn
        svn = libsvn.SVN(svn_url=svn_url, work_copy=work_copy, account=account, password=password)
        stdout, stderr = svn.export()
        flag = True
        if stderr:
            print stderr
            flag = False
        return flag

    def run(self):
        return True

    def after_run(self):
        return True

    def execute(self):
        return self.before_run() and self.run() and self.after_run()

if __name__ == "__main__":
    tool = Tool("http://xxx.xxx.xxx", "1")
    tool.execute()
    print tool.get_conf("tool", "mmt")
