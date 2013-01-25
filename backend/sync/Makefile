INCLUDE=\
        -I.\

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

OBJS = mutex.o


all: libsync.a

libsync.a: $(OBJS)
	ar -crv $@ $(OBJS)

mutex.o:
	$(GCC) $(CFLAG) -fPIC -o $@ mutex.cpp

clean:
	rm -f *.o
	rm -f *.a

