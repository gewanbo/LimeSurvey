<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Resources
 *
 * @package LimeSurvey
 * @access public
 */
class Resources extends Survey_Common_Action
{
    /**
     * Initiates the survey action, checks for superadmin permission
     *
     * @access public
     * @param CController $controller
     * @param string $id
     */
    public function __construct($controller, $id)
    {
        parent::__construct($controller, $id);

    }

    /**
     * Loads list of surveys and its few quick properties.
     *
     * @access public
     * @return void
     */
    public function index()
    {

    }

    /**
     * List Image resources
     */
    public function listresources($surveyid)
    {
        $iSurveyID = sanitize_int($surveyid);
        // Reinit LEMlang and LEMsid: ensure LEMlang are set to default lang, surveyid are set to this survey id
        // Ensure Last GetLastPrettyPrintExpression get info from this sid and default lang
        LimeExpressionManager::SetEMLanguage(Survey::model()->findByPk($iSurveyID)->language);
        LimeExpressionManager::SetSurveyId($iSurveyID);
        LimeExpressionManager::StartProcessingPage(false, true);

        $oSurvey = Survey::model()->findByPk($iSurveyID);
        $aData   = array();

        $aData['oSurvey']                               = $oSurvey;
        $aData['surveyid']                              = $iSurveyID;
        $aData['display']['menu_bars']['listresources'] = true;
        $aData['sidemenu']['listresources']             = true;
        $aData['surveybar']['returnbutton']['url']      = $this->getController()->createUrl("admin/resources/sa/listresources");
        $aData['surveybar']['returnbutton']['text']     = gT('Return to resource list');
        $aData['surveybar']['buttons']['newresource']   = true;

        $aData["surveyHasGroup"]        = $oSurvey->groups;
        $aData['subaction']             = gT("Resources in this survey");
        $aData['title_bar']['title']    = $oSurvey->currentLanguageSettings->surveyls_title." (".gT("ID").":".$iSurveyID.")";

        $this->_renderWrappedTemplate('survey', array(), $aData);
    }

    public function getData(){
        return [];
    }

    /**
     * Function responsible to delete a resource.
     *
     * @access public
     * @param int $iResourceID
     * @param int $iSurveyID
     */
    public function delete($iResourceID, $iSurveyID)
    {
        $aData = $aViewUrls = array();
        $aData['surveyid'] = $iSurveyID = (int) $iSurveyID;
        $survey = Survey::model()->findByPk($iSurveyID);

        $aData['sidemenu']['state'] = false;
        $aData['title_bar']['title'] = $survey->currentLanguageSettings->surveyls_title." (".gT("ID").":".$iSurveyID.")";
        $aData['sidemenu']['state'] = false;


        if (Permission::model()->hasSurveyPermission($iSurveyID, 'survey', 'delete')) {
            SurveyResource::model()->deleteResourceById($iResourceID);
            Yii::app()->session['flashmessage'] = gT("Resource deleted.");
            $this->getController()->redirect(array("admin/resources/sa/listresources&surveyid=" . $iSurveyID));
        } else {
            Yii::app()->user->setFlash('error', gT("Access denied"));
            $this->getController()->redirect(Yii::app()->request->urlReferrer);
        }

        $this->_renderWrappedTemplate('survey', $aViewUrls, $aData);
    }

    /**
     * New system of rendering content
     * Based on yii submenu rendering
     *
     * @uses self::_generalTabEditSurvey()
     * @uses self::_pluginTabSurvey()
     * @uses self::_tabPresentationNavigation()
     * @uses self::_tabPublicationAccess()
     * @uses self::_tabNotificationDataManagement()
     * @uses self::_tabTokens()
     * @uses self::_tabPanelIntegration()
     * @uses self::_tabResourceManagement()
     *
     * @param int $iSurveyID
     * @param string $subaction
     * @return void
     */
    public function rendersidemenulink($iSurveyID, $subaction)
    {
        $aViewUrls = $aData = [];
        $menuaction = (string) $subaction;
        $iSurveyID = (int) $iSurveyID;
        $survey = Survey::model()->findByPk($iSurveyID);

        //Get all languages
        $grplangs = $survey->additionalLanguages;
        $baselang = $survey->language;
        array_unshift($grplangs, $baselang);

        //@TODO add language checks here
        $menuEntry = SurveymenuEntries::model()->find('name=:name', array(':name'=>$menuaction));

        if (!(Permission::model()->hasSurveyPermission($iSurveyID, $menuEntry->permission, $menuEntry->permission_grade))) {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array('admin/survey', 'sa'=>'view', 'surveyid'=>$iSurveyID));
        }

