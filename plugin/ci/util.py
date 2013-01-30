#!/usr/bin/env python2.6
# -*- coding: utf-8 -*-
#be used under Python2.6
#
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#

import logging
import os
import sys
import subprocess
import commands

#scp & ssh

from toast_exception import ToastException
import paramiko
from paramiko import SSHException
from contextlib import closing
from scpclient import *
import simplejson as json


#glabal varibles
#TODO(gongzhi), singleton pattern
logger = logging.getLogger()
running_data = {}

#noinspection PyUnreachableCode,PyUnreachableCode,PyUnreachableCode,PyUnreachableCode,PyUnreachableCode
class util:
    @staticmethod
    def init_logger(file, level=logging.DEBUG):
        """
        init global varible logger,
        @param file: log file name
        @param level: #DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'
        """
        file_handler = logging.FileHandler(file + ".log")
        formatter = logging.Formatter("%(asctime)s %(filename)s(%(lineno)s): %(levelname)-5s %(message)s", "%x %X")
        file_handler.setFormatter(formatter)
        logger.addHandler(file_handler)
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        logger.addHandler(console_handler)
        logger.setLevel(level)

    @staticmethod
    def scp_file(file, hostname, server_path, ssh_user, ssh_pwd):
        port = 22
        ssh_client = paramiko.SSHClient()
        ssh_client.load_system_host_keys()
        ssh_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh_client.connect(hostname, port, ssh_user, ssh_pwd)

        logger.debug("start to scp file:\t" + file)

        if(os.path.isfile(file)):
            with closing(Write(ssh_client.get_transport(), server_path)) as scp:
                scp.send_file(file, True)
        elif(os.path.isdir(file)):
            with closing(WriteDir(ssh_client.get_transport(), server_path)) as scp:
                scp.send_dir(file, True)
        else:
            raise ToastException("SCP ERROR.")

    @staticmethod
    def check_out_code(target_directory, svn_url, svn_username, svn_pwd):
        logger.debug("util.check_out_code")
        cmd = ['/usr/bin/svn', 'co', svn_url, target_directory, '--username', svn_username, \
            '--password', svn_pwd, '--no-auth-cache', '--non-interactive']
        return util.run_single_command_subprocess(cmd, target_directory)

    @staticmethod
    def remote_check_out_code(host, ssh_user, ssh_pwd, \
                  target_directory, svn_url, svn_username, svn_pwd):

        logger.debug("util.check_out_code")
        spc = ' '
        cmd = '/usr/bin/svn' + spc + 'co' + spc + svn_url + spc + target_directory + spc + '--username' + spc + svn_username + spc +  \
            '--password' + spc +  svn_pwd + spc + '--no-auth-cache' + spc + '--non-interactive'
        return util.ssh_run_command_return(cmd, host, ssh_user, ssh_pwd)

    @staticmethod
    def run_single_command_subprocess(run_cmd, run_directory='/tmp'):
        '''
        return stdout and stderr
        TODO(GONGZHI), return value
        '''
        if(len(run_cmd) == 0):
            logger.error('RUN_CMD IS EMPTY, WILL NOT RUN ANYTHING')
            return('', '')
        logger.debug("run directory:\t" + run_directory)
        if(isinstance(run_cmd, list) == False):
            logger.debug("run command:\t" + run_cmd)
        else:
            for num in range(len(run_cmd)):
                logger.debug("run cmd:\t" + run_cmd[num])
        os.chdir(run_directory)
        try:
            if(isinstance(run_cmd, list)):
                p = subprocess.Popen(run_cmd, stderr=subprocess.PIPE, stdout=subprocess.PIPE, close_fds=True)
            else:
                p = subprocess.Popen(run_cmd, shell=True, stderr=subprocess.PIPE, stdout=subprocess.PIPE, close_fds=True)
            stdout, stderr = p.communicate()
        except Exception,e:
                logger.error(e)
                logger.error("Exception was thrown when run single.")
        logger.debug("stdout:\t" + stdout)
        logger.debug("stderr:\t" + stderr)
        return (stdout, stderr)

    @staticmethod
    def run_single_command(run_cmd, run_directory='/tmp'):
        if(len(run_cmd.strip()) == 0):
            logger.error('RUN_CMD IS EMPTY, WILL NOT RUN ANYTHING')
            #raise ToastException('RUN_CMD IS EMPTY, WILL NOT RUN ANYTHING')
            return(-1, '')
        logger.debug("util.run_single_command")
        logger.debug("run directory:\t" + run_directory)
        logger.debug("run command:\t" + run_cmd)

        if(not os.path.exists(run_directory)):
            raise IOError(run_directory + "doesn't exist") #raise RuntimeError("")
        try:
            os.chdir(run_directory)
            res, output = commands.getstatusoutput(run_cmd)
            logger.debug("return value is:\t" + str(res))
            logger.debug("running output is:\t" + output)
            #res = os.system(run_cmd)
        except Exception, e:
            print e
            #logger.error(str(e))
            logger.error("Exception was thrown when run single cmd:\t" + run_cmd)
        return (res, output)

    @staticmethod
    def validate_exist(path):
        if(not os.path.exists(path)):
            logger.error("base_path doesn't exist, program will exit")
            return False
        #raise IOError("doesn't exist")
        return True

    #noinspection PyUnreachableCode
    @staticmethod
    def ssh_run_command(cmd, host, user, pwd):
        """
        @param cmd:
        @param host:
        @param user:
        @param pwd:
        @return:
        """
        logger.debug('start to run cmd:\t' + cmd + '\t on machine:\t' + host)
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh_stderr = ''
        ssh_stdout = ''
        try:
            ssh.connect(host, 22, user, pwd)
            stdin, stdout, stderr = ssh.exec_command(cmd)
            ssh_stderr = stderr.read()
            ssh_stdout = stdout.read()
        except SSHException, e:
            logger.error(e)
            logger.error("Exception was thrown when run ssh cmd:'" + cmd + "'")
        logger.debug('ssh_stderr:\t' + ssh_stderr)
        logger.debug('ssh_stdout:\t' + ssh_stdout)
        return (ssh_stdout, ssh_stderr)

    @staticmethod
    def ssh_sudo_run_command(cmd, host, user = None, pwd = None):
        user 	= 'root'
        pwd 	= 'tjj2tds'
        return util.ssh_run_command(cmd, host, user, pwd)

    @staticmethod
    def ssh_run_command_return(cmd, host, user, pwd):
        logger.debug('start to run cmd:\t' + cmd + '\t on machine:\t' + host)
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
	ssh_stderr = ''
	ssh_stdout = ''
	return_code= 255
        try:
            ssh.connect(host, 22, user, pwd)
            chan = ssh.get_transport().open_session()
            chan.exec_command(cmd)
            return_code = chan.recv_exit_status()
            logger.debug("exit status:" + str(return_code))

            bufsize= -1
            stdin  = chan.makefile('wb', bufsize)
            stdout = chan.makefile('rb', bufsize)
            stderr = chan.makefile_stderr('rb', bufsize)
            ssh.close()

            ssh_stderr = stderr.read()
            ssh_stdout = stdout.read()
        except SSHException, e:
            logger.error(e)
            logger.error("Exception was thrown when run ssh cmd:'" + cmd + "'")
        logger.debug('ssh_stderr:\t' + ssh_stderr)
        logger.debug('ssh_stdout:\t' + ssh_stdout)
        return(ssh_stdout, ssh_stderr, return_code)

    @staticmethod
    def ssh_sudo_run_command_return(cmd, host, user = None, pwd = None):
        user 	= 'root'
        pwd 	= 'tjj2tds'
        return util.ssh_run_command_return(cmd, host, user, pwd)

    @staticmethod
    def get_running_data(options):
        #TODO(GONGZHI), using scp client library, not wget system call
        host = options["server_name"]
        path = options['server_path']
        user = options['server_ssh_user']
        pwd  = options['server_ssh_pwd']
        run_id = options["run_id"]
        json_url = 'http://%s/ci/%s' % (host, (run_id + '.json'))
        json_file = options.get('base_path', '/tmp/') + '/' + run_id + '.new.json'

        get_cfg_cmd = '/usr/bin/wget ' + json_url + ' -d -O ' + json_file
        res, out = util.run_single_command(get_cfg_cmd)
        if(res != 0):
            raise ToastException(get_cfg_cmd + " RUNNING FAILED")
        logger.debug("wget json file from remote share server")
        logger.debug("json url:\t" + json_url)
        logger.debug("json file:\t" + json_file)

        fp = open(json_file, 'r+')
        new_options = json.load(fp)
        global running_data
        running_data = new_options

    @staticmethod
    def save_running_data(options):
        """
        locally/remotely save running data to json file
        @param options: running_data, SHOULD be a dict
        @raise: ToastException
        """
        if(isinstance(options, dict) == False):
            raise ToastException("OPTIONS IS NOT DICTIONARY TYPE.")

        host = options["server_name"]
        path = options['server_path']
        user = options['server_ssh_user']
        pwd  = options['server_ssh_pwd']
        run_id = options["run_id"]
        json_file = options.get('base_path', '/tmp/') + '/' + run_id + '.json'
        json_url = 'http://%s/ci/%s' % (host, os.path.basename(json_file))
        options['json_url'] = json_url

        fp = open(json_file, 'w+')
        json.dump(options, fp, indent=4)
        fp.close()

        util.scp_file(json_file, host, path, user, pwd)
        logger.debug("send json file to remote share server")
        logger.debug("json url:\t" + json_url)



