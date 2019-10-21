<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Lib/SocialIntegration', 'Auth');
App::uses('CakeEvent', 'Event');

class AuthsController extends SocialIntegrationAppController {

    public $components = array('Session', 'SocialIntegration.Social');
    public $uses = array('SocialIntegration.SocialUser', 'User');
    public $helpers = array('Cache');
    private $_provider = null;

    public function beforeFilter() {
        parent::beforeFilter();
        if (empty($this->request->provider)) {
            $this->redirect('/');
        }
        $this->_provider = $this->request->provider;
    }

    public function login() {

        $this->autoRender = false;

        App::import('Lib/SocialIntegration', 'Storage');

        // Get providers configuration
        $config = $this->Social->getSocialProvidersConfigs();

        $config['base_url'] = Router::url(array(
                    'plugin' => 'social_integration',
                    'controller' => 'auths',
                    'action' => 'endpoint',
                    'provider' => $this->_provider,
                        ), true);

        // Initialization
        $authnObj = new SocialIntegration_Auth($config);
        $provider = ucfirst($this->_provider);
        $storage = new SocialIntegration_Storage();

        if ($authnObj->isConnectedWith($provider)) {

            $hauth = $authnObj->setup($provider);
            $provider_user = $hauth->adapter->getUserProfile();
            
            // Write cache
            Cache::write('social_integration_' . CakeSession::id() . '_provider_user', $provider_user);

            $user = $this->User->findByEmail($provider_user['email']);
            
            if (!empty($user)) {
                if ($this->isBanned($user['User']['email'])){
                    $this->autoRender = false;
                    echo __('You are not allowed to view this site');
                    exit;
                }
            }
            
            // special case for user who registered via facebook email , example identifier@facebook.com
            // MOOSOCIAL-2164
            if ($this->_provider == 'facebook' && empty($provider_user['email'])){
                $conditions = array(
                    'provider' => $this->_provider,
                    'provider_uid' => $provider_user['identifier'],
                ); 
                $social_user = $this->SocialUser->find('first', array('conditions' => $conditions));
                if (!empty($social_user)){
                    $user = $this->User->findById($social_user['SocialUser']['user_id']);
                    if (!empty($user)){
                        $this->loadModel('Role');
                        $role = $this->Role->findById($user['User']['role_id']);
                        $user['User']['Role'] = $role['Role'];
                        $this->Auth->login($user['User']);
                        $this->Session->write('provider', $this->_provider);
                        $this->redirect('/');
                    }
                }
            }
            
            if (!empty($user)) {
                $conditions = array(
                    'provider' => $this->_provider,
                    'user_id' => $user['User']['id'],
                );

                $social_user = $this->SocialUser->find('first', array('conditions' => $conditions));

                // need confirm password before sync account with social
                if (!$user['User']['is_social'] && empty($social_user)) {
                    $this->Session->setFlash( __('Please confirm your email and password to associate your facebook/google account with this account'), 'default', array( 'class' => 'error-message') );
                    $this->redirect(array('action' => 'member_login', 'provider' => $this->_provider));
                }

                // different provider but same email
                if ($user['User']['email'] == $provider_user['email'] && empty($social_user)) {
                    $this->SocialUser->create();
                    $this->SocialUser->set(array(
                        'user_id' => $user['User']['id'],
                        'provider' => strtolower($provider),
                        'provider_uid' => $provider_user['identifier'],
                        'access_token' => $provider_user['access_token']
                    ));
                    $this->SocialUser->save();
                }

                if (!empty($social_user) || $user['User']['email'] == $provider_user['email']) {
                    $this->loadModel('Role');
                    $role = $this->Role->findById($user['User']['role_id']);
                    $user['User']['Role'] = $role['Role'];
                    if ($this->Auth->login($user['User'])) {
                    	$this->Session->write('provider', $this->_provider);
                        $this->redirect('/');
                    }
                    $this->Session->setFlash(__('Your username or password was incorrect.'), 'default', array('class' => 'error-message'));
                }
            } else {
                // Go to sign up step 
                $this->redirect(array('action' => 'signup_step2', 'provider' => $this->_provider));
            }
        } else {
            // Authen with provider
            $storage->clear();
            $adapter = $authnObj->authenticate($provider);
        }
    }

