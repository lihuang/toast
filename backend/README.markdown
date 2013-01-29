简介 
----------
    toast后端用于任务的分发，以及任务结果收集，分为controller和agent

目录功能简介
----------
    *agent 为toast客户端
    *agentcmdrsp agent和controller之间消息定义
    *ciagent 是为持续集成而实现的特殊agent，它监控用户指定的svn地址，如果发现其监控的svn地址下的代码有更新，
             则执行相应的测试任务
    *config 配置文件处理代码
    *controller  为toast的服务器端，分发前端发送的命令给指定的agent，并收集任务执行结果
    *daemon  unix系统daemonize代码
    *include 存放依赖的静态库的头文件，curl，json， log4cpp
    *libs 存放静态库，如果这个这里面的库在编译过程中有问题，请分别编译相应的库
    *log  log库
    *net  网络相关
    *poll epoll简单封装
    *pty  伪终端实现
    *sync 线程同步库
    *threadpool 线程池
    *trayicon  windows下的托盘图标库
    *winagent  windows版agent的工程文件


编译
--------
    *toast应用到的开源库包括libcurl,libjson, log4cpp 这三个库controller和agent均有用到，
     这3个均被静态库，代码中libs目录已有这三个库编译好的文件，如果编译过程中这三个库有问题，请按
     各库说明文档分别编译，将静态库放在libs目录
     另外controller 还用到rrdtool， 要编译controller需要rrdtool-devel
     对于svn监控agent还需要svnclient库，需要根据平台安装相应的开发包，RHEL需要安装subversion-devel

    *如果所有库都准备好，首先在backend目录Make, 此时agent和controller公共代码以及agent都应编译成功
     agent在agent目录内可执行晚间名toast
     controller需要单独在controller目录再次make

     我们在下列平台下编译通过：
     rhel 5， 6 centos 6 其中controller需要rrdtool rrdtool-devel， ciagent需要libsubversion-devel

     ubuntu 12.04.1 
     在该平台下编译controller需要安装rrdtool和librrd-dev
     在ubuntu下编译ciagent需要安装libapr1-dev, libaprutil1-dev libsvn-dev，并有可能需要修改make文件
     -I/usr/include/apr-1 to -I/usr/include/apr-1.0 通过apr-config --includes 确定头文件的位置，做修改
     LINK= -lpthread -ldl -lrt -lsvn_client-1 to LINK= -lpthread -ldl -lrt -lsvn_client-1 -lapr-1


配置文件说明
------------

agent配置文件

    []
    server=yourcontrolleripordomainname     //controller的ip或者域名
    port=16868                              // controller端口

    [LOG]       // log4cpp配置
    # --- categories ---
    log4cpp.rootCategory=DEBUG,MAIN

    # --- root Appender ---
    log4cpp.appender.MAIN=org.apache.log4cpp.RollingFileAppender
    log4cpp.appender.MAIN.fileName=AgentDaemon.log
    log4cpp.appender.MAIN.maxFileSize=10240000 
    log4cpp.appender.MAIN.maxBackupIndex=100
    log4cpp.appender.MAIN.layout=org.apache.log4cpp.PatternLayout
    log4cpp.appender.MAIN.layout.ConversionPattern=%d{%Y-%m-%d %H:%M:%S} [%p]: %m%n

ciagent配置文件

    []
    server=yourcontrollerordamain  // controller ip或者域名
    port=16868                     // 端口
    CIGetListURL = http://yourcontrollerordamain/task/getallurl //从controller获取所有ci任务列表
    CITaskURL = http://yourcontrollerordamain/api/runtaskbyid   //调用前端创建一个新的任务运行
    CIInterval = 300            // svn 扫描的频率
    svn_username="username"     // svn username，这个username要有所有ci任务的访问权限，ci只相当于svn log命令，\
                             并不下载代码到本地
    svn_password="password"     // svn password
    [LOG]  // log4cpp配置
    # --- categories ---
    log4cpp.rootCategory=DEBUG,MAIN

    # --- root Appender ---
    log4cpp.appender.MAIN=org.apache.log4cpp.RollingFileAppender
    log4cpp.appender.MAIN.fileName=AgentDaemon.log
    log4cpp.appender.MAIN.maxFileSize=10240000 
    log4cpp.appender.MAIN.maxBackupIndex=100
    log4cpp.appender.MAIN.layout=org.apache.log4cpp.PatternLayout
    log4cpp.appender.MAIN.layout.ConversionPattern=%d{%Y-%m-%d %H:%M:%S} [%p]: %m%n

controller 配置文件

    []
    monitor_path = /tmp/toast     // controller接收前端命令文件目录，需要和前端配合设置，controller不断扫描这个目录，
                                 如果有命令文件则读出，执行命令，然后删除命令
    rrd_path = /tmp/rra           // controller将测试集性能信息以rrd文件方式写入这个目录
    log_path = /home/toast/output  // 命令log文件目录， controller收集所有命令的stdout，stderr信息，
                                  并保存在文件中，文件名为相应执行id，这些命令均在测试机上执行
    response_thread_num = 6        // controller 处理agent相应消息的线程数
    root_url = http://127.0.0.1/toast/   // 前端url的跟
    task_list_url = task/getallruntime?   // 获取前端定时任务列表url，controller启动时会从前端取定时任务列表
    add_agent_url = machine/addmachine?   // 添加机器api
    agent_info_url = machine/updatemachine?  // 更新机器信息url
    update_all_agent_url = machine/updateallmachine? // 更新所有测试机信息，为了机器状态的同步，
                                                     controller在每次启动时会      把所有测试机状态设置为down
    update_all_run_url = run/updateallrun?           // 跟新所有任务状态，controller在启动时会把所有任务设置为完成状态
    udate_run_url = run/updaterun?                   // 更新单个任务的状态
    run_timer_task_url = api/runtaskbyid?            // 调用前端创建某个任务的一次运行api，多用于定时任务定时时间到时应用
    max_agent_number = 4096                          // 最大agent个数
    CIAgent=cisvnmonitoragent                        // ci agent 的机器名，必须为机器名，所有ci监控任务都会发送到这个测试机
    [LOG]                             // log4cpp配置
    # --- categories ---
    log4cpp.rootCategory = DEBUG,MAIN

    # --- root Appender ---
    log4cpp.appender.MAIN = org.apache.log4cpp.RollingFileAppender
    log4cpp.appender.MAIN.fileName = controller.log
    log4cpp.appender.MAIN.maxFileSize = 102400000
    log4cpp.appender.MAIN.maxBackupIndex = 100
    log4cpp.appender.MAIN.layout = org.apache.log4cpp.PatternLayout
    log4cpp.appender.MAIN.layout.ConversionPattern = %d{%Y-%m-%d %H:%M:%S} [%p]: %m%n


这些配置大部分都和前端配置相关，请参考前端配置以及前后端协议定义,并根据前端配置做相应修改
