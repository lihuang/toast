#!/usr/bin/env python2.6
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
import unittest
import string
import os
from util import *


class UtilTest(unittest.TestCase):
	def test_Example(self):
		sayhello = 'hello world'
		assert sayhello == 'hello world'

	def test_run_single_command(self):
		run_dir = '/tmp'
		run_cmd = 'pwd'
		res, out = util.run_single_command(run_cmd, run_dir)
		self.assertTrue(string.find(out, 'tmp'))
		self.assertTrue(res == 0)

	def test_run_single_command_subprocess(self):
		run_dir = '/tmp'
		run_cmd = 'pwd'
		out, err = util.run_single_command_subprocess(run_cmd, run_dir)
		self.assertTrue(string.find(out, 'tmp'))

	def test_scp_file(self):
		hostname = 'v132194.sqa.cm4.tbsite.net'
		username = 'gongzhi'
		password = 'Pwd_123'
		file = '/home/gongzhi/.bash_profile'
		util.scp_file(file, hostname, '/tmp', username, password)

		file = '/home/gongzhi/logs'
		util.scp_file(file, hostname, '/tmp', username, password)

	def test_ssh_run(self):
		hostname = 'v132194.sqa.cm4.tbsite.net'
		username = 'gongzhi'
		password = 'Pwd_123'
		command  = 'hostname'

		out, err = util.ssh_run_command(command, hostname, username, password)
		logger.debug('out is:#'+ out + '#')
		self.assertTrue(out.find("v132194") == 0)

	def test_ssh_sudo_run(self):
		hostname = 'v132194.sqa.cm4.tbsite.net'
		username = 'gongzhi'
		password = 'Pwd_123'
		command  = 'whoami'

		out, err = util.ssh_sudo_run_command(command, hostname, username, password)
		logger.debug('out is:#'+ out + '#')
		self.assertTrue(out.find('root') == 0)

	def test_ssh_run_return(self):
		hostname = 'v132193.sqa.cm4.tbsite.net'
		username = 'gongzhi'
		password = 'Pwd_123'
		command  = 'xxxx'
		out, err, return_code = util.ssh_run_command_return(command, hostname, username, password)
		self.assertTrue(len(err) > 0)
		self.assertTrue(return_code != 0)

		command  = 'cd /tmp; pwd'
		out, err, return_code = util.ssh_run_command_return(command, hostname, username, password)
		self.assertTrue(return_code == 0)
		self.assertTrue(len(err) == 0)

	def test_remote_co(self):
		tar_dir = '/tmp/pythontest/'
		svn_url = 'http://svn.simba.taobao.com/svn/QA/automation/trunk/rpm/'
		svn_usr = 'ads'
		svn_pwd = 'dsa543'
		host = 'v132194.sqa.cm4.tbsite.net'
		ssh_usr = 'gongzhi'
		ssh_pwd = 'Pwd_123'

		util.remote_check_out_code(host, ssh_usr, ssh_pwd, tar_dir, svn_url, svn_usr, svn_pwd)


def setUp():
	file = os.path.abspath(__file__)
	util.init_logger(file)

def tearDown():
	pass

if __name__ == "__main__":
	setUp()
	unittest.main()
	tearDown()
