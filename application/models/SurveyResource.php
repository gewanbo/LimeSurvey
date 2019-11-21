<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


/**
 * Class Resources
 *
 * @property string $id
 * @property string $survey_id Survey ID
 * @property string $file_name The file name of resource
 * @property string $file_size
 * @property string $fronted_link
 * @property string $desc The description of resource
 * @property string $thumb_link
 * @property integer $image_width
 * @property integer $image_height
 * @property string $language
 * @property integer $status
 * @property integer $create_ts
 * @property string $update_ts
 *
 * @inheritdoc
 */
class SurveyResource extends LSActiveRecord
{

    /** @var string $group_name Stock the active group_name for questions list filtering */
    public $group_name;
    public $gid;
    public $language;

    /**
     * @inheritdoc
     * @return SurveyResource
     */
    public static function model($class = __CLASS__)
    {
        /** @var SurveyResource $model */
        $model = parent::model($class);
        return $model;
    }

    /** @inheritdoc */
    public function tableName()
    {
        return '{{survey_resources}}';
    }

    /** @inheritdoc */
    public function primaryKey()
    {
        return array('id');
    }

    /** @inheritdoc */
    public function relations()
    {
        return array(
            'survey' => array(self::BELONGS_TO, 'Survey', 'sid')
        );
    }

    /**
     * @inheritdoc
     * TODO: make it easy to read (if possible)
     */
    public function rules()
    {
        $aRules = array(
            array('file_name', 'required', 'on' => 'update, insert', 'message' => gT('Resource name may not be empty.', 'unescaped')),
            array('file_name', 'length', 'min' => 5, 'max' => 200, 'on' => 'update, insert'),
            array('survey_id,file_type,file_size,image_width,image_height', 'numerical', 'integerOnly' => true),
            array('language', 'length', 'min' => 2, 'max' => 20), // in array languages ?
            array('file_type', 'length', 'min' => 1, 'max' => 10)
        );
        return $aRules;
    }

    /**
     * Rewrites sort order for questions in a group
     *
     * @static
     * @access public
     * @param int $gid
     * @param int $surveyid
     * @return void
     */
    public static function updateSortOrder($gid, $surveyid)
    {
        $questions = self::model()->findAllByAttributes(
            array('gid' => $gid, 'sid' => $surveyid, 'parent_qid' => 0, 'language' => Survey::model()->findByPk($surveyid)->language),
            array('order' => 'question_order')
        );

        $p = 0;
        foreach ($questions as $question) {
            $question->question_order = $p;
            $question->save();
            $p++;
        }
    }


    /**
     * Fix sort order for questions in a group
     * @param int $gid
     * @param string $language
     * @param int $position
     */
    public function updateQuestionOrder($gid, $language, $position = 0)
    {
        $data = Yii::app()->db->createCommand()->select('qid')
            ->where(array('and', 'gid=:gid', 'language=:language', 'parent_qid=0'))
            ->order('question_order, title ASC')
            ->from('{{questions}}')
            ->bindParam(':gid', $gid, PDO::PARAM_INT)
            ->bindParam(':language', $language, PDO::PARAM_STR)
            ->query();

        $position = intval($position);
        foreach ($data->readAll() as $row) {
            Yii::app()->db->createCommand()->update($this->tableName(),
                array('question_order' => $position), 'qid=' . $row['qid']);
            $position++;
        }
    }

