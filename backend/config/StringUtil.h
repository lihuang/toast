/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef _STRINGUTIL_H
#define _STRINGUTIL_H
//#include <ycrypto/ycr/yCrypto.h>
#include <time.h>
#include <sys/types.h>
#include <string>
#include <sstream>
#include <iomanip>
#include <vector>
#include <string.h>
#include <assert.h>
#include <stdlib.h>
class StringUtil
{
public:
	inline static bool IsGBK1(unsigned char c)
	{
		return(c>0x81 - 1)&&(c<0xFD + 1);
	}

	inline static bool IsGBK2(unsigned char c)
	{
		return(c>0x40 - 1) && (c<0xFE + 1) && (c!=0x7F);
	}

	inline static void SafeToupper(char * p)
	{
		if (p == NULL || *p ==0)
			return;

		while (*p)
		{
			if (IsGBK1(*p) && IsGBK2(*(p+1)))
			{
				p+=2;
			}
			else
			{
				*p = toupper(*p);
				p++;
			}
		}
	}

	inline static void StringToupper(std::string& str)
	{
		if (str.empty())
			return;

		for (size_t i=0; i<str.size();)
		{
			if (IsGBK1(str[i]) && IsGBK2(str[i+1]))
			{
				i+=2;
			}
			else
			{
				str[i] = toupper(str[i]);
				i++;
			}
		}
	}
	inline static void StringTolower(std::string& str)
	{
		if (str.empty())
			return;

		for (size_t i=0; i<str.size();)
		{
			if (IsGBK1(str[i]) && IsGBK2(str[i+1]))
			{
				i+=2;
			}
			else
			{
				str[i] = tolower(str[i]);
				i++;
			}
		}
	}
	//清除头尾的space,tab字符
	inline static std::string& Trim(std::string& str)
	{
		std::string::size_type p = str.find_first_not_of(" \t\r\n");
		if (p == std::string::npos)
		{
			str = "";
			return str;
		}
		std::string::size_type q = str.find_last_not_of(" \t\r\n");
		str = str.substr(p, q-p+1);
		return str;
	}

#if 0
	/*获取Keydb中的密码*/
	inline static std::string getPassword(std::string name)
	{
		const ycrKey *key;
		std::string result("");
		ycrKeyDbInit();
		key = ycrGetKey(name.c_str());
		if ((key == NULL)||(key->value == NULL))
		{
			ycrKeyDbDestroy();
			return result;
		}
		result = key->value;    
		ycrKeyDbDestroy();
		return result;
	}
#endif

	inline static unsigned long Hash(const char * str)
	{
		const unsigned long prime1 = 4224542477ul;
		const unsigned long prime2 = 3264857ul;
		unsigned long hash = 0;
		for (const char * c = str; *c; c++)
		{
			hash += prime1 * hash + *c + 1 + (hash % prime2);
		}
		return hash;
	}
	/*
	 *
	 * same on 64bit & 32bit
	 * from 
	 * http://svn.corp.alimama.com/repos/alimama/Algo/trunk/Common/Util.cpp
	 * 10:38 2009-3-6
	 *
	 */
	inline static unsigned int Hash(const char* str,unsigned int len)
	{
		const unsigned int prime1 = 4224542477ul;
		const unsigned int prime2 = 3264857ul;
		unsigned int hash = 0;
		unsigned int tmp = 0;
		const char *c=str;
		for (unsigned int i=0;i<len;i++,c++)
		{
			tmp = prime1 * hash;
			tmp += *c;
			tmp ++;
			tmp +=hash % prime2;
			hash += tmp;
		}
		return hash;
	}

	inline static unsigned int SimpleHash(const char *str)
	{
		unsigned int h;
		unsigned char *p;
		for(h=0, p = (unsigned char *)str; *p ; p++)
			h = 31 * h + *p;
		return h;
	}

	inline static std::string clickTableName(const std::string& pid)
	{
		int hash = Hash(pid.c_str())%256;
		std::ostringstream os;
		os << "Click" << std::setw(3) << std::setfill('0') << hash;
		return os.str();
	}
    /*
	inline static time_t timeParse(const char* tv, const char* fmt) {
		struct tm tm;
		memset(&tm,0,sizeof(tm));
		strptime(tv,fmt,&tm);
		return mktime(&tm);
	}*/

