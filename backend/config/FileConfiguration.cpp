/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <cstring>
#include <errno.h>
#include <cstdio>
#include <cstdlib>
#include "FileConfiguration.h"
#include "YException.h"

#define myisspace(c) ( (c)>0 && isspace(c) )


string FileConfiguration::configFileName = "";

FileConfiguration::FileConfiguration()
{
    char error[1024];

    if (Initialize() == false)
    {
        sprintf(error, "Cannot open configuration file %s: %s",
                FileConfiguration::configFileName.c_str(),
                strerror(errno));
        throw YException(error);
    }
}

FileConfiguration::~FileConfiguration(void)
{
	nameValuePairs.clear();
	defaultValuePairs.clear();
}

bool FileConfiguration::Initialize()
{
	nameValuePairs.clear();
	FILE * fp = fopen(FileConfiguration::configFileName.c_str(),"r");
	if (fp == NULL)
	{
		lastRefreshTime = time(NULL);
		return false;
	}

	std::string session, name, value;
	std::string entryName;
	char Buf[2049];
	while (!feof(fp))
	{
		if (fgets(Buf,sizeof(Buf)-1, fp)==NULL) break;
		if (GetPairs(Buf,session, name, value))
		{
			entryName = session.c_str();
			entryName += name;
			nameValuePairs.insert(std::map<std::string,std::string>::value_type(entryName, value));
		}
	}
	fclose(fp);
	lastRefreshTime = time(NULL);
	return true;
}

bool FileConfiguration::GetPairs(const char * line, std::string& session, std::string& name, std::string& value)
{
	if (line == NULL || * line == 0)
		return false;

	const char * c = line, * b, *v ;

	for (c; myisspace(*c); c++)

	if (*c == 0 || *c == '#' || *c == ';') // empty line or comment
		return false;

	if (*c == '[')
	{ // Session
		while (myisspace(*c)) c++;
		for (b = c+1; *b && *b != ']'; b++);
		if (*b==0) // Cannot find a ']'
		{
			return false;
		}
		session.assign(c, b-c+1);
		// no pair found.
		return false;
	}
	b = strchr(c+1, '=');

	if (b == NULL)
	{
		return false;
	}

	v = b+1;
	while (myisspace(*v))
		v++;

	while (b>c && *(b-1)>0 && myisspace(*(b-1))) b--;

	name.assign(c, b-c);

	b = strchr(v, '\r');
	if (!b) b = strchr(v, '\n');
	if (!b) b = v+strlen(v);

	value.assign(v, b-v);
	return true;
}


void FileConfiguration::SetDefaultValue(const char *session, const char *parameter, const char *value)
{
	string entry;
	if (*session!='[')
		entry = "[";
	entry += session;
	if (*session!='[')
		entry += "]";
	entry += parameter;
	defaultValuePairs.insert(std::map<std::string,std::string>::value_type(entry, value));
	#ifdef WIN32	
	SetConfigFileValue(parameter, value);
	#endif
}
int FileConfiguration::SetConfigFileValue(const char *parameter, const char *value)
{
       int find_flag = 0;
	FILE * fp = fopen(FileConfiguration::configFileName.c_str(),"rb+");
	if (fp == NULL)
	{
		lastRefreshTime = time(NULL);
		return false;
	}
      long before_param;
      long after_param;
      long file_size;
      fseek (fp, 0, SEEK_END);
      file_size=ftell (fp);
      rewind(fp);
	char Buf[2048];
	while (!feof(fp))
	{
	      before_param = ftell(fp);
		if (fgets(Buf,sizeof(Buf)-1, fp)==NULL) break;
		if (strstr(Buf, parameter) == Buf) // find the specific parameter
		{
		       after_param = ftell(fp);
               find_flag = 1;
		       break;
		}
		else
		{
			 continue;

		}
	}
	// 
	if(find_flag)  // no this parameter, add it to the file end
	{
        strcpy(Buf, parameter);
	      strcat(Buf, "=");
	      strcat(Buf, value);
          strcat(Buf, "\n");
	      char *tmp_buf = (char*)malloc(file_size - after_param);
	      if(tmp_buf)
	      	{
	             fread(tmp_buf, 1, file_size - after_param, fp);        // read 2048 byte after the param
	             fseek(fp, before_param, SEEK_SET);
	             fputs(Buf,fp);
	             fwrite(tmp_buf, 1, file_size-after_param, fp);
                 free(tmp_buf);
	      	}
	}
	fclose(fp);

	return find_flag;

}
void FileConfiguration::SetDefaultValue(const char *parameter, const char *value)
{
	SetDefaultValue("",parameter,value);
}

string& FileConfiguration::GetValue(const char *session, const char *parameter)
{
	string entry;
	if (*session!='[')
		entry = "[";
	entry += session;
	if (*session!='[')
		entry += "]";
	entry += parameter;

	std::map<std::string,std::string>::iterator iter = nameValuePairs.find(entry);
	if (iter != nameValuePairs.end())
		return nameValuePairs[entry];

	else
	{
		iter = defaultValuePairs.find(entry);
		if (iter != defaultValuePairs.end())
			return defaultValuePairs[entry];
	}
	string errorMsg = entry;
	errorMsg += " is missed in configuration file.";
	throw YException(errorMsg.c_str());
}

string& FileConfiguration::GetValue(const char * parameter)
{
	return GetValue("",parameter);
}
