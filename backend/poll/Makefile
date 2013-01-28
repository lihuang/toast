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

OBJS = epollmanager.o 


all: libepollmanager.a

libepollmanager.a: $(OBJS)
	ar -crv $@ $(OBJS)
epollmanager.o:
	$(GCC) $(CFLAG) epollmanager.cpp

clean:
	rm -f *.o
	rm -f *.a

