/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include "BaseConfiguration.h"


//static BaseConfiguration * configurationInstance = (BaseConfiguration *)0;

BaseConfiguration::BaseConfiguration(void) : lastRefreshTime(0), expiryInSeconds(0)
{
}

BaseConfiguration::~BaseConfiguration(void)
{
}

void BaseConfiguration::CheckExpiry()
{
	if (expiryInSeconds <=0 )
		return;

	if (time(NULL) >= lastRefreshTime + expiryInSeconds)
		Initialize();
}
