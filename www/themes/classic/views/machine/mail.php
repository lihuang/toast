<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <style type="text/css">
            <?php echo file_get_contents(dirname(__FILE__) . '/../../assets/css/mail.css'); ?>
        </style>
    </head>
    <body>
       <table class="detail-view">
            <tr class="odd">
                <th><?php echo CHtml::activeLabel($vMachine, 'name'); ?></th>
                <td>
                    <?php echo CHtml::link($vMachine->name, array('view', 'id' => $vMachine->id)); ?>
                </td>
            </tr>
            <tr class="even">
                <th><?php echo CHtml::activeLabel($vMachine, 'type'); ?></th>
                <td><?php echo $vMachine->getTypeText(); ?></td>
            </tr>
            <tr class="odd">
                <th><?php echo CHtml::activeLabel($vMachine, 'status'); ?></th>
                <td class="<?php echo $vMachine->getStatusStyle(); ?>"><?php echo $vMachine->getStatusText(); ?></td>
            </tr>
            <tr class="even">
                <th><?php echo CHtml::activeLabel($vMachine, 'product_id'); ?></th>
                <td><?php echo $vMachine->product_name; ?></td>
            </tr>
            <tr class="odd">    
                <th><?php echo CHtml::activeLabel($vMachine, 'agent_version'); ?></th>
                <td><?php echo $vMachine->agent_version; ?></td>
            </tr>
            <tr class="even">    
                <th><?php echo CHtml::activeLabel($vMachine, 'update_time'); ?></th>
                <td><?php echo $vMachine->update_time; ?></td>
            </tr>
            <tr class="odd">
                <th><?php echo CHtml::activeLabel($vMachine, 'desc_info'); ?></th>
                <td><?php echo $vMachine->desc_info; ?></td>
            </tr>
        </table>
        <h3><?php echo Yii::t('Machine', 'Tasks may be affected');?></h3>
        <table class="items">
            <tr>
                <th><?php echo Yii::t('Task', 'Id');?></th>
                <th><?php echo Yii::t('Task', 'Name');?></th>
                <th><?php echo Yii::t('Task', 'Type');?></th>
                <th><?php echo Yii::t('Task', 'Responsible');?></th>
                <th><?php echo Yii::t('Task', 'Product Id');?></th>
                <th><?php echo Yii::t('Task', 'Project Id');?></th>
            </tr>
            <?php
            $vTasks = VTask::model()->getTasksByMachine($vMachine->id, 100)->getData();
            foreach ($vTasks as $key => $vTask) {
                $class = $key % 2 ? 'even' : 'odd';
                echo CHtml::tag('tr', array('class' => $class));
                echo CHtml::tag('td', array('style' => 'text-align: center'), $vTask->id);
                echo CHtml::tag('td', array(), CHtml::link($vTask->name, Yii::app()->getBaseUrl(true) . '/task/view/id/' . $vTask->id, array('target' => '_blank')));
                echo CHtml::tag('td', array('style' => 'text-align: center'), $vTask->getTypeText());
                echo CHtml::tag('td', array('style' => 'text-align: center'), $vTask->responsible_realname);
                echo CHtml::tag('td', array('style' => 'text-align: center'), $vTask->product_name);
                echo CHtml::tag('td', array('style' => 'text-align: center'), $vTask->project_name);
            }
            ?>
        </table>
    </body>
</html>