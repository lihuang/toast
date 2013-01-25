/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#ifndef _BASE_CONFIGURATION_H_
#define _BASE_CONFIGURATION_H_

#ifdef _WIN32
#pragma once
#endif
#include <time.h>
#include <stdio.h>
#include <string>

using namespace std;

class BaseConfiguration
{
public:
	virtual ~BaseConfiguration(void);

	virtual inline void SetExpiry(int expiryInSeconds)
	{
		if (expiryInSeconds < 0)
			this->expiryInSeconds = 0;
		else
			this->expiryInSeconds = expiryInSeconds;
	};

	void CheckExpiry();

	inline bool Init()
	{
		bool result = Initialize();
		lastRefreshTime = time(NULL);
		return result;
	}

protected:
	int expiryInSeconds;
	time_t lastRefreshTime;

	BaseConfiguration(void);

	virtual bool Initialize()=0;

};

#endif
