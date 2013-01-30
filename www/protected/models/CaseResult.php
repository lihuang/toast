<?php
/**
 * The case result model.
 */
class CaseResult extends Model
{
    /**
     * The result id.
     * @var integer
     */
    public $id;
    /**
     * The command run id of result.
     * @var integer
     */
    public $command_run_id;
    /**
     * The case id of result.
     * @var integer
     */
    public $test_case_id;
    /**
     * The case name of result.
     * @var string;
     */
    public $case_name;
    /**
     * The result.
     * @var integer
     */
    public $case_result;
    /**
     * The result info.
     * @var string 
     */
    public $case_info;
    /**
     * The create time of result.
     * @var string
     */
    public $create_time;
    /**
     * The flag of the last case result.
     * @var boolean
     */
    public $is_last;

    /**
     * Passed 
     */
    const RESULT_PASSED = 0;
    /**
     * Failed 
     */
    const RESULT_FAILED = 1;
    /**
     * Skipped 
     */
    const RESULT_SKIPPED = 2;
    /**
     * Blocked
     */
    const RESULT_BLOCKED = 3;

    /**
     * Get a instance.
     * Just implement parent model function.
     *  
     * @param string $className
     * @return CaseResult $model
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Get this model's table name in database. 
     * 
     * @return string $tableName
     */
    public function tableName()
    {
        return 'case_result';
    }

    /**
     * Get the validation rule array.
     * 
     * @return array $rules 
     */
    public function rules()
    {
        return array(
            array('case_name, case_result', 'required'),
            array('command_run_id, test_case_id, case_result', 'numerical', 'integerOnly' => true),
            array('command_run_id, test_case_id, case_result, case_name, case_info', 'safe'),
        );
    }

    /**
     * Get the relation array.
     * 
     * @return array $relations 
     */
    public function relations()
    {
        return array(
            'commandrun' => array(self::BELONGS_TO, 'CommandRun', 'command_run_id'),
            'case' => array(self::BELONGS_TO, 'TestCase', 'test_case_id'),
        );
    }

    /**
     * Get attribute labels
     * @return array $label
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('CaseResult', 'Id'),
            'command_run_id' => Yii::t('CaseResult', 'Command Run Id'),
            'case_id' => Yii::t('CaseResult', 'Case Id'),
            'case_name' => Yii::t('CaseResult', 'Case Name'),
            'case_result' => Yii::t('CaseResult', 'Result'),
            'case_info' => Yii::t('CaseResult', 'Append Info'),
            'create_time' => Yii::t('CaseResult', 'Create Time'),
        );
    }

    /**
     * Set action user and datetime before save.
     * @return boolean $flag 
     */
    public function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->create_time = date(Yii::app()->params->dateFormat);
        }

        return parent::beforeSave();
    }

    /**
     * Get result options.
     * @return array $options 
     */
    public function getResultOptions()
    {
        return array(
            self::RESULT_PASSED => Yii::t('CaseResult', 'Result Passed'),
            self::RESULT_FAILED => Yii::t('CaseResult', 'Result Failed'),
            self::RESULT_SKIPPED => Yii::t('CaseResult', 'Result Skipped'),
            self::RESULT_BLOCKED => Yii::t('CaseResult', 'Result Blocked')
        );
    }

    /**
     * Get result text.
     * @return string $text
     */
    public function getResultText()
    {
        $results = $this->getResultOptions();
        return isset($results[$this->case_result]) ? $results[$this->case_result]
                    : Yii::t('Run', 'Unknown result({result})',
                        array('{result}' => $this->case_result));
    }

    /**
     * Get result css class as style.
     * @return string $style
     */
    public function getResultStyle()
    {
        $style = '';
        switch($this->case_result)
        {
            case CaseResult::RESULT_PASSED :
                {
                    $style = 'passed';
                    break;
                }
            case CaseResult::RESULT_FAILED :
                {
                    $style = 'failed';
                    break;
                }
            case CaseResult::RESULT_SKIPPED :
                {
                    $style = 'skipped';
                    break;
                }
            case CaseResult::RESULT_BLOCKED :
                {
                    $style = 'blocked';
                    break;
                }
            default :
                {
                    break;
                }
        }
        return $style;
    }

    public function getResultAmounts($condition)
    {
        return array(
            self::RESULT_PASSED => $this->countByAttributes(array('case_result' => self::RESULT_PASSED),
                    $condition),
            self::RESULT_FAILED => $this->countByAttributes(array('case_result' => self::RESULT_FAILED),
                    $condition),
            self::RESULT_SKIPPED => $this->countByAttributes(array('case_result' => self::RESULT_SKIPPED),
                    $condition),
            self::RESULT_BLOCKED => $this->countByAttributes(array('case_result' => self::RESULT_BLOCKED),
                    $condition),
        );
    }

    /**
     * Search case result, return case result data provider.
     * @param integer $pageSize
     * 
     * @return CActiveDataProvider $caseResults
     */
    public function search($pageSize, $condition = null)
    {
        $criteria = $condition;
        if(!$criteria)
        {
            $criteria = new CDbCriteria();
        }
        else if(is_string($condition))
        {
            $criteria = new CDbCriteria();
            // TODO: do not use the name field for searching
            $this->case_name = $condition;
        }

        $criteria->select = 't.id as id, test_case_id, case_name, case_result, case_info, '
                . 't.create_time as create_time';
        $criteria->compare('test_case_id', $this->case_name, true, 'OR');
        $criteria->compare('case_name', $this->case_name, true, 'OR');
        $criteria->compare('command_run_id', $this->command_run_id);
        $criteria->compare('test_case_id', $this->test_case_id);
        $criteria->compare('case_result', $this->case_result);

        return new CActiveDataProvider(__CLASS__, array(
                    'criteria' => $criteria,
                    'pagination' => array(
                        'pageSize' => $pageSize
                    ),
                    'sort' => array(
                        'defaultOrder' => 'create_time DESC'
                    ),
                ));
    }

    public function getCaseInfo()
    {
        $caseInfo = $this->case_info;
        $caseInfo = preg_replace('/\[img case=(.*)\](.*)\[\/img\]/i',
                '<div style="display: inline-block; margin-right: 20px"><a href="$2" rel="lightbox[1]" title="$1"><img height="100px" src="$2" /></a></div>',
                $caseInfo);
        return $caseInfo;
    }
}
?>