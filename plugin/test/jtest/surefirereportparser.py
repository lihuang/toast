from junittestsuiteparser import JunitTestSuiteXmlParser
import os
import json
from junittestsuite import JunitSuiteReport
from junittestcase import JunitTestCase
from StringIO import StringIO
class JunitSuiteEncoder(json.JSONEncoder):
    def default(self, obj):
        if isinstance(obj, JunitSuiteReport):
            return obj.encode()
        elif isinstance(obj, JunitTestCase):
            return obj.encode() 
        return json.JSONEncoder.default(self, obj)

class SurefireReportParser:
    def __init__(self, report_directories):
        self.report_dirs = report_directories
        self.testSuites=[]
    def parseXMLReportFiles(self):
        for dir in self.report_dirs:
            if(os.path.isdir(dir)):
                for file in os.listdir(dir):
                    abspath = os.path.join(dir, file)
                    if(os.path.isfile(abspath)):
                        if(abspath.endswith(".xml")):
                            p = JunitTestSuiteXmlParser()
                            suitesInFile = p.parser(abspath)
                            self.testSuites = self.testSuites + suitesInFile
    def parseResult(self):
        d = {}
        d['testsuite'] = self.testSuites
        print "##TESTCASE START##"
        print JunitSuiteEncoder().encode(d)
        print "##TESTCASE END##"
