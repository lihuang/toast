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


def deploy():
    logger.debug("deploy start...")

    global running_data
    host = running_data.get('deploy_box', '127.0.0.1')
    user = running_data['deploy_user']
    pwd = running_data['deploy_pwd']
    post_cmd = running_data['deploy_cmd']
    urls = running_data.get('build_urls', 'NULL')

    if(urls != 'NULL'):
        install_cmds = []
        for url in urls:
            install_cmd = 'rpm -ivh ' + url
            install_cmds.append(install_cmd)
        #install rpms
        for cmd in install_cmds:
            if(host == '127.0.0.1'):
                util.run_single_command_subprocess(cmd)
            else:
                stdout, stderr, return_code = util.ssh_sudo_run_command_return(cmd, host)
                #if(return_code != 0):
                #raise Exception("INSTALL ERROR!")
    else:
        logger.debug("build url is null, will NOT install anything.")
    #post actions
    if(host == '127.0.0.1'):
        #TODO(gongzhi), adding running directory
        util.run_single_command_subprocess(post_cmd)
    else:
        stdout, stderr, return_code = util.ssh_run_command_return(post_cmd, host, user, pwd)
        if(return_code != 0):
            logger.error("post action running failed!")
