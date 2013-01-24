
#
#   Copyright (C) 2007-2013 Alibaba Group Holding Limited
#
#   This program is free software;you can redistribute it and/or modify
#   it under the terms of the GUN General Public License version 2 as
#   published by the Free Software Foundation.
#
# replacedir
#   check the info_file that record the coverage info,if there exists 
#   some relative path then replace it by absolute path
import os
import string
import sys

def deal_file(filename):
    keys = d.keys()
    if not os.path.exists(filename):
        print 'Error: file -%s does not exitst.'%filename
        return False
    input = open(filename,'r')
    output = open("tmp.info",'a')
    try:
        lines= input.readlines()
        input.close()
        for line in lines:
            pm = string.find(line,'SF')
            if pm != -1:
                filepath = line[3:] #the abspath for the file
                filename =  os.path.basename(filepath)  #filename
                if filename.strip() in keys:
                    listdir = d[filename.strip()]
                    rFlag = 0
                    for dir in listdir:
                        if filepath.strip() == dir+'/'+filename:
                            rFlag = 1
                    if rFlag == 0:
                        new = listdir[0]+'/'+filename
                        line = line.replace(str(line[3:]),new)
            output.write(line)
    except:
        print "unknow error"
        output.close()
    return True

d = {}
def process_file(filename,dirname):
    if filename.endswith(".cpp") or filename.endswith(".c") or filename.endswith(".cc")or filename.endswith(".h"):
        d.setdefault(os.path.basename(filename),[]).append(dirname)
    
def visit_dir(dirname):
    for root, dirs, files in os.walk(dirname):
        for filespath in files:
            process_file(os.path.join(root, filespath), root)


