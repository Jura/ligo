<?php 

class Task extends EMongoDocument {
	
	public $start;
	public $finish;
	public $log = array();
	public $status;
	
	/**
	 * Return UNIX timestamp in milliseconds (for compatibility with JavaScript)
	 * @param string $time
	 */
	public static function getTimestamp($time = null) {
		if (is_null($time)) {
			$time = microtime(true);
		}
		return round($time * 1000);		
	}
	
	public function setStart($time = NULL) {
		$this->start = self::getTimestamp($time);
	}
	
	public function setFinish($time = NULL) {
		$this->finish = self::getTimestamp($time);
	}
	
	public function formatLogString($str) {
		return date(DATE_RSS) . ': ' . $str;
	}
	
	public function getCollectionName() {
		return 'task';
	}
	
	public static function model($classname = __CLASS__) {
		return parent::model($classname);
	}
	
} 
?>