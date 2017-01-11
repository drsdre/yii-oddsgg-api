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
 * Class Response
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 */
class Response extends \yii\httpclient\Response {

	const E_API_GENERAL = 1;
	const E_API_INVALID_RESPONSE = 2;
	const E_API_INVALID_PARAMETER = 3;
	const E_API_RATE_LIMIT = 4;
	const E_API_SPAM_LIST = 5;

	protected $error_code;
	protected $error_string;

	/**
	 * @inheritdoc Add error code handling
	 */
	public function getIsOk() {

		$result = parent::getIsOk();

		if ( ! $result ) {
			switch($this->getStatusCode()) {
				case 403:
					$this->error_code = self::E_API_INVALID_PARAMETER;
					$this->error_string = 'Forbidden: '.$this->getContent();
					break;
				case 429:
					$this->error_code = self::E_API_RATE_LIMIT;
					$this->error_string = $this->getContent();
					break;
				case 500:
					$this->error_code = self::E_API_GENERAL;
					$this->error_string = 'Internal Server Error: '.$this->getContent();
					break;
				default:
					$this->error_code = self::E_API_GENERAL;
					$this->error_string = 'Error has occured. Status code: '.$this->getStatusCode() .
					                    ' Error: '.$this->getContent();
					break;
			}
		}
		return $result;
	}

	/**
	 * @return int
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * @return string
	 */
	public function getErrorString() {
		return $this->error_string;
	}
}