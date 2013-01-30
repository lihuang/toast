<?php
class QueryBuilderWidget extends CInputWidget
{
    public $cssFile;
    public $element;
    public $options;
    
    const PLACE_HOLDER = '查找';
    const ADVANCE_SEARCH_TIP = '显示高级搜索';
    const TABLE_LABLE = '搜索';
    const RESET_QUERY_LABLE = '重置搜索';
    const SAVE_QUERY_LABLE = '将此搜索保存';
    const QUERY_TITLE_LABLE = '搜索名称';
    const SAVE_QUERY_ACTION_LABLE = '保存';
    const CANCEL_QUERY_ACTION_LABLE = '取消';
    
    private function getDefaultOpts()
    {
        return array(
            'action' => Yii::app()->getBaseUrl(true) . '/#table#/index',
            'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
            'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
            'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
            'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete',
            'cTable' => '',
            'tables' => array(),
        );
    }
    
    public function init()
    {
        list($this->element['name'], $this->element['id']) = $this->resolveNameID();

        $this->htmlOptions = array('class' => 'qb-search span10', 
            'placeholder' => self::PLACE_HOLDER, 'autocomplete' => 'off');
        $jsOption = CJavaScript::encode(array_merge($this->getDefaultOpts(), $this->options));
        $this->registerScripts($this->element['id'], 'new QueryBuilder("' . $this->element['id'] . '",' 
                . $jsOption . ')');
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
            $cs->registerCssFile($baseUrl . '/style.css');
        }
        $cs->registerCoreScript('jquery');
        $cs->registerCoreScript('jquery.ui');
        $cssCoreUrl = $cs->getCoreScriptUrl();
        $cs->registerCssFile($cssCoreUrl . '/jui/css/base/jquery-ui.css');
        $cs->registerScriptFile($baseUrl . '/qb.js?ver=2');
        $cs->registerScript($id, $embeddedScript);
    }
}
?>