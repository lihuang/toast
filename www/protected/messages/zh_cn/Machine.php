<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
return array(
    'Id' => '#',
    'Name' => '主机域名',
    'Product Id' => '所属产品',
    'Type' => '类型',
    'Status' => '状态',
    'Agent Version' => 'Agent版本',
    'Description Info' => '描述信息',
    'Responsible' => '负责人',
    'Port' => 'KFC Group',
    'Group' => '内容：',
    'Time' => '时间：',
    'To' => '到',
    'Monitor Graph' => '监控图',
    'Create Time' => '创建时间',
    'Update Time' => '最后更新时间',
    'Update Script' => '安装更新脚本',
    'Created By' => '创建者',
    'Updated By' => '修改者',

    'Status Idle' => '空闲',
    'Status Running' => '使用中',
    'Status Down' => '无法通信',

    'Notify' => '测试机异常',
    'No Notify' => '不通知负责人',
    'Notify Responsible' => '通知负责人',
    'Tasks may be affected' => '以下任务可能会受到影响',  
    
    'Linux' => 'Linux',
    'Windows' => 'Windows',
    'Test Machine Info' => '测试机信息',

    'Assigned' => '可用',
    'Unassigned' => '未领取',
    
    'Add Machine' => '添加测试机',
    'New Machine' => '新测试机',
    'Update Machine' => '修改测试机',
    'Delete Machine' => '删除测试机',
    'Unknown status({status})' => '未知机器状态({status})',
    'Unknown type({type})' => '未知机器类型({type})',
    'Update Agent' => '升级Agent',
    'Upgrade Agent' => '升级Agent',
    'Processes' => '监控的进程',
    'Tasks On This Machine' => '相关任务',
    'Tasks which run on ({machine})' => '运行在 ({machine}) 的自动化任务',

    'Input machine info' => '第一步：输入测试机信息',
    'Run toast agent install script' => '第二步：安装TOAST Agent',
    'Attension: Could not modify machine info after making sure.' => '注意：确认后不能修改测试机主要信息',
    'KFC Group has been exist' => 'KFC Group 不能重复.',
    'Port should be numerical' => 'Port 必须为整数.',
    'Run script at {machine}' => '在测试机 <b>{machine}</b> 上执行下面的脚本',
    'Install Specification' => '脚本正确运行完成后，请等待测试机状态变为<span style="padding: 3px 5px" class="idle">空闲</span>，
        表示Toast Agent安装成功通信。<br/>若超过5分钟测试机状态仍是<span style="padding: 3px 5px" class="down">无法通信</span>状态，请联系系统管理员。',
    
    'Install Step' => '安装',
    'Download Windows Agent {download link}' => '<a style="color: #008CD8;" href="{download link}" target="_blank">点击此处</a>下载安装Windows版Agent',
    'Install Linux Agent' => '查看 <a style="color: #008CD8;" href="https://github.com/taobao/toast/wiki/Agent%E7%AB%AF%E5%AE%89%E8%A3%85%E6%96%87%E6%A1%A3">Agent端安装文档</a>',
    
    'Assign Step' => '领取',
    'Assign Step 1' => '1. 进入未领取页面去领取刚刚添加的测试机',
    'Assign Step 2' => '2. 修改新添加测试机的负责人及所属产品后保存',
    'Assign Step 3' => '3. 测试机添加完成',
    
    'Confirm Upgrade' => '立即升级',
    'Upgrade Linux Agent' => '点击立即升级将自动升级测试机上的Agent<br/>或在终端下运行下面命令完成手动升级',
    'Upgrade Windows Agent {download link}' => '<a style="color: #008CD8;" href="{download link}" target="_blank">点击此处</a>下载安装Windows版Agent，完成升级',
);
?>