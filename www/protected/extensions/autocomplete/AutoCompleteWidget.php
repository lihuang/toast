<?php
class AutoCompleteWidget extends CInputWidget
{
    public $cssFile;
    public $element;
    public $config = array();
    public $urlOrData;
    public $multiline = false;
    
    public function init()
    {
        list($this->element['name'], $this->element['id']) = $this->resolveNameID();
        
        $embeddedScript  = '';
        if(isset($this->config['result']))
        {
            $resultFunc = CJavaScript::encode($this->config['result']);
            $embeddedScript = '.result(' . $resultFunc . ')';
            unset($this->config['result']);
        }
        $jsOption = CJavaScript::encode($this->config);
        $embeddedScript = '$("#' . $this->element['id'] . '").autocompleter("' 
                . $this->urlOrData . '", ' . $jsOption . ')' . $embeddedScript;
        $this->registerScripts($this->element['id'], $embeddedScript);
    }

    protected function registerScripts($id, $embeddedScript)
    {
        $basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        $baseUrl = Yii::app()->getAssetManager()->publish($basePath);
        $cs = Yii::app()->clientScript;
        
        if($this->cssFile !== null)
        {
            $cs->registerCssFile($this->cssFile);
        }
        else
        {
            $cs->registerCssFile($baseUrl. '/jquery.autocomplete.css');
        }
        $cs->registerCssFile($baseUrl. '/thickbox.css');
        $cs->registerCoreScript('jquery');
        $cs->registerScriptFile($baseUrl . '/jquery.ajaxQueue.js');
        $cs->registerScriptFile($baseUrl . '/jquery.cookie.js');
        $cs->registerScriptFile($baseUrl . '/thickbox-compressed.js');
        $cs->registerScriptFile($baseUrl . '/json2.js');
        $cs->registerScriptFile($baseUrl . '/jquery.autocomplete.js');
        $cs->registerScript($id, $embeddedScript);
    }
    
    public function run()
    {
        $this->render('widget');
    }
}
?>