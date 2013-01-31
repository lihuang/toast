#!/usr/bin/python2.6
#-*-coding:utf-8 -*-
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# ctest
#   run the test case of c/c++ and capture the code coverage by lcov
#   it is inherit from TestParent and HTMLParser

from Test import TestParent
import os
import sys
import shutil
import string
import socket
import datetime
import urlparse
import traceback
import subprocess
import dealinfo
import replacedir

sys.path.append(os.path.join(os.path.dirname(__file__),"../../common"))
import command

seperator = '&'
LCOVOUTPUTFILE = 'app.info'
INFOPATH  =''

class CTest(TestParent):
    def __init__(self,options,config):
        TestParent.__init__(self,options,config)
        self.lcov_info = ''
	#keep defalt code coverage calculation, do NOT care the codes test doesn't hit
	#1 = keep; 
	self.keep = options.get('keep', 0)

    '''
     Make() will build Source Code
     Makefile MUST be supported make coverage=yes
     1. Compile the application with GCC using the options "-fprofile-arcs" and "-ftest-coverage"
     2. "-o" option also must be removed from Makefile 
    '''
    def Make(self):
        self.Debug(' Start to make project....')
        if os.path.exists(self.makefilepath) ==False:
            print "Invalid makefilepath: "+self.makefilepath;
            return False
        os.chdir(self.makefilepath)
        if(self.makefilecommands.find(seperator) == -1):
            print self.makefilecommands
            returncode,res = command.DoCmd1(self.makefilecommands)
            if returncode == 0:
                return True
            elif self.ignore_build_error == 1 and self.JudgeBuildError(res) == False: #the build error cause returncode!=0
                return True
            else:
                return False
        else:
            makecommandlist=self.makefilecommands.split(seperator)
            for cmd in makecommandlist:
                returncode,res = command.DoCmd1(cmd)
                if returncode == 0:
                    return True
                elif self.ignore_build_error == 1 and self.JudgeBuildError(res) == False: #the build error cause returncode!=0
                    return True
                else:
                    return False
        return True

    def JudgeBuildError(self, result):
        error  = "error:"
        error1 = "Error 1"
        error2 = "閿欒 1"
	
        if( (result.find(error) != -1) or (result.find(error1) != -1) or (result.find(error2) != -1) ):
           return False
        
        return True

    '''
    copy file,guarantee the .o,.gcno , .gcda in the same file
    '''
    d = {}
    def copyfile(self,fName,dirName):
        filename = os.path.basename(fName)
        if os.path.isfile(os.path.join(dirName,filename))==False:
            try:
                command.WriteFile("copying "+fName+" to "+dirName)
                shutil.copy(fName,dirName)
                if fName.endswith("gcda"):
                    os.remove(fName)
            except Exception, e:
                print 'Meet exception while copy file '
                print e

    def extractFileName(self,fName):
        file = os.path.basename(fName)
        return os.path.splitext(file)[0]
    
    def processFile(self,fName,dirName):
        prefileName = self.extractFileName(fName)
        if(fName.endswith(".cpp") or fName.endswith(".c") or fName.endswith(".cc")):
            self.d[prefileName] = dirName
        if(fName.endswith(".o") or fName.endswith(".gcda") or fName.endswith(".gcno")):
            prefixfile = fName.split('.')[0]
            if not (os.path.exists(os.path.join(dirName,prefixfile+'.cpp')) or os.path.exists(os.path.join(dirName,prefixfile+'.cc')) or os.path.exists(os.path.join(dirName,prefixfile+'.c'))):
                if prefileName in self.d:
                    self.copyfile(fName,self.d[prefileName])
    
    def recurCopy(self, dirname):
        for root, dirs, files in os.walk(dirname):
            for filespath in files:
                self.processFile(os.path.join(root, filespath), root)

    '''
    Lcov all the gcov files
    1) Resetting counters
    2) Capturing the current coverage state to a file
    3) Getting HTML output and make a copy to one special directory in htdocs
    '''
    def HasGcda(self,dir):
        for myfile in os.listdir(dir):
            if myfile.endswith(".gcda"):
                return True
        return False
    
    '''
    capture the code coverage by lcov and generate html by genhtml
    '''
    def OutputHtmlData(self):
        self.Debug('Start to capture coverage data by lcov.....')
        if self.ignoreDirs != []:
            self.RemoveIgnoreDirs()
        i = 0;
        for root,dirs,files in os.walk(self.makefilepath,False):
            for dirname in dirs:
                dirname = os.path.join(root,dirname)
                command.WriteFile( "lcovpath: "+dirname)
                if os.path.isdir(dirname) and self.HasGcda(dirname) == True:
                     os.chdir(dirname)
                     outputpath = os.path.join(self.makefilepath,str(i)+".info")
                     lcov_cmd= self.Lcov+" -c -d . -b . -o " + outputpath +" >> "+ self.LogFile
                     i = i+1
                     command.DoCmd1(lcov_cmd)
        dirlist = os.listdir(self.makefilepath);
        hasgcda = False;
        for myfile in dirlist:
            if os.path.isfile(os.path.join(self.makefilepath,myfile)) and myfile.endswith(".gcda"):
                hasgcda = True;
                break;
        if hasgcda:
            os.chdir(self.makefilepath);
            outputpath = os.path.join(self.makefilepath,str(i)+".info")
            lcov_cmd= self.Lcov+" -c -d . -b . -o " + outputpath  + ">>" +self.LogFile
            command.DoCmd1(lcov_cmd);
        
        infolist = os.listdir(self.makefilepath)
        lcov = self.Lcov
        
        for myfile in infolist:
            if myfile.endswith(".info"):
                if  dealinfo.check_file(os.path.join(self.makefilepath,myfile)):
                    lcov = lcov+" -a " +myfile
	
        os.chdir(self.makefilepath)
        if (self.extract == 1):
            lcov = lcov + " -o app1.info"
            command.DoCmd(lcov,'')

            lcov = self.Lcov + " -e app1.info *" + self.makefilepath +"* -o app.info"
            command.DoCmd(lcov,'')
        else:
            lcov = lcov + " -o app.info"
            command.DoCmd(lcov,'')

        replacedir.visit_dir(self.BasePath)
        replacedir.deal_file("app.info")
        if os.path.exists("tmp.info"):
            subprocess.call("mv tmp.info app.info",shell=True)
        print 'replace dir processing end'

        self.lcov_info = os.path.abspath("app.info")

	if(self.keep == 1):
	    dealinfo.modify_info("app.info",self.BasePath)

        os.system("find -name \"*.gcno\" |xargs rm -rf")
        os.system("find -name \"*.gcda\" |xargs rm -rf")
         #Get Current time and store html output to the directory named with current time
        self.Debug('Store html output to the directory named with current time')
        now=datetime.datetime.now().strftime("%m-%d-%Y-%X")
        directory='/tmp/'+now
        info_path = os.path.join(self.makefilepath,"app.info")
        genhtml_cmd= self.Genhtml +' ' + info_path  + ' -o ' + directory
        cp_info = "cp -r "+info_path+" "+directory
        command.WriteFile('genhtml_cmd is:\t'+genhtml_cmd)
        command.DoCmd(genhtml_cmd,'')
        command.DoCmd(cp_info,'')
        print "\nInfomations for code coverage in the: "+directory+"\n"

        self.OutPutLineInfoForGcov(self.LogFile);
        self.OutPutBranchInfoForGcov(self.LogFile);
        
        self.ToHtdocs(directory)
        self.DisplayURL(now)

    '''
    capture the code coverage of the project that build by scons instead of by makefile
    '''
    def OutputHtmlDataForScons(self):
        self.Debug('Start to capture coverage data by lcov.....')
        if self.ignoreDirs != []:
            self.RemoveIgnoreDirs()
        
        lcov_cmd = self.Lcov +" -c -d ./build/ -b . -o "+ " app.info"
        command.DoCmd1(lcov_cmd)


        self.lcov_info = os.path.abspath("app.info")
        
        import replacedir
        replacedir.visit_dir(self.BasePath)
        replacedir.deal_file("app.info")
        if os.path.exists("tmp.info"):
            subprocess.call("mv tmp.info app.info",shell=True)
        print 'replace dir processing end'

        os.system("find -name \"*.gcno\" |xargs rm -rf")
        os.system("find -name \"*.gcda\" |xargs rm -rf")
         #Get Current time and store html output to the directory named with current time
        self.Debug('Store html output to the directory named with current time')
        now=datetime.datetime.now().strftime("%m-%d-%Y-%X")
        directory='/tmp/'+now
        info_path = os.path.join(self.makefilepath,"app.info")
        genhtml_cmd= self.Genhtml +' ' + info_path  + ' -o ' + directory
        cp_info = "cp -r "+info_path+" "+directory
        command.WriteFile('genhtml_cmd is:\t'+genhtml_cmd)
        command.DoCmd(genhtml_cmd,'')
        command.DoCmd(cp_info,'')
        print "\nInfomations for code coverage in the: "+directory+"\n"

        self.OutPutLineInfoForGcov(self.LogFile);
        self.OutPutBranchInfoForGcov(self.LogFile);
        
        self.ToHtdocs(directory)
        self.DisplayURL(now)
    '''
    get the code coverage of line
    '''
    def OutPutLineInfoForGcov(self,logfile):
        if os.path.exists(logfile):
            input = open(logfile,'r');
            lines = input.readlines();
            is_info = False;
            line_cov_info = "CODE COVERAGE RESULT OF LINES IS: "
            rate = ""
            oldinfo = "";
            for line in lines:
                if line.startswith("Writing directory view page."):
                    is_info=True;
                elif is_info and line.startswith("  lines.."):
                    oldinfo = line;
                    break;

            if oldinfo != "":
                pos = string.find(oldinfo,'(')
                if pos != -1:
                    oldinfo = oldinfo[pos+1:]
                    pos = string.find(oldinfo,"of");
                    rate = oldinfo[:pos].strip()
                    oldinfo = oldinfo[pos+2:]
                    pos = string.find(oldinfo,"lines")
                    endpos = string.find(oldinfo,")")
                    rate = rate+"/"+oldinfo[:pos].strip()
                    rate.strip()
            if rate !="":
                line_cov_info = line_cov_info+rate;
                print line_cov_info
    '''
    get the code coverage of branch
    '''
    def OutPutBranchInfoForGcov(self,logfile):
        if os.path.exists(logfile):
            input = open(logfile,'r');
            lines = input.readlines();
            is_info = False;
            branch_cov_info = "CODE COVERAGE RESULT OF BRANCHES IS: "
            rate = ""
            oldinfo = "";
            for line in lines:
                if line.startswith("Writing directory view page."):
                    is_info=True;
                elif is_info and line.startswith("  branches.."):
                    oldinfo = line;
                    break;

            if oldinfo != "":
                pos = string.find(oldinfo,'(')
                if pos != -1:
                    oldinfo = oldinfo[pos+1:]
                    pos = string.find(oldinfo,"of");
                    rate = oldinfo[:pos].strip()
                    oldinfo = oldinfo[pos+2:]
                    pos = string.find(oldinfo,"branches")
                    endpos = string.find(oldinfo,")")
                    rate = rate+"/"+oldinfo[:pos].strip()
                    rate.strip()
            if rate !="":
                branch_cov_info = branch_cov_info+rate;
                print branch_cov_info

    def before_run(self):
        TestParent.before_run(self)
        if(self.makefilecommands and self.Make() == False):
            print "Build/Make ERROR"
            return 100

    def get_codecoverage(self):
        if 0 == self.scons :
            self.recurCopy(self.makefilepath)
            self.OutputHtmlData()
        else:
            self.OutputHtmlDataForScons()
    
    '''
    Main function entry point/
    '''
    def start(self):
        print('====================================Start to Run===================================')
        try:
            returncode  =self.before_run() 
            if 100 == returncode:
                return returncode
            self.running()
            
            if('yes' == self.iscodecoverage):
                print "========================= capture coverage===================="
                self.get_codecoverage()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
            print "We meet an exception while running, program will exit."
        finally:
            if self.debug == 1:
                command.ReadFile()
            if 0 == self.workplace:
                self.clean_data("*.info")
