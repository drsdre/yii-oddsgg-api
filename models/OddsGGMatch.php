<?php

namespace drsdre\OddsGG\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "oddsgg_match".
 *
 * @property integer $id
 * @property integer $SportId
 * @property integer $TournamentId
 * @property integer $StartTime
 * @property integer $HomeTeamId
 * @property integer $AwayTeamId
 * @property string $Score
 * @property integer $Status
 * @property string $StreamUrl
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property OddsGGSport $sport
 * @property OddsGGTournament $tournament
 * @property OddsGGTeam $homeTeam
 * @property OddsGGTeam $awayTeam
 * @property OddsGGMarket[] $markets
 * @property OddsGGOdd[] $odds
 */
class OddsGGMatch extends ActiveRecordWithUpsert
{
	const STATUS_NOT_STARTED = 0;
	const STATUS_IN_PLAY = 1;
	const STATUS_FINISHED = 2;
	const STATUS_CANCELED = 3;

	static $statuses = [
		self::STATUS_NOT_STARTED => 'Not Started',
		self::STATUS_IN_PLAY => 'In Play',
		self::STATUS_FINISHED => 'Finished',
		self::STATUS_CANCELED => 'Canceled',
	];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'oddsgg_match';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
	        // , 'TournamentId' not all tournaments are available through the API
            [['id', 'StartTime', 'SportId', 'HomeTeamId', 'AwayTeamId', 'Score'], 'required'],
            [['id', 'StartTime', 'SportId', 'TournamentId', 'Status', 'HomeTeamId', 'AwayTeamId'], 'integer'],
	        [['Status'], 'in', 'range' => self::$statuses ],
	        [
		        [ 'SportId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGSport::className(),
		        'targetAttribute' => [ 'SportId' => 'id' ],
	        ],
	        [
		        [ 'TournamentId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGTournament::className(),
		        'targetAttribute' => [ 'TournamentId' => 'id' ],
	        ],
	        [
		        [ 'HomeTeamId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGTeam::className(),
		        'targetAttribute' => [ 'HomeTeamId' => 'id' ],
	        ],
	        [
		        [ 'AwayTeamId' ],
		        'exist',
		        'skipOnError'     => true,
		        'targetClass'     => OddsGGTeam::className(),
		        'targetAttribute' => [ 'AwayTeamId' => 'id' ],
	        ],
	        [['Score', 'StreamUrl'], 'string'],
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
            'SportId' => yii::t('app', 'Sport'),
            'TournamentId' => yii::t('app', 'Tournament'),
            'HomeTeamId' => yii::t('app', 'Home Team'),
            'HomeTeamName' => yii::t('app', 'Home Team'),
            'AwayTeamId' => yii::t('app', 'Away Team'),
            'AwayTeamName' => yii::t('app', 'Away Team'),
            'Score' => yii::t('app', 'Score'),
            'Status' => yii::t('app', 'Status'),
            'StreamUrl' => yii::t('app', 'Stream Url'),
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
    public function getSport()
    {
        return $this->hasOne(OddsGGSport::className(), ['id' => 'SportId'])
            ->inverseOf('matches');
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTournament()
	{
		return $this->hasOne(OddsGGTournament::className(), [ 'id' => 'TournamentId'])
		            ->inverseOf('matches');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getHomeTeam()
	{
		return $this->hasOne(OddsGGTeam::className(), ['id' => 'HomeTeamId'])
		            ->inverseOf('homeMatches');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAwayTeam()
	{
		return $this->hasOne(OddsGGTeam::className(), ['id' => 'AwayTeamId'])
		            ->inverseOf('awayMatches');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMarkets()
	{
		return $this->hasMany(OddsGGMarket::className(), ['MatchId' => 'id'])
		            ->inverseOf('match');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOdds()
	{
		return $this->hasMany(OddsGGOdd::className(), ['MatchId' => 'id'])
		            ->inverseOf('match');
	}
}
