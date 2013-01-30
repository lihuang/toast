<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Machine Model 
 * 
 * @package application.models
 */
class Machine extends Model
{
    public $id;
    public $product_id;
    public $name;
    public $status;
    public $type;
    public $responsible;
    public $notify;
    public $hostname;
    public $platform;
    public $kernel;
    public $processes;
    public $agent_version;
    public $desc_info;
    public $created_by;
    public $updated_by;
    public $create_time;
    public $update_time;

    const STATUS_IDLE = 0;
    const STATUS_RUNNING = 1;
    const STATUS_DOWN = 2;

    const TYPE_LINUX = 0;
    const TYPE_WINDOWS = 1;

    const ACTION_ADD = 'Add';
    const ACTION_DEL = 'Del';

    const PROTOCOL_NAME = 'Mapp';
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'machine';
    }

    public function rules()
    {
        return array(
            array('name', 'required'),
            array('status, type, product_id, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('name, type, status, product_id, responsible, notify, hostname, platform, kernel, 
                agent_version, desc_info, created_by, updated_by, create_time, update_time', 'safe'),
            array('name', 'unique'),
            array('name', 'length', 'max' => 255),
        );
    }

    public function relations()
    {
        return array(
            'product' => array(self::BELONGS_TO, 'Product', 'product_id'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Machine', 'Id'),
            'name' => Yii::t('Machine', 'Name'),
            'product_id' => Yii::t('Machine', 'Product Id'),
            'type' => Yii::t('Machine', 'Type'),
            'status' => Yii::t('Machine', 'Status'),
            'notify' => Yii::t('Machine', 'Notify'),
            'responsible' => Yii::t('Machine', 'Responsible'),
            'hostname' => Yii::t('Machine', 'Hostname'),
            'platform' => Yii::t('Machine', 'Platform'),
            'kernel' => Yii::t('Machine', 'Kernel'),
            'processes' => Yii::t('Machine', 'Processes'),
            'agent_version' => Yii::t('Machine', 'Agent Version'),
            'desc_info' => Yii::t('Machine', 'Description Info'),
            'responsible' => Yii::t('Machine', 'Responsible'),
            'created_by' => Yii::t('Machine', 'Created By'),
            'updated_by' => Yii::t('Machine', 'Updated By'),
            'create_time' => Yii::t('Machine', 'Create Time'),
            'update_time' => Yii::t('Machine', 'Update Time')
        );
    }

    protected function beforeSave()
    {
        $this->name = trim($this->name);
        $this->filterProcesses();
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
            $this->created_by = $this->updated_by = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
            $this->updated_by = Yii::app()->user->id;
        }

        return parent::beforeSave();
    }

    public function getStatusOptions()
    {
        return array(
            self::STATUS_IDLE => Yii::t('Machine', 'Status Idle'),
            self::STATUS_RUNNING => Yii::t('Machine', 'Status Running'),
            self::STATUS_DOWN => Yii::t('Machine', 'Status Down')
        );
    }

    public function getTypeOptions()
    {
        return array(
            self::TYPE_LINUX => Yii::t('Machine', 'Linux'),
            self::TYPE_WINDOWS => Yii::t('Machine', 'Windows')
        );
    }

    public function getTypeText()
    {
        $types = $this->getTypeOptions();
        return isset($types[$this->type]) ? $types[$this->type] : Yii::t('Machine', 'Unknown type({type})', array('{type}' => $this->type));
    }

    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status]) ? $status[$this->status] : Yii::t('Machine', 'Unknown status({status})', array('{status}' => $this->status));
    }

    public function getMachineOptions()
    {
        $opts = array();
        $linux = array();
        $windows = array();
        $machines = VMachine::model()->findAll('product_id IS NOT NULL ORDER BY name');
        foreach($machines as $machine)
        {
            switch($machine->type) {
                case Machine::TYPE_LINUX: {
                    $linux[$machine->id] = '';
                    if ($machine->responsible_realname)
                        $linux[$machine->id] .= '[' . $machine->responsible_realname . '] ';
                    $linux[$machine->id] .= $machine->name;
                    if ($machine->ip)
                        $linux[$machine->id] .= ' (' . $machine->ip . ')';
                    break;
                }
                case Machine::TYPE_WINDOWS: {
                    $windows[$machine->id] = '';
                    if ($machine->responsible_realname)
                        $windows[$machine->id] = '[' . $machine->responsible_realname . '] '; 
                    $windows[$machine->id] .= $machine->name;
                    if ($machine->ip)
                        $windows[$machine->id] .= ' (' . $machine->ip . ')';
                    break;
                }
                default: {
                    break;
                }
            }
        }
        $opts[''] = '';
        $opts[Yii::t('Machine', 'Linux')] = $linux;
        $opts[Yii::t('Machine', 'Windows')] = $windows;
        return $opts;
    }

    public function getStatusStyle()
    {
        $style = "";
        switch($this->status)
        {
            case Machine::STATUS_IDLE : {
                    $style = "idle";
                    break;
            }
            case Machine::STATUS_RUNNING : {
                    $style = "running";
                    break;
            }
            case Machine::STATUS_DOWN : {
                    $style = "down";
                    break;
            }
            default : {
                    break;
            }
        }
        return $style;
    }

    public function sendAction($action)
    {
        $config = array();
        $config['TestType'] = 'Machine';
        $config['RunID'] = '0';

        $actions = array();
        if(is_array($action))
        {
            $actions = $action;
        }
        else
        {
            $actions[] = $action;
        }

        foreach($actions as $action)
        {
            $command = array();
            $command['TestBox'] = trim($this->ip);
            $command['BoxType'] = $this->getTypeText();
            $command['TestCommand'] = $action;
            $config['Commands'][] = $command;
        }

        $timestamp = time();
        $iniFile = Yii::app()->params['runFilePath'] . "/Machine_{$action}_{$this->id}_{$timestamp}.ini";
        TLocal::touch($iniFile, CJSON::encode($config), 0022, TRUE);
    }

    public function getAgentInstallScript()
    {
        $script = file_get_contents(Yii::app()->params['installScript']);
        $script = str_replace('{MACHINE_NAME}', $this->name, $script);
        $script = str_replace('{HOST_URL}', Yii::app()->request->hostInfo . Yii::app()->baseUrl, $script);
        $script = str_replace('{MACHINE_URL}', Yii::app()->request->hostInfo . Yii::app()->baseUrl . '/machine/updatemachine', $script);
        $script = str_replace('{HTTP_HOST}', Yii::app()->request->getServerName(), $script);
        return $script;
    }

    public function updateAgent()
    {
        $config = array();
        $config['RunID'] = '0';
        $config['TestType'] = Task::model()->getProtocolName(Task::TYPE_REGRESS);

        $arr = array();
        $arr['CommandID'] = '0';
        $arr['TestBox'] = trim($this->ip);
        $arr['TestCommand'] = 'python /home/a/bin/toastd/toastupdate.py  -u';
        $arr['Timeout'] = '0';
        $arr['Sudoer'] = 'root';
        $config['Commands'][] = $arr;

        $timestamp = time();
        $iniFile = Yii::app()->params['runFilePath'] . "/Update_Agent_{$this->id}_{$timestamp}.ini";
        TLocal::touch($iniFile, CJSON::encode($config), 0022, TRUE);
    }

    public function getProcesses()
    {
        $processes = array();
        if(isset($this->processes))
        {
            $array = explode(',', $this->processes);
            $array = array_unique($array);
            foreach($array as $val)
            {
                $val = trim($val);
                if(!empty($val))
                {
                    $processes[$val] = $val;
                }
            }
        }
        return $processes;
    }

    public function getOpts()
    {
        $array = MachineMonitor::$opts + $this->getProcesses();
        return array_unique($array);
    }

    private function filterProcesses()
    {
        $array = explode(',', $this->processes);
        $array = array_unique($array);
        $temp = array();
        foreach($array as $value)
        {
            if($value !== '')
            {
                $temp[] = trim($value);
            }
        }
        $temp = array_diff($temp, MachineMonitor::$opts);
        $this->processes = join(',', $temp);
    }
    
    public function mapp($oldProcesses)
    {
        $oldArr = explode(',', $oldProcesses);
        $nowArr = explode(',', $this->processes);
        $delProcesses = array_diff($oldArr, $nowArr);
        $addProcesses = array_diff($nowArr, $oldArr);
        foreach($delProcesses as $process)
        {
            if(empty($process))
            {
                continue;
            }
            $config = array();
            $config['RunID'] = '0';
            $config['TestType'] = Machine::PROTOCOL_NAME;

            $arr = array();
            $arr['TestTool'] = $process;
            $arr['TestBox'] = trim($this->ip);
            $arr['TestCommand'] = Machine::ACTION_DEL;
            $config['Commands'][] = $arr;

            $timestamp = time();
            $iniFile = Yii::app()->params['runFilePath'] . "/Del_Process_{$process}_{$timestamp}.ini";
            TLocal::touch($iniFile, CJSON::encode($config), 0022, TRUE);
        }
        
        foreach($addProcesses as $process)
        {
            if(empty($process))
            {
                continue;
            }            
            $config = array();
            $config['RunID'] = '0';
            $config['TestType'] = Machine::PROTOCOL_NAME;

            $arr = array();
            $arr['TestTool'] = $process;
            $arr['TestBox'] = trim($this->ip);
            $arr['TestCommand'] = Machine::ACTION_ADD;
            $config['Commands'][] = $arr;

            $timestamp = time();
            $iniFile = Yii::app()->params['runFilePath'] . "/Add_Process_{$process}_{$timestamp}.ini";
            TLocal::touch($iniFile, CJSON::encode($config), 0022, TRUE);
        }
    }
    
    /**
     * get the options of notify
     * @return type 
     */
    public function getNotifyOptions()
    {
        return array(
            Yii::t('Machine', 'No Notify'),
            Yii::t('Machine', 'Notify Responsible'),
        );
    }
    
    /**
     *
     * @return type 
     */
    public function getNotifyText()
    {
        $notifyOptions = $this->getNotifyOptions();
        return isset($notifyOptions[$this->notify]) ? $notifyOptions[$this->notify] : '';
    }
    
}
?>