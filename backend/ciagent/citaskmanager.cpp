/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

#include <list>
#include<string>
#include <vector>
#include <dirent.h>
#include <fstream>
#include <sys/types.h>
#include <sys/wait.h>
#include "../log/Log.h"
#include "../daemon/Daemon.h"
#include "../config/SimpleConfig.h"
#include "../include/json/json.h"
#include "../include/curl/curl.h"
#include "citaskmanager.h"
#include "svn_sorts.h"
//#include "AgentEngine.h"

//using namespace toast;
/*
//gcc  -I /usr/local/include/subversion-1 \
// -I /usr/include/apr-1 -lsvn_client-1 -lapr-1 -laprutil-1
// dependent on packets
//apr-1.3.8-2.el5.x86_64.rpm
//
//apr-devel-1.3.8-2.el5.x86_64.rpm
//apr-util-1.3.9-2.el5.x86_64.rpm
//apr-util-devel-1.3.9-2.el5.x86_64.rpm
most of code come from svn
*/
/*
REF: http://tools.ietf.org/html/rfc3986#section-2.3
http://en.wikipedia.org/wiki/Percent-encoding
*/
extern string CIGetListURL;
extern string CITaskURL;
extern string svn_password;
extern string svn_username;
extern CITaskManager *g_ci_task_manager;
int UrlEncode(const string src, string *dst)
{
    static char encodeMap[256] = 
    {
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 0-15
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 16-31
        '+', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '-', '.', 0,  // 32-47
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 0, 0, 0, 0, 0, 0,  // 48-63
        0, 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',   // 64-79
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 0, 0, 0, 0, '_',  // 80-95
        0, 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',  // 96-111
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 0, 0, 0, '~', 0,  // 112-127
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 128-143
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 144-159
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 160-175
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 176-191
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 192-207
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 208-223
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 224-239
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,  // 240-255
    };
    dst->clear();
    for(int i = 0; i < src.length(); ++i)
    {
        unsigned char c = src[i];
        if(encodeMap[c])
        {
            dst->push_back(encodeMap[c]);
        }
        else
        {
            dst->push_back('%');
            char low, high;
            low = c&0x0f;
            high = c >> 4;
            // convert the low and high to hex 0-16
            if(high > 9)
                dst->push_back(high + 'A' - 10);
            else
                dst->push_back(high + '0');
            if(low > 9)
                dst->push_back(low + 'A' - 10);
            else
                dst->push_back(low + '0');
        }
    }
    return 0;
}

// function write_calback, this is called by libcurl get data form server
// CURLOPT_WRITEFUNCTION  CURLOPT_WRITEDATA
size_t CurlWriteCallback( char *ptr, size_t size, size_t nmemb, void *server_return)
{
    size_t len = size*nmemb;

    if (server_return)
    {
        ( (string*)server_return)->append(ptr, len);
    }
    return len;
}
int CurlPost(const string& url, const string& post_msg, string *returnString)
{
    CURL *curl;
    CURLcode res;
    Log::Debug("URL: " + url);
    Log::Debug("Content: " + post_msg);
    curl = curl_easy_init();
    if(curl) 
    {
        try
        {
            curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);   // ref libcurl manual
            curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
            curl_easy_setopt(curl, CURLOPT_POST, 1);
            curl_easy_setopt(curl, CURLOPT_POSTFIELDS, post_msg.c_str());
            curl_easy_setopt(curl, CURLOPT_POSTFIELDSIZE, post_msg.length());
            curl_easy_setopt(curl, CURLOPT_TIMEOUT, 120);
            curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, CurlWriteCallback);
            curl_easy_setopt(curl, CURLOPT_WRITEDATA, returnString);

            res = curl_easy_perform(curl);
            curl_easy_cleanup(curl);

            if(res != CURLE_OK)  // CURLE_OK == 0
            {
                Log::Error("Post %s error, curl_easy_perform return %d", post_msg.c_str(), res);
            }
            else
            {
                Log::Info("Curl result: " + *returnString);
            }
        }
        catch(...)
        {
            Log::Error("Curl exception");
        }
        return res;
    }
    else
    {
        Log::Error("curl_easy_init return NULL");
        return -1;
    }
}

