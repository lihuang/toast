/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef _Y_EXCEPTION_H_
#define _Y_EXCEPTION_H_

#ifdef _WIN32
#pragma once
#endif

#include <string>


class YException
{
public:
	YException(const char * message = "YException.");

	std::string& GetMsg();
public:
	virtual ~YException(void);

	std::string message;
};


#endif
