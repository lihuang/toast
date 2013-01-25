#!/usr/bin/python2.6
#-*- coding:utf-8 -*-
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# shelltest
#   run the test case of shell and capture the code coverage
#   it is inherit from TestParent and HTMLParser

from Test import TestParent
import os
import sys
import datetime
import traceback
import re
from HTMLParser import HTMLParser

sys.path.append(os.path.join(os.path.dirname(__file__),"../common"))
import command

shellcov=""
class ShellTest(TestParent,HTMLParser):
    def __init__(self,options,config):
        TestParent.__init__(self,options,config)
        HTMLParser.__init__(self)
        self.total = False
        self.executed = False
        self.total_line=''
        self.executed_line=''

    def handle_data(self,data):
        if '' != self.total_line and '' != self.executed_line:
            return
        elif (data.lstrip().startswith("Instrumented")):
            self.total = True
        elif data.lstrip().startswith("Executed"):
            self.executed = True
        elif True == self.total and '' !=data.strip():
            self.total_line=data.strip()
            self.total= False
        elif True == self.executed and '' !=data.strip():
            self.executed_line = data.strip()
            self.executed = False
    
    def get_covdir(self):
        global shellcov
        os.chdir(self.BasePath)
        commandlines=self.unittestcommands.split(";");
        for command in commandlines:
            if (command.lstrip().startswith("cd") == True):
                newline=command.replace('cd','').strip()
                if (newline.lstrip()[0] =="/"):
                    pwd = newline
                else:
                    pwd = os.getcwd()+"/"+newline
                os.chdir(pwd)
            elif (command.lstrip().startswith("shlcov") == True):
                shellcov = os.path.abspath(command.split()[2])

    def ToHtdocsForShell(self):
        if shellcov =="":
            print "the codecoverage does not exist"
            return
        now = datetime.datetime.now().strftime("%m-%d-%Y-%X")
        directory="/tmp/"+now
        copy= "cp -r "+shellcov+" "+directory
        command.DoCmd(copy,'')
        self.ToHtdocs(directory)
        self.DisplayURL(now)
        if os.path.exists(shellcov+"/index.html"):
            self.output_lineinfo(shellcov+"/index.html")

    def output_lineinfo(self,htmlfile):
        output=open(htmlfile,'r')
        try:
            htmlcode = re.sub("&nbsp;",'',output.read(),0)
            self.feed(htmlcode)
            self.close()
            if self.total_line and self.executed_line !='':
                print "CODE COVERAGE RESULT OF LINES IS: "+self.executed_line+"/"+self.total_line
        except Exception,e:
            print "We meet an error when get the code coverage result of lines"
            print e
        finally:
            output.close()

    def start(self):
        print('====================================Start to Run===================================')
        try:
            self.before_run()
            self.running()     #Todo:BUILD Error?
            if ('yes' == self.iscodecoverage):
                self.get_covdir()
                self.ToHtdocsForShell()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
        finally:
            if self.debug == 1:
                command.ReadFile()
            if self.workplace == 0 and shellcov !="":
                self.clean_data(shellcov)

