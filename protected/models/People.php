<?php 

class People extends EMongoDocument {
	
	public $handle;
	public $groups;
	public $timestamp;
	public $userinfo;
	public $twitter_id;
	
	public function getCollectionName() {
		return 'people';
	}
	
	public static function model($classname = __CLASS__) {
		return parent::model($classname);
	}
	
	public function indexes() {
		return array(
			'people_unique_index' => array(
				'key' => array(
					'twitter_id' => EMongoCriteria::SORT_ASC
				),
				'unique'=>true,
			),
			'people_handle_index' => array(
				'key' => array(
					'handle' => EMongoCriteria::SORT_ASC
				),
				//'unique'=>true,
			)
		);
	}
	
	public function getTopListForGroup($group) {
		$col = self::model()->getCollection();
		$reduce = 'function (obj, prev) { if(obj.userinfo){obj.userinfo.friends_list.forEach( function(e) { prev.friends[e] = prev.friends[e] || 0; prev.friends[e]++; });}}';
		$criteria = array(
				'condition' => array(
						'groups' => $group
				)
		);
		$result = $col->group(array(), array('friends'=>array()), $reduce, $criteria);
		arsort($result['retval'][0]['friends'], SORT_NUMERIC);
		return $result['retval'][0]['friends'];
	}
	
	public function insertBareHandles($handles) {
		
		$col = self::model()->getCollection();
		$docs = array();
		foreach ($handles as $handle) {
            if (is_numeric($handle)) {
                array_push($docs, array('twitter_id' => $handle));
            }
		}

        $col->batchInsert($docs, array('ContinueOnError' => true));

        return count($docs);
		
	}

    public function getPopulatedGroups($threshold=2) {

        $col = self::model()->getCollection();
        $reduce = 'function (obj, prev) { if(obj.groups){ obj.groups.forEach( function(e) { prev.groups[e] = prev.groups[e] || 0; prev.groups[e]++; }); } }';
        $result = $col->group(array(), array('groups' => array()), $reduce);
        ksort($result['retval'][0]['groups']);
        return $result['retval'][0]['groups'];
    }

}
?>