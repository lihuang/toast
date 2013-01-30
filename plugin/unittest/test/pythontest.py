#! /usr/bin/python2.6
#-*- coding:utf-8 -*-
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# pythontest
#   run the test case of python and capture the code coverage
#   it is inherit from TestParent and HTMLParser

import sys
import traceback
import os
import datetime
from HTMLParser import HTMLParser
from Test import TestParent

sys.path.append(os.path.join(os.path.dirname(__file__),"../common"))
import command

class PythonTest(TestParent,HTMLParser):
    def __init__(self,options,config):
        TestParent.__init__(self,options,config)
        HTMLParser.__init__(self)
        self.total = False
        self.flag = False
        self.data = []

    def handle_starttag(self,tag,attrs):
        if tag == "tr":
            if len(attrs) == 0:pass
            else:
                for (var,value)in attrs:
                    if var == "class" and value == "total":
                        self.total = True
                    
        if tag == "td" and self.total == True:
            self.flag = True
        
    
    def handle_data(self,data):
        if self.flag == True and data.find("\n") == -1:
                self.data.append(data)
            

    def handle_endtag(self,tag):
        if tag == "tr" and self.total == True:
            self.total = False
            self.flag = False

    def ToHtdocsForPython(self):
        cov_dir = ''
        for root,dirs,files in os.walk(self.BasePath,None,False):
            for directory in dirs:
                if directory == "htmlcov":
                    cov_dir = os.path.abspath(os.path.join(root,directory))

        if cov_dir != '':
            now=datetime.datetime.now().strftime("%m-%d-%Y-%X")
            directory='/tmp/'+now
            copy = "cp -r "+cov_dir+" "+directory
            command.DoCmd(copy,'')
            self.ToHtdocs(directory);
            self.DisplayURL(now);
            #self.output_lineinfo()
            self.output_lineinfo(cov_dir+"/index.html")
        else:
            print "Couldn't generate coverage info"

    def output_lineinfo(self,htmlfile):
        output = open(htmlfile,"r")
        try:
            htmlcode = output.read()
            self.feed(htmlcode)
            self.close()
            if len(self.data) == 5:
                total = int(self.data[1])
                missing = int(self.data[2])
                run = total - missing
                print "CODE COVERAGE RESULT OF LINES IS: "+str(run)+"/"+str(total)
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
            if('yes' == self.iscodecoverage):
                self.ToHtdocsForPython()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
        finally:
            if self.debug == 1:
                command.ReadFile()
            if self.workplace == 0:
                self.clean_data("htmlcov")
    
