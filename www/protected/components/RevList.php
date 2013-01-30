<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
class RevList extends CBasePager
{
    const CSS_FIRST_REV = 'first';
    const CSS_LAST_REV = 'last';
    const CSS_PREVIOUS_REV = 'previous';
    const CSS_NEXT_REV = 'next';
    const CSS_INTERNAL_REV = 'page';
    const CSS_HIDDEN_REV = 'hidden';
    const CSS_SELECTED_REV = 'selected';

    public $maxButtonCount = 10;
    public $nextRevLabel = '>';
    public $prevRevLabel = '<';
    public $firstRevLabel = '<<';
    public $lastRevLabel = '>>';
    public $cssFile;
    public $htmlOptions = array();
    public $revCount;
    public $currentRev;
    public $revVar = 'rev';

    /**
     * Initializes the pager by setting some default property values.
     */
    public function init()
    {
        if($this->revCount === null) $this->revCount = 0;
        if($this->currentRev === null) $this->currentRev = $this->revCount;

        if(!isset($this->htmlOptions['id']))
                $this->htmlOptions['id'] = $this->getId();
        if(!isset($this->htmlOptions['class']))
                $this->htmlOptions['class'] = 'yiiPager';
    }

    /**
     * Executes the widget.
     * This overrides the parent implementation by displaying the generated page buttons.
     */
    public function run()
    {
        $this->registerClientScript();
        $buttons = $this->createRevButtons();
        if(empty($buttons)) return;
        echo CHtml::tag('ul', $this->htmlOptions, implode("\n", $buttons));
    }

    /**
     * Creates the page buttons.
     * @return array a list of page buttons (in HTML code).
     */
    protected function createRevButtons()
    {
        if(($revCount = $this->revCount) <= 0) return array();

        list($beginRev, $endRev) = $this->getRevRange();
        $currentRev = $this->getCurrentRev();
        $buttons = array();

        // first rev
//		$buttons[]=$this->createRevButton($this->firstRevLabel,1,self::CSS_FIRST_REV,$currentRev<=1,false);
        // prev rev
//		if(($rev=$currentRev-1)<1)
//			$rev=1;
//		$buttons[]=$this->createRevButton($this->prevRevLabel,$rev,self::CSS_PREVIOUS_REV,$currentRev<=1,false);
        // internal revs
        for($i = $beginRev; $i >= $endRev; --$i)
        {
            $buttons[] = $this->createRevButton('r' . $i, $i,
                    self::CSS_INTERNAL_REV, false, $i == $currentRev);
//            if ($i>$endRev)
//                $buttons[]=$this->createChangedButton('<',$i,'');
        }

        // next page
//		if(($rev=$currentRev+1)>=$revCount)
//			$rev=$revCount;
//		$buttons[]=$this->createRevButton($this->nextRevLabel,$rev,self::CSS_NEXT_REV,$currentRev>=$revCount,false);
        // last rev
//		$buttons[]=$this->createRevButton($this->lastRevLabel,$revCount,self::CSS_LAST_REV,$currentRev>=$revCount,false);

        return $buttons;
    }

    /**
     * Creates a page button.
     * You may override this method to customize the page buttons.
     * @param string $label the text label for the button
     * @param integer $page the page number
     * @param string $class the CSS class for the page button. This could be 'page', 'first', 'last', 'next' or 'previous'.
     * @param boolean $hidden whether this page button is visible
     * @param boolean $selected whether this page button is selected
     * @return string the generated button
     */
    protected function createRevButton($label, $rev, $class, $hidden, $selected)
    {
        if($hidden || $selected)
                $class.=' ' . ($hidden ? self::CSS_HIDDEN_REV : self::CSS_SELECTED_REV);
        return '<li class="' . $class . '">' . CHtml::link($label,
                        $this->createRevUrl($rev)) . '</li>';
    }

    protected function createChangedButton($label, $rev, $class)
    {
        return '<li class="' . $class . '">' . CHtml::link($label,
                        $this->createRevUrl($rev)) . '</li>';
    }

    /**
     * @param boolean $recalculate whether to recalculate the current rev based on the rev size and rev count.
     * @return integer the zero-based index of the current rev. Defaults to revCount.
     */
    public function getCurrentRev($recalculate = true)
    {
        if($recalculate)
        {
            if(isset($_GET[$this->revVar]))
            {
                $this->currentRev = (int) $_GET[$this->revVar];
                $revCount = $this->revCount;
                if($this->currentRev >= $revCount) $this->currentRev = $revCount;
                if($this->currentRev < 1) $this->currentRev = 1;
            }
            else $this->currentRev = $this->revCount;
        }
        return $this->currentRev;
    }

    /**
     * @return array the begin and end pages that need to be displayed.
     */
    protected function getRevRange()
    {
        $currentRev = $this->getCurrentRev();
        $revCount = $this->revCount;

        $beginRev = min($revCount, $currentRev + (int) ($this->maxButtonCount / 2));
        if(($endRev = $beginRev - $this->maxButtonCount + 1) <= 1)
        {
            $endRev = 1;
            $beginRev = min($revCount, $endRev + $this->maxButtonCount - 1);
        }
        return array($beginRev, $endRev);
    }

    /**
     * Creates the URL suitable.
     * @param integer $rev the rev that the URL should point to.
     * @return string the created URL
     */
    protected function createRevUrl($rev)
    {
        $controller = $this->getController();
        if($rev < $this->revCount) $_GET[$this->revVar] = $rev;
        else unset($_GET[$this->revVar]);
        return $controller->createUrl('', $_GET);
    }

    /**
     * Registers the needed client scripts (mainly CSS file).
     */
    public function registerClientScript()
    {
        if($this->cssFile !== false) self::registerCssFile($this->cssFile);
    }

    /**
     * Registers the needed CSS file.
     * @param string $url the CSS URL. If null, a default CSS URL will be used.
     * @since 1.0.2
     */
    public static function registerCssFile($url = null)
    {
        if($url === null)
                $url = CHtml::asset(Yii::getPathOfAlias('system.web.widgets.pagers.pager') . '.css');
        Yii::app()->getClientScript()->registerCssFile($url);
    }
}
