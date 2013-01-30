<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/command.css?ver=3" />
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/codemirror.css?ver=2" />
<style type="text/css">
    .CodeMirror {
        height: auto;
        border: 1px solid #ccc;
        border-radius: 3px;
    }
    .CodeMirror-scroll,
    .CodeMirror-gutters {
        min-height: 5em;
    }
</style>
<div class="content">
    <div class="sub-nav">
    <?php
    $this->widget('zii.widgets.CMenu', array(
        'id' => 'path-nav',
        'items' => $command->getNavItems(),
        'firstItemCssClass' => 'first',
        'lastItemCssClass' => 'last'
    ));
    ?>
    </div>
    <div class="main-detail">
    <?php echo CHtml::beginForm(); ?>
    <div class="button-actions clearfix">
        <input type="submit" value="<?php echo Yii::t('TOAST', 'Save'); ?>" class="btn" />
    </div>
    <?php echo CHtml::errorSummary($command); ?>
    <div class="detail block">
        <div class="row-fluid">
            <?php
            echo CHtml::activeLabelEx($command, 'name', array('class' => 'span1'));
            echo CHtml::activeTextField($command, 'name', array('class' => 'focus span11'));
            ?>
        </div>
        <div class="row-fluid">
            <?php
            echo CHtml::activeLabelEx($command, 'parser_id', array('class' => 'span1'));
            echo '<div class="span11">';
            $this->widget('application.extensions.select2.ESelect2', array(
                'model' => $command,
                'attribute' => 'parser_id',
                'data' => Parser::model()->getParserOptions(),
                'htmlOptions' => array(
                    'multiple' => 'multiple',
                    'placeholder' => Yii::t('Parser', 'No need parse'),
                    'class' => 'span3',
                ),
            ));
            echo '</div>';
            ?>
        </div>
        <div class="row-fluid">
            <?php
            echo CHtml::activeLabelEx($command, 'command', array('class' => 'span1'));
            echo '<div class="span11">';
            echo CHtml::activeTextArea($command, 'command');
            echo '</div>';
            ?>
        </div>
        <div class="row-fluid">
            <?php
            echo CHtml::activeLabelEx($command, 'desc_info', array('class' => 'span1'));
            echo CHtml::activeTextArea($command, 'desc_info', array('class' => 'span11 focus', 'style' => 'height: 6em'));
            ?>
        </div>
    </div>
    <?php echo CHtml::endForm(); ?>
    </div>
</div>

<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/codemirror.js?ver=1"></script>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/shell.js?ver=1"></script>
<script type="text/javascript">
    $(document).ready(function(){
        var goodexit = false;
        window.onbeforeunload = function () {
            if(!goodexit) {
                return lang.sureToLeave;
            }
        }
        $("input[type=submit]").click(function(){
            goodexit = true;
        })
        var codeMirror = CodeMirror.fromTextArea($("#Command_command").get(0), {
            mode: "shell",
            lineNumbers: true
        })
    });
</script>