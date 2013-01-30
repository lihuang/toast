<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class ProductController extends Controller
{
    public function filters()
    {
        return array(
            'accessControl'
        );
    }

    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('getProjectTree', 'getProjectOpts'),
                'users' => array('@')
            ),
            array(
                'deny',
                'users' => array('*')
            )
        );
    }

    public function actionGetProjectTree()
    {
        $product = Yii::app()->user->getCurrentProduct(true);
        if(isset($_REQUEST['productid']))
        {
            $product = Product::model()->findByPk($_REQUEST['productid']);
        }
        if(null != $product)
        {
            echo $product->getProjectTree();
        }
        else
        {
            echo '<ul>Error</ul>';
        }
    }

    public function actionGetProjectOpts()
    {
        if(Yii::app()->request->isAjaxRequest && isset($_GET['productid']))
        {
            $productId = $_GET['productid'];
            $product = Product::model()->findByPk($productId);
            $projects = Project::model()->findAllByAttributes(array('product_id' => $productId));
            $html = '';
            foreach($projects as $project)
            {
                $html .= '<option value="' . $project->id . '">' . $project->name . '</option>';
            }
            echo $html;
        }
    }
}
?>