        $templateData = is_array($menuEntry->data) ? $menuEntry->data : [];

        if (!empty($menuEntry->getdatamethod)) {
            $templateData = array_merge($templateData, call_user_func_array(array($this, $menuEntry->getdatamethod), array('survey'=>$survey)));
        }

        $templateData = array_merge($this->_getGeneralTemplateData($iSurveyID), $templateData);
        $this->_registerScriptFiles();

        // override survey settings if global settings exist
        $templateData['showqnumcode'] = getGlobalSetting('showqnumcode') !=='choose'?getGlobalSetting('showqnumcode'):$survey->showqnumcode;
        $templateData['shownoanswer'] = getGlobalSetting('shownoanswer') !=='choose'?getGlobalSetting('shownoanswer'):$survey->shownoanswer;
        $templateData['showgroupinfo'] = getGlobalSetting('showgroupinfo') !=='2'?getGlobalSetting('showgroupinfo'):$survey->showgroupinfo;
        $templateData['showxquestions'] = getGlobalSetting('showxquestions') !=='choose'?getGlobalSetting('showxquestions'):$survey->showxquestions;

        //Start collecting aData
        $aData['surveyid'] = $iSurveyID;
        $aData['menuaction'] = $menuaction;
        $aData['template'] = $menuEntry->template;
        $aData['templateData'] = $templateData;
        $aData['surveyls_language'] = $baselang;
        $aData['action'] = $menuEntry->action;
        $aData['entryData'] = $menuEntry->attributes;
        $aData['dateformatdetails'] = getDateFormatData(Yii::app()->session['dateformat']);
        $aData['subaction'] = $menuEntry->title;
        $aData['display']['menu_bars']['surveysummary'] = $menuEntry->title;
        $aData['title_bar']['title'] = $survey->currentLanguageSettings->surveyls_title." (".gT("ID").":".$iSurveyID.")";
        $aData['surveybar']['buttons']['view'] = true;
        $aData['surveybar']['savebutton']['form'] = 'globalsetting';
        $aData['surveybar']['savebutton']['useformid'] = 'true';
        $aData['surveybar']['saveandclosebutton']['form'] = true;
        $aData['surveybar']['closebutton']['url'] = $this->getController()->createUrl("'admin/survey/sa/view/", ['surveyid' => $iSurveyID]); // Close button

        $aViewUrls[] = $menuEntry->template;

