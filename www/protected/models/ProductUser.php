<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
/**
 * Product and User relation class.
 *
 * @package application.models
 */
class ProductUser extends Model
{
    /**
     * @var integer Product id. 
     */
    public $product_id;
    /**
     * @var integer User id. 
     */
    public $user_id;
    /**
     * @var integer Product user's role. 
     */
    public $role;
    /**
     * @var integer Product user's status. 
     */
    public $status;
    /**
     * @var integer Creator's id. 
     */
    public $created_by;
    /**
     * @var integer Updator's id. 
     */
    public $updated_by;
    /**
     * @var string Create time. 
     */
    public $create_time;
    /**
     * @var string Update time. 
     */
    public $update_time;

    /**
     * Normal user.
     */
    const ROLE_USER = 1;
    /**
     * Administrator.
     */
    const ROLE_ADMIN = 2;

    /**
     * Disable status.
     */
    const STATUS_DISABLE = 0;
    /**
     * Pending status.
     */
    const STATUS_PENDING = 1;
    /**
     * Available status.
     */
    const STATUS_AVAILABLE = 2;

    /**
     * Get a instance of ProductUser class.
     * @param string $className Class name.
     * @return ProductUser A instance of ProductUser.
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     *  Define ProductUser's table name for database.
     * @return string Table name of ProductUser class.
     */
    public function tableName()
    {
        return '{{product_user}}';
    }

    /**
     * Define Productuser's primary key for database.
     * @return array Primary key of ProductUser class.
     */
    public function primaryKey()
    {
        return array('product_id', 'user_id');
    }

    /**
     * Define ProductUser's rule for validator.
     * @return array Rules of Productuser class.
     */
    public function rules()
    {
        return array(
            array('product_id, user_id', 'required'),
            array('role, status, created_by, updated_by', 'numerical', 'integerOnly' => true),
            array('product_id, user_id, role, status', 'safe'),
        );
    }

    /**
     * Attach timestamp behavior to Productuser class.
     * @return array A definng array of CTimestampBehavior.
     */
    public function behaviors()
    {
        return array(
            'CTimestampBehavior' => array(
                'class' => 'zii.behaviors.CTimestampBehavior',
                'timestampExpression' => new CDbExpression('NOW()')
            )
        );
    }

    /**
     * Raise after validate event, set creator and updator.
     * @return boolean After validate result. 
     */
    public function afterValidate()
    {
        if(!Yii::app()->user->isGuest)
        {
            if($this->isNewRecord)
            {
                $this->created_by = Yii::app()->user->id;
            }
            $this->updated_by = Yii::app()->user->id;
        }
        return parent::afterValidate();
    }

    /**
     * Return product user status'es options.
     * @return array Product product user's options.
     */
    public function getStatusOpts()
    {
        return array(
            self::STATUS_DISABLE => Yii::t('ProductUser', 'Disable'),
            self::STATUS_PENDING => Yii::t('ProductUser', 'Pending'),
            self::STATUS_AVAILABLE => Yii::t('ProductUser', 'Available')
        );
    }

    /**
     * Return product user status'es text. 
     * @return string Product user status'es test.
     */
    public function getStatusText()
    {
        $statusOpts = $this->getRoleOpts();
        return isset($statusOpts[$this->status]) ? $statusOpts[$this->role] : Yii::t('ProductUser',
                        'Unknown status({status})',
                        array('{status}' => $this->status));
    }
    
    public static function add($product, $user)
    {
        $productUser = ProductUser::model()->findByAttributes(array('user_id' => $user->id, 'product_id' => $product->id));
        if(null === $productUser)
        {
            $productUser = new ProductUser();
            $productUser->product_id = $product->id;
            $productUser->user_id = $user->id;
            $productUser->status = self::STATUS_DISABLE;
            $productUser->save();
        }
        return $productUser;
    }
}
?>
