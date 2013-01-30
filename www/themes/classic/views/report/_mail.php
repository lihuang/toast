<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <style type="text/css">
<?php echo file_get_contents(dirname(__FILE__) . '/../../assets/css/mail.css'); ?>
        </style>
    </head>
    <body>
        <div style="width: 100%; text-align: center; padding: 5px; font-size: 14px; margin-bottom: 10px;">
            <a href="<?php
echo Yii::app()->request->getBaseUrl(true)
 . '/report/index/date/' . $report->date
 . '/task_type/' . $report->task_type
 . '/product_id/' . $report->product_id;
?>">
                   <?php 
                   if($onlyfail)
                       echo Yii::t('VReport', 'Failed Report');
                   echo $report->getTitle(); 
                   ?>
            </a>
        </div>
        <table class="items">
            <tr>
                <th><?php echo $report->getAttributeLabel('task_name'); ?></th>
                <th><? echo $report->getAttributeLabel('project_name'); ?></th>
                <th><? echo $report->getAttributeLabel('case_pass_amount'); ?></th>
                <th><? echo $report->getAttributeLabel('case_fail_amount'); ?></th>
                <th><? echo Yii::t('VReport', 'Case Passed Percent'); ?></th>
                <th><? echo $report->getAttributeLabel('status'); ?></th>
                <th><? echo $report->getAttributeLabel('result'); ?></th>
                <th><? echo $report->getAttributeLabel('responsible_realname'); ?></th>
            </tr>
            <?php
            $module = '';
            foreach ($reports as $key => $r) {
                $class = $key % 2 ? 'even' : 'odd';
                if ($module != $r->module_id) {
                    echo CHtml::tag('tr');
                    echo CHtml::tag('td', array('colspan' => 9, 'style' => 'font-weight: bold; text-align: left'), CHtml::tag('label', array('for' => 'module_id' . $r->module_id), $r->module_name));
                    $module = $r->module_id;
                }
                if ($r->module_id != $report->module_id && !empty($report->module_id)) {
                    continue;
                }
                echo CHtml::tag('tr', array('class' => $class));
                echo CHtml::tag('td', array(), CHtml::link(CHtml::encode($r->task_name), Yii::app()->getBaseUrl(true) . '/task/view/id/' . $r->task_id . '/runID/' . $r->task_run_id, array('target' => '_blank')));
                echo CHtml::tag('td', array(), $r->project_name);
                echo CHtml::tag('td', array('style' => 'text-align: center', 'class' => 'passed'), $r->case_pass_amount);
                echo CHtml::tag('td', array('style' => 'text-align: center', 'class' => 'failed'), $r->case_fail_amount);
                echo CHtml::tag('td', array('style' => 'text-align: center'), $r->getPassedPercent());
                echo CHtml::tag('td', array('style' => 'text-align: center'), $r->getStatusText());
                echo CHtml::tag('td', array('style' => 'text-align: center', 'class' => $r->getResultStyle()), $r->getResultText());
                echo CHtml::tag('td', array('style' => 'text-align: center'), $r->responsible_realname);
            }
            ?>
        </table>
    </body>
</html>