<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class MooListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'View.afterRender' => 'appendRender',
            'View.beforeRender' => 'prependRender',
            'Controller.Activity.afterDeleteActivity' => 'afterDeleteActivity',
            'UserController.doSaveUser' => 'doSaveUser',
            'Profile.Field.getType' => 'profileType',
        	'UserController.doAfterRegister' => 'doAfterSaveProfile',
        	'Controller.User.afterEdit' => 'doAfterSaveProfile',
        	'UploadController.doAfterSaveAvatar' => 'doAfterSaveProfile',
        	'Controller.User.afterDelete' => 'doAfterSaveProfile',
            'Profile.Field.getNoticeType' => 'profileNoticeType',
            'Auth.afterIdentify' => 'afterIdentify',
        	'Cron.Task.CronTaskJobs.Start' => 'cronStart',
			'MooView.beforeMooConfigJSRender' => 'beforeMooConfigJSRender'
        );
    }
    public function doAfterSaveProfile($event)
    {
    	Cache::delete("UserModel.getLatestUsers","1_day");
    }
    
    public function cronStart($e)
    {
    	$user = MooCore::getInstance()->getModel("User");
    	$expires = time() + Configure::read('Session.timout') * 60 + 60;
    	$user->query('DELETE FROM ' . $user->tablePrefix . 'cake_sessions WHERE expires < ' . $expires);    	
    	
    	//delete file temp
    	$folderName = WWW_ROOT . 'uploads' . DS  . 'tmp';
    	$count = 0;
    	foreach (new DirectoryIterator($folderName) as $fileInfo) {
    		if ($count > 10)
    			break;
    		if ($fileInfo->isDot()) {
    			continue;
    		}
    		if ($fileInfo->getFilename() == 'index.html')
    		{
    			continue;
    		}
    		if ($fileInfo->isFile() && time() - $fileInfo->getCTime() >= 60*60) {
    			unlink($fileInfo->getRealPath());
    		}
    		$count++;
    	}
    }

	public function profileType($event)
	{
		$event->result['location'] = array(
			'label' => __('Location'),
		);
	}
	
	public function profileNoticeType($event)
	{
		$event->result['location'] = array(
			'label' => __('Please only add ONE location field in to member profile.'),
		);
	}
    
    public function doSaveUser($event)
    {
    	$controller = $event->subject();    	
    	$custom_fields = isset($event->data['custom_fields']) ? $event->data['custom_fields'] : '';
    	$data = isset($event->data['data']) ? $event->data['data'] : '';
    	
    	// save profile field values
    	if ($custom_fields)
    	{
			$controller->loadModel('ProfileFieldValue');	
			$helper = MooCore::getInstance()->getHelper("Core_Moo");
			$search_values = array();
			
			foreach ($custom_fields as $field)
			{
				if (!in_array($field['ProfileField']['type'],$helper->profile_fields_default))
				{
					$helper = MooCore::getInstance()->getHelper("Core_Moo");
					if ($field['ProfileField']['plugin'])
						$helper = MooCore::getInstance()->getHelper($field['ProfileField']['plugin'].'_'.$field['ProfileField']['plugin']);

					if (method_exists($helper,'saveProfileField'))
					{
						$helper->saveProfileField($field['ProfileField']['type'],$field,$data,$controller->User->id);
					}
					$value = '';
					if (isset($data['field_' . $field['ProfileField']['id']])) {
						$value = $data['field_' . $field['ProfileField']['id']];
					}

					$controller->ProfileFieldValue->create();
					$controller->ProfileFieldValue->save(array('user_id' => $controller->User->id,
						'profile_field_id' => $field['ProfileField']['id'],
						'value' => $value
					));

					continue;
				}
				if (isset($data['field_' . $field['ProfileField']['id']])) {
					$value = $data['field_' . $field['ProfileField']['id']];

					$search_value = ( is_array($value) ) ? implode(',', $value) : $value;
					$search_values['field_' . $field['ProfileField']['id']] = $search_value;
						
					$value = (is_array($value)) ? implode(', ', $value) : $value;

					$controller->ProfileFieldValue->create();
					$controller->ProfileFieldValue->save(array('user_id' => $controller->User->id,
						'profile_field_id' => $field['ProfileField']['id'],
						'value' => $value
					));
				}
			}
			
			//save profile field search
			$controller->loadModel('ProfileFieldSearch');
			$controller->ProfileFieldSearch->saveSearchValue($controller->User->id, $search_values);
    	}
		
    	// insert into activity feed
		$controller->loadModel( 'Activity' );
		$controller->Activity->save( array( 'type' 	=> APP_USER,
									  'action'  => 'user_create',
									  'user_id' => $controller->User->id
							) );
		
    	
    	$user = $controller->User->read();
		$ssl_mode = Configure::read('core.ssl_mode');
        $http = (!empty($ssl_mode)) ? 'https' :  'http';

		if ($data['confirmed'])
		{
        	$controller->MooMail->send($data['email'],'welcome_user',
    			array(
    				'email' => $data['email'],
                                'password' => $data['password'],
    				'recipient_title' => $user['User']['name'],
    				'recipient_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    				'site_name'=>Configure::read('core.site_name'),
    				'login_link'=> $http.'://'.$_SERVER['SERVER_NAME'].$controller->request->base.'/users/member_login',
    			)
    		);
		}
		else
		{
			$controller->MooMail->send($data['email'],'welcome_user_confirm',
    			array(
    				'email' => $data['email'],
                                'password' => $data['password'],
    				'recipient_title' => $user['User']['name'],
    				'recipient_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    				'site_name'=>Configure::read('core.site_name'),
    				'confirm_link'=> $http.'://'.$_SERVER['SERVER_NAME'].$controller->request->base.'/users/do_confirm/'.$data['code'],
    			)
    		);
		}

		// Send an email to admin if enabled
		if ( Configure::read('core.registration_notify'))
		{		
			$controller->MooMail->send(Configure::read('core.site_email'),'new_registration',
    			array(
    				'new_user_title' => $user['User']['name'],
    				'new_user_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    				'site_name'=>Configure::read('core.site_name'),
    			)
    		);		
			
		}
		
    	$controller->loadModel('Friend');
		$auto_add_friend = Configure::read('core.auto_add_friend');
		if(!empty($auto_add_friend))
		{
			$list_friend = explode(',',$auto_add_friend);
			$controller->Friend->autoFriends($controller->User->id, $list_friend);
		}
		
		//add default profile type if empty (for api create)
		/*if (!$user['User']['profile_type_id'])
		{
			$controller->User->save(array('profile_type_id'=>PROFILE_TYPE_DEFAULT));
		}*/
    }
    public function doAfterRegister($event){
        Cache::delete("UserModel.getLatestUsers","1_day");
    }
    public function afterDeleteActivity($event){
        $activity = $event->data['activity'];
        if (!empty($activity)){ // delete all share activity relate to current Activity
            $activityModel = MooCore::getInstance()->getModel('Activity');
            $activityModel->deleteAll(array(
                'Activity.parent_id' => $activity['Activity']['id']
            ));
        }
    }

    public function prependRender($event)
    {
        $v = $event->subject();
        $v = $event->subject();


        try {
            if ($v instanceof MooView) {
                if (!$v->isContinue()) {
                    return;
                }
            }

        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            die();
        }

    }

    public function appendRender($event)
    {
        // Adding additional custom view-rendering logic to mooSocial application
        $v = $event->subject();
        if ($v instanceof MooView) {

            return $v->renderContent();

        }
    }
    public function afterIdentify($event)
    {
        $user = $event->data['user'];
        $sub = $event->subject();
        //$chatModel = MooCore::getInstance()->getModel('Chat.ChatToken');
        $Model = MooCore::getInstance()->getModel('MooToken');
        $chatToken = uniqid('moo_');

        $userModel = MooCore::getInstance()->getModel('User');
        $userModel->id = $user['id'];
        $userModel->save(array('last_login' => date("Y-m-d H:i:s")));

        if ($sub->Session->check("enable_mooTokens")) {
            if (!$sub->Session->check("mooTokens")){
                $sub->Session->write('mooTokens', $chatToken);
                $Model->create();
                $Model->save(array(
                    'user_id' => $user['id'],
                    'session_id' => $sub->Session->id(),
                    'token' => $chatToken,
                ));
            }

        }


    }
	
	public function beforeMooConfigJSRender($oEvent) {
        $oView = $oEvent->subject();
        $sConfigPath = APP . 'webroot' . DS . 'js' . DS . 'feed.plugin.config.xml';
        if (file_exists($sConfigPath)) {
            $sContent = @file_get_contents($sConfigPath);
            $sContentXML = new SimpleXMLElement($sContent);
            $aContent = json_decode(json_encode($sContentXML), true);
            
            $aResult = array();
            if (!empty($aContent['plugin'])) {
                foreach ($aContent['plugin'] as $aPlugin) {
                    if (!empty($aPlugin['name'])) {
                        $aResult[$aPlugin['name']] = !empty($aPlugin['disable']['item']) ? (array) $aPlugin['disable']['item'] : array();
                    }
                }
            }
            $oView->mooConfig['FeedPluginConfig'] = $aResult;
        }
    }
}