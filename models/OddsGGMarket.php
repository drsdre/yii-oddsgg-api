<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_market".
 *
 * @property integer $id
 * @property string $Name
 * @property integer $MatchId
 * @property integer $IsLive
 * @property integer $Status
 * @property integer $Timestamp
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGMatch $match
 * @property OddsGGOdd $odds
 */
class OddsGGMarket extends ActiveRecordWithUpsert
{
	const STATUS_ACTIVE = 0;
	const STATUS_SUSPENDED = 1;
	const STATUS_CANCELED = 2;
	const STATUS_RESULTED = 3;

	static $statuses = [
		self::STATUS_ACTIVE => 'Active',
		self::STATUS_SUSPENDED => 'Suspended',
		self::STATUS_CANCELED => 'Canceled',
		self::STATUS_RESULTED => 'Resulted',
	];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_market';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'Name', 'MatchId', 'IsLive', 'Timestamp'], 'required'],
            [['id', 'MatchId', 'Status', 'Timestamp'], 'integer'],
	        [['Status'], 'in', 'range' => self::$statuses ],
	        [
		        [ 'MatchId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGMatch::className(),
		        'targetAttribute' => [ 'MatchId' => 'id' ],
	        ],
	        [['IsLive'], 'boolean'],
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
            'MatchId' => yii::t('app', 'Match'),
            'IsLive' => yii::t('app', 'Is Live'),
            'Status' => yii::t('app', 'Status'),
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
	 * Remove obsolete markets
	 */
	public static function removeObsolete() {
		// Find not active items with a timestamp more than two months ago
		/*$Markets = static::find()
			->where([ 'not', 'Status', self::STATUS_ACTIVE ])
			->andWhere([ '<', 'Timestamp', strtotime("-2 month") ])
			->all();*/

		// Remove
		/*foreach($Markets as $Market) {
			$Market->delete();
		}*/
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
            ->inverseOf('markets');
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOdds()
	{
		return $this->hasMany(OddsGGOdd::className(), ['MarketId' => 'id'])
		            ->inverseOf('markets');
	}
}
