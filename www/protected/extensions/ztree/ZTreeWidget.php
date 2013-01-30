<?php
/**
 * ZTreeWidget is the zTree's extension for Yii.
 * The zTree is base on v3.2.
 * 
 * For more detail about zTree http://ztree.me
 */
class ZTreeWidget extends CInputWidget
{
    public $cssFile;
    public $element;
    public $options;
    
    public function init()
    {
        list($this->element['name'], $this->element['id']) = $this->resolveNameID();
        $this->registerScripts($this->element['id'], '');
    }
    
    public function run()
    {
        $this->render('widget');
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
            $cs->registerCssFile($baseUrl . '/ztree.css');
        }
        $cs->registerCoreScript('jquery');
        $cs->registerScriptFile($baseUrl . '/ztree.js?ver=1');
        $cs->registerScript($id, $embeddedScript);
    }
}
?>