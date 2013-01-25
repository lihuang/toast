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

OBJS = StringUtil.o BaseConfiguration.o FileConfiguration.o YException.o SimpleConfig.o


all: libconfig.a

libconfig.a: $(OBJS)
	ar -crv $@ $(OBJS)
StringUtil.o:
	$(GCC) $(CFLAG) StringUtil.cpp

BaseConfiguration.o:
	$(GCC) $(CFLAG) BaseConfiguration.cpp

FileConfiguration.o:
	$(GCC) $(CFLAG) FileConfiguration.cpp

YException.o:
	$(GCC) $(CFLAG) YException.cpp

SimpleConfig.o:
	$(GCC) $(CFLAG) SimpleConfig.cpp

clean:
	rm -f *.o
	rm -f *.a

