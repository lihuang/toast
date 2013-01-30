<?php
/*
 * Copyright (C) 2007-2013 Alibaba Group Holding Limited
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 */
 
Yii::import('system.web.widgets.pagers.CLinkPager');
class LinkPager extends CLinkPager
{
    public $summary = false;
    public $enablePageSize = false;
    public $alwayShow = false;
            
    public function init()
    {
        if($this->summary)
        {
            if(!is_string($this->summary))
            {
                $total = $this->pages->itemCount;
                $start = $this->pages->currentPage*$this->pages->pageSize+1;
                $count = min($this->pages->pageSize, $total-$start+1);
                $end = $start+$count-1;
                $summaryText=Yii::t('TOAST','<span class="summary"><b>{start}-{end}</b> of <b>{count}</b></span>');
                if ($this->enablePageSize)
                    $summaryText .= CHtml::dropDownList('pageSize', $this->pages->getPageSize(), 
                        array('25' => '25', '50' => '50', '100' => '100'), array('class' => 'page-size'));
                $this->header = strtr($summaryText,array(
                                '{start}'=>$start,
                                '{end}'=>$end,
                                '{count}'=>$total,
                                '{page}'=>$this->pages->currentPage+1,
                                '{pages}'=>$this->pages->pageCount,
                            ));
            }
        }
        parent::init();
    }
    
    protected function createPageButtons()
    {
        if(($pageCount=$this->getPageCount())<=1 && !$this->alwayShow)
            return array();

        list($beginPage,$endPage)=$this->getPageRange();
        $currentPage=$this->getCurrentPage(false); // currentPage is calculated in getPageRange()
        $buttons=array();

        // first page
        $buttons[]=$this->createPageButton($this->firstPageLabel,0,self::CSS_FIRST_PAGE,$currentPage<=0,false);

        // prev page
        if(($page=$currentPage-1)<0)
                $page=0;
        $buttons[]=$this->createPageButton($this->prevPageLabel,$page,self::CSS_PREVIOUS_PAGE,$currentPage<=0,false);

        // internal pages
        for($i=$beginPage;$i<=$endPage;++$i)
                $buttons[]=$this->createPageButton($i+1,$i,self::CSS_INTERNAL_PAGE,false,$i==$currentPage);

        // next page
        if(($page=$currentPage+1)>=$pageCount-1)
                $page=$pageCount-1;
        $buttons[]=$this->createPageButton($this->nextPageLabel,$page,self::CSS_NEXT_PAGE,$currentPage>=$pageCount-1,false);

        // last page
        $buttons[]=$this->createPageButton($this->lastPageLabel,$pageCount-1,self::CSS_LAST_PAGE,$currentPage>=$pageCount-1,false);

        return $buttons;
    }
}
?>
