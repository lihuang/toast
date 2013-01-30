<?php
class TimerEditorWidget extends CInputWidget
{
    public function init()
    {
        $basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        $baseUrl = Yii::app()->getAssetManager()->publish($basePath, false, 1, YII_DEBUG);
        
        Yii::app()->clientScript->registerScriptFile($baseUrl . '/cron.js',CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile($baseUrl . '/lang_zh-CN.js',CClientScript::POS_END);
        
        list($name, $id) = $this->resolveNameID();
        Yii::app()->clientScript->registerScript('timerEditor', 
            '$("#' . $id . '").initial("dlg_' . $id .'");',CClientScript::POS_READY
        );
    }

    public function run()
    {
        $this->render('widget');
    }
}