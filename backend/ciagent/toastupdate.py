import commands
import os
import sys
import re
import time
def become_daemon(our_home_dir = '.', out_log = 'AgentDaemon.Update.log',
                  err_log = 'AgentDaemon.Update.log', umask = 022):
# First fork
    try:
        if os.fork() > 0:
            sys.exit(0)
    except OSError, e:
        sys.stderr.write("fork #1 failed: (%d) %s\n" % (e.errno, e.strerror))
        sys.exit(1)
    os.setsid()
    os.chdir(our_home_dir)
    os.umask(umask)

# Second fork
    try:
        if os.fork() > 0:
            os._exit(0)
    except OSError, e:
        sys.stderr.write("fork #2 faiiled: (%d) %s\n" % (e.errno, e.strerror))
        os._exit(1)

    si = open('/dev/null', 'r')
    so = open(out_log, 'a+', 0)
    se = open(err_log, 'a+', 0)
    os.dup2(si.fileno(), sys.stdin.fileno())
    os.dup2(so.fileno(), sys.stdout.fileno())
    os.dup2(se.fileno(), sys.stderr.fileno())

# Set custom file descriptors so that they get proper buffering.
    sys.stdout, sys.stderr = so, se
def getagentpid():
    try:
        pid = int(file('./toast.pid', 'r').read())
        logger.info("agent pid: " + str(pid))
    except Exception:
        pid = 0
    return pid

def update_agent():
# Check root purview
    if os.getuid() != 0:
        print "Permission denied.You need to be root to perform this command."
        sys.exit(1)

# Current time
    print time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(time.time())) + " [UPDATE] "

# Stop AgentDaemon
    os.system("/sbin/service toastdaemon stop")
    agentpid = getagentpid()
    if agentpid != 0:
        print "toast still running, kill it"
        killcmd = "kill -9 " + str(agentpid)
        print killcmd
        os.system(killcmd)

# Check yum status
    (status, output) = commands.getstatusoutput("yum -h")
    if status != 0:
        print "yum should be installed first."
        sys.exit(1)

# Check toast status
    (status, output) = commands.getstatusoutput("yum list installed | grep \"t-test-toast\"")
    if status != 0:
        status = os.system("yum install -y -b test t-test-toast")
        if status == 0:
            print "Toast Agent installation successfully."
        else:
            print "Toast Agent installation failed, please contact the administrator."
            sys.exit(1)
    else:
        os.system("yum update -y -b test t-test-toast")
# sleep 5 second for install complete and toastdaemon start    
    time.sleep(5)
# Start watch dog
    agentpid = getagentpid()
    if agentpid == 0:
        os.system("/sbin/service toastdaemon start")

if __name__ == '__main__':
    os.chdir("/home/a/bin/toastd/")
    isDaemon = False
    if len(sys.argv) >= 2:
        isDaemon = True
    if isDaemon:
        become_daemon()    
    
    update_agent()
