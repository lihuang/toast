<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * The view model of TestCase 
 * 
 * @package application.models
 */
class VTestCase extends TestCase
{
    /**
     * The name of case's project 
     * @var string 
     */
    public $project_name;
    /**
     * The path of case's project
     * @var string
     */
    public $project_path;
    /**
     * The name of case's product 
     * @var string 
     */
    public $proudct_name;
    /**
     * The id of case's product 
     * @var integer 
     */
    public $product_id;
    /**
     * The username of created user 
     * @var string 
     */
    public $created_by_username;
    /**
     * The realname of created user
     * @var string  
     */
    public $created_by_realname;
    /**
     * The username of updated user
     * @var string  
     */
    public $updated_by_username;
    /**
     * The realname of updated user 
     * @var string 
     */
    public $updated_by_realname;
    public $parent_id;

    /**
     * Get a instance.
     * Just implement parent model function.
     *  
     * @param string $className
     * @return VTestCase $model
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    
    /**
     * Get table name of case view
     * @return string 
     */
    public function tableName()
    {
        return 'vtest_case';
    }
    
    /**
     * Get table primary key of case view
     * @return string
     */
    public function primarykey()
    {
        return 'id';
    }
    
    /**
     * Get attribute labels
     * @return array $label
     */
    public function attributeLabels()
    {
        return parent::attributeLabels() + array(
            'product_name' => Yii::t('TestCase', 'Product Name'),
            'project_name' => Yii::t('TestCase', 'Project Name'),
            'project_path' => Yii::t('TestCase', 'Project Path'),
            'created_by_realname' => Yii::t('TestCase', 'Created By'),
            'updated_by_realname' => Yii::t('TestCase', 'Updated By'),
        );
    }
    
    public function rules()
    {
        return array(
            array('product_id, project_id, project_path, created_by, updated_by, create_time, update_time,
                created_by_realname, updated_by_realname, parent_id', 'safe'),
        );
    }
    
    /**
     * Search test case, return test case data provider.
     * 
     * @param integer $pageSize
     * @param integer $condition
     * @return \CActiveDataProvider $testCases
     */
    public function search($pageSize, $condition = null, $needProvider = true)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
            $product_id = $this->product_id;
            if ($product_id == NULL)
                $product_id = Yii::app()->user->getCurrentProduct();
            $criteria->compare('product_id', $product_id);
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            // TODO: do not use the title field for searching
            $this->name = $condition; 
        }
        
        $criteria->compare('id', $this->name, true, 'OR');
        $criteria->compare('name', $this->name, true, 'OR');
        $criteria->compare('created_by_realname', $this->name, true, 'OR');
        $criteria->compare('created_by_username', $this->name, true, 'OR');
        $criteria->compare('project_id', $this->project_id);
        $criteria->compare('updated_by', $this->updated_by);
        $criteria->compare('update_time', $this->update_time, true);
        $criteria->compare('status', self::STATUS_AVAILABLE);

        if(isset($this->parent_id))
        {
            $project = Project::model()->findByPk($this->parent_id);
            if($project != null)
            {
                $subIds = $project->getSubProjects();
                $criteria->addInCondition('project_id', $subIds);
                Yii::app()->user->setCurrentProduct($project->product_id);
            }
        }
        if(isset($this->product_id))
        {
            Yii::app()->user->setCurrentProduct($this->product_id);
        }
        
