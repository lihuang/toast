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

#TODO(GOONGZHI), 1) get code coverage data. 2) upload to file server. 3) json test cases.
class UnitTest:
    def __init__(self, options):
        self.ut_path 		= options['ut_path']
        self.svn_username 	= options['svn_username']
        self.svn_pwd 		= options['svn_pwd']
        self.ut_svn 		= options['ut_svn']
        self.ut_cmd 		= options['ut_cmd']
        self.ut_post_cmd 	= options['ut_post_cmd']


    def run(self):
        logger.debug("start of UnitTest.run()")
        if(len(self.ut_svn.strip()) == 0):
            logger.info("svn url of is empty, will ignore unittest checkout and run cmd")
        else:
            self.__check_out_code(self.ut_path, self.ut_svn, self.svn_username, self.svn_pwd)
            self.__run_ut(self.ut_path, self.ut_cmd)

        if(len(self.ut_post_cmd.strip()) >0):
            self.__run_post_cmd(self.ut_post_cmd.strip(';'))
        else:
            logger.info("post command of unittest is empty.")
        logger.debug("end of UnitTest.run()")

    def __check_out_code(self, target_directory, svn_url, svn_username, svn_pwd):
        logger.debug("target_directory:\t" + target_directory +\
                 "\nsvn_url:\t" + svn_url +\
                 "\nsvn_username:\t" + svn_username)
        util.check_out_code(target_directory, svn_url, svn_username, svn_pwd)

    def __run_ut(self, run_directory, run_cmd):
        logger.debug("run_directory:\t" + run_directory +\
                 "\nrun_cmd:\t" + run_cmd)
        util.run_single_command_subprocess(run_cmd, run_directory)

    def __run_post_cmd(self, ut_post_cmd):
        util.run_single_command(ut_post_cmd)

if __name__ == '__main__':
    file = os.path.abspath(__file__)
    util.init_logger(file)
    logger.debug("start...")

