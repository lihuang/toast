<?php
$action =  Yii::app()->request->baseUrl .  '/task/update/id/' . $task->id;
echo $this->renderPartial('_form', array('task' => $task, 'jobs' => $jobs, 'action' => $action));
?>