<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * This is the product class.
 * 
 * @package application.models
 */
class Product extends Model
{
    /**
     * Product id.
     * @var integer
     */
    public $id;
    /**
     * Product name.
     * @var string
     */
    public $name;
    /**
     * Product status.
     * @var string
     */
    public $status;
    /**
     * Who create the product.
     * @var integer
     */
    public $created_by;
    /**
     * Who update the product last.
     * @var integer
     */
    public $updated_by;
    /**
     * Create the product time.
     * @var date
     */
    public $create_time;
    /**
     * Update the product time last.
     * @var date
     */
    public $update_time;
    /**
     * Regress report notice address.
     * @var string
     */
    public $regress_notice;
    /**
     * Unit report notice address. 
     * @var string
     */
    public $unit_notice;
    
    /**
     * Available status of product.
     */
    const STATUS_AVAILABLE = 1;
    /**
     * Disable status of product
     */
    const STATUS_DISABLE = 0;

    /**
     * Return a instance of $className.
     * 
     * @param string $className class name, product as default.
     * @return $className a instance of $className. 
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Return table name of product.
     * 
     * @return string table name of product 
     */
    public function tableName()
    {
        return '{{product}}';
    }

    /**
     * Return the validation rules for attributes.
     * 
     * @return array a list of validation rules
     */
    public function rules()
    {
        return array(
            array('name', 'required'),
            array('name', 'unique'),
            array('name', 'length', 'min' => 2, 'max' => 128),
            array('status', 'numerical', 'integerOnly' => true),
            array('name, status, regress_notice, unit_notice', 'safe'),
        );
    }
    
    /**
     * Define relations.
     * @return array Relations.
     */
    public function relations()
    {
        return array(
            'users' => array(self::MANY_MANY, 'User', 'product_user(product_id, user_id)',
                'on' => '`users_users`.`status` = :status',
                'params' => array(':status' => ProductUser::STATUS_AVAILABLE),
            ),
            'admins' => array(self::MANY_MANY, 'User', 'product_user(product_id, user_id)',
                'on' => '`admins_admins`.`role` = :role',
                'params' => array(':role' => ProductUser::ROLE_ADMIN),
            ),
            'pending_users' => array(self::MANY_MANY, 'User', 'product_user(product_id, user_id)',
                'on' => '`pending_users_pending_users`.`status` = :status',
                'params' => array(':status' => ProductUser::STATUS_PENDING),
            ),
            'projects' => array(self::HAS_MANY, 'Project', 'product_id',
                'condition' => '`status` = :status',
                'params' => array(':status' => Project::STATUS_AVAILABLE),
                'order' => '`lft` ASC'
            ),
        );
    }

    /**
     * Define scopes.
     * @return array Scope.
     */
    public function scopes()
    {
        return array(
            'disable' => array(
                'condition' => 'status=' . self::STATUS_DISABLE
            ),
            'avaliable' => array(
                'condition' => 'status=' . self::STATUS_AVAILABLE,
            ),
        );
    }

