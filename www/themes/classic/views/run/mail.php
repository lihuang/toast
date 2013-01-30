<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <style type="text/css">
         <?php 
         echo file_get_contents(dirname(__FILE__) . '/../../assets/css/mail.css'); 
         
          // fetch case amount
         list($all_amount, $passed_amount, $failed_amount, $notrun_amount) = $vRun->getCaseAmount();
         ?>
        </style>
    </head>
    <body>
       <?php echo CHtml::tag('h2', array('class' => $vRun->getResultStyle()), $vRun->getResultText()); ?>
       <table class="detail-view">
           <tr class="odd">
               <th><?php echo CHtml::activeLabel($vRun, 'name')?></th>
               <td><?php echo CHtml::link(CHtml::encode($vRun->task_name),
                       $this->createAbsoluteUrl('task/view') . '/id/' . $vRun->task_id . '/runid/' . $vRun->id); ?></td>
           </tr>
           <tr class="even">
               <th><?php echo Yii::t('Task', 'Type'); ?></th>
               <td><?php echo $vRun->task->getTypeText(); ?></td>
           </tr>
           <tr class="odd">
               <th><?php echo CHtml::activeLabel($vRun, 'status'); ?></th>
               <td><?php echo $vRun->getStatusText(); ?></td>
           </tr>
           <tr class="even">
               <th><?php echo Yii::t('Task', 'Build'); ?></th>
               <td>
               <?php
               $build = trim($vRun->task->build,',');
               echo CHtml::encode($build);
               ?>
               </td>
           </tr>
           <tr class="odd">
               <th style="vertical-align: middle"><?php echo Yii::t('Run', 'Dev Log'); ?></th>
               <td><?php echo $vRun->getDevLog(); ?></td>
           </tr>
           <tr class="even">
               <th><?php echo Yii::t('Run', 'Case Total Amount'); ?></th>
               <td><?php echo $all_amount; ?></td>
           </tr>
           <tr class="odd">
               <th><?php echo Yii::t('Run', 'Case Passed Amount'); ?></th>
               <td><?php echo $passed_amount; ?></td>
           </tr>
           <tr class="even">
               <th><?php echo Yii::t('Run', 'Case Failed Amount'); ?></th>
               <td><?php echo $failed_amount; ?></td>
           </tr>
           <tr class="odd">
               <th><?php echo Yii::t('Run', 'Case Not Execute Amonut'); ?></th>
               <td><?php echo $notrun_amount; ?></td>
           </tr>
           <tr class="even">
               <th><?php echo CHtml::activeLabel($vRun, 'start_time')?></th>
               <td><?php echo $vRun->start_time; ?></td>
           </tr>
           <tr class="odd">
               <th><?php echo CHtml::activeLabel($vRun, 'stop_time')?></th>
               <td><?php echo $vRun->stop_time; ?></td>
           </tr>
        </table>
        <?php
        foreach($vRun->vcommandruns as $run)
        {
            echo CHtml::tag('br');
            echo '<table class="detail-view">';
            echo CHtml::tag('tr', array('class' => strtolower($run->getResultText())));
            if(isset($run->command))
            {
                echo CHtml::tag('th', array('colspan' => 2, 'style' => 'text-align: center'), $run->command->name);
            }
            else if(isset($run->suite))
            {
                echo CHtml::tag('th', array('colspan' => 2, 'style' => 'text-align: center'), $run->suite->name);
            }
            echo CHtml::tag('tr', array('class' => 'odd'));
            echo CHtml::tag('th', array(), $run->getAttributeLabel('status'));
            echo CHtml::tag('td', array(), $run->getStatusText());
            echo CHtml::tag('tr', array('class' => 'odd'));
            echo CHtml::tag('th', array(), $run->getAttributeLabel('machine_name'));
            echo CHtml::tag('td', array(), CHtml::link($run->machine_name, Yii::app()->request->getBaseUrl(true)
                       . '/machine/view/id/' . $run->machine_id));
            echo CHtml::tag('tr', array('class' => 'even'));
            echo CHtml::tag('th', array(), Yii::t('Run', 'Case Total Amount'));
            echo CHtml::tag('td', array(), $run->case_total_amount);
            echo CHtml::tag('tr', array('class' => 'odd'));
            echo CHtml::tag('th', array(), Yii::t('Run', 'Case Failed Amount'));
            echo CHtml::tag('td', array(), $run->case_fail_amount);
            echo CHtml::tag('tr', array('class' => 'even'));
            echo CHtml::tag('th', array(),Yii::t('Run', 'Code Line Coverage'));
            $rate = $run->getLineCoverRate();
            if ($rate >= 0.75)
                $color = '#89A54E'; 
            elseif ($rate >= 0.5)
                $color = '#E99D12';
            else
                $color = '#AA4643';
            $rate = 'NA';
            if (is_numeric($run->cc_line_hit) && is_numeric($run->cc_line_total)
                    && ($run->cc_line_total != 0))
            {
                $rate  = Yii::t('Run', 'Line Hit'). '/'. Yii::t('Run', 'Line Total'). ': ';
                $rate .= $run->cc_line_hit . '/'. $run->cc_line_total . '    ';
                $rate .= Yii::t('Run', 'Line Hit Rate'). ': ';
                $rate .= round($run->getLineCoverRate()*100, 1) . '%';
            }
            echo CHtml::tag('td', array('style' => 'color: ' . $color), $rate);
            echo CHtml::tag('tr', array('class' => 'odd'));
            echo CHtml::tag('th', array(),Yii::t('Run', 'Code Branch Coverage'));
            echo CHtml::tag('td', array(), $run->getBranchCoverHtml());
            echo CHtml::tag('tr', array('class' => 'even'));
            echo CHtml::tag('th', array(), CHtml::activeLabel($run, 'cc_result'));
            $cc_result = 'NA';
            if ($run->cc_result !== '' && $run->cc_result !== 'NA')
                $cc_result = CHtml::link($run->cc_result, $run->cc_result);
            echo CHtml::tag('td', array(), $cc_result);
            echo CHtml::tag('tr', array('class' => 'odd'));
            echo CHtml::tag('th', array(), Yii::t('Run', 'Build'));
            echo CHtml::tag('td', array(), $run->build);
            echo CHtml::tag('tr', array('class' => 'even'));
            echo CHtml::tag('th', array(), Yii::t('Run', 'Run Time'));
            echo CHtml::tag('td', array(), CHtml::encode($run->run_time));
            echo CHtml::tag('tr', array('class' => 'odd'));
            echo CHtml::tag('th', array(), Yii::t('Run', 'Output'));
            echo CHtml::tag('td', array(), CHtml::link(Yii::t('Run', 'Download Output'),
                    Yii::app()->getBaseUrl(true) . '/run/getoutput/id/' . $run->id));
            echo '</table>';
        }
        foreach($vRun->vcommandruns as $run)
        {
            $failedResults = $run->getFailedResults();
            if(count($failedResults) > 0)
            {
                echo '<br />';
                $name = '';
                if(isset($run->command))
                {
                    $name = $run->command->name;
                }
                else if(isset($run->suite))
                {
                    $name = $run->suite->name;
                }
                echo CHtml::tag('div', array('class' => 'summary'), $name . ': Total ' . count($failedResults) . ' failed result(s).');
                echo '<table class="items">';
                echo CHtml::tag('tr');
                echo CHtml::tag('th', array(), CaseResult::model()->getAttributeLabel('test_case_id'));
                echo CHtml::tag('th', array(), CaseResult::model()->getAttributeLabel('case_name'));
                echo CHtml::tag('th', array(), CaseResult::model()->getAttributeLabel('case_result'));
                echo CHtml::tag('th', array(), CaseResult::model()->getAttributeLabel('case_info')); 
                foreach($failedResults as $idx => $result)
                {
                    echo CHtml::tag('tr', array('class' => $idx % 2 ? 'even' : 'odd'));
                    echo CHtml::tag('td', array('style' => 'text-aling: center'), CHtml::link($result->test_case_id, Yii::app()->getBaseUrl(true) . "/case/view/id/" . $result->test_case_id));
                    echo CHtml::tag('td', array(), CHtml::encode($result->case_name));
                    echo CHtml::tag('td', array('style' => 'text-aling: center', 
                        'class' => $result->getResultStyle()), $result->getResultText());
                    echo CHtml::tag('td', array('style' => 'word-break: break-all'), nl2br(CHtml::encode($result->case_info)));
                }
                echo '</table>';
            }
        }
        ?>
    </body>
</html>