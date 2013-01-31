#!/usr/bin/python2.6
#-*- coding:utf-8 -*-

import shutil
import sys
import os
import traceback
import subprocess
import datetime
import string
import platform
import socket
import urlparse
from Test import TestParent
from surefirereportparser import SurefireReportParser

sys.path.append(os.path.join(os.path.dirname(__file__),"../../common"))
import command


MVNMERGE               = '/usr/local/toast/script/cobertura-1.9.4.1/cobertura-merge.sh'
MVNREPORT              = '/usr/local/toast/script/cobertura-1.9.4.1/cobertura-report.sh'
pathofmvncov            = "target/site/cobertura"

class MvnTest(TestParent):
    def __init__(self,config,options):
        TestParent.__init__(self,config,options)


    '''
    copy html files from subdirs to makefilepath/target/site/cobertura
    '''
    def copy_html(self,frompath,topath):
        subdirs = [];
        for root,dirs,files in os.walk(frompath):
            for dir in dirs:
                subdir = os.path.join(root,dir)
                if subdir.endswith(pathofmvncov) and subdir != topath:
                    subdirs += [subdir]
        for subdir in subdirs:
            copycommand = "find "+subdir +"  -maxdepth 1 -name '*.html' ! -name 'frame*.html' ! -name 'index.html' ! -name 'help.html' -print | xargs -i cp  {} " +topath
            command.DoCmd1(copycommand)

    '''
       Merge all the cobertura.ser to buildpath/target/cobertura/cobertura.ser" 
    '''
    def MergeCobertura(self):
        self.Debug('Merge all the cobertura.ser....')
        if os.path.exists(self.makefilepath)==False:
            print "Invalid makefilepath: "+self.makefilepath
            return False
        os.chdir(self.makefilepath)
        coberturapath = os.path.join(self.makefilepath,"target/cobertura")
        if not os.path.exists(coberturapath):
            try:
                os.makedirs(coberturapath)
            except Exception,e:
                print "Meet a exception when crating "+coberturapath
                print e
                return false
        mergeto = os.path.join(self.makefilepath,"target/cobertura/cobertura.ser")
        mergeFrom = " "
        sers=[];
        for root,dirs,files in os.walk(self.makefilepath):
            for name in files:
                if name == "cobertura.ser":
                    ser = os.path.join(root,name)
                    if ser != mergeto:
                        mergeFrom += " "+os.path.join(root,name)
        if mergeFrom == " ":#No any .ser to merge
            return False
        merge = self.MvnMerge + " --datafile "+mergeto +mergeFrom 
        command.WriteFile(merge)
        command.DoCmd1(merge);
        
        self.Debug('Generate the html...... ')
        datafile = mergeto
        destination = os.path.join(self.makefilepath,pathofmvncov)
        if not os.path.exists(destination):
            try:
                os.makedirs(destination)
            except Exception,e:
                print "Meet an exception when creating "+destination
                print e
                return false
        generate = self.MvnReport+" --datafile "+datafile +" --destination "+destination
        print generate
        command.DoCmd1(generate);
        self.copy_html(self.makefilepath,destination);
        #self.output_lineinfo_for_mvn(destination);
        return True

    def find_images_css(self):
        images = ''
        css = ''
        for root,dirs,files in os.walk(self.makefilepath):
            for dir_name in dirs:
                if images !='' and css != '':
                    return images,css
                elif images == '' and os.path.join(root,dir_name).endswith("target/site/images"):
                    images = os.path.join(root,dir_name)
                elif css == ''  and os.path.join(root,dir_name).endswith("target/site/css"):
                    css = os.path.join(root,dir_name)
        return images,css

    def copy_surefire_reports(self):
        dir_name = os.path.join(self.makefilepath,"target/site")
        reports_dir = os.path.join(dir_name+"/surefire_reports")
        if not os.path.exists(dir_name):
            print "The project not generate any info for test"
            return
        try:
            os.makedirs(reports_dir)
        except Exception,e:
            print "We meet an exception when creating surefire_reports directory"
            print e
            return

        #copy images and css to reports_dir
        images_dir = os.path.join(reports_dir,"images") 
        css_dir    = os.path.join(reports_dir,"css")
        if not os.path.exists(images_dir) or not os.path.exists(css_dir):
            images,css = self.find_images_css()
            if images != '':
                cp_images = "cp -r "+images+" "+images_dir
                command.DoCmd(cp_images,'')
            if css != '':
                cp_css = "cp -r "+css+" "+css_dir
                command.DoCmd(cp_css,'')

        for root,dirs,files in os.walk(self.makefilepath):
            for file_name in files:
                if file_name == "surefire-report.html" and root != self.makefilepath and not root.startswith(reports_dir):
                    sub_dirs = root.replace(self.makefilepath,'').replace("/target/site",'').split("/")
                    for sub_dir in sub_dirs:
                        if sub_dir == '':
                            sub_dirs.remove(sub_dir)
                    sub_dirs.append("surefire-report.html")
                    to_file = os.path.join(reports_dir,("-".join(sub_dirs)))
                    cp_report = "cp  "+os.path.join(root,file_name)+" "+to_file
                    command.DoCmd(cp_report,'')

    def output_lineinfo_for_mvn(self,path):
        summary_file = os.path.join(path,"frame-summary.html")
        if os.path.isfile(summary_file):
            input = open(summary_file,'r')
            try:
                lines = input.readlines()
                import re                    #regex
                for line in lines:
                    result = re.search(".*All\s+Packages.*?(\\d+/\\d+).*?(\\d+/\\d+)",line)
                    if result != None:
                        print "CODE COVERAGE RESULT OF LINES IS: "+str(result.groups()[0])
                        print "CODE COVERAGE RESULT OF BRANCHES IS: "+str(result.groups()[1])
                        break;
            except Exception,e:
                print "We meet an exception when reading the "+summary_file
                print e
        else:
            print "Could not find the frame-summaty.html"

    def ToHtdocsForMvn(self):
        source = os.path.join(self.makefilepath,"target/site")
        if os.path.exists(source):
            now=datetime.datetime.now().strftime("%m-%d-%Y-%X")
            directory='/tmp/'+now
            copy = "cp -r "+source+" "+directory
            command.DoCmd(copy,'')
            self.ToHtdocs(directory);
            self.DisplayURL(now);
        else:
            print "Couldn't generate coverage info"
        
    def DisplayURL(self,now):
        coveragepath = os.path.join(self.makefilepath,pathofmvncov)
        reportpath = os.path.join(self.makefilepath,"target/site/surefire_reports")
        if self.HTDOCS == 1: 
            hostname=socket.gethostname()
            ip = self.GetIpFromHostname(hostname)
            (pre, post)=os.path.split(self.Htdocs)
            url="http://"+str(ip[0])
            url=urlparse.urljoin(url,post)
            url=url+'/'+now
	 
            if os.path.exists(coveragepath):
                print "\nCODE COVERAGE RESULT WAS SAVED TO LOCAL: "+url+"/cobertura\n"
            if os.path.exists(reportpath) and os.listdir(reportpath)!=[]:
                print "\nRUNNING DATA WAS SAVED TO LOCAL: "+url+"/surefire_reports\n"
        if self.IP != "" and self.ServerAccount != "" and self.ServerPwd != "" and self.ServerHtdocs!="":
            url = "http://"+self.IP+"/"+now
            if os.path.exists(coveragepath):
                print "\nCODE COVERAGE RESULT WAS SAVED TO: "+url+"/cobertura\n"
            if os.path.exists(reportpath) and os.listdir(reportpath)!=[]:
                print "\nRUNNING DATA WAS SAVED TO: "+url+"/surefire_reports\n"
    def get_case_list(self, result):
                # get test case list
        try:
            print "Starting processing report directorys"
            tag = "Surefire report directory:"
            lines = result.splitlines()
            report_dirs = []
            for line in lines:
                if line.find(tag) != -1:
                    report = line[line.find(tag) + len(tag):]
                    report = report.strip()
                    if report not in report_dirs:
                        report_dirs.append(report)
            reportParser = SurefireReportParser(report_dirs)
            reportParser.parseXMLReportFiles()
            reportParser.parseResult()
        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
            print "Get case list failed"

    def before_run(self):
        if (platform.architecture()[0] == '64bit'):
            self.JavaHome = self.JavaHome + '.x86_64'
        os.environ["JAVA_HOME"] = self.JavaHome
        os.environ["PATH"] = self.MvnPath + os.environ["PATH"]
        TestParent.before_run(self)

    
    def running(self):
        result = TestParent.running(self)
        if result.find("[ERROR] BUILD ERROR")!=-1 or result.find("BUILD FAILURE")!=-1:
            print "BUILD ERROR/FAILURE"
            return 100
        self.get_case_list(result)

    def get_codecoverage(self):
        print "========================= generate coverage start here===================="
        merge = self.MergeCobertura()
        self.copy_surefire_reports()
        sitepath = os.path.join(self.makefilepath,"target/site")
        coveragepath = os.path.join(self.makefilepath,pathofmvncov)	    
        if os.path.exists(sitepath):
            self.ToHtdocsForMvn()
            if os.path.exists(coveragepath):
                self.output_lineinfo_for_mvn(coveragepath)
        else:
            print "Didn't find any coverage info or surefire-report"
            return

    def start(self):
        print('====================================Start to Run===================================')
        try:
            self.before_run()

            if (100 == self.running()):
                return 100
 
            if('yes' == self.iscodecoverage):
                self.get_codecoverage()

        except Exception, e:
            exc_type, exc_value, exc_traceback = sys.exc_info()
            traceback.print_exception(exc_type, exc_value, exc_traceback)
            print "We meet an exception while running, program will exit."
        finally:
            if 1 == self.debug:
                command.ReadFile()
            if 0 == self.workplace:
               self.clean_data("target")
            #if os.path.exists(self.LogFile):
                #os.remove(self.LogFile)