void InvokeCITask(int task_id, string dev_log)
{
    string url = ::CITaskURL;
    char buf[64];
    sprintf(buf, "%d", task_id);
    string post_str = "id=" + string(buf) + "&autorun=1" + "&user=TOAST" + "&dev_log=" + dev_log;
    string returnStr;
    if(CurlPost(url, post_str, &returnStr))
    {
        Log::Error("start timer task error %d ", task_id);
    }
}
int  GetCITaskList()
{
    string url = ::CIGetListURL;
    string task_lists_str;
    int post_result = CurlPost(url, "", &task_lists_str);
    
    int ci_task_counter = 0;
    if(post_result == CURLE_OK)
    {
        Json::Reader reader;
        Json::Value root;
        bool parseresult = false;
        try
        {
            parseresult = reader.parse(task_lists_str, root);
            if(parseresult && !root.empty() )
            {

                Json::Value::Members mem = root.getMemberNames();
                for(Json::Value::Members::iterator mem_iter = mem.begin(); mem_iter != mem.end(); mem_iter++)
                {
                    // first convert the mem to id which is int
                    int id;
                    sscanf((*mem_iter).c_str(), "%d", &id); 
                    // second get the timestring
                    string url = root[*mem_iter].asString();
                    g_ci_task_manager->Insert_Monitors_Task(id, url, 0);
                    ci_task_counter++;
                }
            }
            else
            {
                Log::Error("JSON parse error or no object in the json");
            }

        }
        catch(...)
        {
            Log::Error("Get CI task list exception");
        }
    }
    else
    {
        Log::Error("Get CI task list error, curl error %d", post_result);
    }
    return ci_task_counter;

}

//need to read the configure file and get svn user name and password
static svn_error_t *add_user_callback(svn_auth_cred_simple_t **cred,
    void *baton,
    const char *realm,
    const char *username,
    svn_boolean_t may_save,
    apr_pool_t *pool)
{
    svn_auth_cred_simple_t *ret = (svn_auth_cred_simple_t *)apr_pcalloc(pool, sizeof (*ret));
    // read config file
    ret->username = apr_pstrdup(pool, ::svn_username.c_str());
    ret->password = apr_pstrdup(pool, ::svn_password.c_str());
    ret->may_save = 1;
    *cred = ret;
    return SVN_NO_ERROR;
}

int Init_SVN_Client(apr_pool_t **pl, svn_client_ctx_t **ctx)
{

    int returnvalue = EXIT_SUCCESS;
    svn_error_t *err = NULL;
    apr_pool_t *pool;

    apr_initialize();
    /* Create top-level memory pool. Be sure to read the HACKING file to
    understand how to properly use/free subpools. */
    pool = svn_pool_create(NULL);
    *pl = pool;
    /* Initialize the FS library. */
    err = svn_fs_initialize(pool);
    if (err)
    {
        Log::Error(err->message);
        returnvalue = EXIT_FAILURE;
	svn_error_clear(err);
        goto exit;
    }

    err = svn_config_ensure(NULL, pool);
    if (err)
    {
        Log::Error(err->message);
        returnvalue = EXIT_FAILURE;
	svn_error_clear(err);
        goto exit;
    }

    /* Initialize and allocate the client_ctx object. */
    if ((err = svn_client_create_context(ctx, pool)))
    {
        Log::Error(err->message);
        returnvalue = EXIT_FAILURE;
	svn_error_clear(err);
        goto exit;
    }

    /* Load the run-time config file into a hash */
    if ((err = svn_config_get_config(&((*ctx)->config), NULL, pool)))
    {
        Log::Error(err->message);
        returnvalue = EXIT_FAILURE;
	svn_error_clear(err);
        goto exit;
    }

    {
        svn_auth_provider_object_t *provider;
        apr_array_header_t *providers
            = apr_array_make(pool, 4, sizeof (svn_auth_provider_object_t *));

        svn_auth_get_simple_prompt_provider(&provider,
            add_user_callback,
            NULL, 
            2,
            pool);
        APR_ARRAY_PUSH(providers, svn_auth_provider_object_t *) = provider;
        svn_auth_open(&((*ctx)->auth_baton), providers, pool);
    }
exit:
    return returnvalue;
}


