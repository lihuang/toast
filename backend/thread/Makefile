INCLUDE=\
        -I.\
        -I ../sync/

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

OBJS = threadbase.o 


all: libsmartthread.a

libsmartthread.a: $(OBJS)
	ar -crv $@ $(OBJS)
threadbase.o:
	$(GCC) $(CFLAG) threadbase.cpp

clean:
	rm -f *.o
	rm -f *.a

