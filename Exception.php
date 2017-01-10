<?php
namespace drsdre\OddsGG;

/**
 * Class Exception
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 */
class Exception extends \yii\base\Exception
{
	const E_API_GENERAL = 1;
	const E_API_INVALID_RESPONSE = 2;
	const E_API_INVALID_PARAMETER = 3;
	const E_API_RATE_LIMIT = 4;
	const E_API_SPAM_LIST = 5;

	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'OddsGG Client Exception';
	}
}