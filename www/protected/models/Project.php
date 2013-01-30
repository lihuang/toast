<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * Project Model
 * 
 * @package application.models
 */
class Project extends Model
{
    public $id;
    public $parent_id;
    public $module_id;
    public $lft;
    public $rgt;
    public $name;
    public $status;
    public $product_id;
    public $created_by;
    public $updated_by;
    public $create_time;
    public $update_time;
    public $path;

    const STATUS_AVAILABLE = 1;
    const STATUS_DISABLE = 0;

    const ROOT = 0;
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'project';
    }

    public function rules()
    {
        return array(
            array('name, product_id', 'required'),
            array('product_id, parent_id, status', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 128),
            array('parent_id', 'moveValidator')
        );
    }

    public function moveValidator($attribute,$params)
    {
        $parent = Project::model()->findByPk($this->parent_id);
        if($parent !== null && !$this->isNewRecord)
        {
            if($this->lft <= $parent->lft && $this->rgt >= $parent->rgt)
            {
                $this->addError('parent_id', Yii::t('Project', 'Can\'t set self or sub project as parent.'));
            }
        }
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
            'id' => Yii::t('Project', 'Id'),
            'name' => Yii::t('Project', 'Name'),
            'parent_id' => Yii::t('Project', 'Parent Id'),
            'product_id' => Yii::t('Project', 'Product Id'),
            'create_time' => Yii::t('Project', 'Create Time'),
            'update_time' => Yii::t('Project','Update Time'),
            'created_by' => Yii::t('Project', 'Created By'),
            'updated_by' => Yii::t('Project', 'Updated By')
        );
    }

    protected function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Yii::app()->params->dateFormat);
            $this->created_by  = $this->updated_by  = Yii::app()->user->id;
            $this->status = Project::STATUS_AVAILABLE;
        }
        else
        {
            $this->update_time = date(Yii::app()->params->dateFormat);
            $this->updated_by  = Yii::app()->user->id;
        }

        if(!isset($this->parent_id))
        {
            $this->parent_id = Project::ROOT;
        }

        return parent::beforeSave();
    }

    /**
     * save
     * 新建或修改保存
     *
     * 如果是新建保存
     * 1.如果是第一层项目，查询目前最大右值（记为MAX_RIGHT），給该项目左值赋值MAX_RIGHT+1, 项目右值赋值MAX_RIGHT+2
     * 2.如果是其他层项目，查询上一层项目的右值（记为MAX_RIGHT），給该项目左值赋值MAX_RIGHT, 项目右值赋值MAX_RIGHT+1
     *   将所有项目大于等于MAX_RIGTH的左右值+2
     *
     * 如果是修改保存
     * 1.如果修改后的项目的parent_id和初始的项目的parent_id相等，保存修改
     * 2.如果修改后的项目的parent_id和初始的项目的parent_id不相等，获取该项目的所有子项目
     */
    public function save($runValidation = true, $attributes = null)
    {
        if(!$runValidation || $this->validate($attributes))
        {
            if($this->getIsNewRecord())
            {
                if(Project::ROOT == $this->parent_id)
                {
                    $lastProject = Project::model()->findBySql('SELECT `rgt` FROM `' . Project::model()->tableName() . '` ORDER BY `rgt` DESC');
                    if(!empty($lastProject))
                    {
                        $this->lft = $lastProject->rgt + 1;
                        $this->rgt = $lastProject->rgt + 2;
                    }
                    else
                    {
                        $this->lft = 1;
                        $this->rgt = 2;
                    }
                }
                else
                {
                    $parentProject = Project::model()->findByPK($this->parent_id);
                    $this->lft = $parentProject->rgt;
                    $this->rgt = $parentProject->rgt+1;
                    $sql = 'UPDATE `' . Project::model()->tableName() . '` SET lft = lft + 2 WHERE `lft` >  ' . $this->lft;
                    Yii::app()->db->createCommand($sql)->execute();
                    $sql = 'UPDATE `' . Project::model()->tableName() . '` SET rgt = rgt + 2 WHERE `rgt` >= ' . $this->lft;
                    Yii::app()->db->createCommand($sql)->execute();
                }
                $this->insert($attributes);
                $this->module_id = $this->getModuleId();
                $this->path = $this->getPath($this->id);
                return $this->update();
            }
            else
            {
                $original = Project::model()->findByPk($this->id);
                if($this->parent_id != $original->parent_id)
                {
                    //取该项目的子项目，并且包含这个项目
                    $moveProjects = Project::model()->findAllByAttributes(array(), 'lft >= ' . $this->lft . ' AND rgt <= ' . $this->rgt);
                    $targetParent = Project::model()->findByPk($this->parent_id);
                    if($targetParent === null)
                    {
                        $lastProject = Project::model()->findBySql('SELECT `rgt` FROM `' . $this->tableName() . '` ORDER BY `rgt` DESC');
                        $targetParent = new Project();
                        $targetParent->id = $this->parent_id;
                        $targetParent->lft = 1;
                        $targetParent->rgt = $lastProject->rgt + 1;
                    }

                    //取项目移动的距离
                    $distance = $targetParent->rgt - $this->lft;

                    //修改被移动的项目的左右值
                    $rDistance = count($moveProjects) * 2;
                    $from = $targetParent->rgt;
                    $to   = $this->lft;
                    //如果是向后移动，修改相应的值
                    if($distance > 0)
                    {
                        $rDistance = -$rDistance;
                        if($targetParent->lft <= $this->lft && $targetParent->rgt >= $this->rgt)
                        {
                            $distance = $targetParent->rgt - $this->lft - 2;
                            $from = $this->lft + 1;
                            $to   = $targetParent->rgt - 1;
                        }
                        else
                        {
                            $distance = $targetParent->lft - $this->rgt;
                            $from = $this->rgt;
                            $to   = $targetParent->lft;                      
                        }
                    }

                    foreach($moveProjects as $moveProject)
                    {
                        $moveProject->lft += $distance;
                        $moveProject->rgt += $distance;
                    }

                    $sql = 'UPDATE `' . $this->tableName() . '` SET lft = lft + '
                         . $rDistance . ' WHERE `lft` >=  ' . $from . ' AND `lft` <= ' . $to;
                    Yii::app()->db->createCommand($sql)->execute();
                    $sql = 'UPDATE `' . $this->tableName() . '` SET rgt = rgt + '
                         . $rDistance . ' WHERE `rgt` >=  ' . $from . ' AND `rgt` <= ' . $to;
                    Yii::app()->db->createCommand($sql)->execute();


                    //修改该项目及其子项目
                    $this->lft += $distance;
                    $this->rgt += $distance;
                    
                    foreach($moveProjects as $moveProject)
                    {
                        $moveProject->update();
                    }
                }
                $this->module_id = $this->getModuleId();
                return $this->update($attributes);
            }
        }
        else
        {
            return false;
        }
    }
    
    public function afterSave()
    {
        if(!$this->getIsNewRecord())
        {
            $this->saveAttributes(array('path' => self::getPath($this->id),
                'module_id' => $this->getModuleId()));
        }
        parent::afterSave();
    }
    
    /**
     * get module id
     */
    private function getModuleId()
    {
        $moduleId = $this->id;
        if(0 != $this->parent_id)
        {
            $module = Project::model()->findBySql('SELECT `id` FROM `' . $this->tableName() 
                    . '` WHERE `parent_id`= 0 AND `lft` < ' . $this->lft . ' AND `rgt` > ' . $this->rgt);
            if($module != null)
            {
                $moduleId = $module->id; 
            }
        }
        return $moduleId;
    }

    public function delete()
    {
        $this->parent_id = 0;
        $this->save();
        $subIds = $this->getSubProjects();
        $subIds[] = $this->id;
        $sql = 'UPDATE ' . $this->tableName() . ' SET status = ' 
                . Project::STATUS_DISABLE . ' WHERE id IN (' . implode(',', $subIds) . ')';
         return Yii::app()->db->createCommand($sql)->execute();
    }

    public function getSubProjects()
    {
        return $this->product->getProjects(false, $this->id);
    }

    public static function getPath($projectId)
    {
        $project = Project::model()->with('product')->findByPk($projectId);
        $sql = "SELECT `name` FROM `project` WHERE `lft` <= $project->lft AND `rgt` >= $project->rgt ORDER BY lft ASC";
        $projects = Yii::app()->db->createCommand($sql)->queryColumn();
        $projects = array_reverse($projects);
        $projects[] = $project->product->name;
        $projects = array_reverse($projects);
        return '/' . join('/', $projects);
    }

    public function findAll($condition = '', $params = array())
    {
        return parent::findAllByAttributes(array('status' => Project::STATUS_AVAILABLE), $condition, $params);
    }

    public function  findAllByAttributes($attributes, $condition = '', $params = array()) {
        $attributes = array_merge(array('status' => Project::STATUS_AVAILABLE), $attributes);
        return parent::findAllByAttributes($attributes, $condition, $params);
    }
}
?>