<?php

/**
 * Subview of surveybar_view.
 * @param $surveybar
 * @param $oSurvey
 * @param $surveyHasGroup
 */

?>

<!-- Add a new question -->
<?php if (isset($surveybar['buttons']['newresource'])):?>
    <?php if ($oSurvey->isActive): ?>
        <span class="btntooltip" data-toggle="tooltip" data-placement="bottom" title="<?php eT("This survey is currently active."); ?>" style="display: inline-block" data-toggle="tooltip" data-placement="bottom" title="<?php eT('Survey cannot be activated. Either you have no permission or there are no questions.'); ?>">
            <button type="button" class="btn btn-default btntooltip" disabled="disabled">
                <span class="icon-add"></span>
                <?php eT("Add new resource"); ?>
            </button>
        </span>
    <?php elseif (Permission::model()->hasSurveyPermission($oSurvey->sid, 'surveycontent', 'create')): ?>
        <?php if (!$surveyHasGroup): ?>
            <span class="btntooltip" data-toggle="tooltip" data-placement="bottom" title="<?php eT("You must first create a question group."); ?>" style="display: inline-block" data-toggle="tooltip" data-placement="bottom" title="<?php eT('Survey cannot be activated. Either you have no permission or there are no questions.'); ?>">
                <button type="button" class="btn btn-default btntooltip" disabled="disabled">
                    <span class="icon-add"></span>
                    <?php eT("Add new resource"); ?>
                </button>
            </span>
        <?php else :?>
            <a class="btn btn-default" href='<?php echo $this->createUrl("admin/resources/sa/newResource/surveyid/".$oSurvey->sid);
    ?>' role="button">
                <span class="icon-add"></span>
                <?php eT("Add new resource"); ?>
            </a>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
