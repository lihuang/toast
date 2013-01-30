<?php
class KindEditorWidget extends CInputWidget
{
    // General Purpose
    protected $assetsPath;
    protected $cssFile;

    //	HTML Part
    protected $element = array();

    //	Javascript Part
    protected $editorOptions = array();

    //	Initialize widget
    public function init()
    {
        // publish assets folder
        $this->assetsPath = Yii::app()->getAssetManager()->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

        //	resolve HTML element name and id
        list($this->element['name'], $this->element['id']) = $this->resolveNameID();

        //	include CKEditor file
        Yii::app()->clientScript->registerScriptFile($this->assetsPath.'/kindeditor-min.js');
        Yii::app()->clientScript->registerScriptFile($this->assetsPath.'/plugins/preview/preview.js');
        Yii::app()->clientScript->registerScript("kindEditor",
            "KE.show({
                id : '" . $this->element['id'] . "',
                resizeMode : 1,
                width : '100%',
                height : '400px',
                newlineTag : 'br',
                items : ['fontname','fontsize', 'textcolor', 'bgcolor','bold','italic', 'underline', 'strikethrough', 'removeformat',
                           '|', 'justifyleft', 'justifycenter', 'justifyright', 'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent',
                           '|', 'image', 'advtable', 'hr', 'link', 'unlink',
                           '|','selectall', 'fullscreen', 'plainpaste', 'wordpaste','preview','source']
           });"
        );
        //	include CSS file if defined
        if($this->cssFile !== null)
        {
            Yii::app()->clientScript->registerCssFile($this->cssFile);
        }
    }

    public function run()
    {
        $this->render('widget');
    }
}