    public function endpoint() {
        $provider = ucfirst($this->_provider);
        $hauth = $this->_getAuthAdapter($provider);
            
        # if REQUESTed hauth_idprovider is wrong, session not created, etc.
        if (isset($_GET['hauth_start'])) {
            
            try {
                               
                $hauth->adapter->loginBegin();
            } catch (Exception $e) {
                $hauth->returnToCallbackUrl();
            }
        } else if (isset($_GET['hauth_done'])) {

            try {
                $hauth->adapter->loginFinish();
            } catch (Exception $ex) {
                $this->Session->setFlash(__('Authentication failed!'), 'default', array('class' => 'error-message'));
                $this->redirect('/users/register');
            }
            $this->redirect(array('action' => 'login', 'provider' => $this->_provider));
        } else {

            // Finish authentication
            try {
                $hauth->adapter->loginFinish();
            } catch (Exception $ex) {
                $this->Session->setFlash(__('Authentication failed!'), 'default', array('class' => 'error-message'));
                $this->redirect('/users/register');
            }
            $this->redirect(array('action' => 'login', 'provider' => $this->_provider));
        }
    }

    public function member_login() {
        $this->set('provider', $this->_provider);
    }

    public function member_verify() {
        $this->autoRender = false;

        $provider_user = Cache::read('social_integration_' . CakeSession::id() . '_provider_user');

        if ($this->request->data['email'] != $provider_user['email']) {
            $this->Session->setFlash(__('Invalid email address'), 'default', array('class' => 'error-message'));
            $this->redirect(array('action' => 'member_login', 'provider' => $this->_provider));
        }

        // log user in
        $user_id = $this->_logMeIn($this->request->data['email'], $this->request->data['password']);
        if (!$user_id) {
            $this->Session->setFlash(__('Invalid email or password'), 'default', array('class' => 'error-message'));
            $this->redirect(array('action' => 'member_login', 'provider' => $this->_provider));
        }else{
            $this->loadModel('User');
            $user = $this->User->findById($user_id);
            $this->loadModel('Role');
            $role = $this->Role->findById($user['User']['role_id']);
            $user['User']['Role'] = $role['Role'];
            $this->Auth->login($user['User']);
        }

        // Add to synch user
        $this->SocialUser->create();
        $this->SocialUser->set(array(
            'user_id' => $user_id,
            'provider' => strtolower($this->_provider),
            'provider_uid' => $provider_user['identifier'],
            'access_token' => $provider_user['access_token']
        ));
        $this->SocialUser->save();

        // Session provider
        $this->Session->write('provider', $this->_provider);

        // Delete cache
        Cache::delete('social_integration' . CakeSession::id() . '_provider_user');

        $this->redirect('/');
    }

