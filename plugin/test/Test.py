#! /usr/bin/python2.6
#-*- coding:utf-8 -*-
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# Test
#   The base class for unit test

import ConfigParser
import string
import os
import datetime
import shutil
import socket
import urlparse
import getopt
import shutil
import uuid
import subprocess
import traceback
import sys
import platform
from xml.dom import minidom
from glob import glob


sys.path.append(os.path.dirname(__file__)) 
sys.path.append(os.path.join(os.path.dirname(__file__), '../common/')) 
import svn
import command

reload(sys)  
sys.setdefaultencoding('utf-8') 

debug_flag_start        = '\n----------------------------- Debug-Start-Flag --------------------------'
debug_flag_end          = '----------------------------- Debug-End-Flag ----------------------------'+'\n'
configprefix            = 'UnitTest'
svnprefix               = 'SVN'
seperator               = '&'
isstored                = 0

class TestParent:
    '''
        init all the properties
    '''
    def __init__(self,options,confile):
        self.CONFILE = confile
        self.ValidateExist(self.CONFILE)
        cfgParser=ConfigParser.ConfigParser()
        cfgParser.read(self.CONFILE)
        
        self.SvnAccount         =cfgParser.get(configprefix, 'SvnAccount')
        self.SvnPwd             =cfgParser.get(configprefix, 'SvnPwd')
        self.BasePath           =cfgParser.get(configprefix, 'BasePath')
        self.Htdocs             =cfgParser.get(configprefix, 'Htdocs')
        self.IP                 =cfgParser.get(configprefix,'ServerIp')
        self.ServerHtdocs       =cfgParser.get(configprefix,'ServerHtdocs')
        self.ServerAccount      =cfgParser.get(configprefix,'ServerAccount')
        self.ServerPwd          =cfgParser.get(configprefix,'ServerPwd')
        self.JavaHome           =cfgParser.get(configprefix,'JavaHome')
        self.Lcov               =cfgParser.get(configprefix,'Lcov')
        self.Genhtml            =cfgParser.get(configprefix,'Genhtml')
        self.MvnMerge           =cfgParser.get(configprefix,'MvnMerge')
        self.MvnReport          =cfgParser.get(configprefix,'MvnReport')
        self.MvnPath            =cfgParser.get(configprefix,'MvnPath')
        

        self.workplace           = self.get_value(0,options,'workplace')
        self.debug               = self.get_value(0,options,'debug')
        self.svnurl              = self.get_value('',options,'svnurl')
        self.localdir            = self.get_value('',options,'localdir')
        self.iscodecoverage      = self.get_value('no',options,'iscodecoverage')
        self.mvn                 = self.get_value(0,options,'mvn')
        self.extract             = self.get_value(0,options,'extract')
        self.makefilepath        = self.get_value('',options,'makefilepath')
        self.makefilecommands    = self.get_value('',options,'makefilecommands')
        self.dependpackage       = self.get_value('',options,'dependpackage')
        self.specdir             = self.get_value('',options,'specdir')
        self.scons               = self.get_value(0,options,'scons')
        self.ignoreDirs          = self.get_value([],options,'ignoreDirs')
        self.HTDOCS              = self.get_value(0,options,'HTDOCS')
        self.ignore_build_error  = self.get_value(0,options,'ignore_build_error')
        self.onecommand          = self.get_value(0,options,'onecommand')
        self.unittestcommands    = self.get_value('',options,'unittestcommands')
        self.LogFile             =  ''        

        print options
        if self.workplace == 1:
            if not cfgParser.has_section(svnprefix):
                try:
                    cfgParser.add_section(svnprefix)
                    cfgParser.write(open(self.CONFILE,"w"))
                except Exception, e:
                    print "we meet an exception while adding section!\n"
                    print e
                    exit(2)

        self.prepare_env();

    '''
    get value from dictionary:
    if the key does not in dic,return 'not found'

    '''
    def get_value(self,var,dic,key):
        value = dic.get(key,'not found')
        if value == 'not found':
            value = var
        return value

    '''
    Prepare enviroment for run the unittest
    '''
    def prepare_env(self):
        global isstored
        if self.svnurl != '':
            if self.workplace == 1:
                cfgParser=ConfigParser.ConfigParser()
                cfgParser.read(self.CONFILE)
                svnOption = self.svnurl.replace(':','')
                i = 0
                svnstr = self.svnurl.split('/')
                for stri in svnstr:
                    i += 1
                if svnstr[i-1] == '':
                    i -= 1
                path = svnstr[i-2] + '-' + svnstr[i-1]
                print path
                
                if cfgParser.has_option(svnprefix, path):
                    self.BasePath = cfgParser.get(svnprefix, path)
                    isstored = 1
                    if(os.path.exists(self.BasePath)==False):
                        isstored = 0
                else:
                    self.BasePath = self.BasePath + path
                    try:
                        cfgParser.set(svnprefix, path, self.BasePath)
                        cfgParser.write(open(self.CONFILE,"w"))
                    except Exception, e:
                        print "we meet an exception while adding section!\n"
                        print e
                        exit(2)
            else:
                self.BasePath = self.BasePath + str(uuid.uuid4())
        if self.localdir != '':
            self.BasePath =self.localdir
        if self.makefilepath == "":
            self.makefilepath = self.BasePath
        else:
            if self.makefilepath[0] == '.' and len(self.makefilepath)==1:
                self.makefilepath = self.BasePath
            elif self.makefilepath[0]=='/':
                self.makefilepath = self.makefilepath.lstrip('/')
                self.makefilepath = os.path.join(self.BasePath, self.makefilepath)
            elif len(self.makefilepath) > 1 and self.makefilepath[0] == '.' and self.makefilepath[1] == '/':
                self.makefilepath = os.path.join(self.BasePath,self.makefilepath[2:])
            else:
                self.makefilepath = os.path.join(self.BasePath,self.makefilepath)
        if self.svnurl !='' and ((self.workplace ==1 and isstored == 0) or self.workplace == 0):
            self.ValidateBasePath()
        
        self.LogFile = os.path.join(self.BasePath,"log.txt")
        command.SetFileName(self.LogFile)

    '''
    Display Debug Info with debug flags
    '''
    def Debug(self, info):
            print debug_flag_start
            print info
            print debug_flag_end
            command.WriteFile(debug_flag_start)
            command.WriteFile(info)
            command.WriteFile(debug_flag_end)
    
    def ValidateExist(self, path):
        if(os.path.exists(path)==False):
            print path+" can't be found, please check config file and this program will exit now !!!"
            shutil.rmtree(self.BasePath)
            sys.exit(2)

    '''
    create the self.BasePath
    '''
    def ValidateBasePath(self):
        if(os.path.exists(self.BasePath)==True): 
            try:
                print 'remove tree: '+self.BasePath
                shutil.rmtree(self.BasePath)
                os.makedirs(self.BasePath)
                print 'successfully create path: '+self.BasePath
                self.ValidateExist(self.BasePath)
            except Exception, e:
                print 'We meet an exception while rm/creating BasePath!'
                print e
                shutil.rmtree(self.BasePath)
                exit(2)
        else:
            try:
                print 'Create folder ' + self.BasePath
                os.makedirs(self.BasePath)
                self.ValidateExist(self.BasePath)
            except Exception, e:
                print 'Create folder failed ' + self.BasePath
                print e
                shutil.rmtree(self.BasePath)
                exit(2)
    '''
       chechout the code from svn,and store it in self.BasePath
    '''
    def Checkout(self):
        self.Debug(" Checkout code from build/SVN path....")
        objectsvn = svn.SVN()
        global isstored
        if  self.workplace == 1 and isstored == 1:
            print "update to " + self.BasePath
            objectsvn.Update(self.BasePath, self.SvnAccount, self.SvnPwd, False,False)
        else:
            print "checkout to " + self.BasePath
            objectsvn.CheckOut(self.svnurl, self.BasePath, self.SvnAccount, self.SvnPwd, False,False)    
        stdout, stderr = objectsvn.Svnlog(self.BasePath, self.SvnAccount, self.SvnPwd)
        if not stderr:
            self.ParseSvnlog(stdout)
    '''
       get the info of the project by svn log
    '''
    def ParseSvnlog(self, log):
        import re
        pattern = "revision=\"(\d+)\""
        for match in re.findall(pattern,log):
            print "REVISION IS:%s\n"%match
        xmldoc = minidom.parseString(log)
        author = xmldoc.getElementsByTagName('author')[0].firstChild.data
        date   = xmldoc.getElementsByTagName('date')[0].firstChild.data
        comment= xmldoc.getElementsByTagName('msg')[0].firstChild.data
        nodelist=xmldoc.getElementsByTagName('path')
        filelist=[]
        for node in nodelist:
            filelist.append(node.firstChild.data)
        lists = ";".join(filelist)
        command.WriteFile('svn last author: '+author)
        command.WriteFile("svn last date: " + date)
        command.WriteFile("svn last lists: " + lists)
        command.WriteFile("svn last comment: " + comment)
    
    '''
    down load spec file for installing the dependecy packages before build the project
    '''
    def DownloadSpec(self,url):
        self.Debug(" Checkout code from build/SVN path....")
        objectsvn = svn.SVN()
        specfile = url.split("/")[-1]
        target = os.path.join(self.BasePath,specfile)
        objectsvn.SvnOperate("export",url, target, self.SvnAccount, self.SvnPwd, False,False)    
        return target
    
    '''
        get the BuildRequires that descripted in the spec files
        input: the dir of the specfile
        output: the packages that should be depended by the project in build step
    '''
    def ReadSpec(self,specdir):
        abs_specdir = os.path.abspath(self.specdir)
        os.chdir(self.BasePath)
        
        import re
        specfile = open(abs_specdir,"r")
        lines = specfile.readlines()
        pattern = re.compile("\s*BuildRequires:")
        strlist = []
        packages = []
        for line in lines:
            line = line.strip("\n")
            if pattern.match(line):
                strlist = line.split(":")
                if strlist:
                    packages.extend(strlist[1].split(","))
        return packages
    
    def StrCmp(self,str1,str2,cmp):
        if cmp == "=":
            return str1 == str2
        elif cmp == "<=":
            return str1 <= str2
        elif cmp == ">=":
            return str1 >= str2
        elif cmp == "<":
            return str1 < str2
        elif cmp == ">":
            return str1 > str2
        else:
            return False

    def PackagesWithVersion(self,package,version,cmp):
        yumlist_cmd = "yum list " +package + " | grep "+package
        code,outputs = command.DoCmd(yumlist_cmd,'')
        if code == 0:
            results = outputs.split("\n")[:-1]
            for result in results:
                exist_version = result.split()[1]
                if self.StrCmp(exist_version.strip(),version.strip(),cmp):
                    package = package.strip() +"-"+exist_version.strip()
                    return package
        return package
 	
    def GetPackages(self,packages):
        index = 0
        package = ''
        version = ''
        while index < len(packages):
            element = packages[index]
            if element.find("=") == -1 and element.find("<") == -1 and element.find(">") == -1:
                index = index +1
                continue
            elif element.find("=") != -1 and element.find("<") == -1 and element.find(">") == -1:
                package,version = element.split("=")
                packages[index] = self.PackagesWithVersion(package,version,"=")

            elif element.find("=") != -1 and element.find("<") != -1 and element.find(">") == -1:
                package,version = element.split("<=")
                packages[index] = self.PackagesWithVersion(package,version,"<=")
		
            elif element.find("=") != -1 and element.find("<") == -1 and element.find(">") != -1:
                package,version = element.split(">=")
                packages[index] = self.PackagesWithVersion(package,version,">=")
			
            elif element.find("=") == -1 and element.find(">") != -1 and element.find("<") == -1:
                package,version = element.split(">")
                packages[index] = self.PackagesWithVersion(package,version,">")
		
            elif element.find("=") == -1 and element.find("<") != -1 and element.find(">") == -1:
                package,version = element.split("<")
                packages[index] = self.PackagesWithVersion(package,version,"<")
            index = index + 1		
        return packages

    def ExecYum(self,cmd,operation):
        if cmd != '':
            self.Debug('Yum running')
            yumcmds = cmd.split(';')
            for yum_cmd in yumcmds:
                a = yum_cmd.split(',')
                package=(a[0],)
                if len(a) == 2:
                    branch = a[1]
                else:
                     branch = 'test'
                status,output = command.yum(package,'',operation,branch)
                command.WriteFile(output)
                if(status!=0):
                    raise Exception,output
    
    '''
    Run all the unittest binary
    '''
    def RunTest(self):
        self.Debug('Start to run test.....')
        result = ""
        
        if(self.unittestcommands.find(seperator) == -1):
            result = result + self.RunSingleTest(self.unittestcommands)
        else:
            # more than one unittest commands, seperated by seperator "&"
            unittestlist=self.unittestcommands.split(seperator)
            for test in unittestlist:
                result = result + self.RunSingleTest(test)
        return result
    
    '''
    Run Single test command, for example, -u "cd xxx; ./*_unittest; cd yyy; ./*.test"
    '''
    def RunSingleTest(self, unittestcommand):
        result = ""
        os.chdir(self.BasePath)

        if(self.onecommand == 1):
            returncode,res = command.DoCmd(self.unittestcommands,'')
            return res
        commandlines=unittestcommand.split(";");
        for line in commandlines:
            if(line.lstrip().startswith('cd ') == True):   # hard code here, to to modify
                newline = line.replace('cd', '').strip()
                if (newline.lstrip()[0] == '/'):
                    pwd = newline
                else:
                    pwd = os.getcwd() + '/' + newline # MUST using string adding here, instead of os.path.join()
                os.chdir(pwd) 
            elif(line.lstrip().startswith('export ') == True):   # hard code here, to to modify
                line = line.replace('export', '').strip()  #strip() in python remove NOT ONLY spce but also whitespace characters as well(eg. tabs and newlines)
                lines = line.split('=')
                if(len(lines) == 2):
                    key = lines[0]
                    value = lines[1]
                    os.environ[key] = value  # set linux environment value, DO NOT use os.putenv(), because putenv() does not change of.envrion directly.
            else:
                result = result + self.RunTestWithStar(line)
        return result

    '''
    Run Test commandline, which CAN contain '*' (star) 
    '''
    def RunTestWithStar(self, commandline):
        #print 'os.environ is 1:\n'
        #print os.environ
        result = ''
        commandline = commandline.lstrip() #remove left space
        if(commandline.find('*') == -1 or self.mvn == 1):
            res = ''
            returncode,res = command.DoCmd(commandline,'')
            result = result + res
        else:
            testlist = glob(os.getcwd() + '/' + commandline)
            for test in testlist:
                command.DoCmd(test,'')
        return result

    '''
    do not capture the code coverage of the files or dirs that specified by the option:-I,--ingore_dirs
    '''
    def RemoveIgnoreDirs(self):
        scons_build_path = os.path.join(self.makefilepath,"build/debug64")
        index = 0
        if self.scons == 0:
            while index < len(self.ignoreDirs):
                self.ignoreDirs[index] = os.path.join(self.makefilepath,self.ignoreDirs[index])
                index = index + 1
        
        else:
            while index < len(self.ignoreDirs):
                self.ignoreDirs[index] = os.path.join(scons_build_path,self.ignoreDirs[index])
                index = index + 1
        for dir in self.ignoreDirs:
            if dir.endswith(".cpp") or dir.endswith(".c") or dir.endswith(".cc"):
                prefile = os.path.splitext(dir)[0]
                file_gcno = prefile + ".gcno"
                file_gcda = prefile + ".gcda"
                command.DoCmd1("rm -rf "+file_gcno + " "+file_gcda)
            else:
                remove_cmd = "find "+dir+' -name \"*.gcno\" -o -name \"*.gcda\" | xargs rm -rf'
                command.DoCmd1(remove_cmd)
    
    '''
    input:hostname
    output: the ip that corresponding the hostname
    '''
    def GetIpFromHostname(self,hostname):
        result = socket.getaddrinfo(hostname,None,0,socket.SOCK_STREAM)
        return [x[4][0] for x in result]

    def DisplayURL(self, now):
        #Display URL and make sure self.Htdocs is 1-level sub-directory of $HTTP_ROOT
        if self.HTDOCS == 1: 
            hostname=socket.gethostname()
            ip = self.GetIpFromHostname(hostname)
            (pre, post)=os.path.split(self.Htdocs)
            url="http://"+str(ip[0])
            url=urlparse.urljoin(url,post)
            url=url+'/'+now
            print "\nCODE COVERAGE RESULT WAS SAVED TO LOCAL: "+url+"\n" 

        if self.IP != "" and self.ServerAccount != "" and self.ServerPwd != "" and self.ServerHtdocs!="":
            url = "http://"+self.IP+"/"+now
            print "\nCODE COVERAGE RESULT WAS SAVED TO: "+url+"\n" 

    '''
        cp codecoverage info to the local htdocs and scp it to the server
    '''
    def ToHtdocs(self, fromdir):
        self.Debug('copy html informations To htdocs/')
        if self.HTDOCS == 1:
            to=self.Htdocs
            self.ValidateExist(fromdir)
            cmd='cp -r '+fromdir+' '+to
            command.DoCmd(cmd,'')
        if self.IP != "" and self.ServerAccount !="" and self.ServerPwd != "" and self.ServerHtdocs != "":
            import pexpect
            to = self.ServerAccount+"@"+self.IP+":"+self.ServerHtdocs
            scp_cmd = "scp -r " + fromdir + " "+to
            try:
                send = pexpect.spawn(scp_cmd)
                fout = file(self.LogFile,'a')
                send.logfile = fout
                index = send.expect(['yes/no','(p|P)(a|A)(s|S)(s|S)(w|W)(o|O)(r|R)(d|D)',pexpect.EOF,pexpect.TIMEOUT])
                if index == 0:
                    send.sendline('yes')
                    pwd = send.expect(['(p|P)(a|A)(s|S)(s|S)(w|W)(o|O)(r|R)(d|D)',pexpect.EOF,pexpect.TIMEOUT])
                    if pwd == 0:
                        send.sendline(self.ServerPwd)
                    else:
                        print 'scp failed'
                        send.close(force=True)
                elif index == 1:
                    send.sendline(self.ServerPwd)
                else:
                    print "Can't connect to "+self.IP
                    send.close(force =True)
                send.expect(pexpect.EOF)
                fout.close()
                send.interact() 
            except:
                print "some unkown error"
                send.close(force=True)
        shutil.rmtree(fromdir)

    def install_packages(self,specdir):
        spec = specdir
        if specdir.startswith("http:"):
            spec = self.DownloadSpec(specdir)
        dependpackages = self.ReadSpec(spec)
        packages = self.GetPackages(dependpackages)
        command.yum(packages,'',"install","current")

    def before_run(self):
        if(self.svnurl != ""):
            self.Checkout()
        elif self.localdir != "":
            if not os.path.exists(self.BasePath):
                os.makedirs(self.BasePath)

        #install dependpackages which specified by -D
        if not self.dependpackage == '':
            self.ExecYum(self.dependpackage,"install")
                
        #install dependpackages which specified by spec file
        if not self.specdir == '':
            self.install_packages(self.specdir)

    '''
    Run all the unittest binary
    '''
    def running(self):
        self.Debug('Start to run test.....')
        result = ""

        if(self.unittestcommands.find(seperator) == -1):
            result = result + self.RunSingleTest(self.unittestcommands)
        else:
            # more than one unittest commands, seperated by seperator "&"
            unittestlist=self.unittestcommands.split(seperator)
            for test in unittestlist:
                result = result + self.RunSingleTest(test)
        return result
    
    '''
    cleaning up the data
    input:removefiles that need to remove from the machine
    '''
    def clean_data(self,removefiles):
        if self.svnurl != "" and os.path.exists(self.BasePath):
            shutil.rmtree(self.BasePath)
        if self.localdir != "":
            rmcommand = "find -name '"+removefiles+"' | xargs rm -rf "
            command.DoCmd1(rmcommand)
    
