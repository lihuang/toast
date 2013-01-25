/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef BILLLOG_HPP
#define BILLLOG_HPP

#include <string>
#include <log4cpp/Category.hh>
#include <log4cpp/CategoryStream.hh>
#include <log4cpp/Priority.hh>

#include "../sync/mutex.h"

class Log;
class LogStream;

typedef LogStream& (*__lmanip)(LogStream&);

extern LogStream& Endl(LogStream& ls);

class Log
{
public:
    // return 0 success otherwise error occur
    static int Init(const std::string& configFile);
    static int Init(const char* configFile);

    static void Debug(const std::string& msg);
    static void Info(const std::string& msg);
    static void Notice(const std::string& msg);
    static void Warn(const std::string& msg);
    static void Error(const std::string& msg);
    static void Crit(const std::string& msg);
    static void Fatal(const std::string& msg);

    static void Debug(const char* format, ...);
    static void Info(const char* format, ...);
    static void Notice(const char* format, ...);
    static void Warn(const char* format, ...);
    static void Error(const char* format, ...);
    static void Crit(const char* format, ...);
    static void Fatal(const char* format, ...);

    static void set_prog_name(const std::string& name);
    static void set_mod_name(const std::string& name);

    //static log4cpp::CategoryStream WarnStream(void);

    static LogStream debug;
    static LogStream info;
    static LogStream notice;
    static LogStream warn;
    static LogStream error;
    static LogStream crit;
    static LogStream fatal;

private:
    static log4cpp::Category* m_root;
    static std::string m_prog_name;
    static std::string m_mod_name;
    static std::string FormatMessage(const std::string& msg);

    static Mutex m_log_mutex;
};		


class LogStream
{
public:
    LogStream(log4cpp::Priority::Value logLevel,log4cpp::Category** root):m_log_level(logLevel),m_pRoot(root) {}
    void Flush();

    //LogStream& operator<<(const std::string s);
    LogStream& operator<<(const std::string& s);
    LogStream& operator<<(const char* s);
    LogStream& operator<<(char c);
    LogStream& operator<<(int n);
    LogStream& operator<<(long n);
    LogStream& operator<<(unsigned int n);
    LogStream& operator<<(unsigned long n);
    LogStream& operator<<(double n);
    LogStream& operator<<(float n) { return operator<<((double)n);}
    LogStream& operator<<(__lmanip func) { return (*func)(*this); }

    ~LogStream() {Flush();}
private:
    std::stringstream m_buf;
    log4cpp::Priority::Value m_log_level;
    log4cpp::Category** m_pRoot;

    Mutex m_mutex;
};
#endif
