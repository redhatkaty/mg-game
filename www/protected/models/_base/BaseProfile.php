<?php

/**
 * This is the model base class for the table "profile".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "Profile".
 *
 * Columns in table "profile" available as properties of the model,
 * followed by relations of table "profile" available as properties of the model.
 *
 * @property integer $user_id
 *
 * @property User $user
 */
abstract class BaseProfile extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'profile';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'Profile|Profiles', $n);
	}

	public static function representingColumn() {
		return 'user_id';
	}

	public function rules() {
		return array(
			array('user_id', 'required'),
			array('user_id', 'numerical', 'integerOnly'=>true),
			array('user_id', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'user_id' => null,
			'user' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('user_id', $this->user_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->fbvStorage->get("settings.pagination_size"),
      ),
		));
	}
}