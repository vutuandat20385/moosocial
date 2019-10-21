<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Tag extends AppModel 
{
	public function saveTags($new_tags, $target_id, $type)
	{
		$new_tags = str_replace(' ', ',', $new_tags);
		$new_tags = explode(',', $new_tags);


		$current_tags = $this->find('list', array('conditions' => array('Tag.target_id' => $target_id, 'Tag.type' => $type), 'fields' => array('Tag.id', 'tag')));

		// loop through new tags and add any tag that does not exist in current tags
		foreach ($new_tags as $val)
		{
			$check = array_keys($new_tags, $val);
			if (!empty($val) && !in_array($val, $current_tags) && count($check) == 1)
			{
				$this->create();
				$this->save( array('target_id' => $target_id, 'type' => $type, 'tag' => $val) );
			}
		}

		// loop through current tags and remove any tag that does not exist in new tags
		foreach ($current_tags as $key => $val)
			if (!in_array($val, $new_tags))
				$this->delete($key);
	}
    
    /**
     * Get popular tags
     * @param string $type
     * @param int $days
     * @param int $limit
     * @return array $tags
     */
	public function getTags($type = '', $days = 0, $limit = RESULTS_LIMIT, $items_id = null, $order = null)
	{
		
        if(empty($order))
            $order = 'count desc';
        $cond = array();
        if ($type && $type != 'all')
        {
            $aType = array('blogs' => 'Blog_Blog','albums' => 'Photo_Album', 'topics' => 'Topic_Topic', 'videos' => 'Video_Video');
            if(array_key_exists($type,$aType))
                $cond['type'] = $aType[$type];
            else
                $cond['type'] = $type;
        }
        if($items_id)
            $cond[] = 'FIND_IN_SET(Tag.target_id,"'.$items_id.'")';

		$tags = $this->find( 'all', array( 'fields' => array( 'tag','created','(SELECT count(*) FROM ' . $this->tablePrefix . 'tags WHERE tag=Tag.tag) as count' ),
										   'conditions' => $cond,
										   'order' => $order,
										   'limit' => $limit,
                                           'group' => 'Tag.tag'
				 			) 	);
		return $tags;
	}
	
	public function getContentTags( $id, $type )
	{
                $tags = Cache::read('tags_content_' . $type.'_'.$id);
                if (!$tags){
                    $tags = $this->find('list', array('conditions' => array('Tag.target_id' => $id, 'Tag.type' => $type), 'fields' => array('tag')));
                    Cache::write('tags_content_' . $type.'_'.$id, $tags);
                }
		
		return $tags;
	}
	
	public function getSimilarVideos( $id, $tags )
	{
		$this->bindModel(
			array('belongsTo' => array(
					'Video' => array(
						'className' => 'Video',
						'foreignKey' => 'target_id'
					)
				)
			)
		);
		
		$similar_videos = $this->find('all', array( 'conditions' => array( 'Tag.tag' => $tags, 
																		   'Tag.type' => APP_VIDEO, 
																		   'Tag.target_id <> ?' => $id
																		), 
											   		'fields' => array( 'DISTINCT Video.id, Video.title, Video.thumb, Video.like_count' ),
											   		'limit' => 5	
									) 	);
		return $similar_videos;
	}
        
        public function getFunctionUnions($type)
        {
                    list($plugin, $modelClass) = mooPluginSplit($type);
                    $function_name = 'getTagUnions'.str_replace('_','',$modelClass);
                    return $function_name;
        }

    public function afterSave($created, $options = array()){
            Cache::delete('tags_'.$this->data['Tag']['type']);
            Cache::delete('tags_content_'.$this->data['Tag']['type'].'_'.$this->data['Tag']['target_id']);
            Cache::delete('tags_all');
    }
    public function beforeDelete($cascade = true){
        Cache::delete('tags_'.$this->field('type'));
        Cache::delete('tags_content_'.$this->field('type').'_'.$this->field('target_id'));
        Cache::delete('tags_all');
    }
}
?>