    /**
     * This function returns an array of the advanced attributes for the particular question
     * including their values set in the database
     *
     * @access public
     * @param int $iQuestionID The question ID - if 0 then all settings will use the default value
     * @param string $sQuestionType The question type
     * @param int $iSurveyID
     * @param string $sLanguage If you give a language then only the attributes for that language are returned
     * @return array
     */
    public function getAdvancedSettingsWithValues($iQuestionID, $sQuestionType, $iSurveyID, $sLanguage = null)
    {
        if (is_null($sLanguage)) {
            $aLanguages = array_merge(array(Survey::model()->findByPk($iSurveyID)->language), Survey::model()->findByPk($iSurveyID)->additionalLanguages);
        } else {
            $aLanguages = array($sLanguage);
        }
        $aAttributeValues = QuestionAttribute::model()->getQuestionAttributes($iQuestionID, $sLanguage);
        // TODO: move getQuestionAttributesSettings() to QuestionAttribute model to avoid code duplication
        $aAttributeNames = \LimeSurvey\Helpers\questionHelper::getQuestionAttributesSettings($sQuestionType);

        // If the question has a custom template, we first check if it provides custom attributes

        if (!is_null($sLanguage)) {
            $oQuestion = Question::model()->findByPk(array('qid' => $iQuestionID, 'language' => $sLanguage));
        } else {
            $oQuestion = Question::model()->find(array('condition' => 'qid=:qid', 'params' => array(':qid' => $iQuestionID)));
        }
        $aAttributeNames = self::getQuestionTemplateAttributes($aAttributeNames, $aAttributeValues, $oQuestion);

        uasort($aAttributeNames, 'categorySort');
        foreach ($aAttributeNames as $iKey => $aAttribute) {
            if ($aAttribute['i18n'] == false) {
                if (isset($aAttributeValues[$aAttribute['name']])) {
                    $aAttributeNames[$iKey]['value'] = $aAttributeValues[$aAttribute['name']];
                } else {
                    $aAttributeNames[$iKey]['value'] = $aAttribute['default'];
                }
            } else {
                foreach ($aLanguages as $sLanguage) {
                    if (isset($aAttributeValues[$aAttribute['name']][$sLanguage])) {
                        $aAttributeNames[$iKey][$sLanguage]['value'] = $aAttributeValues[$aAttribute['name']][$sLanguage];
                    } else {
                        $aAttributeNames[$iKey][$sLanguage]['value'] = $aAttribute['default'];
                    }
                }
            }
        }

        return $aAttributeNames;
    }

    /**
     * @param array $aAttributeNames
     * @param array $aAttributeValues
     * @param Question $oQuestion
     * @return mixed
     */
    public static function getQuestionTemplateAttributes($aAttributeNames, $aAttributeValues, $oQuestion)
    {
        if (isset($aAttributeValues['question_template'])) {
            if ($aAttributeValues['question_template'] != 'core') {

                $oQuestionTemplate = QuestionTemplate::getInstance($oQuestion);
                if ($oQuestionTemplate->bHasCustomAttributes) {
                    // Add the custom attributes to the list
                    foreach ($oQuestionTemplate->oConfig->custom_attributes->attribute as $oCustomAttribute) {
                        $sAttributeName = (string)$oCustomAttribute->name;
                        $aCustomAttribute = json_decode(json_encode((array)$oCustomAttribute), 1);
                        $aCustomAttribute = array_merge(
                            QuestionAttribute::getDefaultSettings(),
                            array("category" => gT("Template")),
                            $aCustomAttribute
                        );
                        $aAttributeNames[$sAttributeName] = $aCustomAttribute;
                    }
                }
            }
        }
        return $aAttributeNames;
    }

    public function getTypeGroup()
    {

    }

    /**
     * TODO: replace this function call by $oSurvey->questions defining a relation in SurveyModel
     * @param integer $sid
     * @param string $language
     * @return CDbDataReader
     */
    public function getResources($sid, $language)
    {
        return Yii::app()->db->createCommand()
            ->select()
            ->from(self::tableName())
            ->where('survey_id=:sid')
            ->order('update_ts desc')
            ->bindParam(":sid", $sid, PDO::PARAM_INT)
            //->bindParam(":language", $language, PDO::PARAM_STR)
            ->queryAll();
    }


