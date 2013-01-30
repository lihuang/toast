<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class ProductAccessFilter extends CFilter
{
    protected function preFilter($filterChain)
    {
        $accessProductIds = Yii::app()->user->getProductIds();
        // hard code for product id key not unified
        $productId = Yii::app()->request->getParam('product_id');
        if(!isset($productId))
        {
            $modelName = ucfirst($filterChain->controller->id);
            $model = Yii::app()->request->getParam($modelName);
            $model = Yii::app()->request->getParam('V' . $modelName, $model);
            if(isset($model['product_id']))
            {
                $productId = $model['product_id'];
            }
            else
            {
                $productId = Yii::app()->user->getCurrentProduct();
            }
        }
        
        if(isset($productId))
        {
            if(!in_array($productId, $accessProductIds))
            {
                $filterChain->controller->redirect(Yii::app()->getBaseUrl()
                        . '/site/apply/product_id/' . $productId);
            }
        }
        return true;
    }
}
?>