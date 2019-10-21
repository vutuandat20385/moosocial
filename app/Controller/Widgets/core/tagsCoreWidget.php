<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class tagsCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
        // APP_BLOG APP_ALBUM APP_TOPIC APP_VIDEO
        $controller->loadModel('Tag');
        $param_order = $this->params['order_by'];
        $order = null;
        switch($param_order)
        {
            case 'newest':
                $order = 'created desc';
                break;
            case 'popular':
                $order = 'count desc';
                break;
            case 'random':
                $order = 'RAND()';
                break;
        }
        $tags = $controller->Tag->getTags( $this->params['type'], Configure::read('core.popular_interval'), $this->params['num_item_show'],null, $order );
        $tags = $this->_getAllowTags($tags);

        $conditions = null;
        if($this->params['type'] != 'all' )
        {
            $item_table = $this->params['type'];
            if($item_table == 'activities')
                $item_table = array('activities','activity_comments');
            $conditions = array('Hashtag.item_table' => $item_table );
        }
        $cakeEvent = new CakeEvent('Controller.Widgets.tagCoreWidget', $controller);
        $result = $controller->getEventManager()->dispatch($cakeEvent);
        $notEnablePlugin = array_keys(Hash::remove($result->result,'{s}[enable=1]'));

        if(!empty($notEnablePlugin))
        {
            $conditions[] = 'Hashtag.item_table NOT IN("'.implode('","',$notEnablePlugin).'")';
        }
        $controller->loadModel('Hashtag');
        $order = ($param_order == 'popular')? 'id desc':$order;
        $hashtag = $controller->Hashtag->find('all',array('conditions' => $conditions, 'order' => $order) );
        $this->setData('hashtags',$hashtag);

        $this->setData('tagsWidget',$tags);

        $this->setData('order',$param_order);

    }
	public function getFunctionUnions($type)
    {
   		list($plugin, $modelClass) = mooPluginSplit($type);
		$function_name = 'getTagUnions'.str_replace('_','',$modelClass);
		return $function_name;
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
        $sFriendsList = $this->_getFriendList($uid);
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

    private function _getFriendList($uid)
    {
        $this->loadModel('Friend');
        $sFriendsList = '';
        $aFriendListId =  array_keys($this->Friend->getFriendsList($uid));
        $sFriendsList = implode(',',$aFriendListId);
        return $sFriendsList;
    }
}