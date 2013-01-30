<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class Model extends CActiveRecord
{
    public $id;
    
    /**
     * attributes before save
     * @var array 
     */
    private $_oldAttributes = array();
    
    public function setOldAttributes($value)
    {
        $this->_oldAttributes = $value;
    }
    
    public function getOldAttributes()
    {
        return $this->_oldAttributes;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function init()
    {
        $this->attachEventHandler("onAfterFind", function ($event) {
            $event->sender->oldAttributes = $event->sender->attributes;
        });
        parent::init();
    }
    
    public function getDiffIgnoreAttributes()
    {
        return array('id', 'created_by', 'updated_by', 'create_time', 'update_time');
    }
    
    /**
     * get difference of the model after save
     * should be called after save action
     */
    public function getDiff($oldAttributes = null)
    {
        $ignoreAttr = $this->getDiffIgnoreAttributes();
        $diff = array();
        
        if(empty($oldAttributes))
        {
            $oldAttributes = $this->_oldAttributes;
        }
        
        foreach ($this->attributes as $key => $value) 
        {
            $oldValue = empty($oldAttributes) ? NULL : $oldAttributes[$key];
            if (!in_array($key, $ignoreAttr) && $value != $oldValue)
            {
                $diff[] = array('model_name' => get_class($this), 'model_id' => $this->getId(), 
                    'attribute' => $key, 'new' => $value, 'old' => $oldValue);
            }
        }
        return $diff;
    }
    
    public function saveDiff($diffAction = null, $oldAttributes = null)
    {
        $diffs = $this->getDiff($oldAttributes);
        
        if(null == $diffAction)
        {
            $diffAction = new DiffAction();
            $diffAction->model_name = get_class($this);
            $diffAction->model_id = $this->getId();
            $diffAction->save();
        }
        
        foreach($diffs as $diff)
        {
            $diffAttr = new DiffAttribute();
            $diffAttr->attributes = $diff;
            $diffAttr->diff_action_id = $diffAction->id;
            $diffAttr->save();
        }
        
        return $diffAction;
    }
}
?>
