<?php
/* @var $this AdminController */
/* @var QuestionGroup $oQuestionGroup */
/* @var Survey $oSurvey */

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('addQuestion');

?>



<div id='edit-question-body' class='side-body <?php echo getSideBodyClass(false); ?>'>

    <!-- Page Title-->
    <div class="pagetitle h3">
        <?php
        if ($adding) {
            eT("Add a new resource");
        } else {
            eT("Edit resource");
            echo ': <em>'.$eqrow['title'].'</em> (ID:'.$qid.')';
        }
        ?>
    </div>

    <div class="row">
        <!-- Form for the whole page-->
        <?php echo CHtml::form(array("admin/database/index"), 'post',array('class'=>'form30 ','id'=>'frmeditquestion','name'=>'frmeditquestion')); ?>
        <!-- The tabs & tab-fanes -->
        <div class="col-sm-12 col-md-7 content-right">
            <?php if($adding):?>
                <?php
                $this->renderPartial(
                    './survey/Resource/resource_subviews/_tabs',
                    array(
                        'oSurvey'=>$oSurvey,
                        'eqrow'=>$eqrow,
                        'surveyid'=>$surveyid,
                        'adding'=>$adding,
                        'action'=>$action
                    )
                ); ?>
<!--                --><?php //else:?>
<!--                --><?php
//                $this->renderPartial(
//                    './survey/Question/question_subviews/_tabs',
//                    array(
//                        'oSurvey'=>$oSurvey,
//                        'eqrow'=>$eqrow,
//                        'surveyid'=>$surveyid,
//                        'gid'=>$gid, 'qid'=>$qid,
//                        'adding'=>$adding,
//                        'aqresult'=>$aqresult,
//                        'action'=>$action
//                    )
//                ); ?>

                <?php endif;?>
        </div>

        <!-- The Accordion -->
        <div class="col-sm-12 col-md-5" id="accordion-container" style="background-color: #fff; z-index: 2;">
            <div id='questionbottom'>
                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

                    <!-- General Options -->
                    <div class="panel panel-default" id="questionTypeContainer">
                        <!-- General Options : Header  -->
                        <div class="panel-heading" role="tab" id="headingOne">
                            <a class="panel-title h4 selector--questionEdit-collapse" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-question" aria-expanded="true" aria-controls="collapse-question">
                                <?php eT("General options");?>
                            </a>
                        </div>

                        <div id="collapse-question" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">

                                <div  class="form-group" id="OtherSelection">
                                    <label class=" control-label" for="other"><?php eT("Option 'Other':"); ?></label>
                                        <div class="">
                                            <?php $this->widget('yiiwheels.widgets.switch.WhSwitch', array(
                                                'name' => 'other',
                                                'id' => 'other',
                                                'value'=> $eqrow['other'] === "Y",
                                                'onLabel'=>gT('On'),
                                                'offLabel'=>gT('Off'),
                                                'htmlOptions'=>array(
                                                    'disabled'=> true,
                                                    'value'=> 'Y',
                                                    'uncheckValue' => 'N',
                                                ),
                                            ));
                                            ?>
                                        </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        echo TbHtml::hiddenField('file_name', '', ['id' => 'file_name']);
        echo TbHtml::hiddenField('file_size', '', ['id' => 'file_size']);
        echo TbHtml::hiddenField('image_width', '', ['id' => 'image_width']);
        echo TbHtml::hiddenField('image_height', '', ['id' => 'image_height']);
        echo TbHtml::hiddenField('file_frontend_link', '', ['id' => 'file_frontend_link']);
        ?>

        <?php if ($adding): ?>
            <input type='hidden' name='action' value='addResource' />
            <input type='hidden' id='sid' name='sid' value='<?php echo $surveyid; ?>' />
            <p><input type='submit'  class="hidden" value='<?php eT("Add question"); ?>' /></p>
        <?php else: ?>
            <input type='hidden' name='action' value='updatequestion' />
            <input type='hidden' id='qid' name='qid' value='<?php echo $qid; ?>' />
            <p><button type='submit' class="saveandreturn hidden" name="redirection" value="edit"><?php eT("Save") ?> </button></p>
            <input type='submit'  class="hidden" value='<?php eT("Save and close"); ?>' />
        <?php endif; ?>
        <input type='hidden' name='sid' value='<?php echo $surveyid; ?>' />
        </form>

    </div>
    <?php
    App()->getClientScript()->registerScriptFile( 'http://js.selfimg.com.cn/image/js/jquery.uploadify.min.js');
    App()->getClientScript()->registerScriptFile( 'http://js.selfimg.com.cn/image/js/loaduploadify.js');
    App()->getClientScript()->registerScript('', '
        loadUpload("fileUploader", $("#fileUploader"), $("#fileUploader").attr("title"));
        $("#fileUploader").uploadify(\'settings\',\'onUploadSuccess\',function(file,data,response){
            console.log(data);
            if(file.name) $("#file_name").val(file.name);
            if(file.size) $("#file_size").val(file.size);
            
            var data = eval(\'(\'+data+\')\'); 
            if(data.status){
                $("#image_width").val(data.width);
                $("#image_height").val(data.height);
                $("#file_frontend_link").val(data.url);
            } else {
                $("#addImgBtn").attr("disabled", "disabled");
                $("#addImgBtn").html("<span style=\'color: red\'>"+data.message+"</span>");
                return false;
            }
        });
    ');
    ?>
</div>

