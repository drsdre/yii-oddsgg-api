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
		$result = $this->_client->get('Sports');


		// Check if data is available
		if ( ! isset($result->GetLatestUpdatesResult->SubCategory ) ) {
			return true;
		}

		$subCategories = $result->GetLatestUpdatesResult->SubCategory;

		if (!is_array($subCategories)) {
			$subCategories = [$subCategories];
		}

		foreach($subCategories as $key => $sub_category ) {
			// Store the data
			if ($this->StoreCategoryLeague( $sub_category ) === false) {
				continue;
			}

			// Check if data is available
			if ( ! isset( $sub_category->SubCategoryEvents->Event ) ) {
				continue;
			}

			$events = $sub_category->SubCategoryEvents->Event;

			if ( ! is_array( $events ) ) {
				$events = [$events];
			}

			foreach ( $events as $key => $event ) {

				// Store the data
				if ($this->storeEvent( $event, $sub_category->SubCategoryID ) === false) {
					continue;
				};

				// Check if data is available
				if ( ! isset( $event->EventMarkets->Market ) ) {
					continue;
				}

				$markets = $event->EventMarkets->Market;

				if ( ! is_array( $markets ) ) {
					$markets = [$markets];
				}

				foreach ( $markets as $key => $market ) {
					// Store the data
					$this->storeMarket( $market, $event->EventID );
				}
			}
		}
		$this->_client->SetLastUpdate(['utcTimeStamp' => date(DATE_ATOM, $update_start_time)]);

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

	/**
	 * @return bool
	 */
	protected function GetActiveSubCategories() {

		// Mark time when update starts (make sure server time is set to UTC)
		$update_start_time = time();

		// Get data from API
		$result = $this->_client->GetActiveSubCategories();

		// Check if data is available
		if ( !isset($result->GetActiveSubCategoriesResult->SubCategory) ) {
			return false;
		}

		// Parse the data
		foreach( $result->GetActiveSubCategoriesResult->SubCategory as $key => $sub_category ) {
			// Store the data
			if (is_object( $sub_category )) {
				if ($this->StoreCategoryLeague( $sub_category ) === false) {
					continue;
				}

				// Get the events for league
				$this->GetActiveEventsForSubCategory($sub_category->SubCategoryID);
			}
		}

		$this->_client->SetLastUpdate(['utcTimeStamp' => date(DATE_ATOM, $update_start_time)]);

		return true;
	}

	/**
	 * @param $sub_category
	 *
	 * @return OddsGGCategoryLeague|null|static
	 * @throws \drsdre\OddsGG\Exception
	 */
	protected function StoreCategoryLeague($sub_category) {

		// Load or store category
		if ( ! $OddsGGCategory = OddsGGCategory::findOne([
			'CategoryID' => intval($sub_category->CategoryID),
			'LanguageCode' => $sub_category->LanguageCode,
		])
		) {
			$OddsGGCategory = new OddsGGCategory();
			$OddsGGCategory->CategoryID = intval($sub_category->CategoryID);
			$OddsGGCategory->LanguageCode = $sub_category->LanguageCode;
		}
		// TODO: temporary fix to prevent errors when OddsGG provides no category name
		$OddsGGCategory->CategoryName = $sub_category->CategoryName?$sub_category->CategoryName:'Not provided';
		$OddsGGCategory->CacheDate = $sub_category->CacheDate;
		$OddsGGCategory->CacheExpireDate = $sub_category->CacheExpireDate;
		$OddsGGCategory->ErrorMessage = $sub_category->ErrorMessage;

		if (!$this->storeDataRecord($OddsGGCategory)) {
			return false;
		}

		// Load or store category league
		if ( ! $OddsGGCategoryLeague = OddsGGCategoryLeague::findOne([
			'LeagueID' => intval($sub_category->SubCategoryID),
			'LanguageCode' => $sub_category->LanguageCode,
		])
		) {
			$OddsGGCategoryLeague = new OddsGGCategoryLeague();
			$OddsGGCategoryLeague->LeagueID = intval($sub_category->SubCategoryID);
			$OddsGGCategoryLeague->LanguageCode = $sub_category->LanguageCode;
		}
		$OddsGGCategoryLeague->CategoryID = intval($sub_category->CategoryID);
		$OddsGGCategoryLeague->LeagueName = $sub_category->SubCategoryName;
		$OddsGGCategoryLeague->LeagueURL = $sub_category->SubCategoryURL;
		$OddsGGCategoryLeague->CacheDate = gmdate("Y-m-d H:i:s", strtotime($sub_category->CacheDate));
		$OddsGGCategoryLeague->CacheExpireDate = gmdate("Y-m-d H:i:s", strtotime($sub_category->CacheExpireDate));
		$OddsGGCategoryLeague->ErrorMessage = $sub_category->ErrorMessage;

		if (!$this->storeDataRecord($OddsGGCategoryLeague)) {
			return false;
		}
		return true;
	}

	/**
	 * @param $sub_category_id
	 *
	 * @return bool
	 */
	protected function GetActiveEventsForSubCategory($sub_category_id) {

		// Get data from API
		$result = $this->_client->GetActiveEventsForSubCategory(['subCategoryId' => $sub_category_id]);

		// Check if data is available
		if (!isset($result->GetActiveEventsForSubCategoryResult->Event)) {
			return false;
		}

		$events = $result->GetActiveEventsForSubCategoryResult->Event;

		// Check if data is an array
		if (!is_array($events)) {
			$events = [$events];
		}

		// Parse the data
		foreach($events as $key => $event ) {

			// Store the data
			if ( is_object( $event ) ) {
				if ( ! isset( $event->EventID ) ) {
					continue;
				}
				if ($this->storeEvent( $event, $sub_category_id ) === false) {
					continue;
				}

				// Get the markets for event
				$this->GetActiveMarketsForEvent( $event->EventID );
			} else {
				continue;
			}
		}

		return true;
	}

	protected function storeEvent($event, $league_id) {
		// Load or store events
		if ( ! $OddsGGLeagueEvent = OddsGGLeagueEvent::findOne([
			'EventID' => intval($event->EventID),
			'LanguageCode' => $event->LanguageCode,
		])
		) {
			$OddsGGLeagueEvent = new OddsGGLeagueEvent();
			$OddsGGLeagueEvent->EventID = intval($event->EventID);
			$OddsGGLeagueEvent->LanguageCode = $event->LanguageCode;
		}
		$OddsGGLeagueEvent->LeagueID = $league_id;
		$OddsGGLeagueEvent->EventName = $event->EventName;
		$OddsGGLeagueEvent->EventURL = $event->EventURL;
		$OddsGGLeagueEvent->EventDeadline = gmdate("Y-m-d H:i:s", strtotime($event->EventDeadline));
		$OddsGGLeagueEvent->CacheDate = gmdate("Y-m-d H:i:s", strtotime($event->CacheDate));
		$OddsGGLeagueEvent->CacheExpireDate = gmdate("Y-m-d H:i:s", strtotime($event->CacheExpireDate));
		$OddsGGLeagueEvent->ErrorMessage = $event->ErrorMessage;

		if (!$this->storeDataRecord($OddsGGLeagueEvent)) {
			return false;
		}
		return true;
	}

	protected function GetActiveMarketsForEvent($event_id) {

		// Get data from API
		$result = $this->_client->GetActiveMarketsForEvent(['eventId' => $event_id]);

		// Check if data is available
		if (!isset($result->GetActiveMarketsForEventResult->Market)) {
			return false;
		}

		$markets = $result->GetActiveMarketsForEventResult->Market;

		if (!is_array($markets)) {
			// Process single market
			$markets = [$markets];
		}

		// Process array of markets
		foreach($markets as $key => $market) {
			// Store the data
			if (is_object($market)) {
				if (!isset($market->MarketID)) {
					return false;
				}

				if ($this->storeMarket($market, $event_id) === false) {
					continue;
				}
			} else {
				return false;
			}
		}

		return true;
	}


	protected function storeMarket($market, $event_id) {

		if (!isset($market->MarketID)) {
			return false;
		}

		// Load or store events
		if ( ! $OddsGGEventMarket = OddsGGEventMarket::findOne([
			'MarketID' => intval($market->MarketID),
			'LanguageCode' => $market->LanguageCode,
		])
		) {
			$OddsGGEventMarket = new OddsGGEventMarket();
			$OddsGGEventMarket->MarketID = intval($market->MarketID);
			$OddsGGEventMarket->LanguageCode = $market->LanguageCode;
		}
		$OddsGGEventMarket->EventID = $event_id;
		$OddsGGEventMarket->BetGroupUnitID = intval($market->BetGroupUnitID);
		$OddsGGEventMarket->BetGroupUnitName = $market->BetGroupUnitName;
		$OddsGGEventMarket->BetGroupID = intval($market->BetgroupID);
		$OddsGGEventMarket->BetGroupName = $market->BetgroupName;
		$OddsGGEventMarket->BetGroupStyleID = intval($market->BetgroupStyleID);
		$OddsGGEventMarket->BetGroupTypeID = intval($market->BetgroupTypeID);
		$OddsGGEventMarket->IsLive = boolval($market->IsLive);
		$OddsGGEventMarket->MarketDeadline = gmdate("Y-m-d H:i:s", strtotime($market->MarketDeadline));
		$OddsGGEventMarket->MarketEndDate = gmdate("Y-m-d H:i:s", strtotime($market->MarketEndDate));
		$OddsGGEventMarket->MarketPublishDate = gmdate("Y-m-d H:i:s", strtotime($market->MarketPublishDate));
		$OddsGGEventMarket->MarketStartDate = gmdate("Y-m-d H:i:s", strtotime($market->MarketStartDate));
		$OddsGGEventMarket->MarketStatusID = intval($market->MarketStatusID);
		$OddsGGEventMarket->MarketStatusName = $market->MarketStatusName;
		$OddsGGEventMarket->MarketURL = $market->MarketURL;
		$OddsGGEventMarket->StartingPitchers = $market->StartingPitchers;
		$OddsGGEventMarket->SubParticipantName = $market->SubParticipantName;

		$OddsGGEventMarket->CacheDate = gmdate("Y-m-d H:i:s", strtotime($market->CacheDate));
		$OddsGGEventMarket->CacheExpireDate = gmdate("Y-m-d H:i:s", strtotime($market->CacheExpireDate));
		$OddsGGEventMarket->ErrorMessage = $market->ErrorMessage;

		if (!$this->storeDataRecord($OddsGGEventMarket)) {
			return false;
		}

		// Check if data is available
		if ( ! isset( $market->MarketSelections->MarketSelection ) ) {
			return true;
		}

		$selections = $market->MarketSelections->MarketSelection;

		if ( ! is_array( $selections ) ) {
			$selections = [$selections];
		}

		// Process the selections for market
		foreach ( $selections as $key => $selection ) {
			// Store the data
			if ( is_object( $selection ) ) {
				$this->storeSelection( $selection, $OddsGGEventMarket->MarketID );
			} else {
				continue;
			}
		}

		return $OddsGGEventMarket;
	}

	protected function storeSelection($selection, $market_id) {
		// Load or store selection
		if ( ! $OddsGGMarketSelection = OddsGGMarketSelection::findOne([
			'SelectionID' => intval($selection->SelectionID),
			'LanguageCode' => $selection->LanguageCode,
		])
		) {
			$OddsGGMarketSelection = new OddsGGMarketSelection();
			$OddsGGMarketSelection->SelectionID = intval($selection->SelectionID);
			$OddsGGMarketSelection->LanguageCode = $selection->LanguageCode;
		}
		$OddsGGMarketSelection->MarketID = $market_id;
		$OddsGGMarketSelection->Odds = doubleval($selection->Odds);
		$OddsGGMarketSelection->SelectionLimitValue = doubleval($selection->SelectionLimitValue);
		$OddsGGMarketSelection->SelectionName = $selection->SelectionName;
		$OddsGGMarketSelection->SelectionStatus = intval($selection->SelectionStatus);
		$OddsGGMarketSelection->SelectionStatusName = $selection->SelectionStatusName;
		$OddsGGMarketSelection->SelectionSortOrder = intval($selection->SortOrder);


		$OddsGGMarketSelection->CacheDate = gmdate("Y-m-d H:i:s", strtotime($selection->CacheDate));
		$OddsGGMarketSelection->CacheExpireDate = gmdate("Y-m-d H:i:s", strtotime($selection->CacheExpireDate));
		//$OddsGGMarketSelection->ErrorMessage = $selection->ErrorMessage;

		if (!$this->storeDataRecord($OddsGGMarketSelection)) {
			return false;
		}
		return true;
	}
}