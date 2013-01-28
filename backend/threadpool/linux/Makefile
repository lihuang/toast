INCLUDE=\
        -I.
ifeq ($(coverage), yes)
CFLAG = -c -g -Wall -fPIC -DSHMEM -DWITH_NONAMESPACES $(INCLUDE) -fprofile-arcs -ftest-coverage
MAKE	= make coverage=yes
else
CFLAG = -c -g -Wall -fPIC -DSHMEM -DWITH_NONAMESPACES $(INCLUDE)
MAKE	= make
endif

GCC = g++
CC = gcc

.SUFFIXES: .o .cpp

OBJS = threadpool.o workerthread.o multiworkqueuethreadpool.o 


all: libthreadpool.a

libthreadpool.a: $(OBJS)
	ar -crv $@ $(OBJS)

threadpool.o:
	$(GCC) $(CFLAG) threadpool.cpp

workerthread.o:
	$(GCC) $(CFLAG) workerthread.cpp

multiworkqueuethreadpool.o:
	$(GCC) $(CFLAG) multiworkqueuethreadpool.cpp
clean:
	rm -f *.o
	rm -f *.a
