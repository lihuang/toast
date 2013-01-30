<?php

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Do you want to check your user parameters against the valid ones?
 */
define('_CHECK_JS_PARAMETERS_', true);

class JqGrid extends CJuiWidget {

    /**
     * @var CModel the data model associated with this widget.
     */
    public $model;

    /**
     * @var string the attribute associated with this widget.
     * The name can contain square brackets (e.g. 'name[1]') which is used to collect tabular data input.
     */
    public $attribute;

    /**
     * @var string the input name. This must be set if {@link model} is not set.
     */
    public $name;

    /**
     * @var string the input value
     */
    public $value;

    /**
     * jqGrid options.
     *
     * @var array
     */
    public $options = array();

    /**
     * jqGrid navbar options. Example: {edit:false,add:false,del:false}
     *
     * @var array
     */
    public $navBarOptions = array('edit' => false, 'add' => false, 'del' => false);

    /**
     * Callback functions.
     *
     * @var array
     */
    protected $callbacks = array();

    /**
     * Whether to use a navbar in the bottom of the grid.
     *
     * @var boolean
     */
    protected $useNavBar = true;

    /**
     * Possible valid options, according to the jQuery UI widget documentation
     *
     * @var array
     */
    protected $validOptions = array(
        'url' => array('type' => 'string'), // Default: ""
        'height' => array('type' => 'integer'), // Default: 150
        'page' => array('type' => 'integer'), // Default: 1
        'rowNum' => array('type' => 'integer'), // Default: 20
        'records' => array('type' => 'integer'), // Default: 0
        'pager' => array('type' => 'string'), // Default: ""
        'pgbuttons' => array('type' => 'boolean'), // Default: true
        'pginput' => array('type' => 'boolean'), // Default: true
        'colModel' => array('type' => 'array'), // Default: []
        'rowList' => array('type' => 'array'), // Default: []
        'colNames' => array('type' => 'array'), // Default: []
        'sortorder' => array('type' => 'string'), // Default: "asc"
        'sortname' => array('type' => 'string'), // Default: ""
        'datatype' => array('type' => 'string', 'possibleValues' => array('xml', 'json', 'local')), // Default: "xml"
        'mtype' => array('type' => 'string'), // Default: "GET"
        'altRows' => array('type' => 'boolean'), // Default: false
        'selarrrow' => array('type' => 'array'), // Default: []
        'savedRow' => array('type' => 'array'), // Default: []
        'shrinkToFit' => array('type' => 'boolean'), // Default: true
        'xmlReader' => array('type' => 'array'), // Default: {}
        'jsonReader' => array('type' => 'array'), // Default: {}
        'subGrid' => array('type' => 'boolean'), // Default: false
        'subGridModel' => array('type' => 'array'), // Default: []
        'reccount' => array('type' => 'integer'), // Default: 0
        'lastpage' => array('type' => 'integer'), // Default: 0
        'lastsort' => array('type' => 'integer'), // Default: 0
        'selrow' => array('type' => 'integer'), // Default: null
        'viewrecords' => array('type' => 'boolean'), // Default: false
        'loadonce' => array('type' => 'boolean'), // Default: false
        'multiselect' => array('type' => 'boolean'), // Default: false
        'multikey' => array('type' => 'boolean'), // Default: false
        'editurl' => array('type' => 'string'), // Default: null
        'search' => array('type' => 'boolean'), // Default: false
        'searchdata' => array('type' => 'array'), // Default: {}
        'caption' => array('type' => 'string'), // Default: ""
        'hidegrid' => array('type' => 'boolean'), // Default: true
        'hiddengrid' => array('type' => 'boolean'), // Default: false
        'postData' => array('type' => 'array'), // Default: {}
        'userData' => array('type' => 'array'), // Default: {}
        'treeGrid' => array('type' => 'boolean'), // Default: false
        'treeGridModel' => array('type' => 'boolean'), // Default: 'nested'
        'treeReader' => array('type' => 'array'), // Default: {}
        'treeANode' => array('type' => 'integer'), // Default: -1
        'ExpandColumn' => array('type' => 'string'), // Default: null
        'tree_root_level' => array('type' => 'integer'), // Default: 0
        'prmNames' => array('type' => 'array'), // Default: {page:"page",rows:"rows", sort: "sidx",order: "sord"},
        'forceFit' => array('type' => 'boolean'), // Default: false
        'gridstate' => array('type' => 'string'), // Default: "visible"
        'cellEdit' => array('type' => 'false'), // Default: false
        'cellsubmit' => array('type' => 'string'), // Default: "remote"
        'nv' => array('type' => 'integer'), // Default: 0
        'loadui' => array('type' => 'string'), // Default: "enable"
        'toolbar' => array('type' => array('boolean', 'string')), // Default: [false,""]
        'scroll' => array('type' => 'boolean'), // Default: false
        'multiboxonly' => array('type' => 'boolean'), // Default: false
        'deselectAfterSort' => array('type' => 'boolean'), // Default: true
        'scrollrows' => array('type' => 'boolean'), // Default: false
        'autowidth' => array('type' => 'boolean'), // Default: false
    );

