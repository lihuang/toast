/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef _FILE_CONFIGURATION_H_
#define _FILE_CONFIGURATION_H_

#ifdef _WIN32
#pragma once
#endif

#include <map>

#include "BaseConfiguration.h"
#include "YException.h"


class FileConfiguration : public BaseConfiguration
{
public:

	// Singleton class.
	inline static void SetConfigFileName(const char * fileName)
	{
		configFileName = fileName;
	};

	inline static string& GetConfigFileName()
	{
		return configFileName;
	};

public:
	virtual ~FileConfiguration(void);

	// set the default values for a parameter.  
	virtual void SetDefaultValue(const char * session, const char * parameter, const char * value);
	virtual void SetDefaultValue(const char * parameter, const char * value);

	// If a parameter's default value is not set and it cannot be found,
	// an YException will be thrown.
	virtual string& GetValue(const char * session, const char * parameter);
	virtual string& GetValue(const char * parameter);

protected:
	FileConfiguration();

	virtual bool Initialize();

private:
	static string configFileName;
	
	// map["[session]Parameter"] = value;
	std::map<std::string, std::string> nameValuePairs;
	std::map<std::string, std::string> defaultValuePairs;
       int SetConfigFileValue(const char *parameter, const char *value);
	bool GetPairs(const char * line, std::string& session, std::string& name, std::string& value);
};

#endif
