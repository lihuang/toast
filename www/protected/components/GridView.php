<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
Yii::import('zii.widgets.grid.CGridView');
class GridView extends CGridView
{
    public $template = "{items}{pager}";
    public $headerCssClass = "table-header";
    public $bodyCssClass = "table-body";
    public $enablePageSize = false;
    public $pager = array('class' => 'LinkPager');

    /**
     * Renders the data items for the grid view.
     */
    public function renderItems()
    {
        if($this->dataProvider->getItemCount() > 0 || $this->showTableOnEmpty)
        {
            echo "<div class=\"{$this->itemsCssClass}\">\n";
            $this->renderHeader();
            ob_start();
            $this->renderBody();
            $body = ob_get_clean();
            echo $body;
            echo "</div>";
        }
        else $this->renderEmptyText();
    }

    /**
     * Renders the table header.
     */
    public function renderHeader()
    {
        if(!$this->hideHeader)
        {
            echo "<div class=\"{$this->headerCssClass}\">\n<table>\n<thead>\n";

            if($this->filterPosition === self::FILTER_POS_HEADER)
                    $this->renderFilter();

            echo "<tr>\n";
            foreach($this->columns as $column) $column->renderHeaderCell();
            echo "</tr>\n";

            if($this->filterPosition === self::FILTER_POS_BODY)
                    $this->renderFilter();

            echo "</thead>\n</table>\n</div>\n";
        }
        else if($this->filter !== null && ($this->filterPosition === self::FILTER_POS_HEADER || $this->filterPosition === self::FILTER_POS_BODY))
        {
            echo "<div class=\"{$this->headerCssClass}\">\n<table>\n<thead>\n";
            $this->renderFilter();
            echo "</thead>\n</table>\n</div>\n";
        }
    }

    /**
     * Renders the table body.
     */
    public function renderBody()
    {
        $data = $this->dataProvider->getData();
        $n = count($data);
        echo "<div class='top-shadow'>\n</div>\n<div class=\"{$this->bodyCssClass}\">\n<table class=\"{$this->itemsCssClass}\">\n<thead>\n</thead>\n<tbody>\n";

        if($n > 0)
        {
            for($row = 0; $row < $n; ++$row) $this->renderTableRow($row);
        }
        else
        {
            echo '<tr><td colspan="' . count($this->columns) . '">';
            $this->renderEmptyText();
            echo "</td></tr>\n";
        }
        echo "</tbody>\n</table>\n</div>\n<div class='bottom-shadow'>\n</div>\n";
    }

    /**
     * Renders a table body row.
     * @param integer $row the row number (zero-based).
     */
    public function renderTableRow($row)
    {
        foreach($this->columns as $column)
        {
            $data = $this->dataProvider->data[$row];
            if($column instanceof CDataColumn)
            {
                if($column->value !== null)
                        $value = $column->evaluateExpression($column->value,
                            array('data' => $data, 'row' => $row));
                else if($column->name !== null)
                        $value = CHtml::value($data, $column->name);
                else $value = null;
                $value = $value === null ? $column->grid->nullDisplay : $column->grid->getFormatter()->format($value,
                                $column->type);
                $value = strip_tags($value);
                $column->htmlOptions['title'] = $value;
            }
        }

        parent::renderTableRow($row);
    }

    /**
     * Renders the pager.
     */
    public function renderPager()
    {
        if(!$this->enablePagination) return;

        $pager = array();
        $class = 'LinkPager';
        if(is_string($this->pager)) $class = $this->pager;
        else if(is_array($this->pager))
        {
            $pager = $this->pager;
            if(isset($pager['class']))
            {
                $class = $pager['class'];
                unset($pager['class']);
            }
        }

        if($this->enablePageSize) $pager['enablePageSize'] = true;
        $pagination = $this->dataProvider->getPagination();
        $pager['summary'] = true;
        $pager['pages'] = $pagination;

        //maximum number of page buttons that can be displayed.
        $pager['maxButtonCount'] = 5;
//        $pager['alwayShow'] = true;

        echo '<div class="' . $this->pagerCssClass . '">';
        $this->widget($class, $pager);
        echo '</div>';
    }

    /**
     * Registers necessary client scripts.
     */
    public function registerClientScript()
    {
        $id = $this->getId();
        $cs = Yii::app()->getClientScript();
        $cs->registerScript(__CLASS__ . '#' . $id,
                "
                var opacityBottom = (jQuery('#$id .table-body').children().height() > jQuery('#$id .table-body').height())?1:0;
                jQuery('#$id .bottom-shadow').attr('style', 'opacity: ' + opacityBottom);
                jQuery('#$id .table-body').scroll(function(){
                var opacityTop = $(this).scrollTop()/50;
                var opacityBottom = ($(this).children().height() - $(this).scrollTop() - $(this).height())/50;
                jQuery('#$id .top-shadow').attr('style', 'opacity: ' + opacityTop);
                jQuery('#$id .bottom-shadow').attr('style', 'opacity: ' + opacityBottom);
            })");
        parent::registerClientScript();
    }
}