static svn_error_t *get_vevision(void *baton,
    const char *path,
    const svn_dirent_t *dirent,
    const svn_lock_t *lock,
    const char *abs_path,
    apr_pool_t *pool)
{
    struct CITaskManager::print_baton *pb = (struct CITaskManager::print_baton *)baton;
    pb->monitor_task->new_revision = dirent->created_rev;
    return SVN_NO_ERROR;
}
typedef struct last_modify_info
{
    string author;
    string date;
    string message;
    vector<string> change_list;
    vector<char>   action;
    svn_revnum_t revision;
}last_modify_info_t;
static svn_error_t *get_last_modify_info_cb(void *baton,
    svn_log_entry_t *log_entry,
    apr_pool_t *pool)
{
    vector<last_modify_info> *info = (vector<last_modify_info>*)(baton);
    last_modify_info aInfo;
    const char * author;
    const char * date;
    const char* message;
    svn_compat_log_revprops_out(&author, &date, &message, log_entry->revprops);

    //if((date) && (date))
    //  svn_cl__time_cstring_to_human_cstring(&date, date, pool);
    aInfo.revision = log_entry->revision;
    aInfo.author = string(author);
    aInfo.date = string(date);
    aInfo.message = string(message);

    //get modify list
    if (log_entry->changed_paths2)
    {
        apr_array_header_t *sorted_paths;
        int i;

        /* Get an array of sorted hash keys. */
        sorted_paths = svn_sort__hash(log_entry->changed_paths2,
            svn_sort_compare_items_as_paths, pool);

        for (i = 0; i < sorted_paths->nelts; i++)
        {
            svn_sort__item_t *item = &(APR_ARRAY_IDX(sorted_paths, i,
                svn_sort__item_t));
            const char *path = (const char*)item->key;
            svn_log_changed_path2_t *log_item  = (svn_log_changed_path2_t *)apr_hash_get(log_entry->changed_paths2, item->key, item->klen);

            const char *modify_info = apr_psprintf(pool, "%s", path);
            string tmp = string(modify_info);
            aInfo.change_list.push_back(tmp);
            aInfo.action.push_back(log_item->action);
        }
    }
    svn_compat_log_revprops_clear(log_entry->revprops);
    info->push_back(aInfo);
    return SVN_NO_ERROR;
}

static int get_last_modify_info(apr_pool_t *pool, svn_client_ctx_t *ctx, svn_revnum_t oldrev, svn_revnum_t newrev, const char *url, vector <last_modify_info> *modifys)
{
    apr_pool_t *subpool = svn_pool_create(pool);
    //svn_opt_revision_t peg_revision;
    svn_error_t *err = NULL;
    int ret = 0;
    apr_array_header_t *targets;
    targets = apr_array_make(subpool, 3, sizeof(char *));   //targets
    APR_ARRAY_PUSH(targets, const char *) = url;

    svn_opt_revision_t peg_revision;                  //peg_revision
    peg_revision.kind = svn_opt_revision_unspecified;
    peg_revision.value.number = newrev;

    apr_array_header_t *  revision_ranges;        //revision_ranges
    revision_ranges = apr_array_make(subpool, 0, sizeof(svn_opt_revision_range_t *));
    svn_opt_revision_range_t *range = (svn_opt_revision_range_t *)apr_palloc(subpool, sizeof(*range));
    range->start.kind = svn_opt_revision_number;
    range->end.kind = svn_opt_revision_number;
    range->start.value.number = oldrev;
    range->end.value.number = newrev;
    APR_ARRAY_PUSH(revision_ranges, svn_opt_revision_range_t *) = range;

    err = svn_client_log5(targets,
        &peg_revision,
        revision_ranges,
        0,  // max number of log messages
        1,  //svn log -v 
        0,
        0,
        NULL,
        get_last_modify_info_cb,
        (void*)modifys,
        ctx,
        subpool);    
    if(err)
    {
        svn_error_clear(err);
	ret = -1;
    }
    svn_pool_destroy(subpool); 
    return ret;
}
int Check_SVN_Revision(apr_pool_t *pool, svn_client_ctx_t *ctx, CITaskManager::svn_monitor_task_t* monitor_task)
{
    int ret = EXIT_SUCCESS;
    svn_error_t *err = NULL;
    svn_opt_revision_t revision;
    svn_opt_revision_t peg_revision;
    apr_pool_t *subpool = NULL;
    if (monitor_task->url.empty())
    {
        Log::Error("svn url is empty\n");
        ret =  EXIT_FAILURE;
        return ret;
    }

    revision.kind = svn_opt_revision_head;
    peg_revision.kind = svn_opt_revision_unspecified;

    subpool = svn_pool_create(pool);
    

    struct CITaskManager::print_baton pb;
    pb.monitor_task = monitor_task;
    pb.verbose = 1;

    /* Main call into libsvn_client does all the work. */
    err = svn_client_list2(monitor_task->url.c_str(),
        &peg_revision,
        &revision,
        svn_depth_empty, //svn_depth_immediates,
        SVN_DIRENT_CREATED_REV,
        0,
        get_vevision,
        &pb,
        ctx,
        subpool);
    if (err)
    {
        Log::Error("svn_client_list2 error");
        Log::Error(err->message);
	svn_error_clear(err);
        ret =  EXIT_FAILURE;
    }
    
    svn_pool_destroy(subpool);
    return ret;
}

