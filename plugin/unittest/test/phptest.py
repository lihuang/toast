#!/usr/bin/python2.6
#-*- coding:utf-8 -*-
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# phptest
#   run the test case of php and capture the code coverage
#   it is inherit from TestParent and HTMLParser

from Test import TestParent
import os
import sys
import datetime
import traceback
import re

sys.path.append(os.path.join(os.path.dirname(__file__),"../common"))
import command

phpcov    = "testreport"
log    = "log_res"

class PhpTest(TestParent):
    def __init__(self,options,config):
        TestParent.__init__(self,options,config)

    def get_resultdir(self):
        global phpcov,log
        commands=self.unittestcommands.split(" ")
        index = 0
        while index+1 < len(commands):
            if "--log-junit" == commands[index]:
                log = os.path.basename(commands[index+1])
            elif "--coverage-html" == commands[index]:
                phpcov = os.path.basename(commands[index+1])

            index = index+1

    
    def output_lineinfo(self,html_file):
        output = open(html_file,"r")
        try:
            lines = output.readlines()
            pattern = re.compile("\<td\s+class=\"coverNum\w*\"\>(\d+\s*/\s*\d+)")
            for line in lines:
                match = pattern.search(line)
                if match:
                    lineinfo = match.group(1)
                    print "CODE COVERAGE RESULT OF LINES IS: "+lineinfo.replace(" ","")
                    break
        except Exception,e:
            print "we meet an error when read the index.html"
            print e
        finally:
            output.close()
                    
                
                 

    def ToHtdocsForPhp(self):
        cov_dir = ''
        for root,dirs,files in os.walk(self.BasePath,None,False):
            for directory in dirs:
                if directory == phpcov:
                    cov_dir = os.path.abspath(os.path.join(root,directory))

        if cov_dir != '':
            now=datetime.datetime.now().strftime("%m-%d-%Y-%X")
            directory='/tmp/'+now
            copy = "cp -r "+cov_dir+" "+directory
            command.DoCmd(copy,'')
            self.ToHtdocs(directory);
            self.DisplayURL(now);
            
            if os.path.exists(cov_dir+"/index.html"):
                self.output_lineinfo(cov_dir+"/index.html")
        else:
            print "Couldn't generate coverage info"

    def output_log(self):
        log_dir = ''
        for root,dirs,files in os.walk(self.BasePath,None,False):
            for filename in files:
                if filename == log:
                    log_dir = os.path.abspath(os.path.join(root,filename))
        if '' != log_dir:
            command.DoCmd("cat "+log_dir,'')
        else:
            print "xml log does not exists"


    def start(self):
        print('====================================Start to Run===================================')
        try:
            self.before_run()
            self.running()     #Todo:BUILD Error?
            self.get_resultdir()
            self.output_log()
            if('yes' == self.iscodecoverage):
                self.ToHtdocsForPhp()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
        finally:
            if self.debug == 1:
                command.ReadFile()
            if self.workplace == 0:
                self.clean_data(phpcov)
                self.clean_data(log)

