<?php
/**
 * Nordicbet.com API Yii2 Client Component
 *
 * @author Andre Schuurman <andre.schuurman@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG;

use yii\db\ActiveRecord;

use drsdre\OddsGG\models\OddsGGLeague;
use drsdre\OddsGG\models\OddsGGMarket;
use drsdre\OddsGG\models\OddsGGMatch;
use drsdre\OddsGG\models\OddsGGOdd;
use drsdre\OddsGG\models\OddsGGSport;
use drsdre\OddsGG\models\OddsGGTeam;
use drsdre\OddsGG\models\OddsGGTournament;

use drsdre\OddsGG\Exception;

/**
 * Class Cache
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 */
class Cache {

	/** @var Client  $_client */
	protected $_client;

	protected $_stats = [];

	public function __construct($client) {
		$this->_client = $client;
	}

	/**
	 * Increase statistic
	 * @param $stat_name
	 */
	protected function incStat($stat_name, $amount = 1) {
		if ($amount == 0) {
			return;
		}
		if (isset($this->_stats[$stat_name])) {
			$this->_stats[$stat_name] += $amount;
		} else {
			$this->_stats[$stat_name] = $amount;
		}
	}

	/**
	 * Retrieve statistics
	 * @return array key value statistics
	 */
	public function getStatistics() {
		return $this->_stats;
	}

	/**
	 * Initiate the data in the cache
	 *
	 * @return bool
	 */
	public function update($init = false) {
		// Mark time when update starts (make sure server time is set to UTC)
		$update_start_time = time();

		// Get data from API
		
		// Update sports data
		$sports = $this->_client->get('Sports', [], ['isUpdate' => $init == false]);

		foreach ($sports as $sport) {
			OddsGGSport::upsert(
				$sport->id,
				$sport->name
			);

			// Update League data
			$leagues = $this->_client->get('Leagues/Sport/'.$sport->id, [], ['isUpdate' => $init == false]);

			foreach ($leagues as $league) {
				OddsGGLeague::upsert(
					$leagues->id,
					$leagues->CategorryName,
					$this->SportId
				);

				foreach ($leagues->Tournaments as $tournament) {
					OddsGGTournament::upsert(
						$tournament->id,
						$tournament->Name,
						$tournament->CategoryId,
						$tournament-Timestamp
					);
				}
			}

			// Update match data
			$matches = $this->_client->get('Matches/Sport/'.$sport->id, [], ['isUpdate' => $init == false]);

			foreach ($matches as $match) {

				OddsGGTeam::upsert(
					$match->HomeTeamId,
					$match->HomeTeamName
				);

				OddsGGTeam::upsert(
					$match->AwayTeamId,
					$match->AwayTeamName
				);

				OddsGGMatch::upsert(
					$match->id,
					$match->SportId,
					$match->TournamentId,
					$match->HomeTeamId,
					$match->AwayTeamId,
					isset($match->Status)?$match->Status:null,
					isset($match->StreamUrl)?$match->StreamUrl:null
				);
			}

			// Update Market data
			$markets = $this->_client->get('Markets/Sport/'.$sport->id, [], ['isUpdate' => $init == false]);

			foreach ($markets as $market) {
				OddsGGMarket::upsert(
					$market->id,
					$market->Name,
					$market->MatchId,
					$market->IsLive,
					$market->MatchId,
					isset($match->Status)?$match->Status:null,
					$market->Timestamp
				);

				foreach ($market->Odds as $odd) {
					OddsGGOdd::upsert(
						$odd->id,
						$odd->Name,
						$odd->Title,
						$odd->Value,
						$odd->IsActive,
						isset($match->Status)?$match->Status:null,
						$odd->MatchId,
						$odd->MarketId,
						$market->Timestamp
					);
				}
			}

		}

		//$this->_client->SetLastUpdate(['utcTimeStamp' => date(DATE_ATOM, $update_start_time)]);

		return true;
	}

	/**
	 * Expire obsolete data in the cache
	 */
	public function expireData() {
		$this->incStat('expire_market', OddsGGEventMarket::expireOpen() );
		$this->incStat('expire_selection', OddsGGMarketSelection::expireOpen() );
		return true;
	}

	/**
	 * Store data record and track change statistics
	 *
	 * @param ActiveRecord $ActiveRecord
	 *
	 * @return bool false if not saved
	 */
	protected function storeDataRecord( ActiveRecord $ActiveRecord ) {
		if ( $ActiveRecord->getDirtyAttributes() ) {
			$unsaved_record = clone $ActiveRecord;

			// Save record
			if ( ! $ActiveRecord->save() ) {
				// Create error message
				$message = "Save error: ".json_encode($ActiveRecord->errors)."\n";
				$message .= "Record data: ".json_encode($ActiveRecord->getAttributes())."\n";
				trigger_error($message, E_USER_WARNING);
				$this->incStat('error_'.$ActiveRecord->tableName());
				return false;
			}

			// Store statistics
			if ($unsaved_record->isNewRecord) {
				$this->incStat('new_'.$ActiveRecord->tableName());
			} else {
				$this->incStat('update_'.$ActiveRecord->tableName());
			}
		}
		return true;
	}
}