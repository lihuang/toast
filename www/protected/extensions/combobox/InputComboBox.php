<?php

Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * InputComboBox class.
 */
class InputComboBox extends CJuiInputWidget {

    /**
     * @var array the entries that the autocomplete should choose from.
     */
    public $data = array();

    /**
     * @var string A jQuery selector used to apply the widget to the element(s).
     * Use this to have the elements keep their binding when the DOM is manipulated
     * by Javascript, ie ajax calls or cloning.
     * Can also be useful when there are several elements that share the same settings,
     * to cut down on the amount of JS injected into the HTML.
     */
    public $scriptSelector;
    public $defaultOptions = array('allowText' => true, 'showStyle' => false, 'disableAutocomplete' => false);

    protected function setSelector($id, $script, $event = null) {
        if ($this->scriptSelector) {
            if (!$event)
                $event = 'focusin';
            $js = "jQuery('body').delegate('{$this->scriptSelector}','{$event}',function(e){\$(this).{$script}});";
            $id = $this->scriptSelector;
        }
        else
            $js = "jQuery('#{$id}').{$script}";
        return array($id, $js);
    }

    public function init() {
        $cs = Yii::app()->getClientScript();
        $assets = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/assets');
        $cs->registerScriptFile($assets . '/jquery.ui.widget.min.js');
        $cs->registerScriptFile($assets . '/jquery.ui.combobox.js');
        $cs->registerCssFile($assets . '/style.css');
        
        parent::init();
    }

    /**
     * Run this widget.
     * This method registers necessary javascript and renders the needed HTML code.
     */
    public function run() {
        //echo CHtml::openTag('div', array('class' => 'btn-group'));

        list($name, $id) = $this->resolveNameID();

        if (is_array($this->data) && !empty($this->data)) {
            $data = $this->data;
        }
        else
            $data = array();

        if ($this->hasModel())
            echo CHtml::activeDropDownList($this->model, $this->attribute, $data);
        else
            echo CHtml::dropDownList($name, $this->value, $data);

        $this->htmlOptions['id'] = $id . '_input';
        echo CHtml::textField(null, null, $this->htmlOptions);

        $this->options = array_merge($this->defaultOptions, $this->options);

        $options = CJavaScript::encode($this->options);

        $cs = Yii::app()->getClientScript();

        $js = "combobox({$options});";

        list($id, $js) = $this->setSelector($id, $js);
        $cs->registerScript(__CLASS__ . '#' . $id, $js);
        //echo '</div>';
    }

}
