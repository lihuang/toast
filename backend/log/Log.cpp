/*
* Copyright (C) 2007-2013 Alibaba Group Holding Limited
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License version 2 as
* published by the Free Software Foundation.
*/

#include <log4cpp/PropertyConfigurator.hh>
#include <iostream>
#ifndef WIN32
#include <unistd.h>
#include <stdlib.h>
#else
#include <process.h>
#include <Windows.h>
#endif
#include <stdio.h>

#include "Log.h"
#include "../sync/guard.h"
#include "../sync/mutex.h"
using namespace log4cpp;
using namespace std;

Category* Log::m_root = NULL;
string Log::m_prog_name;
string Log::m_mod_name;

LogStream Log::debug(Priority::DEBUG,&(Log::m_root));
LogStream Log::info(Priority::INFO,&(Log::m_root));
LogStream Log::notice(Priority::NOTICE,&(Log::m_root));
LogStream Log::warn(Priority::WARN,&(Log::m_root));
LogStream Log::error(Priority::ERROR,&(Log::m_root));
LogStream Log::crit(Priority::CRIT,&(Log::m_root));
LogStream Log::fatal(Priority::FATAL,&(Log::m_root));

Mutex Log::m_log_mutex;

string Log::FormatMessage(const string& msg)
{
	stringstream tmp;
	tmp << "(" <<
		Log::m_prog_name << "," <<
		Log::m_mod_name << "," << getpid() << ",0)" << msg;
	return tmp.str();
}

int Log::Init(const std::string& configFile)
{
	try
	{
		PropertyConfigurator::configure(configFile);
	}
	catch(ConfigureFailure& f)
	{
#ifdef WIN32
        MessageBox(NULL, f.what(), "Log4Cpp Configure Problem!", MB_OK);
#else
		cout << "Log4Cpp Configure Problem !" << f.what() << "\n";
#endif		
        return 1;
	}
	Log::m_root=&(Category::getRoot());
    return 0;
}	

int Log::Init(const char* configFile)
{
	try
	{
		string confFileName = configFile;
		PropertyConfigurator::configure(confFileName);
	}
	catch(ConfigureFailure& f)
	{
#ifdef WIN32
        MessageBox(NULL, f.what(), "Log4Cpp Configure Problem!", MB_OK);
#else
		cout << "Log4Cpp Configure Problem !" << f.what() << "\n";
#endif
		return 1;
	}
	Log::m_root=&(Category::getRoot());
    return 0;
}

void Log::Warn(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::WARN) )
	{
		Guard<Mutex> guard(&m_log_mutex);
		Log::m_root->warn(FormatMessage(msg));
	}
}

void Log::Error(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::ERROR) )
	{
		Guard<Mutex> guard(&m_log_mutex);	
		Log::m_root->error(FormatMessage(msg));
	}
}

void Log::Info(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::INFO) )
	{
		Guard<Mutex> guard(&m_log_mutex);	
		Log::m_root->info(FormatMessage(msg));
	}
}

void Log::Notice(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::NOTICE) )
	{
		Guard<Mutex> guard(&m_log_mutex);	
		Log::m_root->notice(FormatMessage(msg));
	}
}

void Log::Debug(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::DEBUG) )
	{
		Guard<Mutex> guard(&m_log_mutex);	
		Log::m_root->debug(FormatMessage(msg));
	}
}

void Log::Crit(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::CRIT) )
	{
		Guard<Mutex> guard(&m_log_mutex);	
		Log::m_root->crit(FormatMessage(msg));
	}
}

void Log::Fatal(const string& msg)
{
	if( m_root->isPriorityEnabled(Priority::FATAL) )
	{
		Guard<Mutex> guard(&m_log_mutex);	
		Log::m_root->fatal(FormatMessage(msg));
	}
}

void Log::set_prog_name(const std::string& name)
{
	Log::m_prog_name=name;
}

void Log::set_mod_name(const std::string& name)
{
	Log::m_mod_name=name;
	Log::m_root = &(Category::getInstance(name));
}

void Log::Warn(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::WARN) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::WARN,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}

void Log::Error(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::ERROR) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::ERROR,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}

void Log::Info(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::INFO) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::INFO,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}

void Log::Notice(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::NOTICE) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::NOTICE,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}
void Log::Debug(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::DEBUG) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::DEBUG,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}
void Log::Crit(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::CRIT) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::CRIT,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}

void Log::Fatal(const char* format, ...)
{
	if( m_root->isPriorityEnabled(Priority::FATAL) )
	{
		Guard<Mutex> guard(&m_log_mutex);		
		va_list ap;
		va_start(ap,format);
		m_root->logva(Priority::FATAL,FormatMessage(format).c_str(),ap);
		va_end(ap);
	}
}

/*
LogStream& LogStream::operator<<(const string s)
{
        m_buf += s;
        return (*this);
}
*/
LogStream& LogStream::operator<<(const string& s)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);
		m_buf << s;
	}
	return (*this);
}

LogStream& LogStream::operator<<(const char* s)
{
	if( s != NULL && (*m_pRoot)->isPriorityEnabled(m_log_level) ) 
	{
		Guard<Mutex> guard(&m_mutex);		
		m_buf << s;
	}
	return (*this);
}

LogStream& LogStream::operator<<(char c)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);		
		m_buf << c;
	}
	return (*this);
}

LogStream& LogStream::operator<<(int n)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);		
		m_buf << n;
	}
	return (*this);
}

LogStream& LogStream::operator<<(long n)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);		
		m_buf << n;
	}
	return (*this);
}

LogStream& LogStream::operator<<(unsigned int n)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);
		m_buf << n;
	}
	return (*this);
}

LogStream& LogStream::operator<<(unsigned long n)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);		
		m_buf << n;
	}
	return (*this);
}

LogStream& LogStream::operator<<(double n)
{
	if( (*m_pRoot)->isPriorityEnabled(m_log_level) )
	{
		Guard<Mutex> guard(&m_mutex);		
		m_buf << n;
	}
	return (*this);
}

void LogStream::Flush()
{
	if( m_buf.str() == "" )
	{
		return;
	}

	Guard<Mutex> guard(&m_mutex);
	
	switch( m_log_level )
	{
	case Priority::FATAL :
		Log::Fatal(m_buf.str());
		break;
	case Priority::CRIT :
		Log::Crit(m_buf.str());
		break;
	case Priority::ERROR :
		Log::Error(m_buf.str());
		break;
	case Priority::WARN :
		Log::Warn(m_buf.str());
		break;
	case Priority::NOTICE :
		Log::Notice(m_buf.str());
		break;
	case Priority::INFO :
		Log::Info(m_buf.str());
		break;
	case Priority::DEBUG :
		Log::Debug(m_buf.str());
		break;
	default:;
	}
	m_buf.str("");
}

LogStream& Endl(LogStream& ls)
{
	ls.Flush();
	return ls;
}
