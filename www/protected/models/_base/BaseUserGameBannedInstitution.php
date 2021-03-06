<?php

/**
 * This is the model base class for the table "user_game_banned_institution".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "UserGameBannedInstitution".
 *
 * Columns in table "user_game_banned_institution" available as properties of the model,
 * followed by relations of table "user_game_banned_institution" available as properties of the model.
 *
 * @property integer $user_id
 * @property integer $game_id
 * @property integer $institution_id
 * @property string $created
 *
 * @property User $user
 * @property Game $game
 * @property Institution $institution
 */
abstract class BaseUserGameBannedInstitution extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'user_game_banned_institution';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'UserGameBannedInstitution|UserGameBannedInstitutions', $n);
	}

	public static function representingColumn() {
		return 'created';
	}

	public function rules() {
		return array(
			array('user_id, game_id, institution_id, created', 'required'),
			array('user_id, game_id, institution_id', 'numerical', 'integerOnly'=>true),
			array('user_id, game_id, institution_id, created', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
			'game' => array(self::BELONGS_TO, 'Game', 'game_id'),
			'institution' => array(self::BELONGS_TO, 'Institution', 'institution_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'user_id' => null,
			'game_id' => null,
			'institution_id' => null,
			'created' => Yii::t('app', 'Created'),
			'user' => null,
			'game' => null,
			'institution' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('user_id', $this->user_id);
		$criteria->compare('game_id', $this->game_id);
		$criteria->compare('institution_id', $this->institution_id);
		$criteria->compare('created', $this->created, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}