<?php 

class Sandbox extends EMongoDocument {

	public $handle;
	public $remoteip;
	public $ts;
    public $groups = array();

	public function getCollectionName() {
		return 'sandbox';
	}
	
	public static function model($classname = __CLASS__) {
		return parent::model($classname);
	}
	
} 
?>