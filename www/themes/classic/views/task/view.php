<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/task.css?ver=1" />
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/lightbox.css?ver=1"/>
<div class="content">
    <div class="sub-nav">
        <?php
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'path-nav',
            'items' => $vTask->getNavItems($vTaskRun),
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
        ));
        ?>
    </div>
    <div class="main-detail">
        <div class="button-actions clearfix">
            <?php
            echo $vTask->getBtnList($vTaskRun);
            echo CHtml::activeHiddenField($vTask, 'id', array('id' => 'task-id'));
            ?>
        </div>
        <div class="follow detail-info block basic-info">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'type', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTask->getTypeText());
                echo CHtml::activeLabel($vTask, 'name', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span5'), CHtml::encode($vTask->name));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'responsible_realname', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTask->responsible_realname);
                echo CHtml::activeLabel($vTask, 'project_path', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTask->project_path);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'cron_time', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTask->cron_time);
                echo CHtml::activeLabel($vTask, 'exclusive', array('class' => 'span1', 'title' => Yii::t('Task', 'Exclusive Tip')));
                echo CHtml::tag('span', array('class' => 'span1'), $vTask->getExclusiveText());
                echo CHtml::tag('div', array('class' => 'span2'), CHtml::activeLabel($vTask, 'wait_machine', 
                        array('style' => 'float: left;margin-right: 40px', 'title' => Yii::t('Task', 'Wait Machine Tip'))) 
                        . $vTask->getWaitMachineText());
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'build', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span9'), CHtml::encode(trim($vTask->build, ',')));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'svn_url', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span9'), CHtml::link($vTask->svn_url, $vTask->svn_url, array('target' => '_blank')));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'created_by', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTask->created_by_realname . '&nbsp;&nbsp;' . $vTask->create_time);
                echo CHtml::activeLabel($vTask, 'updated_by', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTask->updated_by_realname . '&nbsp;&nbsp;' . $vTask->update_time);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTask, 'report_to', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span9', 'style' => 'word-break: break-all'), $vTask->getReportToText());
                ?>
            </div>
            <?php 
            if($vTaskRun):
                list($case_total_amount, $case_passed_amount, $case_failed_amount, $case_notrun_amount) = $vTaskRun->getCaseAmount(); 
            ?>
            <div class="run-summary">
                <table>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Status');?></th>
                        <td class="run-status summary-status">
                            <?php 
                            if($vTaskRun && !$vTaskRun->hasCompleted())
                            {
                                echo CHtml::hiddenField('task-running-flag', '1');
                            }
                            echo $vTaskRun->getStatusText(); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Total Amount');?></th>
                        <td id="view-allrun-case" class="total" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => ''));?>">
                            <?php echo $case_total_amount;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Passed Amount');?></th>
                        <td id="view-allrun-passed-case" class="passed" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Passed'));?>">
                            <?php echo $case_passed_amount;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Failed Amount');?></th>
                        <td id="view-allrun-failed-case" class="failed" style="cursor: pointer;" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Failed'));?>">
                            <?php echo $case_failed_amount;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Not Execute Amonut');?></th>
                        <td id="view-allrun-null-case" class="null" style="cursor: pointer;" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Skipped&Blocked'));?>">
                            <?php echo $case_notrun_amount?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            <div class="task-detail" <?php if($vTaskRun) echo 'style="display: none"'; ?>>
                <div class="stage">
                <?php
                $stage_num = 0;
                foreach($vTask->jobs as $key => $job)
                {
                    if($job->stage_num > $stage_num)
                    {
                        echo '</div><div class="stage">';
                        $stage_num++;
                    }
                    echo '<div class="job">';
                    echo $this->renderPartial('jobView', array('job' => $job, 'jobNum' => $key, 'editable' => false));
                    echo '</div>';
                }
                ?>
                </div>
            </div>
        </div>
        <div class="update-history follow detail-info" style="display: none;">
            <div class="detail-title"><?php echo Yii::t('TOAST', 'Update History') ?></div>
            <div class="history-content">
                <div style="text-align: center;">
                    <? echo CHtml::image(Yii::app()->theme->baseUrl . '/assets/images/loading.gif'); ?>
                </div>
            </div>
        </div>
        <?php
        if($vTaskRun)
        {
            $blockView = $this->createWidget('zii.widgets.CListView', array(
                'id' => 'vruns',
                'dataProvider' => $vRunProvider,
                'ajaxUpdate' => 'recentruns',
                'afterAjaxUpdate' => 'js:function(){flagRun();}',
                'pager' => array(
                    'class' => 'LinkPager',
                    'summary' => true,
                    'maxButtonCount' => 5,
                ),
                'htmlOptions' => array('class' => 'widget-view', 'style' => 'overflow:auto'),
                'itemsCssClass' => 'run-items',
                'pagerCssClass' => 'pager run-pager',
                'template'=>'{items}',
                'itemView' => '_block',
            ));
            $pagination = $vRunProvider->getPagination();
            $currentPage = $pagination->getCurrentPage() + 1;
            $pageCount = $pagination->getPageCount();
            $itemCount = $pagination->getItemCount();
            $pageSize = $pagination->getPageSize();
            
            $start = ($currentPage - 1) * $pageSize + 1;
            $count = min($pageSize, ($itemCount - $start + 1));
            $end = $start + $count - 1;
            echo '<div id="recentruns" class="clearfix block follow runs-block">';
            echo '<div class="page-summary">' . Yii::t('Run', 'Recent Runs') . " ($start-$end of $itemCount) </div>";
            echo '<a class="pagination previous' . ($currentPage==1?' disabled':'') . '" href="/task/view/id/' . $vTask->id . '/VTaskRun_page/' . ($currentPage==1?1:$currentPage-1) . '">«</a>';
            echo '<a class="pagination next' . ($currentPage==$pageCount?' disabled':'') . '" href="/task/view/id/' . $vTask->id . '/VTaskRun_page/' . ($currentPage==$pageCount?$currentPage:$currentPage+1) . '">»</a>';
            $blockView->run();
            echo '</div>';
            echo '<div style="position: relative"><div class="current-arrow"></div></div>';
            echo CHtml::activeHiddenField($vTaskRun, 'id');
            
            echo '<div class="detail-info">';
            echo '<div class="detail-title">' . $vTaskRun->getTitle() . '</div>';
            echo $this->renderPartial('/run/jobruns', array('vTaskRun' => $vTaskRun)); 
            echo '</div>';
        }
        ?>
    </div>
</div>
<style type="text/css">
.row-fluid {margin-bottom: 3px}
.pagination {font-size: 18px;}
.basic-info label {line-height: 19px}
</style>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lightbox.js?ver=1"></script>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/task.view.js?ver=12"></script>
