<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_team".
 *
 * @property integer $id
 * @property string $Name
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGMatch[] $homeMatches
 * @property OddsGGMatch[] $awayMatches
 */
class OddsGGTeam extends ActiveRecordWithUpsert
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_team';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'Name'], 'required'],
            [['id'], 'integer'],
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
        ];
    }

    /**
     * @return array
     */
    public static function getForDropdown()
    {
        $models = static::find()->orderBy('Name')->all();

        return ArrayHelper::map($models, 'id', 'Name');
    }

	/**
	 * ==== Connections to other records
	 */

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getHomeMatches()
    {
        return $this->hasMany(OddsGGMatch::className(), ['HomeTeamId' => 'id'])
            ->inverseOf('homeTeam');
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAwayMatches()
	{
		return $this->hasMany(OddsGGMatch::className(), ['AwayTeamId' => 'id'])
		            ->inverseOf('awayTeam');
	}
}
