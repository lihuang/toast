#!/usr/bin/python2.6
#-*- coding:utf-8 -*-
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# lunatest
#   run the test case of shell and capture the code coverage
#   it is inherit from TestParent and HTMLParser

import os
import sys
import datetime
import traceback
from Test import TestParent

sys.path.append(os.path.join(os.path.dirname(__file__),"../common"))
import command

COV_FILE = "lcov.report.out"
class LuaTest(TestParent):
    def __init__(self,options,config):
        TestParent.__init__(self,options,config)
        self.flag = False
        self.line_info = ""
    
    def CopyCovReportFile(self):
        cov_file = os.path.join(self.BasePath, COV_FILE)
        
        if os.path.exists(cov_file):
            now = datetime.datetime.now().strftime("%m-%d-%Y-%X")
            tmpdir = '/tmp/' + now
            copycmd = "mkdir " + tmpdir + "; cp " + cov_file + " " + tmpdir + "/"
            command.DoCmd(copycmd, '')
            self.ToHtdocs(tmpdir)
            self.DisplayURL(now + "/" + COV_FILE)
        else:
            print "There is no coverage report file"

    def start(self):
        print('====================================Start to Run===================================')
        try:
            self.before_run()
            self.running()     #Todo:BUILD Error?
            if('yes' == self.iscodecoverage):
                self.CopyCovReportFile()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
        finally:
            if self.debug == 1:
                command.ReadFile()

            if self.workplace == 0:
                self.clean_data("")

