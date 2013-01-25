#!/usr/bin/env python
#coding=utf-8

#chkconfig: - 91 35
#description: Starts and stops the toast Daemon

import os, sys, commands
import subprocess
import getopt
import logging
import time 
import ConfigParser

def docmd(cmd):
    try:
        logger.info(cmd)
        p = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, close_fds=True)
        outputs=''
        while True:
            line = p.stdout.readline()
            if not line:
                break
            outputs=outputs+line
        p.wait()
        return (p.returncode,outputs)
    except Exception, e:
        print "we meet an exception while invoke command: " + cmd
        print str(e)
        sys.exit(2) 
# have Z process       
def docmd1(cmd):
    """
    input: cmd, the command needed to run
    output: (stdcode, output), a tuple include stdcode and output
    """
    #p = subprocess.Popen(cmd, shell = True, stdout = subprocess.PIPE, stderr = subprocess.PIPE, close_fds = True)
    #stdout, stderr = p.communicate()        
    print cmd
    if not cmd:
        return (0, '')
    retcode, output = commands.getstatusoutput(cmd)
    if debug:
        print 'Command:', cmd
        print 'Return Code:', retcode
        print 'Output:', output 
    if retcode >= 0:
        return (retcode, output)
    else:
        raise Exception 
    
def daemonize(stdout='/dev/null', stderr=None, stdin='/dev/null',  
              pidfile=None, startmsg = 'Start toastdaemon services with pid %s' ):  
    ''' 
         This forks the current process into a daemon. 
         The stdin, stdout, and stderr arguments are file names that 
         will be opened and be used to replace the standard file descriptors 
         in sys.stdin, sys.stdout, and sys.stderr. 
         These arguments are optional and default to /dev/null. 
        Note that stderr is opened unbuffered, so 
        if it shares a file with stdout then interleaved output 
         may not appear in the order that you expect. 
     '''  
    # flush io  
    sys.stdout.flush()  
    sys.stderr.flush()  
    # Do first fork.  
    try:  
        pid = os.fork()  
        if pid > 0: os._exit(0) # Exit first parent.  
    except OSError, e:  
        sys.stderr.write("fork #1 failed: (%d) %s\n" % (e.errno, e.strerror))  
        sys.exit(1)         
    # Decouple from parent environment.  
    #os.chdir("/")  
    os.umask(0)  
    os.setsid()  
    # Do second fork.  
    try:  
        pid = os.fork()  
        if pid > 0: os._exit(0) # Exit second parent.  
    except OSError, e:  
        sys.stderr.write("fork #2 failed: (%d) %s\n" % (e.errno, e.strerror))  
        sys.exit(1)  
    # Open file descriptors and print start message  
    if not stderr: 
        stderr = stdout  
    si = file(stdin, 'r')  
    so = file(stdout, 'a+')  
    se = file(stderr, 'a+', 0)  #unbuffered  
    pid = str(os.getpid())  
    sys.stderr.write("%s\t\t\t   [\033[32m  OK  \033[0m]\n" % startmsg % pid)  
    sys.stderr.flush()  

    if pidfile: 
        file(pidfile,'w+').write("%s\n" % pid)  
    # Redirect standard file descriptors.  
    #os.dup2(si.fileno(), sys.stdin.fileno())  
    #os.dup2(so.fileno(), sys.stdout.fileno())  
    #os.dup2(se.fileno(), sys.stderr.fileno())  

def format(cmdline):
    cmdline = cmdline.replace('\0', '')
    cmdline = cmdline.replace('\n', '')
    cmdline = cmdline.replace('\r', '')
    cmdline = cmdline.replace(' ', '')
    return cmdline

def check():
    global agent_key, logger
    agentDied = True

    # agent part
    agentPID = getagentpid()
    if agentPID != 0:
        agentDied = False
    if agentDied:
        logger.info('toastcontroller is died!')
        if debug:
            print 'toastcontroller is died!'

        stdcode, output = docmd('./toastcontroller')
        logger.info(output)
        if debug:
            print output 

def main():
    global agent_key, logger, cfgParser, config_file, workingdir
    while True:
        timeinterval = cfgParser.getint('COMMON', 'timeinterval')

        agent_key = cfgParser.get('AGENT', 'key')
        
        check()
        time.sleep(timeinterval)

def stopagent():
    global  pid_file, logger
    logger.info('Stopping toastcontroller...')
    try:
        stdcode, output = docmd("./toastcontroller" + ' -e')
        logger.info(output)
        time.sleep(10)
        agentpid = getagentpid()
        if agentpid != 0:
            logger.info('start kill -9 toastcontroller')
            cmd = 'kill -9 ' + str(agentpid)
            os.system(cmd)
        os.remove('./toastcontroller.pid')
    except Exception, e:
        logging.error('Handler Error: ' + str(e))
        if debug:
            print e
    