    /**
     * This function is only called from surveyadmin.php
     * @param integer $iSurveyID
     * @param string $sLanguage
     * @param string|boolean $sCondition
     * @return array
     */
    public function getQuestionsWithSubQuestions($iSurveyID, $sLanguage, $sCondition = false)
    {
        $command = Yii::app()->db->createCommand()
            ->select('{{questions}}.*, q.qid as sqid, q.title as sqtitle,  q.question as sqquestion, ' . '{{groups}}.*')
            ->from($this->tableName())
            ->leftJoin('{{questions}} q', "q.parent_qid = {{questions}}.qid AND q.language = {{questions}}.language")
            ->join('{{groups}}', "{{groups}}.gid = {{questions}}.gid  AND {{questions}}.language = {{groups}}.language");
        $command->where("({{questions}}.sid = '$iSurveyID' AND {{questions}}.language = '$sLanguage' AND {{questions}}.parent_qid = 0)");

        if ($sCondition != false) {
            $command->where("({{questions}}.sid = :iSurveyID AND {{questions}}.language = :sLanguage AND {{questions}}.parent_qid = 0) AND {$sCondition}")
                ->bindParam(":iSurveyID", $iSurveyID, PDO::PARAM_STR)
                ->bindParam(":sLanguage", $sLanguage, PDO::PARAM_STR);
        }
        $command->order("{{groups}}.group_order asc, {{questions}}.question_order asc");

        return $command->query()->readAll();
    }


    /**
     * Delete a resource
     *
     * @param string $iResourceId
     * @return void
     */
    public static function deleteResourceById($iResourceId)
    {
        if (is_numeric($iResourceId)) {
            self::model()->deleteByPk($iResourceId);
        }
    }

    /**
     * This function is called from everywhere, which is quiet weird...
     * TODO: replace it everywhere by Answer::model()->findAll([Critieria Object]) (thumbs up)
     */
    function getAllRecords($condition, $order = false)
    {
        $command = Yii::app()->db->createCommand()->select('*')->from($this->tableName())->where($condition);
        if ($order != false) {
            $command->order($order);
        }
        return $command->query();
    }


    /**
     * TODO: replace it everywhere by Answer::model()->findAll([Critieria Object])
     * @param string $fields
     * @param mixed $condition
     * @param string $orderby
     * @return array
     */
    public function getQuestionsForStatistics($fields, $condition, $orderby = false)
    {
        $command = Yii::app()->db->createCommand()
            ->select($fields)
            ->from(self::tableName())
            ->where($condition);
        if ($orderby != false) {
            $command->order($orderby);
        }
        return $command->queryAll();
    }

    /**
     * @param integer $surveyid
     * @param string $language
     * @return array
     */
    public function getQuestionList($surveyid, $language)
    {
        $query = "SELECT questions.*, question_groups.group_name, question_groups.group_order"
            . " FROM {{questions}} as questions, {{groups}} as question_groups"
            . " WHERE question_groups.gid=questions.gid"
            . " AND question_groups.language=:language1"
            . " AND questions.language=:language2"
            . " AND questions.parent_qid=0"
            . " AND questions.sid=:sid";
        return Yii::app()->db->createCommand($query)
            ->bindParam(":language1", $language, PDO::PARAM_STR)
            ->bindParam(":language2", $language, PDO::PARAM_STR)
            ->bindParam(":sid", $surveyid, PDO::PARAM_INT)->queryAll();
    }



    public function search()
    {
        $pageSize = Yii::app()->user->getState('pageSize', Yii::app()->params['defaultPageSize']);

        $sort = new CSort();
        $sort->attributes = array(
            'id' => array(
                'asc' => 't.id asc',
                'desc' => 't.id desc',
            ),
            'create_ts' => array(
                'asc' => 't.create_ts asc',
                'desc' => 't.create_ts desc',
            ),
            'update_ts' => array(
                'asc' => 't.update_ts asc',
                'desc' => 't.update_ts desc',
            )
        );

        $sort->defaultOrder = array(
            'id' => CSort::SORT_ASC,
        );

        $criteria = new CDbCriteria;
        $criteria->compare("t.survey_id", $this->survey_id, false, 'AND');
        //$criteria->compare("t.language", $this->language, false, 'AND');

//        $criteria2 = new CDbCriteria;
//        $criteria2->compare('t.title', $this->title, true, 'OR');
//        $criteria2->compare('t.question', $this->title, true, 'OR');
//        $criteria2->compare('t.type', $this->title, true, 'OR');
//        /* search id exactly */
//        if (is_numeric($this->title)) {
//            $criteria2->compare('t.qid', $this->title, false, 'OR');
//        }
//        if ($this->gid != '' and is_numeric($this->gid)) {
//            $criteria->compare('groups.gid', $this->gid, false, 'AND');
//        }
//
//        $criteria->mergeWith($criteria2, 'AND');

        $dataProvider = new CActiveDataProvider('SurveyResource', array(
            'criteria' => $criteria,
            'sort' => $sort,
            'pagination' => array(
                'pageSize' => $pageSize,
            ),
        ));
        return $dataProvider;
    }