    /**
     * Possible valid callbacks, according to the jQuery UI widget documentation
     *
     * @var array
     */
    protected $validCallbacks = array(
        'beforeSelectRow',
        'onSelectRow',
        'onSortCol',
        'ondblClickRow',
        'onRightClickRow',
        'onPaging',
        'onSelectAll',
        'loadComplete',
        'gridComplete',
        'loadError',
        'loadBeforeSend',
        'afterInsertRow',
        'beforeRequest',
        'onHeaderClick',
    );

    /**
     * Base assets' URL.
     *
     * @var string
     */
    private $_baseUrl = '';

    /**
     * Client script object
     *
     * @var object
     */
    private $_clientScript = null;

    //***************************************************************************
    // Constructor
    //***************************************************************************

    public function __construct($owner = null) {
        parent::__construct($owner);
    }

    /**
     * @return array the name and the ID of the input.
     */
    protected function resolveNameID()
    {
        if($this->name!==null)
            $name=$this->name;
        else if(isset($this->htmlOptions['name']))
            $name=$this->htmlOptions['name'];
        else if($this->hasModel())
            $name=CHtml::activeName($this->model,$this->attribute);
        else
            throw new CException(Yii::t('zii','{class} must specify "model" and "attribute" or "name" property values.',array('{class}'=>get_class($this))));

        if(($id=$this->getId(false))===null)
        {
            if(isset($this->htmlOptions['id']))
                $id=$this->htmlOptions['id'];
            else
                $id=CHtml::getIdByName($name);
        }

        return array($name,$id);
    }

    /**
     * @return boolean whether this widget is associated with a data model.
     */
    protected function hasModel()
    {
        return $this->model instanceof CModel && $this->attribute!==null;
    }    
    
    /**
     * Setter
     *
     * @param array $value options
     */
    public function setOptions($value) {
        if (!is_array($value))
            throw new CException(Yii::t('JqGrid', 'options must be an array'));
        if (__CHECK_JS_PARAMETERS__)
            self::checkOptions($value, $this->validOptions);
        $this->options = $value;
    }

    /**
     * Getter
     *
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Setter
     *
     * @param array $value callbacks
     */
    public function setCallbacks($value) {
        if (!is_array($value))
            throw new CException(Yii::t('JqGrid', 'callbacks must be an array'));
        self::checkCallbacks($value, $this->validCallbacks);
        $this->callbacks = $value;
    }

    /**
     * Getter
     *
     * @return array
     */
    public function getCallbacks() {
        return $this->callbacks;
    }

    /**
     * Setter
     *
     * @param boolean $value useNavBar
     */
    public function setUseNavBar($value) {
        if (!is_bool($value))
            throw new CException(Yii::t('JqGrid', 'useNavBar must be boolean'));
        $this->useNavBar = $value;
    }

    /**
     * Getter
     *
     * @return boolean
     */
    public function getUseNavBar() {
        return $this->useNavBar;
    }

    /**
     * Check callbacks against the valid ones
     *
     * @param array $value user's callbacks
     * @param array $validCallbacks valid callbacks
     */
    protected static function checkCallbacks($value, $validCallbacks) {
        if (!empty($validCallbacks)) {
            foreach ($value as $key => $val) {
                if (!in_array($key, $validCallbacks)) {
                    throw new CException(Yii::t('jqGrid', '{k} must be one of: {c}', array('{k}' => $key, '{c}' => implode(', ', $validCallbacks))));
                }
            }
        }
    }

    /**
     * Publishes the assets
     */
    public function publishAssets() {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
        $this->_baseUrl = Yii::app()->getAssetManager()->publish($dir);
    }

