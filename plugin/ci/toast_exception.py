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
class ToastException(Exception):
    def __init__(self, value):
        self.value = value

    def __str__(self):
        return repr(self.value)
