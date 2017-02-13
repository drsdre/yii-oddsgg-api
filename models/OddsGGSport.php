<?php
/**
 * This file is part of the Yii2-oddsgg-api extension
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG\models;

use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_sport".
 *
 * @property integer $id
 * @property string $Name
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGLeague[] $leagues
 * @property OddsGGMatch[] $matches
 */
class OddsGGSport extends ActiveRecord
{
	use ActiveRecordUpsertTrait;

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