    /**
     * Set the default value before save.
     *  
     * @return boolean alway is true
     */
    protected function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = $this->update_time = date(Option::model()->getDateFormatOpt());
            $this->created_by  = $this->updated_by  = Yii::app()->user->id;
        }
        else
        {
            $this->update_time = date(Option::model()->getDateFormatOpt());
            $this->updated_by  = Yii::app()->user->id;
        }

        return parent::beforeSave();
    }
    
    /**
     * Return the labels of attribute.
     * 
     * @return array a list labels of attribute.
     */    
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('Product', 'Id'),
            'name' => Yii::t('Product','Name'),
            'status' => Yii::t('Product', 'Status'),
            'create_time' => Yii::t('Product', 'Create Time'),
            'update_time' => Yii::t('Product', 'Update Time'),
            'created_by' => Yii::t('Product', 'Created By'),
            'updated_by' => Yii::t('Product', 'Updated By'),
            'regress_notice' => Yii::t('Product', 'Regress Notice'),
            'unit_notice' => Yii::t('Product', 'Unit Notice'),
        );
    }
    
    /**
     * Return product options.
     * If no product, return empty array.
     * 
     * @return array product options.
     */
    public function getProductOpts()
    {
        $opts = array();
        
        $products = Product::model()->findAllByAttributes(array('status' => self::STATUS_AVAILABLE));
        foreach($products as $product)
        {
            $opts[$product->id] = $product->name;
        }
        
        return $opts;
    }
    
    public function getProjects($returnObject = false, $parentId = 0)
    {
        $projects = array();
        if(!$returnObject)
        {
            $sql = 'SELECT id FROM `' . Project::model()->tableName()
                 . '` WHERE `product_id` = ' . $this->id . ' AND `status` = ' . Project::STATUS_AVAILABLE
                 . ' ORDER BY lft ASC';
            if($parentId > 0)
            {
                $sql = 'SELECT `project`.id FROM `' . Project::model()->tableName()
                    . '` AS parent, `' . Project::model()->tableName()
                    . '` WHERE `project`.`product_id` = ' . $this->id
                    . ' AND `parent`.id = ' . $parentId
                    . ' AND `' . Project::model()->tableName() . '`.lft BETWEEN `parent`.lft AND `parent`.rgt'
                    . ' AND `project`.`status` = ' . Project::STATUS_AVAILABLE
                    . ' ORDER BY `project`.lft ASC';
            }
            $projects = Yii::app()->db->createCommand($sql)->queryColumn();
        }
        else
        {
            if($parentId > 0)
            {
                $parent = Project::model()->findByPk($parentId);
                $criteria = new CDbCriteria();
                $criteria->addBetweenCondition('lft', $parent->lft, $parent->rgt);
                $criteria->order = 'lft ASC';
                $projects = Project::model()->findAllByAttributes(array('product_id' => $this->id), $criteria);
            }
            else
            {
                $criteria = new CDbCriteria();
                $criteria->order = 'lft ASC';
                $projects = Project::model()->findAllByAttributes(array('product_id' => $this->id), $criteria);
            }
        }

        return $projects;
    }
    
    public function getProjectTree()
    {
        $html = '<ul>';
        $projects = $this->getProjects(true);
        foreach($projects as $index => $project)
        {
            $title = $project['name'];
            $html .= '<li><a href="javascript:;" ' . ' title="' . $title . '" data-parent-id="' 
                    . $project['parent_id'] . '" data-project-id="' . $project['id'] . '" data-product-id="' . $project['product_id'] 
                    . '" data-project-path="' . $project['path'] . '">' . $project['name'] . '</a>';
            if(!isset($projects[$index + 1]))
            {
                $html .= '</li>';
                $count = substr_count($html, '<ul>') - substr_count($html, '</ul>') - 1;
                if($count > 0)
                {
                    $html .= str_repeat('</ul></li>', $count);
                }
            }
            else if($projects[$index + 1]['lft'] > $project['rgt'])
            {
                $html .= '</li>';
                $count = $projects[$index + 1]['lft'] - $project['rgt'] - 1;
                $count1 = substr_count($html, '<ul>') - substr_count($html, '</ul>') - 1;
                $count = ($count > $count1) ? $count1 : $count;
                if($count > 0)
                {
                    $html .= str_repeat('</ul></li>', $count);
                }
            }
            else
            {
                $html .= '<ul>';
            }

        }
        $html .= '</ul>';
        if($html == '<ul></ul>')
        {
            $html = '<span>No Data</span>';
        }
        return $html;
    }
    
    public function getProjectTree2()
    {
        $projectTree = array();
        $projects = $this->getProjects(true);
        foreach($projects as $index => $project)
        {
            $subTree = array('text' => $project['name']);
            $project['parent_id'];
            $projectTree[$project['id']] = $subTree;
        }
        return $projectTree;
    }

    public function getStatusOptions()
    {
        return array(
            self::STATUS_AVAILABLE => Yii::t('Product', 'Status Available'),
            self::STATUS_DISABLE => Yii::t('Product', 'Status Disable')
        );
    }

    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status])
               ? $status[$this->status]
               : Yii::t('ProductUser', 'Unknown status({status})', array('{status}' => $this->status));
    }

    public function getStatusStyle()
    {
        $style = '';
        switch ($this->status) {
            case self::STATUS_DISABLE : {
                $style = 'disabled';
                break;
            }
            default : {
                break;
            }
        }
        return $style;
    }

    public static function getAllProductsList()
    {
        $productsList = array();
        $products = self::model()->findAll();
        foreach($products as $product)
        {
            $productsList[$product->id] = $product->name;
        }
        return $productsList;
    }

    public function getProductUserList()
    {
        $productUserList = array();
        $users = VProductUser::model()->getUsers($this->id);
        foreach($users as $user)
        {
            if(!empty($user->realname))
            {
                $productUserList[$user->user_id] = PinYin::getPinYin($user->realname) . ' ' . $user->realname;
            }
            else
            {
                $productUserList[$user->user_id] = $user->username;
            }
        }
        asort($productUserList);
        return $productUserList;
    }

    public function getProductAdminList()
    {
        $productAdminList = array();
        $users = VProductUser::model()->getAdmins($this->id);
        foreach($users as $user)
        {
            if(!empty($user->realname))
            {
                $productAdminList[$user->user_id] = PinYin::getPinYin($user->realname) . ' ' . $user->realname;
            }
            else
            {
                $productAdminList[$user->user_id] = $user->username;
            }
        }
        asort($productAdminList);
        return $productAdminList;
    }

    private function setUserRole($userIds, $role)
    {
        foreach($userIds as $userId)
        {
            $productUser = ProductUser::model()->findByAttributes(array('product_id' => $this->id, 'user_id' => $userId));
            if($productUser === null)
            {
                $productUser = new ProductUser();
                $productUser->user_id = $userId;
                $productUser->product_id = $this->id;
                $productUser->save();
            }
            Yii::app()->cache->delete(WebUser::USER_OPT . '_' . $userId);
            Yii::app()->cache->delete(WebUser::USERNAME_OPT . '_' . $userId);
        }
        $users = ProductUser::model()->findAllByAttributes(array('product_id' => $this->id));
        foreach($users as $user)
        {
            Yii::app()->cache->delete(WebUser::USER_OPT . '_' . $user->user_id);
            Yii::app()->cache->delete(WebUser::USERNAME_OPT . '_' . $user->user_id);
        }
        $condition = new CDbCriteria();
        $condition->compare('product_id', $this->id);
        $condition->addInCondition('user_id', $userIds);
        return  ProductUser::model()->updateAll(array('role' => $role, 'status' => ProductUser::STATUS_AVAILABLE), $condition);
    }

    public function setProductUser($userIds)
    {
        return $this->setUserRole($userIds, ProductUser::ROLE_USER);
    }

    public function setProductAdmin($userIds)
    {
        return $this->setUserRole($userIds, ProductUser::ROLE_ADMIN);
    }

    public function setNotProductUser($userIds)
    {
        $condition = new CDbCriteria();
        $condition->compare('product_id', $this->id);
        $condition->addInCondition('user_id', $userIds);
        $users = ProductUser::model()->findAllByAttributes(array('product_id' => $this->id));
        foreach($users as $user)
        {
            Yii::app()->cache->delete(WebUser::USER_OPT . '_' . $user->id);
            Yii::app()->cache->delete(WebUser::USERNAME_OPT . '_' . $user->id);
        }
        foreach($userIds as $userId)
        {
            Yii::app()->cache->delete(WebUser::USER_OPT . '_' . $userId);
            Yii::app()->cache->delete(WebUser::USERNAME_OPT . '_' . $userId);
        }
        return ProductUser::model()->deleteAll($condition);
    }
}
?>