    /**
     * Make sure we don't save a new question group
     * while the survey is active.
     *
     * @return bool
     */
    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            return true;
        } else {
            return false;
        }
    }


    public function getBasicFieldName()
    {
        if ($this->parent_qid != 0) {
            /* Fix #15228: This survey throw a Error when try to print : seems subquestion gid can be outdated */
            // Use parents relation
            if (!empty($this->parents)) { // Maybe need to throw error or find it if it's not set ?
                return "{$this->parents->sid}X{$this->parents->gid}X{$this->parent_qid}";
            }
            return "{$this->sid}X{$this->gid}X{$this->parent_qid}";
        }
        return "{$this->sid}X{$this->gid}X{$this->qid}";
    }

    public function getFieldName()
    {
        return $this->getBasicFieldName() . $this->title;
    }

    /**
     * @return QuestionAttribute[]
     */
    public function getQuestionAttributes()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('qid=:qid');
        $criteria->params = [':qid' => $this->qid];
        return QuestionAttribute::model()->findAll($criteria);
    }

    /**
     * @param array $data
     * @return boolean|null
     */
    public function insertRecords($data)
    {
        $oRecord = new self;
        foreach ($data as $k => $v) {
            $oRecord->$k = $v;
        }
        if ($oRecord->validate()) {
            return $oRecord->save();
        }
        Yii::log(\CVarDumper::dumpAsString($oRecord->getErrors()), 'warning', 'application.models.Question.insertRecords');
    }

    public function getbuttons()
    {

        $previewUrl  = Yii::app()->createUrl("survey/index/action/previewresource/resource_id/") . '/'.$this->id;
        $editurl     = Yii::app()->createUrl("admin/questions/sa/editresource/resource_id/$this->id");
        $button      = '<a class="btn btn-default open-preview"  data-toggle="tooltip" title="'.gT("Resource preview").'"  aria-data-url="'.$previewUrl.'" aria-data-sid="'.$this->survey_id.'" aria-data-language="" href="#" role="button" ><span class="fa fa-eye"  ></span></a> ';

        if (Permission::model()->hasSurveyPermission($this->survey_id, 'surveycontent', 'update')) {
            $button .= '<a class="btn btn-default"  data-toggle="tooltip" title="'.gT("Edit resource").'" href="'.$editurl.'" role="button"><span class="fa fa-pencil" ></span></a>';
        }

//        if (Permission::model()->hasSurveyPermission($this->survey_id, 'surveycontent', 'read')) {
//            $button .= '<a class="btn btn-default"  data-toggle="tooltip" title="'.gT("Question summary").'" href="'.$url.'" role="button"><span class="fa fa-list-alt" ></span></a>';
//        }


        if (Permission::model()->hasSurveyPermission($this->survey_id, 'surveycontent', 'delete')) {
            $button .= '<a class="btn btn-default"  data-toggle="tooltip" title="'.gT("Delete").'" href="#" role="button"'
                ." onclick='$.bsconfirm(\"".CHtml::encode(gT("Deleting will not restore. Are you sure you want to continue?"))
                ."\", {\"confirm_ok\": \"".gT("Yes")."\", \"confirm_cancel\": \"".gT("No")."\"}, function() {"
                . convertGETtoPOST(Yii::app()->createUrl("admin/resources/sa/delete/", ["rid" => $this->id, "surveyid" => $this->survey_id]))
                ."});'>"
                .' <i class="text-danger fa fa-trash"></i>
                </a>';
        }

        return $button;
    }

}
