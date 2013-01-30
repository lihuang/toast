<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/codemirror.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/style.css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap.css" />
    </head>
    <body style="overflow: hidden">
        <?php echo CHtml::beginForm('', 'post', array('id' => 'editor-form'));?>
        <div class="row well well-small" style="margin-bottom: 0">
            <input type="submit" value="<?php echo Yii::t('TOAST', 'Save');?>" class="btn span1 editor-submit"/>
            <input type="button" value="<?php echo Yii::t('TOAST', 'Close');?>" class="btn span1 editor-close"/>
        </div>
        <div class="row-fluid">
            <?php
            echo CHtml::textArea('config-content', $content);
            ?>
        </div>
        <?php echo CHtml::endForm();?>
        <?php Yii::app()->getClientScript()->registerCoreScript('jquery');?>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lang.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.form.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/codemirror.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/properties.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                var goodexit = false;
                window.onbeforeunload = function () {
                    if(!goodexit) {
                        return lang.sureToLeave;
                    }
                }
                $("input.editor-submit").click(function(){
                    goodexit = true;
                    $("#editor-form").ajaxForm({
                        dataType: 'json',
                        success: function(json) {
                            if(json.code === 0)
                            {
                                if(window.opener)
                                {
                                    var parent = $(window.opener.document);
                                    parent.find("#ci-config-filename").html("<a href='" + json.url + "' target='_blank' title='" + json.name + "'>" + json.name + "</a>");
                                    parent.find("#ci-config-newname").val(json.newname);
                                    parent.find("a.ci-config-edit").show();
                                }
                                window.close();
                            }
                            else
                            {
                                alert(json.msg);
                            }
                        }
                    })
                })
                
                $("input.editor-close").click(function() {
                    window.close();
                })
                function setHeight() {
                    if (!editor) return;
                    var height = window.innerHeight || (document.documentElement || document.body).clientHeight;
                    height = height - $("div.row.well.well-small").height() - 20;
                    editor.getWrapperElement().style.height = height + "px";
                    editor.refresh();
                }
                CodeMirror.on(window, "resize", function() {
                    setHeight();
                });
                var editor = CodeMirror.fromTextArea($("#config-content").get(0), {
                    mode: "properties",
                    lineNumbers: true,
                    extraKeys: {
                        Tab: function(cm) {
                            cm.replaceSelection("    ", "end")
                        }
                    }
                })
                setHeight();
            });
        </script>
    </body>
</html>