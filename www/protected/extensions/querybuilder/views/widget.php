<?php
echo CHtml::textField($this->name, $this->value, $this->htmlOptions);
echo CHtml::tag('div', array('class' => 'qb-search-btn', 
    'title' => QueryBuilderWidget::ADVANCE_SEARCH_TIP), '', true);

echo CHtml::openTag('div', array('class' => 'qb-search-panel'));
echo CHtml::tag('div', array('class' => 'qb-search-panel-x'), '', true);

echo CHtml::openTag('div', array('class' => 'item table-list'));
echo CHtml::tag('span', array('class' => 'table-label'), QueryBuilderWidget::TABLE_LABLE);
echo CHtml::dropDownList('table', null, array());
echo CHtml::closeTag('div');

echo CHtml::openTag('div', array('class' => 'item action'));
echo CHtml::link(CHtml::tag('i', array('class' => 'search'), '', true) 
        . ' '. QueryBuilderWidget::PLACE_HOLDER, 'javascript:;', array('class' => 'search-btn'));

echo CHtml::link(QueryBuilderWidget::RESET_QUERY_LABLE, 'javascript:;', array('class' => 'reset-query'));

echo CHtml::link(QueryBuilderWidget::SAVE_QUERY_LABLE, 'javascript:;', array('class' => 'save-query'));
echo CHtml::closeTag('div');

echo CHtml::openTag('div', array('class' => 'query-list'));
echo CHtml::closeTag('div');

echo CHtml::closeTag('div');

echo CHtml::openTag('div', array('class' => 'save-query-dialog'));
echo CHtml::tag('div', array('class' => 'dialog-error'), '', true);
echo CHtml::textField('Query[title]', '', array('placeholder' => QueryBuilderWidget::QUERY_TITLE_LABLE, 'class' => 'query-title'));
echo CHtml::openTag('div', array('class' => 'save-query-dialog-action'));
echo CHtml::button(QueryBuilderWidget::SAVE_QUERY_ACTION_LABLE, array('class' => 'save'));
echo CHtml::button(QueryBuilderWidget::CANCEL_QUERY_ACTION_LABLE, array('class' => 'cancel'));
echo CHtml::closeTag('div');
echo CHtml::closeTag('div');
?>