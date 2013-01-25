
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
#include "YException.h"

using namespace std;

YException::YException(const char * mes) : message(mes)
{
}

YException::~YException(void)
{
}

string& YException::GetMsg()
{
	return message;
}
