#include "threadpool.h"
#include <iostream>
#include <time.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <stdlib.h>
#include <stdio.h>
#include <sys/types.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <netdb.h>
#include <list>
#include <sys/epoll.h>
#include <errno.h>
#include <signal.h>
using namespace std;
void *(*job_func)(void *); 
void *ThreadFunction(void * param)
{
    cout << "thread %d " << pthread_self() << endl;
    sleep(10);
    return 0;
}
int main(int argc, char **argv)
{
    ThreadPool *pool;
    pthread_attr_t attri;
    pthread_attr_init(&attri);
    pool = ThreadPool::Create(3, 10, 2, NULL);
    for(int i = 0; i < 30; i++)
    {
        pool->AddWork(ThreadFunction, (void*)0);
    }
    sleep(10);
    //pool->Destroy();
    cout << "Waiting for the work down " << endl;
//    pool->PoolWait();
    ThreadPool::Destroy(pool, -1);
    printf("all the thread end");
}
