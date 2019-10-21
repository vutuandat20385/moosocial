<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CommentHistory extends AppModel 
{
	public $belongsTo = array( 'User' );
	
	public $validate = array( 'user_id' => array( 'rule' => 'notBlank'),
							'type' => array( 'rule' => 'notBlank'),
							'target_id' => array( 'rule' => 'notBlank'),
						 );	
						 
	public $order = 'CommentHistory.created ASC';
	
	public function getHistory($type,$target_id,$page)
	{
		$cond = array(
			'type' => $type,
			'target_id' => $target_id
		);
		$activities = $this->find('all', array( 'conditions' => $cond, 
												'limit' => RESULTS_LIMIT,
												'page' => $page
								)	);
		return $activities;
	}
	
	public function getHistoryCount($type,$target_id)
	{
		$cond = array(
			'type' => $type,
			'target_id' => $target_id
		);
		
		$count = $this->find('count', array('conditions' => $cond));
            	
        return $count;
	}
	
	public function getLastHistory($type,$target_id)
	{
		$cond = array(
			'type' => $type,
			'target_id' => $target_id
		);
		
		$history = $this->find('first', array(
			'conditions' => $cond,
	        'order' => array('CommentHistory.id' => 'desc'),
			'limit' => 1
	    ));
	    
	    return $history;
	}
	
	public function getText($type,$target_id)
	{
		$history = $this->getLastHistory($type, $target_id);		
		$text = __('Edited');
		if ($history)
		{
			list($plugin, $name) = mooPluginSplit($type);
			$target = MooCore::getInstance()->getItemByType($type,$target_id);
			if ($target[$name]['user_id'] != $history['CommentHistory']['user_id'])
			{
				$text.= ' '.__('by').' '.$history['User']['name']; 
			}	
		}
				
		return $text;
	}
}
 