    /*
	inline static char* timeFormat(const time_t& tv,
								   const char* fmt,
								   char* str,
								   int len) {
		struct tm tm;
		localtime_r(&tv, &tm);
		strftime(str,len,fmt,&tm);
		return str;
	}*/
	inline static int Split(std::vector<std::string>& vs,
							const std::string& line,
							int col,
							char dmt='\t')
	{
		std::string::size_type p=0;
		std::string::size_type q;
		for(int i=0;i<col;i++)
		{
			q = line.find(dmt,p);
			if(q == std::string::npos) if(i<col-1) return -1;
			vs[i]=line.substr(p,q-p);
			Trim(vs[i]);
			p = q+1;
		}
		return 0;
	}
	inline static void Split2(std::vector<std::string>& vs,
							const std::string& line,
							char dmt='\t')
	{
		std::string::size_type p=0;
		std::string::size_type q;
		vs.clear();
		for(;;)
		{
			q = line.find(dmt,p);
			std::string str = line.substr(p,q-p);
			Trim(str);
			if(!str.empty()) vs.push_back(str);
			if(q == std::string::npos) break;
			p = q+1;
		}
	}
	inline static void Split3(std::vector<std::string>& vs,
							const std::string& line,
							char dmt='\t')
	{
		std::string::size_type p=0;
		std::string::size_type q;
		vs.clear();
		for(;;)
		{
			q = line.find(dmt,p);
			std::string str = line.substr(p,q-p);
			Trim(str);
			vs.push_back(str);
			if(q == std::string::npos) break;
			p = q+1;
		}
	}
	inline static void SplitInt(std::vector<int>& vs,
							const std::string& line,
							char dmt='\t')
	{
		std::string::size_type p=0;
		std::string::size_type q;
		vs.clear();
		for(;;)
		{
			q = line.find(dmt,p);
			std::string str = line.substr(p,q-p);
			Trim(str);
			if(!str.empty()) vs.push_back(atoi(str.c_str()));
			if(q == std::string::npos) break;
			p = q+1;
		}
	}
	inline static std::string Domain(const std::string& url)
	{
		std::string::size_type p = url.find("://");
		if(std::string::npos == p) p = 0;
		else p += 3;
		std::string::size_type q = url.find("/",p);
		std::string domain = url.substr(p,q-p);
		if(domain.empty()) return domain;
		if(domain[domain.size()-1]!='.') return domain;
		return domain.substr(0,domain.size()-1);
	}
	inline static bool DomainMatch(const std::string& domain,
								   const std::string& pattern)
	{
		int off = domain.size()-pattern.size();
		if(off<0) return false;
		if(off>0)
		{
			if( pattern[0] != '.' && domain[off-1] != '.') return false;
		}
		return pattern == domain.substr(off);
	}
	/* 判断两个字符串是否相等 */
	static bool equal( const char* str1, const char* str2 )
	{
		assert(str1&&str2);
		return strcmp( str1, str2 ) == 0;
	}

	/* 把字符串中的每个字符都转为小写字符 */
	static void toLower( char* str1 )
	{
		assert(str1);
		size_t len = strlen(str1);
		for( size_t i = 0; i < len; i++ )
		{
			if ((unsigned char)str1[i]>0x80)//双字节
				i ++;
			else
				str1[i] = ::tolower(str1[i]);
		}
	}
	/* 复制字符串 */
	static char* replicate( const char* str )
	{
		assert(str);
		size_t len = strlen( str );
		char *szRet = new (std::nothrow)char[len+1];
		strcpy( szRet, str );
		return szRet;
	}

	/* 判断两个字符串是否相等 */
	static bool equalNoCase( const char* str1, const char* str2 )
	{
		assert(str1&&str2);
		char *s1 = replicate( str1 );
		char *s2 = replicate( str2 );
		toLower(s1);
		toLower(s2);
		bool bResult = equal(s1, s2);
		delete[] s1;
		delete[] s2;
		return bResult;
	}
};

#endif
