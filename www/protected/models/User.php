<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */

/**
 * This is the user class.
 * 
 * @package application.models
 */
class User extends Model
{
    /**
     * User id.
     * @var integer 
     */
    public $id;
    /**
     * Username.
     * @var string
     */
    public $username;
    /**
     * Password.
     * @var string
     */
    public $password;
    /**
     * Realname.
     * @var string
     */
    public $realname;
    /**
     * Email.
     * @var string
     */
    public $email;
    /**
     * Pinyin
     * @var string
     */
    public $pinyin;
    /**
     * Abbreviation
     * @var sting
     */
    public $abbreviation;
    /**
     * Role.
     * @var integer
     */
    public $role = self::ROLE_USER;
    /**
     * Status.
     * @var integer
     */
    public $status;
    /**
     * UserGroup
     * @var integer
     */
    public $group_id;
    /**
     * Create time.
     * @var date
     */
    public $create_time;
    /**
     * Update time.
     * @var date
     */
    public $update_time;

    /**
     * Role of user.
     */
    const ROLE_USER = 1;
    /**
     * Role of adminitrator.
     */
    const ROLE_ADMIN = 2;

    /**
     * User disable status.
     */
    const STATUS_DISABLE = 0;
    /**
     * User available status.
     */
    const STATUS_AVAILABLE = 1;
    const SALT = '';

    /**
     * Return a instance of $className.
     * 
     * @param string $className class name, User as default.
     * @return $className
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Return the table name.
     * 
     * @return string table name
     */
    public function tableName()
    {
        return '{{user}}';
    }

    /**
     * Return the validation rules for attributes.
     * 
     * @return array a list of validation rules
     */
    public function rules()
    {
        return array(
            array('username, realname, password, email, role', 'required'),
            array('username', 'length', 'min' => 3, 'max' => 20),
            array('username', 'match', 'pattern' => '/^[a-zA-z][a-zA-Z-_0-9\.]*$/'),
            array('password', 'length', 'min' => 8),
            array('realname', 'length', 'min' => 2, 'max' => 128),
            array('email', 'length', 'max' => 255),
            array('email', 'email'),
            array('username, email', 'unique'),
            array('username, role, email, status, group_id, pinyin, abbreviation', 'safe'),
        );
    }

