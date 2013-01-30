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
    'Name' => '任务名称',
    'Type' => '任务类型',
    'Product Id' => '所属产品',
    'Project Id' => '所属项目',
    'Project Path' => '模块路径',
    'Create Time' => '创建时间',
    'Update Time' => '修改时间',
    'Created By' => '创建者',
    'Updated By' => '修改者',
    
    'Unit Test Task' => '单元测试',
    'BVT Test Task' => 'BVT',
    'Regression Test Task' => '功能测试',
    'System Test Task' => '系统测试',
    'Comparison Test Task' => '对比测试',
    'Continuous Integration' => '持续集成',
   
    'Create' => '新建',
    'Report' => '报表',
    'Task Commands' => '命令',
    
    'Run Task' => '运行任务',
    'Running Task' => '运行中 ...',
    'Update Task' => '修改任务',
    'Delete Task' => '删除任务',
    'Deleted Task' => '已删除',
    'Cancel Run' => '取消运行',
    'Canceling Run' => '取消中 ...',
    'Copy Task' => '复制任务',
    
    'New Task' => '新建任务',
    'Task Detail' => '任务详情',
    'Module Ids' => '测试用例模块ID',
    'Description' => '描述',
    'Scheme Time' => '定时运行',
    'Report Filter' => '邮件通知',
    'Report To' => '邮件通知',
    'Command' => '命令',
    'Responsible' => '负责人',
    'Status' => '状态',
    'Status Order' => '正常',
    'Status Disable' => '禁用',
    'Unknown Type({type})' => '未知任务类型({type})',
    'Trigger' => '触发任务',
    'Build' => 'Build触发',
    'SVN URL' => 'SVN触发',
    'Exclusive' => '互斥运行',
    'Exclusive Tip' => '同一时刻只允许该任务的一个运行实例',
    'Wait Machine' => '等待运行环境',
    'Wait Machine Tip' => '运行环境无法通信时使任务处于等待状态',
    
    'Stage {num}' => '阶段 {num}',
    'Add Stage' => '增加阶段',
    'Add Command' => '+ 增加命令',
    'Last Runs' => '最近运行记录',
    '{field} format error, should be {format}' => '{field} 格式错误, 应为 {format}',
    'Responsible {responsible} is not exist.' => '负责人 {responsible} 不存在.',
    
    'Success Task' => '成功',
    'Failed Task' => '失败',
    'Other Task' => '其他',
    
    'Report None' => '不通知',
    'Report Fail' => '失败时通知',
    'Report All' => '全部通知',
    
    'No Mail Report' => '不发送邮件通知',
    'Mail Report When Failed' => '(仅失败时通知) ',
    
    'Svn url tip' => '填写SVN URL, 当SVN发生提交时触发任务执行',
    'Build tip' => '填写包名（如"t-test-toast", 不带版本号），多个包名以","分割，可以通过API触发任务执行，命令可以通过添加参数 $BUILD 获取到新包',
    
    'History' => '修改历史',
    'Run' => '运行',
    'Update' => '修改',
    'Copy' => '复制',
    'Delete' => '删除',
    'Return' => '返回',
    'Cancel' => '取消',
    'Canceling' => '正在取消',
    'Save' => '保存',
    'Save And Run' => '保存并运行',
    
    'Yes' => '是',
    'No' => '否',
    
    'Modify Task' => '修改任务',
    'Add Job' => '添加子任务',
    
    'Normal' => '常用',
    'Custom' => '高级',
    'NoCron' => '不设定时',
    'Every Hour' => '每小时',
    'Every Day' => '每日',
    'Every Month' => '每月',
    'Every Week' => '每周',
    'Minute' => '分钟',
    'Hour' => '小时',
    'Day' => '日',
    'Month' => '月',
    'Week' => '星期',
    
    'Select Task Type' => '选择任务类型',
);
?>