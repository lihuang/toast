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

'''
#TODO
1. define namespace
2. enrich modules
3. unit test for this project
'''

#system package 
import sys
import os
import ConfigParser
import shutil
import uuid
import logging
import getopt
import traceback

#custom package
import unit_test
import build
import fun_test
from util import *
from deploy import *
from fun_test import *
from toast_exception import ToastException

class ContinuouseIntegrationTest:
    """
    main logic method
    #TODO(gongzhi), factory generator design pattern
    """

    def __init__(self):
        """
        init method only.
        """
        self.options = {} 	#store all the input and output values
        self.config_file = ""   #specify configuration file

    def __get_value(self, dictionary, key):
        """
        @param dictionary:
        @param key:
        @return value from dictionary by using specific key:
        """
        return dictionary.get(key, 'default')

    def __set_up(self):
        self.__set_default_option()
        self.__get_options_cmd()
        self.__get_options_cfg()
        self.__check_options()
        self.__create_run_path()
        self.__store_running_data()

    def __check_options(self):
        '''
        check -s --stage is valid or not
        #TODO(gongzhi) valid options['stage'] is 'ubdf'
        '''
        if(self.options.has_key('stage') == False):
            raise ToastException("STAGE IS EMPTY")
        else:
            stage = self.options['stage']

    def __usage(self):
        print "Usage: ci_main.py command line help \n"
        print "-h, --help   print this help information"
        print "-c, --config     specify the config file"
        print "-r, --run_id     optional, specify the task run id, calc data url in file server"
        print "-s, --stage      u:unittest; b:build; d:deploy; f:function test; default is 'ubdb'\n"
        print"Examples of running command:"
        print"[./ci_main.py -c ci_main.conf -s ub]\n"

    def __set_default_option(self):
        #set stage
        if('stage' not in self.options):
            self.options['stage'] = 'ubdf'
            logger.debug("set default stage...")
        #set config file
        logger.debug("set default config file...")
        ci.config_file = os.path.splitext(os.path.abspath(__file__))[0] + '.conf'
        #set run id
        if('run_id' not in self.options):
            self.options['run_id'] = '0000'
            logger.debug("set default id to 0000")

    def __get_options_cmd(self):
    #get options from command lines if specified
        argv = sys.argv[1:]
        try:
            opts,args = getopt.getopt(argv,"hc:s:r:", ["help", "config=", "stage=", "run_id="])
            for opt, arg in opts:
                if opt in ("-h", "--help"):
                    self.__usage()
                    sys.exit()
                elif opt in ("-c", "--config"):
                    self.config_file = arg
                    logger.debug("config file:\t" + arg)
                elif opt in ("-s", "--stage"):
                    self.options['stage'] = arg
                elif opt in ("-r", "--run_id"):
                    self.options["run_id"] = arg
        except getopt.GetoptError:
            logger.error("failed to get option from command lines")
            self.__usage()
            sys.exit(2)

    def __get_cfg(self):
        logger.debug("get config file from remote...")
        if(self.config_file.find('.conf') == -1):
            raise ToastException('CONFIG FILE IS NOT END WITH .conf')
        #TODO(GONGZHI) replace wget system call by using urllib2
        cfg_file = '/tmp/ci.conf'
        get_cfg_cmd = '/usr/bin/wget ' + self.config_file + ' -d -O ' + cfg_file
        util.run_single_command_subprocess(get_cfg_cmd)

        self.config_file = cfg_file


    def __get_options_cfg(self):
        if(self.config_file.find('http://') == 0):
            self.__get_cfg()

        cfgParser = ConfigParser.ConfigParser()
        cfgParser.read(self.config_file)

        #get options from configuration file
        general_prefix 		= "general"
        unittest_prefix 	= "unit_test"
        build_prefix 		= "build"
        deploy_prefix 		= "deploy"
        fun_test_prefix 	= "fun_test"

        #for general
        self.options['svn_username'] 	 = cfgParser.get(general_prefix, 'svn_username')
        self.options['svn_pwd']         = cfgParser.get(general_prefix, 'svn_pwd')
        self.options['base_path']       = cfgParser.get(general_prefix, 'base_path')
        self.options['server_name']     = cfgParser.get(general_prefix, 'server_name')
        self.options['server_path']     = cfgParser.get(general_prefix, 'server_path')
        self.options['server_ssh_user'] = cfgParser.get(general_prefix, 'server_ssh_user')
        self.options['server_ssh_pwd']  = cfgParser.get(general_prefix, 'server_ssh_pwd')
        #for unittest
        self.options['ut_svn']          = cfgParser.get(unittest_prefix, 'ut_svn')
        self.options['ut_cmd']          = cfgParser.get(unittest_prefix, 'ut_cmd')
        self.options['ut_post_cmd']     = cfgParser.get(unittest_prefix, 'ut_post_cmd')
        #for build
        self.options['build_svn']           = cfgParser.get(build_prefix, 'build_svn')
        self.options['build_spec'] 	     = cfgParser.get(build_prefix, 'build_spec')
        self.options['build_cmd'] 	         = cfgParser.get(build_prefix, 'build_cmd')
        self.options['build_post_cmd'] 	  = cfgParser.get(build_prefix, 'build_post_cmd')
        #for deploy
        self.options['deploy_box'] 	= cfgParser.get(deploy_prefix, 'deploy_box')
        self.options['deploy_user'] 	= cfgParser.get(deploy_prefix, 'deploy_user')
        self.options['deploy_pwd'] 	= cfgParser.get(deploy_prefix, 'deploy_pwd')
        self.options['deploy_cmd'] 	= cfgParser.get(deploy_prefix, 'deploy_cmd')
        #for fun_test
        self.options['fun_test_box'] 	= cfgParser.get(fun_test_prefix, 'fun_test_box')
        self.options['fun_test_user'] 	= cfgParser.get(fun_test_prefix, 'fun_test_user')
        self.options['fun_test_pwd'] 	= cfgParser.get(fun_test_prefix, 'fun_test_pwd')
        self.options['fun_test_svn'] 	= cfgParser.get(fun_test_prefix, 'fun_test_svn')
        self.options['fun_test_cmd'] 	= cfgParser.get(fun_test_prefix, 'fun_test_cmd')
        self.options['fun_test_path'] 	= cfgParser.get(fun_test_prefix, 'fun_test_path')

    def __store_running_data(self):
        global running_data
        for key in self.options:
            if(running_data.has_key(key)):
                pass
            else:
                running_data[key] = self.options[key]

    def __create_run_path(self):
        '''
        create a uuid as the directory name under base path.
        and change base path to the new created uuid path.
        '''
        __base_path = self.options["base_path"]
        __base_path = __base_path + str(uuid.uuid4())
        __ut_path = __base_path + '/unittest/'
        __build_path = __base_path + '/build/'
        __ft_path = __base_path + '/functiontest/'
        __output_path = __base_path + '/output/'

        self.options["base_path"] 	= __base_path
        self.options["ut_path"] 	= __ut_path
        self.options["build_path"] 	= __build_path
        self.options["ft_path"] 	= __ft_path
        self.options["output_path"] 	= __output_path
        try:
            if(os.path.exists(__base_path)):
                shutil.rmtree(__base_path)

            os.makedirs(__base_path)
            os.makedirs(__ut_path)
            os.makedirs(__build_path)
            os.makedirs(__ft_path)
            os.makedirs(__output_path)
            logger.debug('successfully create path:\t' + __base_path)
            if(util.validate_exist(__base_path) == False):
                raise ToastException("base path doesn't exist at all")
        except Exception, e:
            logger.error("we meet an exception while rm/creating path!" + __base_path)
            logger.error(e)
            shutil.rmtree(__base_path)

    def __run_unit_test(self):
        #TODO(gongzhi), reduce self.options parameters
        ut = unit_test.UnitTest(self.options)
        ut.run()

    def __build_package(self):
        bp = build.Build(self.options)
        bp.run()

    def __deploy_package(self):
        deploy()

    def __run_functional_test(self):
        fun_test_run()

    def __run_perf_test(self):
        pass

    def __clean_up(self):
        logger.debug("start to clean up environment...")
        try:
            shutil.rmtree(self.options['base_path'])
        except Exception, e:
            logger.error("we meet an exception while remove tree" + self.options["base_path"])
            logger.error(e)

    def start(self):
        """
        main logic here
        """

        #setup env, get & check options
        self.__set_up()
        stage = self.options['stage']
        global running_data
        try:
            #run unittest
            if(stage.find('u') != -1):
                self.__run_unit_test()
            #build rpm package
            if(stage.find('b') != -1):
                self.__build_package()
                util.save_running_data(running_data)
            #deployment
            if(stage.find('d') != -1):
                util.get_running_data(running_data)
                self.__deploy_package()
            #functional test
            if(stage.find('f') != -1):
                self.__run_functional_test()
        except Exception, e:
            logger.debug("we are meeting exception, while running...")
            logger.debug(str(e))
            logger.debug(traceback.format_exc())
        finally:
            #clean env
            pass
            #self.__clean_up()

if __name__ == '__main__':
    file = os.path.abspath(__file__)
    util.init_logger(file)
    logger.debug("starting...")
    ci = ContinuouseIntegrationTest()
    logger.debug(sys.argv[1:])

    ci.start()
    logger.debug("run complete.\n\n")