    /**
     * Registers the external javascript files
     */
    public function registerClientScripts() {
        if ($this->_baseUrl === '')
            throw new CException(Yii::t('JqGrid', 'baseUrl must be set. This is done automatically by calling publishAssets()'));

        $files = array();

        $this->_clientScript = Yii::app()->getClientScript();

        $this->_clientScript->registerCoreScript('jquery');

        $this->_clientScript->registerCssFile($this->_baseUrl . '/css/ui.jqgrid.css');
        $this->_clientScript->registerCssFile($this->_baseUrl . '/css/searchFilter.css');
        $this->_clientScript->registerCssFile($this->_baseUrl . '/css/ui.multiselect.css');

        $this->_clientScript->registerScriptFile($this->_baseUrl . '/js/i18n/grid.locale-cn.js');

        $this->_clientScript->registerScriptFile($this->_baseUrl . '/js/jqModal.js');
        $this->_clientScript->registerScriptFile($this->_baseUrl . '/js/jqDnR.js');

        $files[] = "JsonXml.js"; // xmljson utils
        $files[] = "grid.base.js"; // jqGrid base
        $files[] = "grid.celledit.js"; // jqGrid cell editing
        $files[] = "grid.common.js"; // jqGrid common for editing
        $files[] = "grid.custom.js"; // jqGrid custom
        $files[] = "grid.filter.js"; // jqGrid filter
        $files[] = "grid.formedit.js"; // jqGrid Form editing
        $files[] = "grid.grouping.js"; // jqGrid grouping
        $files[] = "grid.import.js"; // jqGrid import
        $files[] = "grid.inlinedit.js"; // jqGrid inline editing
        $files[] = "grid.jqueryui.js"; // jqGrid jqueryui
        $files[] = "grid.subgrid.js"; // jqGrid subgrid
        $files[] = "grid.tbltogrid.js"; // jqGrid tbltogrid
        $files[] = "grid.treegrid.js"; // jqGrid treegrid
        $files[] = "jquery.fmatter.js"; // jqGrid fmatter

        $plugins[] = "grid.addons.js"; // jqGrid addons    
        $plugins[] = "grid.postext.js"; // jqGrid postext      
        $plugins[] = "grid.setcolumns.js"; // jqGrid setcolumns
        $plugins[] = "jquery.contextmenu.js"; // jqGrid contextmenu
        $plugins[] = "jquery.searchFilter.js"; // jqGrid searchFilter
        $plugins[] = "jquery.tablednd.js"; // jqGrid tablednd
        $plugins[] = "ui.multiselect.js"; // multiselect

        foreach ($files as $file) {
            $this->_clientScript->registerScriptFile($this->_baseUrl . '/js/' . $file);
        }

//        foreach ($plugins as $file) {
//            $this->_clientScript->registerScriptFile($this->_baseUrl . '/plugins/' . $file);
//        }
    }

    /**
     * Make the options javascript string.
     *
     * @return string
     */
    protected function makeOptions($id) {
        $options = array();

        if ($this->useNavBar)
            $options['pager'] = $id . '_pager';

        $encodedOptions = CJavaScript::encode(array_merge($options, $this->options));

        return $encodedOptions;
    }

    /**
     * Generate the javascript code.
     *
     * @param string $id id
     * @return string
     */
    protected function jsCode($id) {
        $options = $this->makeOptions($id);
        $navOptions = CJavaScript::encode($this->navBarOptions);

        $nav = '';
        if ($this->useNavBar) {
            $nav = ".navGrid('#{$id}_pager', {$navOptions}, 
                {drag:true,resize:false,closeOnEscape:true,closeAfterEdit:true}, 
                {drag:true,resize:false,closeOnEscape:true,closeAfterAdd:true}, 
                {drag:true,resize:false,closeOnEscape:true}, 
                {drag:true,closeOnEscape:true,closeAfterSearch:true,multipleSearch:true})";
        }

        $script = <<<EOP
$("#{$id}_grid").jqGrid({$options}){$nav};
EOP;

        return $script;
    }

    /**
     * Make the HTML code
     *
     * @param string $id id
     * @return string
     */
    protected function htmlCode($id) {
        $tableOptions = array('id' => $id . '_grid', 'class' => 'scroll', 'cellpadding' => 0, 'cellspacing' => 0);
        $pagerOptions = array('id' => $id . '_pager', 'class' => 'scroll', 'style' => 'text-align:center;');

        $html = CHtml::tag('table', $tableOptions, '', true) . "\n";
        if ($this->useNavBar)
            $html .= CHtml::tag('div', $pagerOptions, '', true);

        return $html;
    }

    //***************************************************************************
    // Run Lola, Run!
    //***************************************************************************

    /**
     * Render the widget
     */
    public function run() {
        list($name, $id) = $this->resolveNameID();

        $this->publishAssets();
        $this->registerClientScripts();

        $js = $this->jsCode($id);
        $html = $this->htmlCode($id);

        $this->_clientScript->registerScript('Yii.' . get_class($this) . '#' . $id, $js, CClientScript::POS_READY);

        echo $html;
    }

}