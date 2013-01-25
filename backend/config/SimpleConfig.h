/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef	_SIMPLECONFIG_H_
#define	_SIMPLECONFIG_H_

#include "FileConfiguration.h"
#include "StringUtil.h"
#include "../sync/mutex.h"
#include "../sync/guard.h"
#include <stdlib.h>

class SimpleConfig : public FileConfiguration
{
	static SimpleConfig* configurationInstance;
	
public:
	enum
	{
		MAX = 0x7FFFFFFF
	};

	inline static SimpleConfig* Instance()
	{
		if(configurationInstance == NULL)
		{
			Guard<Mutex> guard(&m_lock);
			if(configurationInstance == NULL)
			{
				configurationInstance = new (std::nothrow)SimpleConfig();
			}
		}
		return configurationInstance;
	};

	static void reloadConfigFile(const std::string& configFileName)
	{
		Guard<Mutex> guard(&m_lock);
		//SetConfigFileName("dbn.conf");
                const char* name = configFileName.c_str();
		SetConfigFileName(name);
		SimpleConfig* tmp = new (std::nothrow)SimpleConfig();
		tmp->Init();
		
		SimpleConfig* old = configurationInstance;
		configurationInstance = tmp;

		delete old;
	}

	inline const char *GetStrVal(const char *section, const char *var) throw(YException)
	{
		return GetValue(section, var).c_str();
	}
	inline const char *GetStrVal(const char *var) throw(YException)
	{
		return GetValue(var).c_str();
	}

	inline int GetIntVal(const char *section, const char *var) throw(YException)
	{
		return atoi(GetValue(section, var).c_str());
	}
	inline int GetIntVal(const char *var, int low=0, int up=MAX) throw(YException)
	{
		int ret=atoi(GetValue(var).c_str());
		if(ret >= low && ret < up) return ret;
		throw YException("Out of range");
	}

	inline double GetDoubleVal(const char *section, const char *var) throw(YException)
	{
		return atof(GetValue(section, var).c_str());
	}
	inline double GetDoubleVal(const char *var, double low=0, double up=MAX) throw(YException)
	{
		double ret=atof(GetValue(var).c_str());
		if(ret >= low && ret < up) return ret;
		throw YException("Out of range");
	}

	inline const char *getStringValue(const char *var,
									  const char *defaultValue,
									  const char *section = NULL)
	{
		try
		{
			return StringUtil::Trim(section?GetValue(section,var):GetValue(var)).c_str();
		}
		catch(...) { }
		return defaultValue;
	}
	
	inline int getIntegerValue(const char *var,
							   int defaultValue,
							   int low = 0,
							   int up = MAX,
							   const char *section = NULL)
	{
		try
		{
			int ret=atoi(section?GetValue(section,var).c_str():GetValue(var).c_str());
			if(ret >= low && ret < up) return ret;
		}
		catch(...) { }
		return defaultValue;
	}
	
	inline double getDoubleValue(const char *var,
								  double defaultValue,
								  double low=0,
								  double up=MAX,
								  const char *section = NULL)
	{
		try
		{
			double ret=atof(section?GetValue(section,var).c_str():GetValue(var).c_str());
			if(ret >= low && ret < up) return ret;
		}
		catch(...) { }
		return defaultValue;
	}

protected:
	SimpleConfig()
	{
	}

public:
	static void setGlobalStringValue(const std::string& str)
	{
		m_globalStr = str;
	}

	static std::string getGlobalStringValue(void)
	{
		return m_globalStr;
	}
private:
	static std::string m_globalStr;

	static Mutex m_lock;
};

#endif
