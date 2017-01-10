<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_sport".
 *
 * @property integer $id
 * @property string $name
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGLeague[] $leagues
 * @property OddsGGMatch[] $matches
 */
class OddsGGSport extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_sport';
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
	 * @return OddsGGSport|static
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
    public function getLeagues()
    {
        return $this->hasMany(OddsGGLeague::className(), ['SportId' => 'id'])
            ->inverseOf('sport');
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMatches()
	{
		return $this->hasMany(OddsGGMatch::className(), ['SportId' => 'id'])
		            ->inverseOf('sport');
	}
}
