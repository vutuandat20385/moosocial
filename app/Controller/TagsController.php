<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class TagsController extends AppController {
	
	public $paginate = array( 'limit' => RESULTS_LIMIT );
	
	public function view($tag = null, $order = null) {
            $tag = h(urldecode($tag));
            $items = $this->Tag->findAllByTag($tag);

            $unions = array();
            $plugins = array();
            
            foreach ($items as $item) {
                list($plugin, $modelClass) = mooPluginSplit($item['Tag']['type']);
                $plugins[$plugin][$modelClass][] = $item['Tag']['target_id'];
            }
            foreach ($plugins as $plugin => $items) {
                $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                foreach ($items as $model => $ids) {
                    $name = $this->Tag->getFunctionUnions($model);
                    if (method_exists($helper, $name)) {
                        $unions[] = $helper->$name($ids);
                    }
                }
            }
            $friendModel = MooCore::getInstance()->getModel('Friend');
            if (!empty($unions)) {
                $sFriendsList = $friendModel->getFriendListAsString($this->Auth->user('id'));
                $order = ( $order == 'popular' ) ? 'like_count' : 'created';

                $query = implode(' union ', $unions) . ' order by ' . $order . ' desc limit ' . RESULTS_LIMIT;
                $items = $this->Tag->query($query);
                
                $viewer = MooCore::getInstance()->getViewer();

                foreach ($items as $key => $item){
                    $owner_id = $item[key($item)]['user_id'];
                    $privacy = isset($item[key($item)]['privacy']) ? $item[key($item)]['privacy'] : 1;

                    if (empty($viewer)){ // guest can view only public item
                        if ($privacy != PRIVACY_EVERYONE){
                            unset($items[$key]);
                        }
                    }else{ // viewer
                        $aFriendsList = array();
                        $aFriendsList = $friendModel->getFriendsList($owner_id);
                        if ($privacy == PRIVACY_ME){ // privacy = only_me => only owner and admin can view items
                            if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id){
                                unset($items[$key]);
                            }
                        }else if ($privacy == PRIVACY_FRIENDS){ // privacy = friends => only owner and friendlist of owner can view items
                            if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], array_keys($aFriendsList))){
                                unset($items[$key]);
                            }
                        }else {

                        }
                    } 
                }
                
                $this->set('items', $items);
                $this->set('order', $order);
                $this->set('unions', count($unions));
            }

            $tags = $this->Tag->getTags(null, Configure::read('core.popular_interval'), RESULTS_LIMIT * 2);
            
            $tags = $this->_getAllowTags($tags);
            $this->set('title_for_layout', htmlspecialchars(__('Tag') . ' "' . $tag . '"'));
            $this->set('tag', $tag);
            $this->set('tags', $tags);
        }

        public function admin_index()
	{
		if ( !empty( $this->request->data['keyword'] ) )
			$this->redirect( '/admin/tags/index/keyword:' . $this->request->data['keyword'] );
			
		$cond = array();
		if ( !empty( $this->request->named['keyword'] ) )
			$cond['tag'] = $this->request->named['keyword'];
                    
		$tags = $this->paginate( 'Tag', $cond );	
		
		$this->set('tags', $tags);
		$this->set('title_for_layout', __('Tags Manager'));
	}
	
	public function admin_delete()
	{
		$this->_checkPermission(array('super_admin' => 1));
		
		if ( !empty( $_POST['tags'] ) )
		{					
			foreach ( $_POST['tags'] as $tag_id )
				$this->Tag->delete( $tag_id );	

			$this->Session->setFlash( __('Tags deleted' ));				
		}
		
		$this->redirect( array(
                    'controller' => 'tags',
                    'action' => 'admin_index'
                ) );
	}

    public function popular_tags(){
        if ($this->request->is('requested')) {
            $limit = $this->request->named['limit'];
            $type = $this->request->named['type'];
            if($type == 'all') $type = null;
            // APP_BLOG APP_ALBUM APP_TOPIC APP_VIDEO
            $tags = $this->Tag->getTags( $type, Configure::read('core.popular_interval'), $limit );
            $tags = $this->_getAllowTags($tags);
            return $tags;
        }
    }
    

    private function _getAllowTags($tags)
    {
        $aTags = Hash::combine($tags,'{n}.Tag.target_id','{n}.Tag.tag','{n}.Tag.type');
        $intersectTag = array();
        foreach($aTags as $type => &$tag)
        {
            if($type != 'Topic_Topic'){
                list($plugin, $modelClass) = mooPluginSplit($type);
                // Get all the items this user can view
                $allowTags = $this->_getAllowItems($plugin,$modelClass);
                $allTags = array_keys($tag);
                $intersectTag[$type] = array_intersect($allTags,$allowTags);
            }
        }
        foreach($tags as $key => &$tag){
            if(!empty($intersectTag) && $tag['Tag']['type'] != 'Topic_Topic' && !in_array($tag['Tag']['target_id'],$intersectTag[$tag['Tag']['type']]))
                unset($tags[$key]);
        }
        return $tags;
    }

    private function _getAllowItems($plugin, $modelClass)
    {
        $uid = $this->Auth->user('id');
        $sFriendsList = $friendModel->getFriendListAsString($uid);
        if($plugin)
            $this->loadModel($plugin.'.'.$modelClass);
        else
            $this->loadModel($modelClass);
        $aData = $this->$modelClass->find('all',array(
            'conditions' => array(
                'OR' => array(
                    array(
                        $modelClass.'.privacy' => PRIVACY_EVERYONE,
                    ),
                    array(
                        $modelClass.'.user_id' => $uid
                    ),
                    array(
                        'Find_In_Set('.$modelClass.'.user_id,"'.$sFriendsList.'")',
                        $modelClass.'.privacy' => PRIVACY_FRIENDS
                    )
                ),
            ),
            'fields' => array('id')
        ));
        return Hash::extract($aData,'{n}.'.$modelClass.'.id');
    }

}
