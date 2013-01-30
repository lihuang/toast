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
            {
                if(isset($model->command))
                {
                    $name = CHtml::encode($model->command->name);
                }
                else
                {
                    $name = Yii::t('Job', 'Test Case Mode');
                }
            }
            else
            {
                $name = CHtml::encode($model->name);
            }
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
                switch($diffAttr->attribute)
                {                    
                    case 'name':
                        $diffAttr->old = CHtml::encode($diffAttr->old);
                        $diffAttr->new = CHtml::encode($diffAttr->new);
                        break;
                    case 'responsible':
                        $oldUser = User::model()->findByPk($diffAttr->old);
                        if($oldUser) $diffAttr->old = $oldUser->realname;
                        $newUser = User::model()->findByPk($diffAttr->new);
                        if($newUser) $diffAttr->new = $newUser->realname;
                        break;
                    case 'type':
                        $types = Task::model()->getTypeOptions();
                        if($diffAttr->old && isset($types[$diffAttr->old])) $diffAttr->old = $types[$diffAttr->old];
                        if($diffAttr->new && isset($types[$diffAttr->new])) $diffAttr->new = $types[$diffAttr->new];
                        break;
                    case 'report_filter':
                        $filters = Task::model()->getReportFilterOptions();
                        $diffAttr->old = isset($filters[$diffAttr->old])?$filters[$diffAttr->old]:NULL;
                        $diffAttr->new = isset($filters[$diffAttr->new])?$filters[$diffAttr->new]:NULL;
                        break;
                    case 'project_id':
                        $oldProject = Project::model()->findByPk($diffAttr->old);
                        if($oldProject) $diffAttr->old = $oldProject->path;
                        $newProject = Project::model()->findByPk($diffAttr->new);
                        if($newProject) $diffAttr->new = $newProject->path;
                        break;
                    case 'command_id':
                        $oldCommand = Command::model()->findByPk($diffAttr->old);
                        if($oldCommand) $diffAttr->old = $oldCommand->name;
                        $newCommand = Command::model()->findByPk($diffAttr->new);
                        if($newCommand) $diffAttr->new = $newCommand->name;
                        break;
                    case 'machine_id':
                        $oldMachine = Machine::model()->findByPk($diffAttr->old);
                        if($oldMachine) $diffAttr->old = $oldMachine->name;
                        $newMachine = Machine::model()->findByPk($diffAttr->new);
                        if($newMachine) $diffAttr->new = $newMachine->name;
                        break;
                    case 'crucial':
                        $crucials = Job::model()->getCrucialOptions();
                        $diffAttr->old = isset($crucials[$diffAttr->old])?$crucials[$diffAttr->old]:NULL;
                        $diffAttr->new = isset($crucials[$diffAttr->new])?$crucials[$diffAttr->new]:NULL;
                        break;
                }
                
                if($diffAttr->old === NULL) $diffAttr->old = '[NULL]';
                if($diffAttr->new === NULL) $diffAttr->new = '[NULL]';
                $model = new $diffAttr->model_name;
                $html .= '<div>' . Yii::t('Diff', 'Update {name}{attribute} from "{old}" to "{new}"', 
                    array('{name}' => Yii::t('Diff', $diffAttr->model_name) . '(' . $name . ')', '{attribute}' => $model->getAttributeLabel($diffAttr->attribute),
                        '{old}' => $diffAttr->old, '{new}' => $diffAttr->new)) . '</div>';
            }
        }
        echo $html;
    ?>
    </div>
    <hr>
</div>