        $this->_renderWrappedTemplate('survey', $aViewUrls, $aData);
    }

    /**
     * Edit surveytexts and general settings
     */
    public function surveygeneralsettings($iSurveyID)
    {
        $aViewUrls = $aData = array();
        $aData['surveyid'] = $iSurveyID = sanitize_int($iSurveyID);
        $survey = Survey::model()->findByPk($iSurveyID);
        $aData['oSurvey'] = $survey;

        if (!(Permission::model()->hasSurveyPermission($iSurveyID, 'surveylocale', 'read') || Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'read'))) {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array('admin/survey', 'sa'=>'view', 'surveyid'=>$iSurveyID));
            Yii::app()->end();
        }

        $this->_registerScriptFiles();
        if (Permission::model()->hasSurveyPermission($iSurveyID, 'surveylocale', 'update')) {
            Yii::app()->session['FileManagerContext'] = "edit:survey:{$iSurveyID}";
        }

        //This method creates the text edition and the general settings
        $aData['panels'] = [];

        Yii::app()->loadHelper("admin/htmleditor");

        $aData['scripts'] = PrepareEditorScript(false, $this->getController());

        $aTabTitles = $aTabContents = array();
        foreach ($survey->allLanguages as $sLang) {
            // this one is created to get the right default texts fo each language
            Yii::app()->loadHelper('database');
            Yii::app()->loadHelper('surveytranslator');

            $esrow = SurveyLanguageSetting::model()->findByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $sLang))->getAttributes();
            $aTabTitles[$sLang] = getLanguageNameFromCode($esrow['surveyls_language'], false);

            if ($esrow['surveyls_language'] == $survey->language) {
                $aTabTitles[$sLang] .= ' ('.gT("Base language").')';
            }

            $aData['esrow'] = $esrow;
            $aData['action'] = "surveygeneralsettings";
            $aData['dateformatdetails'] = getDateFormatData(Yii::app()->session['dateformat']);
            $aTabContents[$sLang] = $this->getController()->renderPartial('/admin/survey/editLocalSettings_view', $aData, true);
        }

        $aData['aTabContents'] = $aTabContents;
        $aData['aTabTitles'] = $aTabTitles;

        $esrow = self::_fetchSurveyInfo('editsurvey', $iSurveyID);
        $aData['esrow'] = $esrow;
        $aData['has_permissions'] = Permission::model()->hasSurveyPermission($iSurveyID, 'surveylocale', 'update');

        $aData['display']['menu_bars']['surveysummary'] = "surveygeneralsettings";
        $tempData = $aData;

        $aData['settings_data'] = $tempData;


        $aData['sidemenu']['state'] = false;


        $aData['title_bar']['title'] = $survey->currentLanguageSettings->surveyls_title." (".gT("ID").":".$iSurveyID.")";
        $aData['surveybar']['savebutton']['form'] = 'globalsetting';
        $aData['surveybar']['savebutton']['useformid'] = 'true';
        if (Permission::model()->hasSurveyPermission($iSurveyID, 'surveysettings', 'update') || Permission::model()->hasSurveyPermission($iSurveyID, 'surveylocale', 'update')) {
            $aData['surveybar']['saveandclosebutton']['form'] = true;
        } else {
            unset($aData['surveybar']['savebutton']['form']);
        }

        $aData['surveybar']['closebutton']['url'] = 'admin/survey/sa/view/surveyid/'.$iSurveyID; // Close button

        $aViewUrls[] = 'editLocalSettings_main_view';
        $this->_renderWrappedTemplate('survey', $aViewUrls, $aData);
    }

    public function newResource($surveyid)
    {
//        if (!Permission::model()->hasSurveyPermission($surveyid, 'surveycontent', 'create')) {
//            Yii::app()->user->setFlash('error', gT("Access denied"));
//            $this->getController()->redirect(Yii::app()->request->urlReferrer);
//        }

        $aData = [];
        $surveyid = $iSurveyID = $aData['surveyid'] = sanitize_int($surveyid);


        $aData['subaction'] = gT('Add a new question');
        $aData['surveybar']['importquestion'] = false;
        $aData['surveybar']['savebutton']['form'] = 'frmeditgroup';
        $aData['surveybar']['saveandclosebutton']['form'] = 'frmeditgroup';
        $aData['surveybar']['closebutton']['url'] = '/admin/survey/sa/listresources/surveyid/'.$iSurveyID; // Close button


        Yii::app()->session['FileManagerContext'] = "create:resource:{$surveyid}";

        $baselang = Survey::model()->findByPk($surveyid)->language;

        $eqrow = [];
        $eqrow['language'] = $baselang;
        $eqrow['file_name'] = '';
        $eqrow['desc'] = '';
        $eqrow['help'] = '';
        $eqrow['lid'] = 0;
        $eqrow['lid1'] = 0;
        $eqrow['gid'] = null;
        $eqrow['other'] = 'N';
        $eqrow['mandatory'] = 'N';
        $eqrow['preg'] = '';
        $eqrow['relevance'] = 1;
        $eqrow['group_name'] = '';
        $eqrow['modulename'] = '';
        $eqrow['conditions_number'] = false;
        $eqrow['type'] = 'T';

        if (isset($_GET['gid'])) {
            $eqrow['gid'] = $_GET['gid'];
        }
        $aData['eqrow'] = $eqrow;
        $aData['groupid'] = $eqrow['gid'];
        $qid = null;


        $aData['adding'] = true;
        $aData['copying'] = false;

        $aData['aqresult'] = '';
        $aData['action'] = 'newresource';

        ///////////
        // sidemenu
        ///////////
        // sidemenu
        $aData['sidemenu']['state'] = false;
        $aData['sidemenu']['explorer']['state'] = true;

        $aViewUrls = [];
        $aViewUrls['editResource_view'][] = $aData;


        $this->_renderWrappedTemplate('survey/Resource', $aViewUrls, $aData);
    }

}