CITaskManager::CITaskManager()
{

}
CITaskManager::~CITaskManager()
{
    pthread_mutex_destroy(&m_svn_monitor_tasks_list.mtx_lock);
    list<svn_monitor_task*>::iterator iter;
    for(iter = m_svn_monitor_tasks_list.monitor_list.begin();
        iter != m_svn_monitor_tasks_list.monitor_list.end();
        iter ++)
    {
        delete *iter;
    }
    m_svn_monitor_tasks_list.monitor_list.clear();
}
void CITaskManager::Init_Monitor_Task_List()
{
    pthread_mutex_init(&m_svn_monitor_tasks_list.mtx_lock, NULL);
    m_svn_monitor_tasks_list.monitor_list.clear();
}
void CITaskManager::Initlize()
{
    Init_Monitor_Task_List();
    Init_SVN_Client(&m_pl, &m_ctx);
    m_subpl = svn_pool_create(m_pl);
    GetCITaskList();
}



//check the taskid is exist in the list
//NULL if not exist othrise the iter
CITaskManager::svn_monitor_task_t* CITaskManager::Is_Exist(int taskid)
{
    list<svn_monitor_task_t*>::iterator iter;
    for(iter = m_svn_monitor_tasks_list.monitor_list.begin();
        iter != m_svn_monitor_tasks_list.monitor_list.end();
        iter ++)
    {
        if((*iter)->taskid == taskid)
        {
            return *iter;
        }
    }
    return NULL;
}
//insert monitor task to the monitors list
// 0 already exist
// 1 new insert 

