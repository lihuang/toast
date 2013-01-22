## Introduction

TOAST is short for "Toast Open Automation System for Test". 

General speaking, it's a tool to run your automation test case/code in a specific test box and then show the test result back to you via Email notification or from web UI test result page.


## Basic Design  

About automation test case, actually if a test case was automated, a corresponding test code (usually based on a specific programming test framework, such as Xunit, Selenium or etc.) having been written already, from running aspect we look test code (usually a test method in souce code) as test case. Test code is built to excutable binary and it will run on a test box against deployed test environment. Since test code is written based on a framework, the output is formatted. Once toast get the output of test code, after parsing output, we can know how many case passed or failed.


## Setup 

TOAST compose of three parts:
  * Front End, including Web UI and DB. (Linux)
  * Back End, or Controller. (Linux)
  * Test Box, we call it as "test agent" or "agent". (Linux or Windows)

for each part installation, see [install link](http://github.com/taobao/toast/install).


## Usage

Here is the whole process,
  * write a script(shell, batch file etc) which can drive your test code running. 
  * keep the scirpt in a specific test box.
  * add this test box into toast machine pool.
  * in toast web UI, create a test job and input required information, such as: crontab run time, test machine, test stage, test command in each test stage, test case parser for each test command and so on.
  * manaully run this job, or by crontab.
  * get test result return from remote test box and parse case information.
  * test report will show how many cases have been passed or failed.


## Bug tracker

Have a bug or a feature request? [Please open a new issue](https://github.com/taobao/toast/issues). Before opening any issue, please search for existing issues and read the [Issue Guidelines](https://github.com/taobao/toast/issue-guidelines).


## Difference with STAFF OR Hudson Jenkins

to be continued.