    public function signup_step2() {
		
        $provider_user = Cache::read('social_integration_' . CakeSession::id() . '_provider_user');
        $gen_password = $this->Social->generatePassword();
        // User params
        
        // Fix bugs email not alway returned, due to some reason
        // Ref: https://developers.facebook.com/bugs/298946933534016/
        $email = !empty($provider_user['email']) ? $provider_user['email'] : $provider_user['identifier'] . '@facebook.com';
        
        $data = array(
            'email' => $email,
            'name' => $provider_user['displayName'],
            'password' => $gen_password,
            'password2' => $gen_password
        );
        
        // load spam challenge if enabled
        if ( Configure::read('core.enable_spam_challenge') )
        {
            $this->loadModel('SpamChallenge');                
            $challenges = $this->SpamChallenge->findAllByActive(1);

            if ( !empty( $challenges ) )
            {
                $rand = array_rand( $challenges );

                $this->Session->write('spam_challenge_id', $challenges[$rand]['SpamChallenge']['id']);
                $this->set('challenge', $challenges[$rand]);
            }
        }

        if($this->request->params['provider'] == 'facebook' || $this->request->params['provider'] == 'google')
        {
            //get user avatar on facebook or google
            if($this->request->params['provider'] == 'google')
                $provider_user['photoURL'] = str_replace('sz=50','sz=200',$provider_user['photoURL']);

            if($this->request->params['provider'] == 'facebook')
                $provider_user['photoURL'] = str_replace('width=150&height=150','width=200&height=200',$provider_user['photoURL']);

            $thumb = file_get_contents(urldecode(urlencode($provider_user['photoURL'])));
            //$avatar = $HttpSocket->get( str_replace('https','http',$thumb->headers['Location'] ));

            $avatar_name = md5(uniqid()).'.jpg';
            file_put_contents( WWW_ROOT . 'uploads' . DS . 'tmp' . DS  . $avatar_name, $thumb );
            $data['avatar'] = 'uploads' . DS . 'tmp' . DS . $avatar_name;
        }
        list($packages,$compare) = MooCore::getInstance()->getHelper('Subscription_Subscription')->getPackageSelect(1);
        $isGatewayEnabled = MooCore::getInstance()->getHelper('Subscription_Subscription')->checkEnableSubscription();
        $currency = Configure::read('Config.currency');
        $this->set('isGatewayEnabled',$isGatewayEnabled);
        $this->set('compare',$compare);
        $this->set('packages',$packages);
        $this->set('currency',$currency);

        $this->User->set($data);
        $this->set($data);
        $this->set('provider', $this->_provider);
        
        unset($this->User->validate['username']['blankUsername']);
        unset($this->User->validate['timezone']);
        unset($this->User->validate['about']);

        if ($this->User->validates()) {
            $this->loadModel('ProfileField');
            list($profile_type,$custom_fields) = $this->getProfileType();

            //packages
            $cbPackage = array();

            $this->set('cbPackage', $cbPackage);
            $this->set('custom_fields', $custom_fields);
            $this->set('profile_type', $profile_type);
        } else {
            $this->autoRender = false;
            $errors = $this->User->invalidFields();

            echo '<span id="mooError">' . current(current($errors)) . '</span>';
        }
    }
    
    protected function getProfileType()
    {
    	$this->loadModel('ProfileType');
    	$this->loadModel('ProfileField');
    	$fields_type = $this->ProfileType->find( 'all', array( 'conditions' => array('actived' => 1) ) );
    	$profile_type = array();
    	$id = 0;
    	foreach($fields_type as $field_type) {
    		if (!$id)
    			$id = $field_type['ProfileType']['id'];
    			$profile_type[$field_type['ProfileType']['id']] = $field_type['ProfileType']['name'];
    	}
    
    	$profile_field = array();
    	if ($id)
    	{
    		$profile_field = $this->ProfileField->getRegistrationFields($id);
    	}
    	return array( $profile_type, $profile_field );
    }

    // ajax sign up
    public function ajax_signup_step2() {
        $this->autoRender = false;

        $provider = ucfirst($this->_provider);
        $provider_user = Cache::read('social_integration_' . CakeSession::id() . '_provider_user');
        
        // check spam challenge
        if (Configure::read('core.enable_spam_challenge') && isset($this->request->data['spam_challenge'])) {
            $this->loadModel('SpamChallenge');

            $challenge = $this->SpamChallenge->findById($this->Session->read('spam_challenge_id'));
            if (!empty($challenge) && $challenge['SpamChallenge']['active']){
                $answers = explode("\n", $challenge['SpamChallenge']['answers']);

                $found = false;
                foreach ($answers as $answer) {
                    if (strtolower(trim($answer)) == strtolower($this->request->data['spam_challenge']))
                        $found = true;
                }

                if (!$found) {
                    echo __('Invalid security question');
                    return;
                }
            }
        }

        // check captcha
        $checkRecaptcha = MooCore::getInstance()->isRecaptchaEnabled();
        $recaptcha_privatekey = Configure::read('core.recaptcha_privatekey');
        if ( $checkRecaptcha)
        {
            App::import('Vendor', 'recaptchalib');
            $reCaptcha = new ReCaptcha($recaptcha_privatekey);
            $resp = $reCaptcha->verifyResponse(
                    $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]
            );

            if ($resp != null && !$resp->success) {
                echo __('Invalid security code');
                return;
            }

        }

