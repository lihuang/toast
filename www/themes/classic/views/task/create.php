<?php
$action = Yii::app()->request->baseUrl .  '/task/create';
echo $this->renderPartial('_form', array('task' => $task, 'jobs' => $jobs, 'action' => $action));
?>
<script type="text/javascript">
    $(document).ready(function(){
        $("#Task_type").change(function(){
            if(1 == $(this).val()) {
                $("#timer_txt_field").val("0 1 * * *")
            }
        });
    })
</script>