int CITaskManager::Insert_Monitors_Task(int taskid, string url, int interval)
{
    int res = 1;
    // erase the / at the end of url
    if(*(url.rbegin()) == '/')
        url.erase(url.begin() + url.length() - 1);

    pthread_mutex_lock(&m_svn_monitor_tasks_list.mtx_lock);
    CITaskManager::svn_monitor_task_t * task = NULL;

    if((task = Is_Exist(taskid)))
    {
        //update the task id's url
        task->url = url;
        task->last_revision = 0;
        task->new_revision = 0;
        res = 0;
    }
    else
    {
        //new a task and add to list
        task = new (std::nothrow)CITaskManager::svn_monitor_task_t;
        task->interval      = interval;
        task->taskid        = taskid;
        task->url           = url;
        task->last_revision = 0;
        task->new_revision  = 0;
        m_svn_monitor_tasks_list.monitor_list.push_back(task);
    }
    //if the url already exist, return success
    //else insert new, and dpdate the config file
    pthread_mutex_unlock(&m_svn_monitor_tasks_list.mtx_lock);
    return res;
}
//delete a task from monitor list
void CITaskManager::Delete_Monitors_Task(int taskid)
{
    pthread_mutex_lock(&m_svn_monitor_tasks_list.mtx_lock);
    CITaskManager::svn_monitor_task_t * task = NULL;
    if((task = Is_Exist(taskid)))
    {
        m_svn_monitor_tasks_list.monitor_list.remove(task);
        delete task; //free memory
    }
    //find the list and get the monitor task, delete the monitor task
    pthread_mutex_unlock(&m_svn_monitor_tasks_list.mtx_lock);
}
/*

{ [{ "author":"xxx",

"date": "xxxx", 
"comment": "blbl", 
"revisionbegin": "vbxxx",
"revisionend": "vexxxx", 
"lists": [{"file": "file xxxx", "action": "M/A/D"}] }]}
*/
void CITaskManager::CheckChanged()
{
    pthread_mutex_lock(&m_svn_monitor_tasks_list.mtx_lock);
    list<svn_monitor_task_t * >::iterator task_iter;
    vector <last_modify_info> modifys;
    for (task_iter = m_svn_monitor_tasks_list.monitor_list.begin();
        task_iter != m_svn_monitor_tasks_list.monitor_list.end();
        task_iter++)
    {
        svn_pool_clear(m_subpl);
        if(EXIT_SUCCESS != Check_SVN_Revision(m_subpl, m_ctx, (*task_iter)))
              continue;
        if((*task_iter)->last_revision == 0)
        {
            (*task_iter)->last_revision = (*task_iter)->new_revision;
        }
        else if ((*task_iter)->last_revision != (*task_iter)->new_revision)
        {
            Log::Debug("There is modify files in %s ", (*task_iter)->url.c_str());
            //notify frount end
            //update last_revision for next check
            modifys.clear();
            Log::Debug("Last revision %d, new revision %d", (*task_iter)->last_revision, (*task_iter)->new_revision);
            if(get_last_modify_info(m_subpl, m_ctx, (*task_iter)->last_revision+1, (*task_iter)->new_revision, (*task_iter)->url.c_str(), &modifys))
	    {
	        Log::Error("get_last_modify_info error, url: %s", (*task_iter)->url.c_str());
		continue;
	    }
            // packet the modify info into json
            string desc_info;
            char revision_buf[32];
            Json::Value svninfo;
            vector<last_modify_info>::iterator iter_dodifys;
            for(iter_dodifys = modifys.begin(); iter_dodifys != modifys.end(); iter_dodifys++)
            {
                Json::Value aRevisionInfo;
                aRevisionInfo["author"] = iter_dodifys->author;
                aRevisionInfo["date"] = iter_dodifys->date;
                sprintf(revision_buf, "%ld", iter_dodifys->revision);
                aRevisionInfo["revisionend"] = revision_buf;
                if(iter_dodifys == modifys.begin())
                {
                    sprintf(revision_buf, "%ld", (*task_iter)->last_revision);
                    aRevisionInfo["revisionbegin"] = revision_buf;
                }
                else
                {
                    sprintf(revision_buf, "%ld", (iter_dodifys-1)->revision);
                    aRevisionInfo["revisionbegin"] = revision_buf;
                }
                aRevisionInfo["comment"] = iter_dodifys->message;
                aRevisionInfo["lists"] = Json::Value::null;
                int i = 0;
                vector<string>::iterator iter_changed_list = iter_dodifys->change_list.begin();
                for(; iter_changed_list != iter_dodifys->change_list.end(); ++iter_changed_list, ++i)
                {
                    Json::Value file_info;
                    string svn_url = (*task_iter)->url;
                    size_t svn_pos = svn_url.find("/svn/");
                    if(string::npos != svn_pos)
                    {
                        svn_url = svn_url.substr(svn_pos + 4);
                        string str_left = svn_url;
                        string perfix;
                        while(str_left.length() && ((*iter_changed_list).find(str_left) != 0))
                        {
                            size_t slash_pos = str_left.find('/', 1);
                            if(slash_pos != string::npos)
                            {
                                perfix += str_left.substr(0, slash_pos);
                                str_left = str_left.substr(slash_pos);
                            }
                            else
                            {
                                perfix += str_left;
                                str_left = "";
                            }
                        }
                        file_info["file"] = perfix + string(*iter_changed_list);
                    }
                    else
                    {
                        file_info["file"] =*iter_changed_list;
                    }
                    file_info["action"] = string(1, iter_dodifys->action[i]);
                    aRevisionInfo["lists"].append(file_info);
                }
                svninfo.append(aRevisionInfo);
            }

            (*task_iter)->last_revision = (*task_iter)->new_revision;

            Json::FastWriter writer;

            desc_info = writer.write(svninfo);
            Log::Info(desc_info);
            // urlencode desc_info
            string encode_info;
            UrlEncode(desc_info, &encode_info);
            InvokeCITask((*task_iter)->taskid, encode_info);
            Log::Info("TaskRun %d, URL %s  version %d \n", (*task_iter)->taskid, (*task_iter)->url.c_str(), (*task_iter)->new_revision);
        }
    }
    pthread_mutex_unlock(&m_svn_monitor_tasks_list.mtx_lock);
}