        $user_id = $this->_saveRegistration($this->request->data);

        // Add to synch user
        if ($user_id) {

            $this->loadModel('SocialIntegration.SocialUser');
            $this->SocialUser->create();
            $this->SocialUser->set(array(
                'user_id' => $user_id,
                'provider' => strtolower($provider),
                'provider_uid' => $provider_user['identifier'],
                'access_token' => $provider_user['access_token']
            ));
            $this->SocialUser->save();

            // Delete cache
            Cache::delete('social_integration' . CakeSession::id() . '_provider_user');
        }
    }

    protected function _getAuthAdapter($provider) {
        App::import('Lib/SocialIntegration', 'Storage');
        $storage = new SocialIntegration_Storage();

        // Check if SocialIntegration_Auth session already exist
        if (!$storage->config("CONFIG")) {
            header("HTTP/1.0 404 Not Found");
            die("You cannot access this page directly.");
        }

        SocialIntegration_Auth::initialize($storage->config("CONFIG"));

        $hauth = SocialIntegration_Auth::setup($provider);
        $hauth->adapter->initialize();

        return $hauth;
    }

    private function _saveRegistration($data) {
        // check if registration is disabled            
        if (Configure::read('core.disable_registration')) {
            echo '<span id="mooError">' . __('The admin has disabled registration on this site') . '</span>';
            return;
        }

        // check registration code            
        if (Configure::read('core.enable_registration_code') && $data['registration_code'] != Configure::read('core.registration_code')) {
            echo '<span id="mooError">' . __('Invalid registration code') . '</span>';
            return;
        }
        
        if (!empty($data['email'])) {
            if ($this->isBanned($data['email'])){
                $this->autoRender = false;
                echo __('You are not allowed to view this site');
                exit;
            }
        }

        $data['role_id'] = ROLE_MEMBER;
        $clientIP = getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : $_SERVER['REMOTE_ADDR'];
        $data['ip_address'] = $clientIP;
        $data['code'] = md5($data['email'] . microtime());
        $data['confirmed'] = ( Configure::read('core.email_validation') ) ? 0 : 1;
        $data['last_login'] = date("Y-m-d H:i:s");
        $data['privacy'] = Configure::read('core.profile_privacy');
        $data['featured'] = 0;
        //$data['username'] = '';
        $data['is_social'] = 1;
        
        if (isset($data['plan_id']))
        {
        	$data['package_select'] = $data['plan_id'];
        }
        
        if (!Configure::read('core.approve_users')){
            $data['approved'] = 1;
        }

        $this->User->set($data);
        
        if (!Configure::read('core.show_username_signup'))
        {
        	if (isset($this->User->validate['username']['blankUsername']))
        		unset($this->User->validate['username']['blankUsername']);
        }
        
        if (!Configure::read('core.enable_timezone_selection'))
        {
        	if (isset($this->User->validate['timezone']))
        		unset($this->User->validate['timezone']);
        }
        
        if (!Configure::read('core.show_about_signup'))
        {
        	if (isset($this->User->validate['about']))
        		unset($this->User->validate['about']);
        }
        
        if (!$this->User->validates()) {
            $errors = $this->User->invalidFields();
            echo '<span id="mooError">' . current(current($errors)) . '</span>';

            return;
        }
        
        // fixed issue: require real email, not using Facebook fake email
        if (strstr($data['email'], "facebook.com")){
            echo '<span id="mooError">' . __('Please using your real email to continue signup') . '</span>';
            return;
        }

        // check custom required fields
        $this->loadModel('ProfileField');
        $custom_fields = $this->ProfileField->getRegistrationFields($data['profile_type_id'],true);
        $helper = MooCore::getInstance()->getHelper("Core_Moo");

    	foreach ($custom_fields as $field)
		{
            if (!in_array($field['ProfileField']['type'],$helper->profile_fields_default))
            {
                $helper = MooCore::getInstance()->getHelper("Core_Moo");
                if ($field['ProfileField']['plugin'])
                    $helper = MooCore::getInstance()->getHelper($field['ProfileField']['plugin'].'_'.$field['ProfileField']['plugin']);

                if (method_exists($helper,'checkProfileField'))
                {
                    $result = $helper->checkProfileField($field['ProfileField']['type'],$field,$data);
                    if ($result)
                    {
                        echo $result;
                        return;
                    }
                }
                continue;
            }
			$value = $data['field_' . $field['ProfileField']['id']];
			
			if ( $field['ProfileField']['required'] && empty( $value ) && !is_numeric( $value ) )
			{
				echo $field['ProfileField']['name'] . __(' is required');
                
				return;
			}
		}

        // keep a copy of avatar for Profile Album picture, because after uploaded, behavior deleted original file
        $newTmpAvatar = '';
        $tmp_avatar_string = md5(microtime());
        if(!empty($data['avatar']))
        {
            $file = $data['avatar'];
            $epl = explode('.', $file);
            $extension = $epl[count($epl) - 1];
            $newTmpAvatar = WWW_ROOT . 'uploads' . DS . 'tmp' . DS . $tmp_avatar_string.'.' . $extension;
            copy(WWW_ROOT . $file, $newTmpAvatar);
        }

        if ($this->User->save()) { // successfully saved
            // save profile field values
            $this->loadModel('ProfileFieldValue');
            $helper = MooCore::getInstance()->getHelper("Core_Moo");

	        foreach ($custom_fields as $field)
			{
				if (!in_array($field['ProfileField']['type'],$helper->profile_fields_default))
				{
					$helper = MooCore::getInstance()->getHelper("Core_Moo");
					if ($field['ProfileField']['plugin'])
						$helper = MooCore::getInstance()->getHelper($field['ProfileField']['plugin'].'_'.$field['ProfileField']['plugin']);

					if (method_exists($helper,'saveProfileField'))
					{
						$helper->saveProfileField($field['ProfileField']['type'],$field,$data,$this->User->id);
					}
					$value = '';
					if (isset($data['field_' . $field['ProfileField']['id']])) {
						$value = $data['field_' . $field['ProfileField']['id']];
					}

					$this->ProfileFieldValue->create();
					$this->ProfileFieldValue->save(array('user_id' => $this->User->id,
						'profile_field_id' => $field['ProfileField']['id'],
						'value' => $value
					));

					continue;
				}
				if (isset($data['field_' . $field['ProfileField']['id']])) {
					$value = $data['field_' . $field['ProfileField']['id']];
					$value = (is_array($value)) ? implode(', ', $value) : $value;

					$this->ProfileFieldValue->create();
					$this->ProfileFieldValue->save(array('user_id' => $this->User->id,
						'profile_field_id' => $field['ProfileField']['id'],
						'value' => $value
					));
				}
			}

            // insert into activity feed
            $this->loadModel('Activity');
            $this->Activity->save(array('type' => APP_USER,
                'action' => 'user_create',
                'user_id' => $this->User->id
            ));

        $user = $this->User->read();
			$ssl_mode = Configure::read('core.ssl_mode');
        	$http = (!empty($ssl_mode)) ? 'https' :  'http';
			if ($data['confirmed'])
			{
        		$this->MooMail->send($data['email'],'welcome_user',
    				array(
    					'email' => $data['email'],
                                        'password' => $data['password'],
    					'recipient_title' => $user['User']['name'],
    					'recipient_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    					'site_name'=>Configure::read('core.site_name'),
    					'login_link'=> $http.'://'.$_SERVER['SERVER_NAME'].$this->request->base.'/users/member_login',
    				)
    			);
			}
			else
			{
				$this->MooMail->send($data['email'],'welcome_user_confirm',
    				array(
    					'email' => $data['email'],
                                        'password' => $data['password'],
    					'recipient_title' => $user['User']['name'],
    					'recipient_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    					'site_name'=>Configure::read('core.site_name'),
    					'confirm_link'=> $http.'://'.$_SERVER['SERVER_NAME'].$this->request->base.'/users/do_confirm/'.$data['code'],
    				)
    			);
			}

			// Send an email to admin if enabled
			if ( Configure::read('core.registration_notify'))
			{		
				$this->MooMail->send(Configure::read('core.site_email'),'new_registration',
    				array(
    					'new_user_title' => $user['User']['name'],
    					'new_user_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    					'site_name'=>Configure::read('core.site_name'),
    				)
    			);		
				//$this->_sendEmail( Configure::read('core.site_email'), 'New Registration', null, null, null, null, '<a href="' . FULL_BASE_URL . $this->request->base . '/users/view/' . $this->User->id . '">' . $data['name'] . '</a> has just signed up on ' . Configure::read('core.site_name'));
			}

            // Log user in
            //$this->Session->write('uid', $this->User->id);
            $cuser = $user['User'];
            $cuser['Role'] = $user['Role'];
            $this->Auth->login($cuser);

            $this->Session->write('provider', $this->_provider);

            if (Configure::read('core.email_validation'))
                $this->Session->setFlash(__('An email has been sent to your email address<br />Please click the validation link to confirm your email<br />Click <a href="javascript:void(0);" id="resend_validation_link">here</a> to resend validation link.'));

            //custom: upload avatar after sign up
            if(!empty($newTmpAvatar))
            {
                $uid = $this->User->id;
                $this->loadModel('Photo.Album');
                $album = $this->Album->getUserAlbumByType($uid, 'profile');
                $title = 'Profile Pictures';
                if (empty($album)) {
                    $this->Album->save(array('user_id' => $uid, 'type' => 'profile', 'title' => $title), false);
                    $album_id = $this->Album->id;
                } else {
                    $album_id = $album['Album']['id'];
                }


                $tmp_photo_url = 'uploads' . DS . 'tmp' . DS . $tmp_avatar_string.'.' . $extension;
                // save to db
                $this->loadModel('Photo.Photo');
                $this->Photo->create();
                $this->Photo->set(array('user_id' => $uid,
                    'target_id' => $album_id,
                    'type' => 'Photo_Album',
                    'thumbnail' => $tmp_photo_url,
                ));

                $this->Photo->save();
                $this->Album->id = $album_id;
                $filename = explode('/', $tmp_photo_url);
                $filename1 = $filename[count($filename) - 1];
                $this->Album->save(array('cover' => $filename1));

            }
            
            $this->loadModel('Friend');
            $auto_add_friend = Configure::read('core.auto_add_friend');
            if(!empty($auto_add_friend))
            {
                $list_friend = explode(',',$auto_add_friend);
                $this->Friend->autoFriends($this->User->id, $list_friend);

            }

            //subscription
            /*$this->loadModel('Subscription.Package');

            if ($this->Package->hasAny(array('signup' => 1, 'enabled' => 1)) &&
                    isset($data['package_id']) && (int) $data['package_id'] > 0) {
                $this->Session->write('package_id', $data['package_id']);
                echo json_encode(array('redirect' => $this->request->base . '/subscription/subscribes/gateway/'));
            }*/
            
            // avatar social
			$this->getEventManager()->dispatch(new CakeEvent('UserController.doAfterRegister', $this));
			$json = ob_get_contents();
			ob_end_clean();
			if (!trim($json) && Configure::read("core.link_after_login"))
			{
				echo json_encode(array('redirect' => $this->request->base.'/'.Configure::read("core.link_after_login")));
			}
            return $this->User->id;
        } else
            echo __('Something went wrong. Please contact the administrators');
    }

    public function getProviderUser() {
        return $this->_provider_user;
    }

    public function disconnect() {
        $uid = $this->Auth->user('id');
        if (!$uid) {
            $this->redirect('/');
        }
        $sync = empty($this->request->named['sync']) ? 0 : $this->request->named['sync'];

        $this->SocialUser->deleteAll(array('SocialUser.provider' => $this->_provider, 'SocialUser.user_id' => $uid), false);

        if ($sync) {
            $this->redirect(array(
                'plugin' => 'social_integration',
                'controller' => 'connect'
            ));
        } else {
            $this->redirect('/');
        }
    }

}
