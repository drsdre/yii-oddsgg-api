<?php
/**
 * Nordicbet.com API Yii2 Client Component
 *
 * @author Andre Schuurman <andre.schuurman@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG;

use drsdre\OddsGG\models\OddsGGSport;
use yii\base\InvalidConfigException;

use drsdre\OddsGG\Cache;
use drsdre\OddsGG\Exception;

/**
 * Class Exception
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 */
class Client extends yii\httpclient\Client {
	/**
	 * @var string url API endpoint
	 */
	public $baseUrl = "https://api.odds.gg/";

	/**
	 * @var string api_key of account as shown on http://www.odds.gg/UserAccount/UserAccount
	 */
	public $api_key;

	/**
	 * @inheritdoc
	 */
	public function init() {
		parent::init();

		if ( empty( $this->baseUrl ) ) {
			throw new InvalidConfigException( "service_url cannot be empty. Please configure." );
		}

		if ( empty( $this->api_key ) ) {
			throw new InvalidConfigException( "api_key cannot be empty. Please configure." );
		}
	}



	/**
	 * @inheritdoc
	 */
	public function createRequest($content = null, array $headers = []) {

		return parent::createRequest(
			$content,
			array_merge($headers, ['api-key' => $this->api_key])
		);
	}

	/**
	 * Update the cache
	 *
	 * @param bool|false|string $force Force full data retrieval, or update or no data update
	 * @param bool|false $expire Expire data in the database
	 *
	 * @return array process statistics
	 */
	public function updateCache($force = false, $expire = false) {

		$cache = new Cache($this);

		// Init data if force or no existing categories
		if ($force === true || OddsGGSport::find()->count() == 0) {
			$cache->initData();
		} elseif ($force === false) {
			$cache->updateData();
		}

		// Expire data
		if ($expire) {
			$cache->expireData();
		}
		return $cache->getStatistics();
	}
}