def stopped():
    pid = getpid()
    for i in range(10):
        if pid and os.path.exists('/proc/' + str(pid)):
            time.sleep(1) 
        else:
            return True 
    else:
        return False
def getagentpid():
    try:
        pid = int(file('./toastcontroller.pid', 'r').read())
        if not os.path.exists('/proc/' + str(pid)):
            logger.info('toastcontroller is not run') 
            pid = 0
    except Exception:
        logger.info('toastcontroller is not run')
        pid = 0
    return pid

def getpid():
    global pid_file
    try:
        pid = int(file(pid_file, 'r').read())
    except Exception:
        pid = 0
    return pid

def usage():
    print "usage: toastdaemon start | stop | restart | status"

if __name__ == '__main__':
    if os.getuid():
        print "You need to be root to perform this command."
        sys.exit(1)
    debug = 0

    config_file = '/path to controller/toastd.conf'
    pid_file = '/var/run/toastcontrollerd.pid'
    cfgParser = ConfigParser.ConfigParser()
    cfgParser.read(config_file)
    workdingdir = cfgParser.get('AGENT', 'workdingdir')
    os.chdir(workdingdir) 
    log_file = workdingdir+'toastd.log'
   
    agent_key = cfgParser.get('AGENT', 'key')

    logger = logging.getLogger()
    hdlr = logging.FileHandler(log_file)
    formatter = logging.Formatter('%(asctime)s %(filename)s[line:%(lineno)d][%(levelname)s]: %(message)s')
    hdlr.setFormatter(formatter)
    logger.addHandler(hdlr)
    logger.setLevel(logging.NOTSET)
    try:
        opts,args=getopt.getopt(sys.argv[1:], "hc:d", ["help", "config=", "debug"])
    except getopt.GetoptError:
        usage()
        sys.exit(2)
    for opt, opt_arg in opts:
        if opt in ("-h", "--help"):
            usage()
            sys.exit(1)
        elif opt in ("-c", "--config"):
            config_file = opt_arg
        elif opt in ("-d", "--debug"):
            debug = 1

    for arg in args:
        if arg == 'start':
            break
        elif arg == 'stop':
            sys.stdout.write('Shutting down toastcontrollerd services:\t\t\t\t   ')
            sys.stdout.flush()
            if not getpid():
                print '[\033[31mFAILED\033[0m]'
                print 'toastcontrollerd is not start'
                sys.exit(1)
            try:
                killcmd = 'kill -9 ' + str(getpid())
                os.system(killcmd)
            except Exception, e:
                logging.error('Stop Error:' + str(e))
            if stopped():
                stopagent()
                print '[\033[32m  OK  \033[0m]'
                os.remove(pid_file)
                sys.exit(0)
            else:
                print '[\033[31mFAILED\033[0m]'
                sys.exit(1)
        elif arg in ('restart', 'reload'):
            sys.stdout.write('Shutting down toastcontrollerd services:\t\t\t\t   ')
            sys.stdout.flush()
            if not getpid():
                print '[\033[31mFAILED\033[0m]'
            else:
                try:
                    killcmd = 'kill -9 ' + str(getpid())
                    os.system(killcmd)
                    stopagent()
                except Exception, e:
                    logging.error('Restart Error: ' + str(e))
                if stopped():
                    stopagent()
                    print '[\033[32m  OK  \033[0m]'
                else:
                    print '[\033[31mFAILED\033[0m]'
            break
        elif arg == 'status':
            pid = getpid()
            if pid:
                print 'toastcontrollerd (pid ' + str(pid) + ') is running...'
            else:
                print 'toastcontrollerd is stopped'
            sys.exit(0)
        else:
            usage()
            sys.exit(1)
    else:
        usage()
        sys.exit(1)

    if os.path.isfile(config_file) == False:
        print 'ERROR: toastd run error, config file is not exist!'
        usage()
        sys.exit(1)
        
    logging.info('Starting...')
    pid = getpid()
    if pid and os.path.exists('/proc/' + str(pid)):
        print 'toastcontrollerd (pid ' + str(getpid()) + ') is still running, please stop it first.'
        logging.info('toastcontrollerd (pid ' + str(getpid()) + ') is still running, please stop it first.')
        sys.exit(1)

    if not debug:
        daemonize(pidfile = pid_file)

    # start the daemon main loop  
    try:
        main()
    except Exception, e:
        logging.error('Main Error: ' + str(e))


