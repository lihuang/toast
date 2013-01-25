ifeq ($(shell uname -p|sed -e 's/amd64/x86_64/g'), x86_64)
LDPATH=\
	-L. \
        -L../log/\
        -L../poll/\
        -L../net/\
	-L../libs/\
        -L../threadpool/linux/\
        -L../config/\
        -L../sync/\
        -L../daemon/\
        -L../pty/\
        -L../thread/\
        -L../webcontroller/\
        -L../curlwapper/\
    	-L/usr/lib64/\
        -L/opt/rrdtool-1.4.5/lib
STATLIB=../log/liblog.a ../net/libnet.a ../poll/libepollmanager.a  ../threadpool/linux/libthreadpool.a ../config/libconfig.a ../daemon/libdaemon.a ../pty/libpopen.a ../libs/64/libjson.a ../libs/64/libcurl.a ../thread/libsmartthread.a ../sync/libsync.a ../libs/64/liblog4cpp.a



else
LDPATH=\
	-L. \
        -L../log/\
        -L../pool/\
        -L../net/\
        -L../threadpool/linux/\
        -L../config/\
        -L../sync/\
        -L../daemon/\
        -L../pty/\
        -L../thread/\
        -L../webcontroller/\
        -L../curlwapper/\
	-L../libs/\
	-L/usr/lib/\
	-L/home/a/lib\
    -L/opt/rrdtool-1.4.5/lib
STATLIB=../log/liblog.a ../net/libnet.a ../poll/libepollmanager.a  ../threadpool/linux/libthreadpool.a ../config/libconfig.a ../daemon/libdaemon.a ../pty/libpopen.a ../libs/32/libjson.a ../libs/32/libcurl.a ../thread/libsmartthread.a ../sync/libsync.a ../libs/32/liblog4cpp.a

endif


LINK= -lpthread -ldl -lrt -lrrd

INCLUDE=\
	-I.\
        -I../include/log4cpp\
 	-I../include\
        -I../net\
        -I../poll\
        -I../threadpool\
        -I../log\
        -I../config/\
        -I../sync/\
        -I../daemon/\
        -I../pty/\
        -I../thread/\
        -I../webcontroller/\
        -I../curlwapper/\
        -I/opt/rrdtool-1.4.5/include/
CC = gcc
GCC = g++ -g

ifeq ($(coverage), yes)
CFLAG = -g -Wall $(INCLUDE) -fprofile-arcs -ftest-coverage
else
CFLAG = -g -Wall $(INCLUDE)
endif

#STATLIB= ../log/liblog.a ../util/libutil.a ../net/libnet.a ../poll/libepollmanager.a  ../threadpool/linux/libthreadpool.a 

all: toastcontroller

toastcontroller: ToastMain.cpp ToastThread.o ToastEngine.o connectionmanager.o agentlist.o sendpacket.o servereventprocessing.o taskjobmanager.o crontab.o timertaskmanager.o webinterface.o rrdcontrol.o jsoncommandprocess.o
	$(GCC) ${CFLAG} $(LDPATH) -o $@  ToastMain.cpp ToastEngine.o ToastThread.o connectionmanager.o agentlist.o taskjobmanager.o sendpacket.o servereventprocessing.o crontab.o timertaskmanager.o webinterface.o rrdcontrol.o jsoncommandprocess.o $(STATLIB) $(LINK)
ToastEngine.o: ToastEngine.h ToastEngine.cpp 
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ ToastEngine.cpp

connectionmanager.o: connectionmanager.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ connectionmanager.cpp

agentlist.o: singleton.h agentlist.h agentlist.cpp 
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ agentlist.cpp

sendpacket.o: sendpacket.h sendpacket.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ sendpacket.cpp

servereventprocessing.o: servereventprocessing.h servereventprocessing.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ servereventprocessing.cpp	

taskjobmanager.o: singleton.h taskjobmanager.h taskjobmanager.cpp 	
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ taskjobmanager.cpp

ToastThread.o: ToastThread.h ToastThread.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ ToastThread.cpp
crontab.o: crontab.h crontab.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ crontab.cpp
timertaskmanager.o: timertaskmanager.h timertaskmanager.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ timertaskmanager.cpp
webinterface.o: webinterface.h webinterface.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ webinterface.cpp
rrdcontrol.o: rrdcontrol.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ rrdcontrol.cpp
jsoncommandprocess.o: jsoncommandprocess.cpp
	$(GCC) -c ${CFLAG} $(LDPATH) -o $@ jsoncommandprocess.cpp

clean:
	rm -f *.o
	rm -f *.gcno
	rm -f *.gcda
	rm -f toastcontroller
