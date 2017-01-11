<?php
/**
 * Nordicbet.com API Yii2 Client Component
 *
 * @author Andre Schuurman <andre.schuurman@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG;

use drsdre\OddsGG\models\ActiveRecordWithUpsert;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\httpclient\Response;
use drsdre\OddsGG\Exception;

use drsdre\OddsGG\models\OddsGGLeague;
use drsdre\OddsGG\models\OddsGGMarket;
use drsdre\OddsGG\models\OddsGGMatch;
use drsdre\OddsGG\models\OddsGGOdd;
use drsdre\OddsGG\models\OddsGGSport;
use drsdre\OddsGG\models\OddsGGTeam;
use drsdre\OddsGG\models\OddsGGTournament;


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
	protected function incStat( $stat_name, $amount = 1 ) {
		if ( $amount == 0 ) {
			return;
		}
		if ( isset( $this->_stats[ $stat_name ] ) ) {
			$this->_stats[ $stat_name ] += $amount;
		} else {
			$this->_stats[ $stat_name ] = $amount;
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
	public function updateAll( $init = false ) {
		// Mark time when update starts (make sure server time is set to UTC)
		$update_start_time = time();
		$result            = true;

		if ( ! $this->updateSports($init) ) {
			$result = false;
		}

		// Get data for all sports
		foreach ( OddsGGSport::find()->all() as $Sport ) {

			if ( ! $this->updateLeagueTournamentsBySport($Sport->id, $init) ) {
				$result = false;
			}

			if ( ! $this->updateMatchesBySport( $Sport->id, $init ) ) {
				$result = false;
			}

			// Does not provide any results, switched to using league based market retrieval 11-01-2017
			/*
			if ( ! $this->updateMarketsBySport($Sport->id, $init) ) {
				$result = false;
			}
			*/
		}

		// Get data for all leagues
		foreach ( OddsGGLeague::find()->all() as $League ) {
			if ( ! $this->updateMarketsByLeague( $League->id, $init ) ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Expire obsolete data in the cache
	 */
	public function expireData() {
		$this->incStat( 'expire_market', OddsGGMarket::removeObsolete() );

		return true;
	}

	/**
	 * Check upsert action and generate statistics
	 *
	 * @param ActiveRecordWithUpsert $Record
	 *
	 * @return bool if upsert succesfull
	 */
	protected function checkUpsert( ActiveRecordWithUpsert $Record ) {
		// Check if errors occured
		if ( $Record->hasErrors() ) {
			$this->incStat( 'Error saving ' . $Record->tableName() );
			// Write log
			\yii::error( 'Error saving ' . $Record->tableName() . ' errors:' . print_r( $Record->getErrors(), true ) );

			return false;
		} elseif ( $Record->upsertNewRecord ) {
			$this->incStat( 'New ' . $Record->tableName() );
		} elseif ( $Record->upsertUpdated ) {
			$this->incStat( 'Updated ' . $Record->tableName() );
		}

		return true;
	}

	/**
	 * Update Sports
	 *
	 * @param $bool init
	 *
	 * @return bool if update succesfull
	 */
	public function updateSports( bool $init = false ) {
		// Get the data from API
		$SportsResponse = $this->_client
			->get( 'Sports', [], [ 'isUpdate' => $init == true ? 'false' : 'true' ] )
			->send()
		;

		// Check if API response is valid
		if ( ! $SportsResponse->isOk ) {
			return false;
		}

		foreach ( $SportsResponse->data as $sport ) {
			$OddsGGSport = OddsGGSport::upsert(
				$sport['Id'],
				$sport
			);
			$this->checkUpsert( $OddsGGSport );
		}

		return true;
	}

	/**
	 * Update League Tournaments by Sport from API
	 *
	 * @param int $SportId
	 * @param bool $init
	 *
	 * @return bool if update succesfull
	 */
	public function updateLeagueTournamentsBySport( int $SportId, bool $init = false ) {
		// Get the data from API
		$LeaguesResponse = $this->_client
			->get( 'Leagues/Sport/' . $SportId, [], [ 'isUpdate' => $init == true ? 'false' : 'true' ] )
			->send()
		;

		// Check if API response is valid
		if ( ! $LeaguesResponse->isOk ) {
			return false;
		}

		$this->storeLeagueTournaments( $LeaguesResponse->data );

		return true;
	}

	/**
	 * Update League Tournaments by Tournament from API
	 *
	 * @param int $SportId
	 * @param bool $init
	 *
	 * @return bool if update succesfull
	 */
	public function updateLeagueTournamentsByTournament( int $TournamentId, bool $init = false ) {
		// Get the data from API
		$LeaguesResponse = $this->_client
			->get( 'Leagues/Tournaments/' . $TournamentId, [], [ 'isUpdate' => $init == true ? 'false' : 'true' ] )
			->send()
		;

		// Check if API response is valid
		if ( ! $LeaguesResponse->isOk ) {
			return false;
		}

		$this->storeLeagueTournaments( $LeaguesResponse->data );

		return true;
	}

	/**
	 * Store League Tournament Data from API
	 *
	 * @param array $LeagueTournamentData
	 */
	protected function storeLeagueTournaments( array $LeagueTournamentData ) {
		foreach ( $LeagueTournamentData as $league ) {
			$OddsGGLeague = OddsGGLeague::upsert(
				$league['Id'],
				$league
			);
			// If upsert not okay, skip further processing of this record
			if ( ! $this->checkUpsert( $OddsGGLeague ) ) {
				continue;
			};

			// Process the tournaments
			foreach ( $league['Tournaments'] as $tournament ) {
				$tournament['Timestamp'] = strtotime( $tournament['Timestamp'] );
				$tournament['LeagueId']  = $tournament['CategoryId'];
				unset( $tournament['CategoryId'] );

				$OddsGGTournament = OddsGGTournament::upsert(
					$tournament['Id'],
					$tournament
				);
				$this->checkUpsert( $OddsGGTournament );
			}
		}
	}

	/**
	 * Update Matches by Sport from API
	 *
	 * @param int $SportId
	 * @param bool $init
	 *
	 * @return bool if update succesfull
	 */
	public function updateMatchesBySport( int $SportId, bool $init = false ) {
		// Get the data from API
		$MatchesResponse = $this->_client
			->get( 'Matches/Sport/' . $SportId, [], [ 'isUpdate' => $init == true ? 'false' : 'true' ] )
			->send()
		;

		// Check if API response is valid
		if ( ! $MatchesResponse->isOk ) {
			return false;
		}

		$this->storeMatches( $MatchesResponse->data );

		return true;
	}

	/**
	 * Store match data from API
	 *
	 * @param array $MatchData
	 */
	protected function storeMatches( array $MatchData ) {
		foreach ( $MatchData as $match ) {

			$OddsGGTeam = OddsGGTeam::upsert(
				$match['HomeTeamId'],
				[ 'Name' => $match['HomeTeamName'] ]
			);

			// If upsert not okay, skip further processing of this record
			if ( ! $this->checkUpsert( $OddsGGTeam ) ) {
				continue;
			};

			$OddsGGTeam = OddsGGTeam::upsert(
				$match['AwayTeamId'],
				[ 'Name' => $match['AwayTeamName'] ]
			);

			// If upsert not okay, skip further processing of this record
			if ( ! $this->checkUpsert( $OddsGGTeam ) ) {
				continue;
			};

			unset( $match['HomeTeamName'], $match['AwayTeamName'] );
			$match['StartTime'] = strtotime( $match['StartTime'] );

			$OddsGGMatch = OddsGGMatch::upsert(
				$match['Id'],
				$match
			);

			// Error handling when tournament ID is not know
			$errors = $OddsGGMatch->getErrors();
			if (
				count( $errors ) == 1 &&
				isset( $errors['TournamentId'] ) &&
				array_search('Tournament is invalid.', $errors['TournamentId']) !== false
			) {
				// Clear tournment ID
				$match['TournamentId'] = null;
				$OddsGGMatch           = OddsGGMatch::upsert(
					$match['Id'],
					$match
				);
			}

			$this->checkUpsert( $OddsGGMatch );
		}
	}

	/**
	 * Update Markets by Sport from API
	 *
	 * @param int $SportId
	 * @param bool $init
	 *
	 * @return bool if update succesfull
	 */
	public function updateMarketsBySport( int $SportId, bool $init = false ) {

		// Get the data from API
		$MarketsResponse = $this->_client
			->get( 'Markets/Sport/' . $SportId, [], [ 'isUpdate' => $init == true ? 'false' : 'true' ] )
			->send()
		;

		// Check if API response is valid
		if ( ! $MarketsResponse->isOk ) {
			return false;
		}

		$this->storeMarkets( $MarketsResponse->data );

		return true;
	}

	/**
	 * Update Markets by League from API
	 *
	 * @param int $SportId
	 * @param bool $init
	 *
	 * @return bool if update succesfull
	 */
	public function updateMarketsByLeague( int $LeagueId, bool $init = false ) {

		// Get the data from API
		$MarketsResponse = $this->_client
			->get( 'Markets/Categories/' . $LeagueId, [], [ 'isUpdate' => $init == true ? 'false' : 'true' ] )
			->send()
		;

		// Check if API response is valid
		if ( ! $MarketsResponse->isOk ) {
			return false;
		}

		$this->storeMarkets( $MarketsResponse->data );

		return true;
	}

	/**
	 * Store market data from API
	 *
	 * @param array $MarketData
	 */
	protected function storeMarkets( array $MarketData ) {
		foreach ( $MarketData as $market ) {
			$market['Timestamp'] = strtotime( $market['Timestamp'] );
			$OddsGGMarket        = OddsGGMarket::upsert(
				$market['Id'],
				$market
			);

			// If upsert not okay, skip further processing of this record
			if ( ! $this->checkUpsert( $OddsGGMarket ) ) {
				continue;
			};

			// Store the odds
			foreach ( $market['Odds'] as $odd ) {
				$odd['Timestamp'] = strtotime( $odd['Timestamp'] );
				$odd['Value'] = (string) $odd['Value'];
				$OddsGGOdd        = OddsGGOdd::upsert(
					$odd['Id'],
					$odd
				);
				$this->checkUpsert( $OddsGGOdd );
			}
		}
	}
}