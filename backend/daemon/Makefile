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

OBJS = Daemon.o UnixProcessController.o 


all: libdaemon.a

libdaemon.a: $(OBJS)
	ar -crv $@ $(OBJS)
Daemon.o:
	$(GCC) $(CFLAG) Daemon.cpp

UnixProcessController.o:
	$(GCC) $(CFLAG) UnixProcessController.cpp

clean:
	rm -f *.o
	rm -f *.a

