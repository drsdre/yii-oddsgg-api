<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_team".
 *
 * @property integer $id
 * @property string $name
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGMatch[] $homeMatches
 * @property OddsGGMatch[] $awayMatches
 */
class OddsGGTeam extends \yii\db\ActiveRecord
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
            [['id', 'name'], 'required'],
            [['id'], 'integer'],
            [['name'], 'string'],
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
            'name' => yii::t('app', 'name'),
        ];
    }

	/**
	 * Update or insert record
	 *
	 * @param $id
	 * @param $name
	 *
	 * @return OddsGGTeam|static
	 */
	public static function upsert($id, $name) {
		// Find record by id
		$Record = self::findOne($id);

		// If no record found, make it
		if ( ! $Record) {
			$Record = new self();
			$Record->id = $id;
		}

		// Update parameters
		$Record->name = $name;

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
        $models = static::find()->orderBy('name')->all();

        return ArrayHelper::map($models, 'id', 'name');
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
