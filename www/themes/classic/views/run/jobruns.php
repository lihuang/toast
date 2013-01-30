<?php
echo $vTaskRun->getDevLog();
$stageNum =0;
echo '<div class="stage">';
foreach($vTaskRun->vcommandruns as $vCommandRun)
{
    if($vCommandRun->stage_num > $stageNum)
    {
        echo '</div><div class="stage">';
        $stageNum++;
    }
    echo '<div class="job">';
    echo CHtml::tag('div', array('class' => 'result result-' . $vCommandRun->getResultText()), 
            $vCommandRun->getResultText());
    echo '<div class="row-fluid">';
    // display command or test suite
    if(isset($vCommandRun->command))
    {
        echo CHtml::activeLabel($vCommandRun, 'command_name', array('class' => 'span2'));
        echo CHtml::link(CHtml::encode($vCommandRun->command->name), 
                array('command/view', 'id' => $vCommandRun->command_id), 
                array('target' => '_blank', 'title' => $vCommandRun->command->command, 'class' => 'span10 ellipsis'));   
    }
    else if(isset($vCommandRun->job) && Job::TYPE_CASE == $vCommandRun->job->type)
    {
        echo CHtml::label(Yii::t('Job', 'Test Case Id'), 'test-case-id', array('class' => 'span2'));
        echo '<div class="span10">';
        if($vCommandRun->hasCompleted())
        {
            foreach($vCommandRun->caseresults as $caseresult)
            {
                echo CHtml::link($caseresult->test_case_id, array('case/view', 'id' => $caseresult->test_case_id), array('target' => '_blank'));
                echo '&nbsp;';
            }
        }
        else
        {
            echo Yii::t('Job', 'Running');
        }
        echo '</div>';
    }
    echo CHtml::activeHiddenField($vCommandRun, 'id');
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::activeLabel($vCommandRun, 'sudoer', array('class' => 'span2'));
    echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->sudoer);
    echo CHtml::activeLabel($vCommandRun, 'machine_name', array('class' => 'span2'));
    echo CHtml::link($vCommandRun->machine_name, 
        array('machine/view', 'id' => $vCommandRun->machine_id), 
        array('target' => '_blank', 'class' => 'span4')); 
    echo '</div>';
    echo '<div class="row-fluid">';
    // display command parser
    if(isset($vCommandRun->command))
    {
        echo CHtml::activeLabel($vCommandRun->vcommand, 'parser_id', array('class' => 'span2'));
        echo CHtml::tag('span', array('title' => $vCommandRun->command->getParsers(FALSE), 'class' => 'span4', 
            'style' => 'white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'), $vCommandRun->command->getParsers(FALSE));
        echo CHtml::activeLabel($vCommandRun, 'timeout', array('class' => 'span2'));
        echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->timeout . '&nbsp;&nbsp;' . Yii::t('Run', 'Minutes'));
    }
    else
    {
        echo CHtml::activeLabel($vCommandRun, 'timeout', array('class' => 'span2'));
        echo CHtml::tag('span', array('class' => 'span10'), $vCommandRun->timeout . '&nbsp;&nbsp;' . Yii::t('Run', 'Minutes'));
    }
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::activeLabel($vCommandRun, 'start_time', array('class' => 'span2'));
    echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->start_time);
    echo CHtml::activeLabel($vCommandRun, 'stop_time', array('class' => 'span2'));
    echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->stop_time);
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::activeLabel($vCommandRun, 'status', array('class' => 'span2'));
    echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->getStatusText());
    echo CHtml::tag('label', array('class' => 'span2'), Yii::t('Run', 'Case Rate Info'));
    echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->getCaseRatioHtml());
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::tag('label', array('class' => 'span2'), Yii::t('Run', 'Code Line Coverage'));
    echo CHtml::tag('span', array('class' => 'span10'), $vCommandRun->getLineCoverHtml());
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::tag('label', array('class' => 'span2'), Yii::t('Run', 'Code Branch Coverage'));
    echo CHtml::tag('span', array('class' => 'span10'), $vCommandRun->getBranchCoverHtml());
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::activeLabel($vCommandRun, 'build', array('class' => 'span2'));
    echo CHtml::tag('span', array('class' => 'span10'), $vCommandRun->build);
    echo '</div>';
    echo '<div class="row-fluid">';
    echo CHtml::activeLabel($vCommandRun, 'run_times', array('class' => 'span2'));
    echo CHtml::tag('span', array('class' => 'span4'), $vCommandRun->run_times);
    echo '<div class="span6">';
    // dispaly case result view link
    if (($vCommandRun->hasCompleted() 
            && isset($vCommandRun->command) 
            && $vCommandRun->command->getParsers()) 
            || ($vCommandRun->hasCompleted() && (Job::TYPE_CASE == $vCommandRun->job->type)))
    {
        echo CHtml::link(Yii::t('Run', 'Case Detail'), 'javascript:;', 
                array('class' => 'view_detail', 'id' => 'detail-' . $vCommandRun->id));
        echo '&nbsp;&nbsp;';
    }
    echo CHtml::link(Yii::t('Run', 'View Output'), 'javascript:;', 
            array('class' => 'view_output', 'id' => 'output-' . $vCommandRun->id));
    echo '&nbsp;&nbsp;';
    echo CHtml::link(Yii::t('Run', 'Download Output'), 
            Yii::app()->baseUrl . '/run/getoutput/id/' . $vCommandRun->id, array('target' => '_blank'));
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';
?>
<script type="text/javascript">
$(document).ready(function(){
    var viewDetail = function(taskRunId, commandRunId, option) {
        var url = getRootPath() + "/run/case/commandrun/" + commandRunId;
        if(option)
            url += "/" + option;
        window.open(url, '', "width=1100, height=600, top=100, left=100, resizable=yes, scrollbars=1");
    }
    $("a.view_passed_detail").click(function(){
        var commandRunId = $(this).parents("div.job").find("#VCommandRun_id").val();
        viewDetail(null, commandRunId, 'filter/<?php echo CaseResult::RESULT_PASSED?>');
    })
    $("a.view_failed_detail").click(function(){
        var commandRunId = $(this).parents("div.job").find("#VCommandRun_id").val();
        viewDetail(null, commandRunId, 'filter/<?php echo CaseResult::RESULT_FAILED?>');
    })
    $("a.view_skipped_detail").click(function(){
        var commandRunId = $(this).parents("div.job").find("#VCommandRun_id").val();
        viewDetail(null, commandRunId, 'filter/<?php echo CaseResult::RESULT_SKIPPED?>');
    })
    $("a.view_blocked_detail").click(function(){
        var commandRunId = $(this).parents("div.job").find("#VCommandRun_id").val();
        viewDetail(null, commandRunId, 'filter/<?php echo CaseResult::RESULT_BLOCKED?>');
    })
    $("a.view_detail").click(function(){
        var commandRunId = $(this).parents("div.job").find("#VCommandRun_id").val();
        viewDetail(null, commandRunId);
    })
    $("a.view_output").click(function(){
        window.open(toast.openRunOutput + "/id/" + this.id.split("-")[1], '', 
            "width=900, height=500, top=100, left=100, resizable=yes, scrollbars=1");
    })
});
</script>