        $data = array();
        if($needProvider)
        {
            $data = new CActiveDataProvider(__CLASS__, array(
                'criteria' => $criteria,
                'pagination' => array(
                    'pageSize' => $pageSize
                ),
                'sort' => array(
                    'defaultOrder' => "create_time DESC"
                ),
            ));
        }
        else
        {
            $criteria->order = 'create_time DESC';
            $criteria->limit = $pageSize;
            $testCases = $this->findAll($criteria);
            $data = array();
            foreach($testCases as $testCase)
            {
                $data[$testCase->id]['name'] = '#' . $testCase->id 
                        . ' ' . $testCase->name . ' @ ' . $testCase->updated_by_realname;
                $data[$testCase->id]['url'] = $testCase->code_url;
            }
        }
        return $data;
    }
    
    public function getCodeLink()
    {
        $pathInfo = pathinfo($this->code_url);
        return CHtml::link($pathInfo['basename'], $this->code_url, array('target' => 'blank', 'class' => 'code_url'));
    }
    
    public function getCode($language = 'sh')
    {

        $info = pathinfo($this->code_url);
        if(isset($info['extension']))
            $language = $info['extension'];
        return @TString::highLight($code, $language);
    }
    
    public function getResultProvider($pageSize = 10)
    {
        $result = new CaseResult();
        $result->test_case_id = $this->id;
        return $result->search($pageSize);
    }
    
    public function getListMenuItems()
    {
        $actives = array(false, false);
        if (preg_match('#^\S*\@case\%20created\_by\_username\:\(\=\=\%7B' . 
                Yii::app()->user->username . '\%7D\)$#', Yii::app()->request->requestUri)) {
            $actives[1] = true;
        } else {
            $actives[0] = true;
        }
        
        return array(
                array(
                    'label' => Yii::t('TestCase', 'All Cases'), 
                    'url' => array('/case'),
                    'active' => $actives[0]
                ),
                array(
                    'label' => Yii::t('TestCase', 'Created By Me'), 
                    'url' => array('/case/index/q/@case created_by_username:(=={' . Yii::app()->user->username . '})'), 
                    'active' => $actives[1]),
            );
    }
    
    public function getQueryOpts()
    {
        return array(
            'cTable' => 'case',
            'tables' => array(
                'case' => array(
                    'label' => Yii::t('TOAST', 'Test Case Label'),
                    'items' => array(
                        'id' => array(
                            'label' => 'ID',
                            'type' => 'text',
                            'operators' => array(
                                '==' => '等于',
                                '-=' => '不等于',
                                '>=' => '大于等于',
                                '>' => '大于',
                                '<' => '小于',
                                '<=' => '小于等于',
                                '=' => '包含',
                                '!=' => '不包含'
                            ),
                        ),
                        'name' => array(
                            'label' => $this->getAttributeLabel('name'),
                            'type' => 'text',
                            'operators' => array(
                                '' => '含有',
                                '-' => '不含有',
                            ),
                        ),
                        'created_by_username' => array(
                            'label' => $this->getAttributeLabel('created_by'),
                            'type' => 'select',
                            'operators' => array(
                                '==' => '等于',
                                '-=' => '不等于',
                                'tl' => 'TL等于',
                            ),
                            'data' => Yii::app()->user->getUsernameOpts()
                        ),
                        'updated_by_username' => array(
                            'label' => $this->getAttributeLabel('updated_by'),
                            'type' => 'select',
                            'operators' => array(
                                '==' => '等于',
                                '-=' => '不等于',
                                'tl' => 'TL等于',
                            ),
                            'data' => Yii::app()->user->getUsernameOpts()
                        ),
                        'create_time' => array(
                            'label' => $this->getAttributeLabel('create_time'),
                            'type' => 'text',
                            'operators' => array(
                                '' => '等于',
                                '-' => '不等于',
                                '>=' => '大于等于',
                                '>' => '大于',
                                '<' => '小于',
                                '<=' => '小于等于',
                            ),
                        ),
                        'update_time' => array(
                            'label' => $this->getAttributeLabel('update_time'),
                            'type' => 'text',
                            'operators' => array(
                                '' => '等于',
                                '-' => '不等于',
                                '>=' => '大于等于',
                                '>' => '大于',
                                '<' => '小于',
                                '<=' => '小于等于',
                            ),
                        )
                    )
                ),
            )
        );
    }
}
?>