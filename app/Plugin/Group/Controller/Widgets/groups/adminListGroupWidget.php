<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class adminListGroupWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$num_group_admin = $this->params['num_item_show'];
    	$subject = MooCore::getInstance()->getSubject();
    	$data = array(
			'groupAdmin' => array(),
			'groupAdminCnt' => 0			
		);
    	if ($subject)
    	{
    		$id = $subject['Group']['id'];
    		$controller->loadModel('Group.GroupUser');
                  $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);  
            }
            
             if(empty($user_blocks)){
			$group_admins = Cache::read('group_' . $id . '_admins_widget', 'group');
			if (!$group_admins){
				$group_admins = $controller->GroupUser->getUsers($id, GROUP_USER_ADMIN, null, $num_group_admin);
				Cache::write('group_' . $id . '_admins_widget', $group_admins, 'group');
			}
             }else{
                 $group_admins = $controller->GroupUser->getUsers($id, GROUP_USER_ADMIN, null, $num_group_admin);
             }	
             
    		$admin_count = Cache::read('admin_count_group_' . $id, 'group');
			if (!$admin_count){
				$admin_count = $controller->GroupUser->getUserCount($id, GROUP_USER_ADMIN);
				Cache::write('admin_count_group_' . $id, $admin_count, 'group');
			}
						
			$data['groupAdmin'] = $group_admins;
			$data['groupAdminCnt'] = $admin_count;
    	}
        
		$this->setData('data', $data) ;
    }
}