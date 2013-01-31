import json
class JunitTestCase:
    def __init__(self):
        self.fullClassName = ""
        self.className = ""
        self.fullName = ""
        self.name     = ""
        self.time     = 0.0
        self.failure  = {}
        self.detail = ""
        self.result = "PASS"
    def encode(self):
        d = {}
        d['name'] = self.name
        d['result'] = self.result
        d['info'] = self.detail
        return d
    def getName(self):
        return self.name
    def setName(self, name):
        self.name = name
    def getFullClassName(self):
        return self.fullClassName
    def setFullClassName(self, name):
        self.fullClassName = name
    def getClassName(self):
        return self.className
    def getFullName(self):
        return self.fullName
    def setFullName(self, fullName):
        self.fullName = fullName
    def setClassName(self, name):
        self.className = name
    def getTime(self):
        return self.time
    def setTime(self, time):
        self.time = time
    def getFailure(self):
        return self.failure
    def setFailure(self, message, type):
        self.failure["message"] = message
        self.failure["type"] = type
    def getDetail(self):
        return self.detail
    def setDetail(self, detail):
        self.detail = detail
    def getResult(self):
        return self.result
    def setResult(self, result):
        self.result = result
    def toString(self):
        return self.fullName
