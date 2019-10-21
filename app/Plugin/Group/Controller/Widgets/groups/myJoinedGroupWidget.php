<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class myJoinedGroupWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$uid = MooCore::getInstance()->getViewer(true);
    	$aMyJoinedGroup = null;
    	if ( !( empty($uid) && Configure::read('core.force_login') ) ):
                    $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);  
            }
            $controller->loadModel('Group.GroupUser');
            $num_item_show = $this->params['num_item_show'];
            if(empty($user_blocks)){		   
		    $aMyJoinedGroup = Cache::read('my_joined_group_'.$uid, 'group');
		    if(empty($aMyJoinedGroup))
		    {	           
	            $aMyJoinedGroup = $controller->GroupUser->getJoinedGroups($uid, $num_item_show);
		        
		        Cache::write('my_joined_group_'.$uid, $aMyJoinedGroup , 'group');
		    }
            }else{
                $aMyJoinedGroup = $controller->GroupUser->getJoinedGroups($uid, $num_item_show);
            }
    	endif;
    	if(is_array($aMyJoinedGroup) && count($aMyJoinedGroup)){
                    $controller->loadModel('Group.GroupUser');
                    foreach ($aMyJoinedGroup as $key=>$val) {
                        $aMyJoinedGroup[$key]['Group']['group_user_count'] = $controller->GroupUser->getBlockedUserCount($aMyJoinedGroup[$key]['Group']['id']);
                    }
                }
    	$this->setData('myJoinedGroupWidget',$aMyJoinedGroup);
    }
}