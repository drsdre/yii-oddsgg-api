<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_tournament".
 *
 * @property integer $id
 * @property string $Name
 * @property integer $LeagueId renamed from CategoryID
 * @property string $Timestamp
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGLeague $league
 */
class OddsGGTournament extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_tournament';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'Name', 'LeagueId', 'Timestamp'], 'required'],
            [['id', 'LeagueId'], 'integer'],
	        [
		        [ 'LeagueId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGLeague::className(),
		        'targetAttribute' => [ 'LeagueId' => 'id' ],
	        ],
            [['Name', 'Timestamp'], 'string'],
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
            'Name' => yii::t('app', 'Name'),
            'LeagueId' => yii::t('app', 'Category'),
            'Timestamp' => yii::t('app', 'Timestamp'),
        ];
    }


	/**
	 * Update or insert record
	 *
	 * @param int $id
	 * @param string $Name
	 * @param int $LeagueId
	 * @param int $Timestamp
	 *
	 * @return OddsGGTournament|static
	 */
	public static function upsert(int $id, string $Name, int $LeagueId, int $Timestamp) {
		// Find record by id
		$Record = self::findOne($id);

		// If no record found, make it
		if ( ! $Record) {
			$Record = new self();
			$Record->id = $id;
		}

		// Update parameters
		$Record->Name = $Name;
		$Record->LeagueId = $LeagueId;
		$Record->Timestamp = $Timestamp;

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
            ->orderBy('Name')
            ->all();

        return ArrayHelper::map($models, 'id', 'Name');
    }

	/**
	 * ==== Connections to other records
	 */

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getLeague()
    {
        return $this->hasOne(OddsGGLeague::className(), [ 'id' => 'LeagueId'])
                    ->inverseOf('tournaments');
    }
}
