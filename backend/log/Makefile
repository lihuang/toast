INCLUDE=\
        -I. \
	-I../util/thread\
        -I../include/log4cpp

ifeq ($(coverage), yes)
CFLAG = -c -g -Wall -fPIC -DSHMEM -DWITH_NONAMESPACES $(INCLUDE) -fprofile-arcs -ftest-coverage
MAKE	= make coverage=yes
else
CFLAG = -c -g -Wall -fPIC -DSHMEM -DWITH_NONAMESPACES $(INCLUDE)
MAKE	= make
endif

LDPATH=\
       -L.\

GCC = g++
CC = gcc

.SUFFIXES: .o .cpp

OBJS = Log.o 


all: liblog.a

liblog.a: $(OBJS) 
	ar -crv $@ $(OBJS)
Log.o:
	$(GCC) $(CFLAG) Log.cpp 

clean:
	rm -f *.o
	rm -f *.a
