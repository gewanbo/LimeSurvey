<?php
/**
 * This view displays the tabs for the question creation
 *
 * @var AdminController $this
 * @var Survey $oSurvey
 * @var array $eqrow
 */
?>


<!-- New question language tabs -->
<ul class="nav nav-tabs" style="margin-right: 8px;" >
    <li role="presentation" class="active">
        <a role="tab" data-toggle="tab" href="#<?php echo $oSurvey->language; ?>">
            <?php echo getLanguageNameFromCode($oSurvey->language,false); ?> (<?php eT("Base language"); ?>)
        </a>
    </li>
    <?php foreach  ($oSurvey->additionalLanguages as $addlanguage):?>
        <li role="presentation">
            <a data-toggle="tab" href="#<?php echo $addlanguage; ?>">
                <?php echo getLanguageNameFromCode($addlanguage,false); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Editors for each languages -->
<div class="tab-content" v-pre>

    <!-- Base Language tab-pane -->
    <div id="<?php echo $oSurvey->language; ?>" class="tab-pane fade in active">

        <!-- Question Code -->
        <div class="form-group">
                <label class=" control-label"  for='title'><?php eT("Code:"); ?></label>
                <div class="">
                    <?php echo CHtml::fileField("fileUploader",$eqrow['file_name'],array('class'=>'form-control','title' => 'generalimg','maxlength'=>'20','accept'=>"image/jpeg,image/png")); ?>
                    <span class='text-warning'><?php  eT("Required"); ?> </span>
                </div>
        </div>

        <!-- Question Text -->
        <div class="form-group">
                <label class=" control-label" for='question_<?php echo $oSurvey->language; ?>'><?php eT("Note:"); ?></label>
                <div class="">
                <div class="htmleditor input-group">
                    <?php echo CHtml::textArea("resource_{$oSurvey->language}",$eqrow['desc'],array('class'=>'form-control','cols'=>'60','rows'=>'2','id'=>"resource_{$oSurvey->language}")); ?>
                </div>
                </div>
        </div>

    </div>
</div>
