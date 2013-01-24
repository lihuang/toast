#!/usr/bin/python2.6
#-*- coding:utf-8 -*-
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# perltest
#   run the test case of perl and capture the code coverage
#   it is inherit from TestParent and HTMLParser

import os
import sys
import datetime
import traceback
from Test import TestParent
from HTMLParser import HTMLParser

sys.path.append(os.path.join(os.path.dirname(__file__),"../common"))
import command

perl_cov = "cover_db"
class PerlTest(TestParent,HTMLParser):
    def __init__(self,options,config):
        TestParent.__init__(self,options,config)
        HTMLParser.__init__(self)
        self.flag = False
        self.line_info = ""
    
    def handle_starttag(self,tag,attrs):
        if "td" == tag and True == self.flag and 0 != len(attrs):
            for (variable,value) in attrs:
                if "title" == variable:
                    self.line_info = value.replace(" ","")
                    self.flag = False
                    return

    def handle_data(self,data):
        if "Total" == data:
            self.flag = True

    def output_lineinfo(self,htmlfile):
        output = open(htmlfile,"r")
        try:
            htmlcode = output.read()
            self.feed(htmlcode)
            self.close()
            if self.line_info != "":
                print "CODE COVERAGE RESULT OF LINES IS: "+self.line_info
        except Exception,e:
            print "We meet an error when get the code coverage result of lines"
            print e
        finally:
            output.close()

    def ToHtdocsForPerl(self):
        cov_dir = ''
        for root,dirs,files in os.walk(self.BasePath,None,False):
            for directory in dirs:
                if directory == perl_cov:
                    cov_dir = os.path.abspath(os.path.join(root,directory))

        if cov_dir != '':
            now=datetime.datetime.now().strftime("%m-%d-%Y-%X")
            directory='/tmp/'+now
            copy = "cp -r "+cov_dir+" "+directory
            command.DoCmd(copy,'')
            
            self.ToHtdocs(directory);
            if os.path.exists(cov_dir+"/coverage.html"):
                self.DisplayURL(now+"/coverage.html")
                self.output_lineinfo(cov_dir+"/coverage.html")

        else:
            print "Couldn't generate coverage info"

    def start(self):
        print('====================================Start to Run===================================')
        try:
            self.before_run()
            self.running()     #Todo:BUILD Error?
            if('yes' == self.iscodecoverage):
                self.ToHtdocsForPerl()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
        finally:
            if self.debug == 1:
                command.ReadFile()
            if self.workplace == 0:
                self.clean_data(perl_cov)


