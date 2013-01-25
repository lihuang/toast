/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

// Usage steps
// 1) Derive your class from Singleton<YourClass>
// 2) Add this line to your class declaration
// 3) Becaureful the thread secturity( multiple object, if multithread initlize conrrently
//friend class Singleton<YourClass>; // only allow singleton to consturct this object;
#ifndef SINGLETON_H
#define SINGLETON_H
#include <new>
template<typename T> class Singleton
{
public:
    static T* Instance()
    {
        if(!m_Instance)
        {
           m_Instance = new (std::nothrow)T();
        }
        return m_Instance;
    }
protected:
    Singleton(){}
    virtual ~Singleton()=0;

private:
    Singleton(const Singleton&);
    T& operator=(const Singleton&);
    static T* m_Instance;
};
template<typename T> T* Singleton<T>::m_Instance = 0;
template<typename T> Singleton<T>::~Singleton()
{ 
    delete m_Instance;
    m_Instance = 0;
}
#endif
