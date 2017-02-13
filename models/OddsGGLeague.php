<?php
/**
 * This file is part of the Yii2-oddsgg-api extension
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_league".
 *
 * @property integer $id
 * @property string $CategoryName renamed from $CategorryName
 * @property integer $SportId
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGSport $sport
 * @property OddsGGTournament[] $tournaments
 */
class OddsGGLeague extends ActiveRecord
{
	use ActiveRecordUpsertTrait;

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
		return $this->hasMany(OddsGGTournament::className(), ['LeagueId' => 'id'])
		            ->inverseOf('league');
	}
}
