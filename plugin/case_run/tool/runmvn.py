#!/usr/bin/python
# call mvn command run mvn case
# 1. checkout code in the svn
# 2. mvn test to run the specify case
# Infact it's just a mvn wapper

#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#

import ConfigParser
import string, os, sys
import subprocess
import uuid
import shutil
class run_mvn_case:
    def __init__(self):
        self.options       = {}
        self.CONFILE       = ""
        self.casetorun     = ""
        self.mvnprojectsvn = ""
        self.configer      =NULL
        self.local_path = ""
    def __init__(self, cfg_file, casetorun, mvnprojectsvn, caseid):
        self.CONFILE = cfg_file
        self.casetorun = casetorun
        self.mvnprojectsvn=mvnprojectsvn
        self.configer = ConfigParser.ConfigParser()
        self.configer.read(self.CONFILE)
        self.local_path = "/tmp/" + str(uuid.uuid4())
        self.id = caseid
        self.cleanup()
    def get_code(self):
        svn_account = self.configer.get('svn', 'account')
        svn_password = self.configer.get('svn', 'password')
        svn_command = "svn co " + "--username " + svn_account + " --password " + svn_password + " --no-auth-cache " + " --non-interactive " + self.mvnprojectsvn + " " + self.local_path
        print svn_command
        pipe = subprocess.Popen(svn_command, bufsize=4096, shell=True, stderr=subprocess.STDOUT, stdout = subprocess.PIPE, close_fds=True)
        while True:
            line = pipe.stdout.readline(4096)
            if not line:
                break
            sys.stdout.write(line)
        return pipe.wait()  

    def run_a_case(self, case):
        print 'start to run case'
        command = "cd " + self.local_path + "; mvn -Dtest=" + case + " test"
        print command
        pipe = subprocess.Popen(command, bufsize=4096, shell=True, stderr=subprocess.STDOUT, stdout = subprocess.PIPE, close_fds=True)
        while True:
            line = pipe.stdout.readline()
            if not line:
                break
            sys.stdout.write(line)
        print "CASE ID: " + self.id + "\n"
        return pipe.wait()

    def cleanup(self):
        if os.path.exists(self.local_path):
            shutil.rmtree(self.local_path)


def usage():
    print "run mvn\n" \
        "-h --help print this help message\n" \
        "-c --class test calass want to run\n" \
        "-u --svnurl the maven project base svn url\n"

if __name__ == '__main__':
    import getopt
    
    if len(sys.argv) < 2:
        usage()
        sys.exit(1)
    try:
        opts,args = getopt.getopt(sys.argv[1:], "hc:u:", ["help", "class=", "svnurl="])
    except getopt.GetoptError as err:
        print str(err)
        usage()
        system.exit(2)    
    runclass = ""     
    svnurl = ""                 
    for o, a in opts:
        if o in("-h", "--help"):
            usage()
            sys.exit()
        elif o in ("-c", "--class"):
            runclass = a
        elif o in("-u", "--svnurl"):
            svnurl = a
        else:
            assert False, "unhandled option"
    cfg_file = os.path.splitext(os.path.abspath(__file__))[0] + ".conf"
    print cfg_file
    print runclass    
    print svnurl
    runner = run_mvn_case(cfg_file, runclass, svnurl)
    try:
        runner.get_code()
        print 'code has checked out'
        runner.run_a_case(runclass)
    except Exception, ex:
        print Exception,":",ex
        print traceback.format_exc()
    finally:
        runner.cleanup()
        sys.exit(0)
