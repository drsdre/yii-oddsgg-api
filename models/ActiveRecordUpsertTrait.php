<?php
/**
 * This file is part of the Yii2-oddsgg-api extension
 *
 * @author Andre Schuurman <andre.schuurman+yii2-oddsgg-api@gmail.com>
 * @license MIT License
 */

namespace drsdre\OddsGG\models;

/**
 * ActiveRecordWithUpsert extends ActiveRecord with upsert function
 */
trait ActiveRecordUpsertTrait {

	/** @var bool $upsertNewRecord Upsert New Record tracking */
	public $upsertNewRecord = false;

	/** @var bool $upsertUpdated Upsert Update tracking */
	public $upsertUpdated = false;


	/**
	 * Update or insert record by id
	 *
	 * @param int $id
	 * @param array $name
	 *
	 * @return ActiveRecord
	 */
	public static function upsert(int $id, array $attributes = []) {
		// Find record by id
		$Record = static::findOne($id);

		// If no record found, make it
		if ( ! $Record) {
			$Record = new static();
			$Record->id = $id;

			// Mark record as new
			$Record->upsertNewRecord = true;
		}

		// Update attributes
		if ( $attributes ) {
			$Record->attributes = $attributes;
		}

		// If record changed, save it and mark as updated if not new
		if ( $Record->dirtyAttributes && $Record->save() && ! $Record->upsertNewRecord ) {
			$Record->upsertUpdated = true;
		}

		return $Record;
	}
}