    protected function afterValidate()
    {
        if(!$this->hasErrors() && $this->isNewRecord)
        {
            $this->pinyin = TString::pinyin($this->realname);
            $this->abbreviation = PinYin::getPinYin($this->realname);
        }

        parent::afterValidate();
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
        }
        else
        {
            $this->update_time = date(Option::model()->getDateFormatOpt());
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
            'id' => Yii::t('User', 'Id'),
            'username' => Yii::t('User', 'User Name'),
            'password' => Yii::t('User', 'Password'),
            'realname' => Yii::t('User', 'Real Name'),
            'email' => Yii::t('User', 'Email'),
            'pinyin' => Yii::t('User', 'Pin Yin'),
            'abbreviation' => Yii::t('User', 'Abbreviation'),
            'role' => Yii::t('User', 'Role'),
            'status' => Yii::t('User', 'Status'),
            'group_id' => Yii::t('User', 'UserGroup'),
            'create_time' => Yii::t('User', 'Create Time'),
            'update_time' => Yii::t('User', 'Update Time')
        );
    }

    public function relations()
    {
        return array(
            'products' => array(self::MANY_MANY, 'Product', 'product_user(product_id, user_id)',
                'on' => '`products_products`.`status` = :status',
                'params' => array(':status' => ProductUser::STATUS_AVAILABLE),
            ),
            'pending_products' => array(self::MANY_MANY, 'Product', 'product_user(product_id, user_id)',
                'on' => '`pending_products_pending_products`.`status` = :status',
                'params' => array(':status' => ProductUser::STATUS_PENDING),
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
            'normal_user' => array(
                'condition' => 'role=' . self::ROLE_USER,
            ),
            'admin' => array(
                'condition' => 'role=' . self::ROLE_ADMIN,
            ),
        );
    }

    public function getStatusOptions()
    {
        return array(
            self::STATUS_AVAILABLE => Yii::t('User', 'Status Available'),
            self::STATUS_DISABLE => Yii::t('User', 'Status Disable')
        );
    }

    public function getStatusText()
    {
        $status = $this->getStatusOptions();
        return isset($status[$this->status]) ? $status[$this->status] : Yii::t('User',
                        'Unknown status({status})',
                        array('{status}' => $this->status));
    }

    public function getRoleOptions()
    {
        return array(
            self::ROLE_USER => Yii::t('User', 'Role User'),
            self::ROLE_ADMIN => Yii::t('User', 'Role Admin')
        );
    }

    public function getRoleText()
    {
        $roles = $this->getRoleOptions();
        return isset($roles[$this->role]) ? $roles[$this->role] : Yii::t('User',
                        'Unknown role({role})', array('{role}' => $this->role));
    }

    public function getStatusStyle()
    {
        $style = '';
        switch($this->status)
        {
            case self::STATUS_DISABLE :
                {
                    $style = 'disabled';
                    break;
                }
            default :
                {
                    break;
                }
        }
        return $style;
    }

    /**
     * Return a list of products.
     * 
     * @param boolean $returnObjects if it is true return product obj list, else return product id list.
     * <p>default is false</p>
     * @param boolean $onlyAdmin if it is true return the products which the user control
     * <p>default is false</p>
     * @return array a list of product.
     */
    public function getProducts($returnObjects = false, $onlyAdmin = false)
    {
        $products = array();
        if(User::ROLE_ADMIN == $this->role)
        {
            $products = Product::model()->findAll();
            if(!$returnObjects)
            {
                $sql = 'SELECT id FROM `' . Product::model()->tableName()
                        . '` WHERE `status` = ' . Product::STATUS_AVAILABLE;
                $products = Yii::app()->db->createCommand($sql)->queryColumn();
            }
        }
        else
        {
            $tProductUser = ProductUser::model()->tableName();
            $tProduct = Product::model()->tableName();
            $status = Product::STATUS_AVAILABLE;
            $productUserStatus = ProductUser::STATUS_AVAILABLE;
            $userId = $this->id;
            $sql = "SELECT DISTINCT(`{$tProductUser}`.`product_id`) FROM `{$tProductUser}`, `{$tProduct}` "
                    . "WHERE `{$tProductUser}`.`product_id` = `{$tProduct}`.`id` AND `{$tProduct}`.`status` = {$status} "
                    . "AND `{$tProductUser}`.`user_id` = {$userId} AND `{$tProductUser}`.`status` = {$productUserStatus}";
            if($onlyAdmin)
            {
                $sql .= " AND `{$tProductUser}`.`role` = " . ProductUser::ROLE_ADMIN;
            }
            $products = Yii::app()->db->createCommand($sql)->queryColumn();
            if($returnObjects)
            {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('id', $products);
                $products = Product::model()->findAll($criteria);
            }
        }
        return $products;
    }

    public function getAllUserList($withInActive = false)
    {
        $allUserList = array();
        $attr = array();
        if($withInActive == false)
        {
            $attr['status'] = User::STATUS_AVAILABLE;
        }
        $users = User::model()->findAllByAttributes($attr);
        foreach($users as $user)
        {
            if(!empty($user->realname))
            {
                $allUserList[$user->id] = PinYin::getPinYin($user->realname) . ' ' . $user->realname . ' (' . $user->username . ')';
            }
            else
            {
                $allUserList[$user->id] = $user->username;
            }
        }
        asort($allUserList);
        return $allUserList;
    }

    public static function getAllAdmin()
    {
        $admins = array();
        $sql = 'SELECT username FROM `' . User::model()->tableName()
                . '` WHERE `role` = ' . User::ROLE_ADMIN . ' AND `status` = ' . User::STATUS_AVAILABLE
                . ' GROUP BY username';
        $admins = Yii::app()->db->createCommand($sql)->queryColumn();
        return $admins;
    }

    public function getRoleFormField()
    {
        $field = $this->getRoleText();
        if(Yii::app()->user->isAdmin())
        {
            $field = CHtml::activeRadioButtonList($this, 'role',
                            $this->getRoleOptions(),
                            array('separator' => '&nbsp;&nbsp;', 'style' => 'width: auto;'));
        }
        return $field;
    }

    public function updateStatus()
    {
        $productUser = ProductUser::model()->findByAttributes(array('user_id' => $this->id, 'status' => ProductUser::STATUS_AVAILABLE));
        if($productUser === null && $this->role == self::ROLE_USER)
        {
            $this->status = self::STATUS_DISABLE;
        }
        else
        {
            $this->status = self::STATUS_AVAILABLE;
        }
        $this->save();
    }

    public function search($pageSize, $condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            // TODO: do not use the name field for searching
            $this->username = $condition;
        }

        $criteria->select = 'id, username, realname, role, status, update_time';
        $criteria->compare('id', $this->username, true, 'OR');
        $criteria->compare('username', $this->username, true, 'OR');
        $criteria->compare('realname', $this->username, true, 'OR');

        return new CActiveDataProvider('User', array(
                    'criteria' => $criteria,
                    'pagination' => array(
                        'pageSize' => $pageSize
                    ),
                    'sort' => array(
                        'defaultOrder' => "create_time DESC"
                    ),
                ));
    }

    /**
     * Encrypt password.
     * @param string $password password
     * @return string encrypted password.
     */
    public static function encrypt($password)
    {
        return md5($password . self::SALT);
    }
}
?>