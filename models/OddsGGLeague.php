<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_league".
 *
 * @property integer $id
 * @property string $CategoryName
 * @property integer $SportId
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGSport $sport
 * @property OddsGGTournament[] $tournaments
 */
class OddsGGLeague extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_league';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'CategoryName', 'SportId'], 'required'],
            [['id', 'SportId'], 'integer'],
	        [
		        [ 'SportId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGSport::className(),
		        'targetAttribute' => [ 'SportId' => 'id' ],
	        ],
            [['CategoryName'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'CategoryName' => yii::t('app', 'Category Name'),
            'SportId' => yii::t('app', 'Sport'),
        ];
    }

	/**
	 * Update or insert record
	 *
	 * @param $id
	 * @param $name
	 *
	 * @return OddsGGSport|static
	 */
	public static function upsert(int $id, string $CategoryName, int $SportId) {
		// Find record by id
		$Record = self::findOne($id);

		// If no record found, make it
		if ( ! $Record) {
			$Record = new self();
			$Record->id = $id;
		}

		// Update parameters
		$Record->CategoryName = $CategoryName;
		$Record->SportId = $SportId;

		// If record changed, save it
		if ( $Record->dirtyAttributes && ! $Record->save() ) {
			new Exception('Save '.self::className().' failed: '.print_r($Record->getErrors(), true));
		}

		return $Record;
	}

    /**
     * @return array
     */
    public static function getForDropdown()
    {
        $models = static::find()
            ->orderBy('CategoryName')
            ->all();

        return ArrayHelper::map($models, 'id', 'CategoryName');
    }

	/**
	 * ==== Connections to other records
	 */

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getSport()
    {
        return $this->hasOne(OddsGGSport::className(), ['id' => 'SportId'])
                    ->inverseOf('leagues');
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTournaments()
	{
		return $this->hasMany(OddsGGCTournament::className(), ['LeagueId' => 'id'])
		            ->inverseOf('league');
	}
}
