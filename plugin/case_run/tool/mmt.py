#!/usr/bin/python2.6
# Filename: tool.py
# -*- coding: utf-8 -*-

#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#

import _tool
import subprocess


class Mmt(_tool.Tool):
    def run(self):
        '''
        @summary: run mmt case.
        '''
        bin = self.get_conf("mmt", "bin")
        cmd = [bin, self.work_copy]
        print cmd
        try:
            p = subprocess.Popen(cmd, stdout=None,\
                stderr=subprocess.STDOUT, close_fds=True)
            stdout, stderr = p.communicate()
            flag = True
            if stderr:
                print stderr
                flag = False
            else:
                print "CASE ID: " + self.case_id + "\n"
            return flag
        except OSError, e:
            print e
            exit(1)

if __name__ == "__main__":
    mmt = Mmt("http://xxx.xxx.xxx", "1")
    mmt.get_conf("mmt", "bin")
    mmt.execute()
