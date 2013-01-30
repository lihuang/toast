<?php
// set hidden fields
echo CHtml::hiddenField('Jobs[' . $jobNum . '][stage_num]', $job->stage_num, array('class' => 'stage-num'));
echo CHtml::hiddenField('Jobs[' . $jobNum . '][job_num]', $jobNum, array('class' => 'job-num'));
echo CHtml::hiddenField('Jobs[' . $jobNum . '][id]', $job->id);
echo CHtml::hiddenField('Jobs[' . $jobNum . '][type]', $job->type);
if(Job::TYPE_COMMAND == $job->type)
{
    echo CHtml::hiddenField('Jobs[' . $jobNum . '][command_id]', $job->command_id);
    echo CHtml::hiddenField('command-' . $jobNum . '-name', $job->command->name);
}
else
{
    foreach($job->vtestcases as $testcase)
    {
        echo CHtml::hiddenField('Jobs[' . $jobNum . '][test_case_ids][]', $testcase->id, array(
            'id' => 'Jobs_' . $jobNum . '_test_case_ids_' . $testcase->id));
        echo CHtml::hiddenField('Jobs[' . $jobNum . '][test_case_names][]', '#' . $testcase->id . ' ' .  
        $testcase->name . ' @ ' . $testcase->created_by_realname, array(
            'id' => 'Jobs_' . $jobNum . '_test_case_names_' . $testcase->id));
        echo CHtml::hiddenField('Jobs[' . $jobNum . '][test_case_urls][]', $testcase->code_url, array(
            'id' => 'Jobs_' . $jobNum . '_test_case_urls_' . $testcase->id));
    }
}
echo CHtml::hiddenField('Jobs[' . $jobNum . '][machine_id]', $job->machine_id);
echo CHtml::hiddenField('Jobs[' . $jobNum . '][timeout]', $job->timeout);
echo CHtml::hiddenField('Jobs[' . $jobNum . '][sudoer]', $job->sudoer);
echo CHtml::hiddenField('Jobs[' . $jobNum . '][crucial]', $job->crucial);
echo CHtml::hiddenField('Jobs[' . $jobNum . '][failed_repeat]', $job->failed_repeat);
$machineLabel = $job->vmachine->name;
if($job->vmachine->responsible_realname)
{
   $machineLabel =  '[' . $job->vmachine->responsible_realname . '] ' . $machineLabel;
}
if($job->vmachine->ip)
{
    $machineLabel .= ' (' . $job->vmachine->ip . ')';
}
echo CHtml::hiddenField('machine-' . $jobNum . '-name', $machineLabel);

// display job
echo '<div class="row-fluid">';
if(Job::TYPE_COMMAND == $job->type)
{
    echo CHtml::activeLabel($job, 'command_id', array('class' => 'span2'));
    echo CHtml::link(CHtml::encode($job->command->name), 
        array('command/view', 'id' => $job->command_id), 
        array('target' => '_blank', 'title' => $job->command->command, 'class' => 'span10 ellipsis'));   
}
else
{
    echo CHtml::label(Yii::t('Job', 'Test Case Id'), 'test-cases', array('class' => 'span2'));
    echo '<div class="span10">';
    foreach($job->vtestcases as $testcase)
    {
        echo CHtml::link($testcase->id, array('case/view', 'id' => $testcase->id), array('target' => '_blank'));
        echo '&nbsp;';
    }
    echo '</div>';
}
echo '</div>';
echo '<div class="row-fluid">';
echo CHtml::activeLabel($job, 'sudoer', array('class' => 'span2'));
echo CHtml::tag('span', array('class' => 'span4'), $job->sudoer);
echo CHtml::activeLabel($job, 'machine_id', array('class' => 'span2'));
echo CHtml::link($job->machine->name, 
    array('machine/view', 'id' => $job->machine_id), 
    array('target' => '_blank', 'class' => 'span4')); 
echo '</div>';
echo '<div class="row-fluid">';
echo CHtml::activeLabel($job, 'timeout', array('class' => 'span2'));
echo CHtml::tag('span', array('class' => 'span4'), $job->timeout . '&nbsp;&nbsp;' . Yii::t('Run', 'Minutes'));
echo CHtml::activeLabel($job, 'crucial', array('class' => 'span2'));
echo CHtml::tag('span', array('class' => 'span4'), $job->getCrucialText());
echo '</div>';
echo '<div class="row-fluid">';
echo CHtml::activeLabel($job, 'failed_repeat', array('class' => 'span2'));
echo CHtml::tag('span', array('class' => 'span4'), $job->failed_repeat);
if(!isset($editable) || $editable)
{
    echo '<div class="offset2 span1">';
    echo CHtml::link(Yii::t('TOAST', 'Edit'), 'javascript:;', array('class' => 'job-edit'));
    echo '</div><div class="span1">';
    echo CHtml::link(Yii::t('TOAST', 'Delete'), 'javascript:;', array('class' => 'job-delete'));
    echo '</div>';
}
echo '</div>'
?>
<script type="text/javascript">
    $(document).ready(function(){
        $("a.job-edit").click(function(){
            jobForm.dlgInit(lang.Save, $(this).parents("div.job"))
            jobForm.dlgLoad($(this).parents("div.job"))
            jobForm.dlgShow()
        })
    
        $("a.job-delete").click(function(event){
            var cur_job = $(this).parents("div.job")
            var cur_stage = cur_job.parents("div.stage-detail")
            if ($("div.stage-detail").size() > 1 && cur_stage.find("div.job").size() <= 1)
            {
                var jobs = cur_stage.nextAll("div.stage-detail").find("input.stage-num")
                jobs.each(function(index){
                    $(this).val($(this).val() - 1)
                });
                cur_stage.remove()
            }
            else
                cur_job.remove()
            event.stopImmediatePropagation()
        })
    })
</script>