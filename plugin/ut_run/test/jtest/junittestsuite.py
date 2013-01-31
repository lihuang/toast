import json
class JunitSuiteReport(json.JSONEncoder):
    def __init__(self):
        self.testCases=[]
        self.numberOfErrors = 0
        self.numberOfFailures = 0
        self.numberOfSkipped = 0
        self.numberOfTests =0
        self.name          = ''
        self.fullClassName = ''
        self.packageName   = ''
        self.timeElapsed = 0.0
    
    def encode(self):
        d = {}
        d['name']=self.fullClassName
        li = []
        for case in self.testCases:
            li.append(case)
        d['testcase']=li
        return d
    def getTestCases(self):
        return self.testCases
    def getNumberOfErrors(self):
        return self.numberOfErrors
    def setNumberOfErrors(self, numberOfErrors):
        self.numberOfErrors = numberOfErrors
    def getNumberOfFailures(self):
        return self.numberOfFailures
    def setNumberOfFailures(self, numberOfFailures):
        self.numberOfFailures = numberOfFailures
    def getNumberOfSkipped(self):
        return self.numberOfSkipped
    def setNumberOfSkipped(self, numberOfSkipped):
        self.numberOfSkipped = numberOfSkipped
    def getNumberOfTests(self):
        if(self.numberOfTests != 0):
            return self.numberOfTests;
        if(len(self.testCases)):
            return len(self.testCases)
        return 0
    def setNumberOfTests(self, numberOfTests):
        self.numberOfTests = numberOfTests
    def getName(self):
        return self.name
    def setName(self, name):
        self.name = name
    def getFullClassName(self):
        return self.fullClassName
    def setFullClassName(self, fullClassName):
        self.fullClassName = fullClassName;
        lastDotPosition = fullClassName.rfind('.')
        name = fullClassName[lastDotPosition + 1:]
        if(lastDotPosition < 0):
            packageName = ""
        else:
            packageName = fullClassName[0:lastDotPosition]
    def getPackageName(self):
        return self.packageName
    def setPackageName(self, packageName):
        self.packageName = packageName
    def getTimeElapsed(self):
        return self.timeElapsed
    def setTimeElapsed(self, time):
        slef.timeElapsed = time
    def setTestCases(self, testCases):
        self.testCases = testCases
    def toString(self):
        return self.fullClassName
