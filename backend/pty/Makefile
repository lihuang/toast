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

OBJS = toastpopen.o toastpty.o 


all: libpopen.a

libpopen.a: $(OBJS)
	ar -crv $@ $(OBJS)
toastpopen.o:
	$(GCC) $(CFLAG) toastpopen.cpp

toastpty.o:
	$(GCC) $(CFLAG) toastpty.cpp

clean:
	rm -f *.o
	rm -f *.a

