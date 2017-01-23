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
 * @property integer $Timestamp
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGLeague $league
 */
class OddsGGTournament extends ActiveRecordWithUpsert
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
            [['id', 'LeagueId', 'Timestamp'], 'integer'],
	        [
		        [ 'LeagueId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGLeague::className(),
		        'targetAttribute' => [ 'LeagueId' => 'id' ],
	        ],
            [['Name'], 'string'],
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


	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMatches()
	{
		return $this->hasMany(OddsGGMatch::className(), [ 'TournamentId' => 'id'])
		            ->inverseOf('tournament');
	}
}
