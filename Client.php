<?php
/**
 * This file is part of the Yii2-oddsgg-api extension
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG;

use drsdre\OddsGG\models\OddsGGSport;
use yii\base\InvalidConfigException;

use drsdre\OddsGG\Cache;
use drsdre\OddsGG\Exception;

/**
 * Odds.gg API Http Client Component
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 */
class Client extends \yii\httpclient\Client {
	/**
	 * @var string url API endpoint
	 */
	public $baseUrl = "https://api.odds.gg";

	/**
	 * @var string api_key of account as shown on http://www.odds.gg/UserAccount/UserAccount
	 */
	public $api_key;

	/**
	 * @inheritdoc
	 */
	public function init() {
		parent::init();

		// Check parameters
		if ( empty( $this->baseUrl ) ) {
			throw new InvalidConfigException( "service_url cannot be empty. Please configure." );
		}

		if ( empty( $this->api_key ) ) {
			throw new InvalidConfigException( "api_key cannot be empty. Please configure." );
		}

		// Set response class with error handling
		$this->responseConfig['class'] = '\drsdre\OddsGG\Response';
	}



	/**
	 * @inheritdoc
	 */
	public function createRequest() {

		// Add api-key to request headers
		return parent::createRequest()
			->setFormat(self::FORMAT_JSON)
			->addHeaders(['api-key' => $this->api_key]);
	}

	/**
	 * Update the cache
	 *
	 * @param bool|false|string $force Force full data retrieval, or update or no data update
	 * @param bool|false $expire Expire data in the database
	 *
	 * @return array process statistics
	 */
	public function updateCache($force = false, $expire = true) {

		$cache = new Cache($this);

		// Initialize data if no sports data or forced
		if ( OddsGGSport::find()->count() == 0 || $force === true ) {
			$cache->updateAll(true);
		} else {
			$cache->updateAll(false);
		}

		// Expire data
		if ($expire) {
			$cache->expireData();
		}
		return $cache->getStatistics();
	}
}