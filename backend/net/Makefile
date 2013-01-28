INCLUDE=\
        -I.\
        -I../include/log4cpp

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

OBJS = tcpsocket.o\
       sendreceivedata.o 


all: libnet.a

libnet.a: $(OBJS)
	ar -crv $@ $(OBJS)
tcpsocket.o:
	$(GCC) $(CFLAG) tcpsocket.cpp
sendreceivedata.o:
	$(GCC) $(CFLAG) sendreceivedata.cpp
clean:
	rm -f *.o

