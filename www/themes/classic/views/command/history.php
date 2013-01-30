<div>
    <h3>
        <?php 
        $user = User::model()->findByPk($data->updated_by);
        $username = NULL;
        if($user) $username = $user->realname;
        echo Yii::t('Diff', '{date} Updated By {username}', array('{date}' => $data->update_time, '{username}' => $username));
        echo '&nbsp;' . CHtml::link(Yii::t('Diff', 'Expand'), 'javascript:void(0);', array('class' => 'expand-toggle', 'style' => 'font-weight:normal'));
        ?>
    </h3>
    <div style="margin: 5px 10px">
    <?php 
        $part = NULL;
        $html = '';
        foreach($data->diffattrs as $diffAttr)
        {
            if($part != $diffAttr->model_id)
            {
                if($html !== '') echo $html . '<br>';
                $part = $diffAttr->model_id;
                $html = '';
            }
            $name = '';
            $model = new $diffAttr->model_name;
            $model = $model->model()->findByPk($diffAttr->model_id);
            if($diffAttr->model_name == 'Job')
                $name = $model->command->name;
            else
                $name = $model->name;
            if($diffAttr->attribute == 'status')
            {
                if(!$diffAttr->old && $diffAttr->new)
                    $html = '<div>' . Yii::t('Diff', 'Add {Class}({name})', array('{Class}' => Yii::t('Diff', $diffAttr->model_name), '{name}' => $name)) . '</div>' . $html;
                else
                    $html = '<div>' . Yii::t('Diff', 'Delete {Class}({name})', array('{Class}' => Yii::t('Diff', $diffAttr->model_name), '{name}' => $name)) . '</div>' . $html;
            }
            else if($diffAttr->attribute == 'stage_num')
            {
                $diffAttr->old = Yii::t('Task', 'Stage {num}', array('{num}' => $diffAttr->old + 1));
                $diffAttr->new = Yii::t('Task', 'Stage {num}', array('{num}' => $diffAttr->new + 1));
                $html .= '<div>' . Yii::t('Diff', 'Update Job({name}) from "{old}" to "{new}"', 
                    array('{name}' => $name, '{old}' => $diffAttr->old, '{new}' => $diffAttr->new)) . '</div>';
            }
            else
            {
                if($diffAttr->attribute == 'parser_id')
                {
                    $oldParserIDs = preg_split('/,/', $diffAttr->old);
                    $oldParserStr = '';
                    foreach($oldParserIDs as $oldParserID)
                    {
                        $parser = Parser::model()->findByPk($oldParserID);
                        if($parser)
                        {
                            $oldParserStr .= $parser->name . ',';
                        }
                    }
                    $oldParserStr = trim($oldParserStr, ', ');
                    if($oldParserStr) $diffAttr->old = $oldParserStr;
                    
                    $newParserIDs = preg_split('/,/', $diffAttr->new);
                    $newParserStr = '';
                    foreach($newParserIDs as $newParserID)
                    {
                        $parser = Parser::model()->findByPk($newParserID);
                        if($parser)
                        {
                            $newParserStr .= $parser->name . ',';
                        }
                    }
                    $newParserStr = trim($newParserStr, ', ');
                    if($newParserStr) $diffAttr->new = $newParserStr;
                }
                
                if($diffAttr->old === NULL) $diffAttr->old = '[NULL]';
                if($diffAttr->new === NULL) $diffAttr->new = '[NULL]';
                $model = new $diffAttr->model_name;
                $html .= '<div>' . Yii::t('Diff', 'Update {name}{attribute} from "{old}" to "{new}"', 
                    array('{name}' => '', '{attribute}' => $model->getAttributeLabel($diffAttr->attribute),
                        '{old}' => CHtml::encode($diffAttr->old), '{new}' => CHtml::encode($diffAttr->new))) . '</div>';
            }
        }
        echo $html;
    ?>
    </div>
    <hr>
</div>
