<?php
class MassPickerWidget extends CInputWidget
{
    public $cssFile;
    public $element;
    public $options;
    public $data;
    
    public function init()
    {
        list($this->element['name'], $this->element['id']) = $this->resolveNameID();
        $this->htmlOptions['id'] = $this->element['id'] . '-input'; 
        $jsOption = CJavaScript::encode($this->options);
        $this->registerScripts($this->htmlOptions['id'], '$("#' . $this->htmlOptions['id']
                . '").MassPicker('. $jsOption . ');');
    }
    
    public function run()
    {
        if ($this->hasModel())
            echo CHtml::activeHiddenField($this->model, $this->attribute);
        else
            echo CHtml::hiddenField($this->element['name'], $this->value);
        $this->render('widget');
    }
    
    protected function registerScripts($id, $embeddedScript)
    {
        $basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        $baseUrl = Yii::app()->getAssetManager()->publish($basePath);
		
        $cs = Yii::app()->clientScript;

        $cs->registerCoreScript('jquery');
        $cs->registerScriptFile($baseUrl . '/masspicker.js?ver=1');
        $cs->registerScript($id, $embeddedScript);
    }
}
?>