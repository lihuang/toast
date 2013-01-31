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
from util import *
import uuid

def fun_test_run():
	'''
	check out code & run test on host
	'''
	global running_data
	host 	= running_data.get('fun_test_box', '127.0.0.1')
	user 	= running_data['fun_test_user']
	pwd 	= running_data['fun_test_pwd']

	test_svn 	= running_data['fun_test_svn']
	test_cmd 	= running_data['fun_test_cmd']
	ft_path 	= running_data['ft_path']
	test_path 	= running_data['fun_test_path']
	test_path 	= test_path + str(uuid.uuid4())

	svn_usr 	= running_data['svn_username']
	svn_pwd 	= running_data['svn_pwd']

	if(host == '127.0.0.1'):
		#locally running functional testing
		util.check_out_code(ft_path, test_svn, svn_usr, svn_pwd)
		util.run_single_command_subprocess(test_cmd, ft_path)
	else:
		#remote check out code
		test_cmd = 'cd ' + test_path + ';' + test_cmd
		util.remote_check_out_code(host, user, pwd, test_path, test_svn, svn_usr, svn_pwd)

	util.ssh_run_command_return(test_cmd, host, user, pwd)	
