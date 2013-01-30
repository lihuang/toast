<div class="run-block">
    <?php
        echo CHtml::hiddenField('taskrun_id', $data->id);
        $title = $data->created_by_realname . ' @ ' . $data->create_time;
        $statusText = $data->getStatusText();
        if($data->updated_by && in_array($data->status, array(CommandRun::STATUS_CANCELING, CommandRun::STATUS_CANCELED)))
            $statusText .= ' By ' . $data->updated_by_realname;
        $content = 
            '<table class="detail-table">
                <tr>
                    <th>ID</th>
                    <td>' . $data->id . '</td>
                </tr>
                <tr>
                    <th>' . CHtml::activeLabel($data, 'status') . '</th>
                    <td>' . $statusText . '</td>
                </tr>
                <tr>
                    <th>' . CHtml::activeLabel($data, 'result') . '</th>
                    <td><span class="label '. $data->getResultStyle() . '">' . $data->getResultText() . '</span></td>
                </tr>
                <tr>
                    <th>' . CHtml::activeLabel($data, 'created_by') . '</th>
                    <td>' . $data->created_by_realname . '</td>
                </tr>
                <tr>
                    <th>' . CHtml::activeLabel($data, 'start_time') . '</th>
                    <td>' . $data->start_time . '</td>
                </tr>
                <tr>
                    <th>' . CHtml::activeLabel($data, 'stop_time') . '</th>
                    <td>' . $data->stop_time . '</td>
                </tr>
            </table>';
    ?>
    <a href="<?php echo Yii::app()->getBaseUrl(true) . '/task/view/id/' . $data->task->id . '/runID/' . $data->id?>" class="<?php echo $data->getResultStyle();?>" 
        rel="popover" 
        data-placement="bottom"
        data-trigger="hover"
        data-title='<?php echo $title; ?>'
        data-content='<?php echo $content;?>'>
    </a>
</div>