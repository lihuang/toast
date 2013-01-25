from xml.sax.handler import ContentHandler
import xml.sax
import junittestsuite
import junittestcase
class JunitTestSuiteXmlParser(ContentHandler):
    def __init__(self):
        self.defaultSuite = junittestsuite.JunitSuiteReport()
        self.currentSuite = junittestsuite.JunitSuiteReport()
        self.classesToSuites = {}
        self.currentElement = ''
        self.testCase = junittestcase.JunitTestCase()
    def startElement(self, name, attr):
        if(name == "testsuite"):
            self.currentSuite = self.defaultSuite = junittestsuite.JunitSuiteReport()
            fullClassName = attr.getValue("name")
            if(attr.has_key("group") and (attr.getValue("group") != "")):
                packageName = attr.getValue("group")
                tmpName = attr.getValue("name")
                self.defaultSuite.setFullClassName(packageName + "." + tmpName)
            else:
                fullClassName = attr.getValue("name")
                self.defaultSuite.setFullClassName(fullClassName)
            self.classesToSuites[self.defaultSuite.getFullClassName()] = self.defaultSuite
        elif(name == "testcase"):
            self.currentElement = ""
            self.testCase = junittestcase.JunitTestCase()
            self.testCase.setName(attr.getValue("name"))
            fullClassName = attr.getValue("classname");
            if(fullClassName != ""):
                if(self.classesToSuites.has_key(fullClassName)):
                    self.currentSuite = self.classesToSuites[fullClassName]
                else:
                    self.currentSuite = None
                if(self.currentSuite == None):
                    self.currentSuite = junittestsuite.JunitSuiteReport()
                    self.currentSuite.setFullClassName(fullClassName)
                    self.classesToSuites[fullClassName] = self.currentSuite
            self.testCase.setFullClassName(self.currentSuite.getFullClassName())
            self.testCase.setClassName(self.currentSuite.getName())
            self.testCase.setFullName(self.currentSuite.getFullClassName() + "." + self.testCase.getName())
        elif(name == "failure"):
            self.testCase.setResult("FAIL")
            if(attr.has_key("message") and attr.has_key("type")):
                self.testCase.setFailure(attr.getValue("message"), attr.getValue("type"))
            self.currentSuite.setNumberOfFailures(1 + self.currentSuite.getNumberOfFailures())
        elif(name == "error"):
            self.testCase.setResult("FAIL")
            if(attr.has_key("message") and attr.has_key("type")):
                self.testCase.setFailure(attr.getValue("message"), attr.getValue("type"))
            self.currentSuite.setNumberOfErrors(1 + self.currentSuite.getNumberOfErrors())
        elif(name == "skipped"):
            self.testCase.setResult("SKIP")
            if(attr.has_key("message") and attr.has_key("type")):
                self.testCase.setFailure(attr.getValue("message"), attr.getValue("type"))
            self.currentSuite.setNumberOfSkipped(1+self.currentSuite.getNumberOfSkipped())
    
    def endElement(self, qName):
        if(qName == "testcase"):
            self.currentSuite.getTestCases().append(self.testCase)
        elif(qName == "failure"):
            self.testCase.setDetail(self.currentElement)
        elif(qName == "error"):
            self.testCase.setDetail(self.currentElement)
    
    def characters(self, content):
        self.currentElement += content.encode("UTF-8")
    def parser(self, xmlFile):
        parser = xml.sax.make_parser()
        #handler = JunitTestSuiteXmlParser();
        parser.setContentHandler(self)
        parser.parse(xmlFile)
        if(self.currentSuite != self.defaultSuite):
            if(self.defaultSuite.getNumberOfTests() == 0 and self.defaultSuite.getFullClassName() != ''):
                del self.classesToSuites[self.defaultSuite.getFullClassName()]
        return self.classesToSuites.values()
