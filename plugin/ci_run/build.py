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
import os
from util import *

#TODO(gongzhi) base class of stagebase, virtual ingnor method
class Build:
    def __init__(self, options):
        self.options = options
        self.__upload_server_name 	= self.options['server_name']
        self.__upload_server_path 	= self.options['server_path']
        self.__upload_ssh_user 		= self.options['server_ssh_user']
        self.__upload_ssh_pwd 		= self.options['server_ssh_pwd']
        self.__svn_username 		= self.options['svn_username']
        self.__svn_pwd 			= self.options['svn_pwd']
        self.__build_svn 		= self.options['build_svn']
        self.__build_cmd 		= self.options['build_cmd']
        self.__build_post_cmd 	= self.options['build_post_cmd']
        self.__build_path 		= self.options['build_path']
        self.__build_spec 		= self.options['build_spec']

    def run(self):
        '''
        main entrance of building
        '''
        rpms = self.__build_package()
        if(len(rpms) > 0):
            self.__upload_package(rpms)
            urls = []
            for rpm in rpms:
                file_name = os.path.basename(rpm)
                url = 'http://' + self.__upload_server_name + '/ci/' + file_name
                logger.debug("BUILD INFORMATION:\t" + url)
                print "BUILD INFORMATION:\t" + url
                urls.append(url)
            #set build urls
            global running_data
            running_data["build_urls"] = urls

        if (len(self.__build_post_cmd.strip()) >0):
            logger.debug("run build post command")
            util.run_single_command(self.__build_post_cmd)
    def __build_package(self):
        logger.debug("build package method")
        if(len(self.__build_svn.strip()) == 0):
            return []
        RPM_TOOL = '/usr/bin/t-abs'
        #RPM_TOOL = '/usr/local/bin/rpm_create'
        if(os.path.exists(RPM_TOOL) == False):
            #raise ToastException(RPM_TOOL + " DOES NOT EXIST, PLEASE INSTALL THIS TOOL FIRST")
            logger.error(RPM_TOOL + " DOES NOT EXIST, PLEASE INSTALL THIS TOOL FIRST")
            return []

        util.check_out_code(self.__build_path, self.__build_svn, self.__svn_username, self.__svn_pwd)
        util.run_single_command_subprocess(self.__build_cmd, self.__build_path)
        spec_full_name = os.path.join(self.__build_path, self.__build_spec)

        if(util.validate_exist(spec_full_name) == False):
            raise ToastException("build spec doesn't exist")

        spec_full_path 	= os.path.dirname(spec_full_name)
        spec_name 	= os.path.basename(spec_full_name)
        rpm_create_cmd 	= [RPM_TOOL, spec_name]
        util.run_single_command_subprocess(rpm_create_cmd, spec_full_path)

        return self.__find_package(spec_full_path)

    def __find_package(self, path):
        '''
        find rpm package in specified directory
        '''
        rpms = []
        RPM_POSTFIX = '.rpm'

        files = os.listdir(path)
        for file in files:
            if(file.endswith(RPM_POSTFIX)):
                rpm_file = os.path.join(path, file)
                rpms.append(rpm_file)

        if(len(rpms) < 1):
            raise Exception("NOT FIND RPMs IN BUILD DIRECOTRY!")
        return rpms

    def __upload_package(self, rpms):
        logger.debug("start to upload package ......")
        try:
            for rpm in rpms:
                util.scp_file(rpm, self.__upload_server_name, self.__upload_server_path, self.__upload_ssh_user, self.__upload_ssh_pwd)
        except Excption, e:
            logger.error(e)
            logger.error("Exception was thrown when upload rpm package")

