<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_odd".
 *
 * @property integer $id
 * @property string $Name
 * @property string $Title
 * @property string $Value
 * @property bool $IsActive
 * @property integer $Status
 * @property integer $MatchId
 * @property integer $MarketId
 * @property integer $Timestamp
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGMatch $match
 * @property OddsGGMarket $market
 */
class OddsGGOdd extends \yii\db\ActiveRecord
{
	const STATUS_NOT_RESULTED = 0;
	const STATUS_WIN = 1;
	const STATUS_LOSS = 2;
	const STATUS_HALF_WIN = 3;
	const STATUS_HALF_LOSS = 4;
	const STATUS_REFUND = 5;
	const STATUS_CANCELED = 6;

	static $statuses = [
		self::STATUS_NOT_RESULTED => 'Not Resulted',
		self::STATUS_WIN => 'Win',
		self::STATUS_LOSS => 'Loss',
		self::STATUS_HALF_WIN => 'Half Win',
		self::STATUS_HALF_LOSS => 'Half Loss',
		self::STATUS_REFUND => 'Refund',
		self::STATUS_CANCELED => 'Canceled',
	];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_odd';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'Name', 'Title', 'Value', 'IsActive', 'Status', 'MatchId', 'MarketId', 'Timestamp'], 'required'],
            [['id', 'Status', 'MarketId', 'MarketId', 'Timestamp'], 'integer'],
	        [['IsActive'], 'boolean'],
            [['Name', 'Title', 'Value'], 'string'],
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
            'Title' => yii::t('app', 'Title'),
            'Value' => yii::t('app', 'Value'),
            'IsActive' => yii::t('app', 'Is Active'),
            'Status' => yii::t('app', 'Status'),
            'MatchId' => yii::t('app', 'Match'),
            'MarketId' => yii::t('app', 'Market'),
            'Timestamp' => yii::t('app', 'Time'),
        ];
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
	 * Returns the name of status
	 *
	 * @param  null|integer $status Status integer value if sent to method.
	 *
	 * @return string               Nicely formatted status.
	 */
	public function getStatusName( $status = null ) {
		if ( is_null( $status ) ) {
			$status = $this->Status;
		}
		if ( array_key_exists( $status, self::$statuses ) ) {
			return self::$statuses[ $status ];
		} else {
			return $status;
		}
	}

	/**
	 * ==== Connections to other records
	 */

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getMatch()
    {
        return $this->hasOne(OddsGGMatch::className(), ['id' => 'MatchId'])
            ->inverseOf('odds');
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMarket()
	{
		return $this->hasOne(OddsGGMarket::className(), [ 'id' => 'MarketId'])
		            ->inverseOf('odds');
	}
}
