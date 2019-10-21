<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class HomeController extends AppController {
	
    public function index() {
        if ($this->isApp())
        {
            $this->redirect('/activities/ajax_browse/everyone');
        }

        $uid = $this->Auth->user('id');

        $this->loadModel('Tag');
        $tags = $tags = $this->Tag->getTags(null, Configure::read('core.popular_interval'));

        if ($uid){
            
           if (Configure::read('Event.event_enabled')) {
                $this->loadModel('Event.EventRsvp');
                $events_count = $this->EventRsvp->getMyEventsCount($uid);
                if (!$events_count)
                	$events_count = 0;
                $this->set('events_count', $events_count);
            }
            
            if (Configure::read('Group.group_enabled')) {
                $this->loadModel('Group.Group');
                $groups_count = $this->Group->getMyGroupCount($uid);
                $this->set('groups_count', $groups_count);
            } 
            
            $this->loadModel("Friend");
            $friend_count = $this->Friend->find('count',array('conditions'=>array(
            	'user_id' => $uid
            )));
            $this->set('friend_count', $friend_count);
        }
        
        if (!empty($this->request->named['tab'])) { // open a specific tab
            $this->_checkPermission();
            $this->set('tab', $this->request->named['tab']);
        } else {
            $activity_feed = Configure::read('core.default_feed');
            if (!empty($uid) || ( empty($uid) && Configure::read('core.default_feed') == 'everyone' && !Configure::read('core.hide_activites') )) {
                $this->loadModel('Activity');

                // save activity feed that you selected
                if (!empty($uid) && Configure::read('core.feed_selection') && $this->Cookie->read('activity_feed'))
                    $activity_feed = $this->Cookie->read('activity_feed');

                if (!in_array($activity_feed, array('everyone', 'friends'))) {
                    $activity_feed = Configure::read('core.default_feed');
                }
            }
            $this->set('activity_feed', $activity_feed);
            $this->set('homeActivityWidgetParams',$this->Feeds->get());
            $this->set('homeActivityWidgetParams',null);
            $this->set('title_for_layout', '');

        }

        $this->set('tags', $tags);
        //Get profile avatar
        if (!empty($uid)) {
            $this->loadModel('User');
            $user = $this->User->findById($uid);
            $this->set('user', $user);
        }
    }

    public function ajax_theme() {
        //$this->autoRender = false;
        $this->layout = false;
    }
	public function ajax_lang() {
        
        $this->layout = false;
    }
	
	public function do_theme( $theme_key )
	{
		if ( !empty( $theme_key ) )
			$this->Cookie->write('theme', $theme_key);
	    $uid = MooCore::getInstance()->getViewer(true);
        if(empty($uid)){
            $this->Session->write('non_login_user_theme',$theme_key);
            $this->Session->delete('non_login_user_default_theme');
        }
		$this->redirect( $this->referer() );
	}
    
  
    
    public function do_fullsite()
    {
        $this->Session->write('fullsite', 1);
        $this->redirect( $this->referer() );
    }
    
    public function do_mobile()
    {
        $this->Session->delete('fullsite');
        $this->redirect( $this->referer() );
    }
	
	public function do_language( $key )
	{
		if ( !empty( $key ) )
		{
			$this->Cookie->write('language', $key);
			
			$uid = $this->Auth->user('id');
			
			// update user profile if logged in
			if ( !empty( $uid ) )
			{
				$this->loadModel('User');
				
				$this->User->id = $uid;
				$this->User->save( array( 'lang' => $key ) );
			}
		}
		
		$this->redirect( $this->referer() );
	}
	
	public function contact()
	{
		if ( !empty( $this->request->data ) )
		{
            $this->autoRender = false;
            // check captcha
            $result['status'] = true;
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
                    $result['status'] = false;
                    $result['message'] = __('Invalid security code');
                }
            }

            if (!Validation::email( trim( $this->request->data['sender_email'] ) ) )
			{
                $result['status'] = false;
                $result['message'] = __('Invalid email address');
            }

            if ($result['status']) {
                $ssl_mode = Configure::read('core.ssl_mode');
                $http = (!empty($ssl_mode)) ? 'https' : 'http';
                $this->MooMail->send(Configure::read('core.site_email'), 'mail_contact',
                    $this->request->data
                );

                $this->Session->setFlash(__('Thanks you! Your message has been sent'));
            }

			echo json_encode($result);
			//$this->redirect( $this->referer() );
		}
	}


	public function admin_index()
	{
		$this->loadModel('User');
		$this->loadModel('Photo.Photo');
		$this->loadModel('Blog.Blog');
		$this->loadModel('Group.Group');
		$this->loadModel('Event.Event');
		$this->loadModel('Topic.Topic');
		$this->loadModel('Video.Video');
        $this->loadModel('Activity');
        
        $stats = Cache::read('admin_stats');
        
        if (!$stats) 
        {        
            $date = new DateTime();
            $stats = array();
            
            for ( $i = 1; $i <= 7; $i++ )
            {
                
                $date->modify('-1 day');
                
                $stats[$date->format('M j')]['users'] = $this->User->find('count', array( 'conditions' => array( 
                    'User.created >= ?' => $date->format('Y-m-d') . ' 00:00:00',
                    'User.created <= ?' => $date->format('Y-m-d') . ' 23:59:59'
                ) ) );
                
                $stats[$date->format('M j')]['activities'] = $this->Activity->find('count', array( 'conditions' => array( 
                    'Activity.created >= ?' => $date->format('Y-m-d') . ' 00:00:00',
                    'Activity.created <= ?' => $date->format('Y-m-d') . ' 23:59:59'
                ) ) );
            }
        
            $stats = array_reverse( $stats, true );
            
            Cache::write('admin_stats', $stats);
        }
        
            $settings = $this->Setting->find('all', array(
                'conditions' => array('Setting.group_id' => 1, 'Setting.name' => 'admin_notes')
            ));
            
            $this->set(compact('settings', 'stats'));
            $this->set('user_count', $this->User->find( 'count' ));
            $this->set('photo_count', $this->Photo->find( 'count' ));
            $this->set('blog_count', $this->Blog->find( 'count' ));
            $this->set('group_count', $this->Group->find( 'count' ));
            $this->set('event_count', $this->Event->find( 'count' ));
            $this->set('topic_count', $this->Topic->find( 'count' ));
            $this->set('video_count', $this->Video->find( 'count' ));

            $this->set('title_for_layout', __('Admin Home'));
            
            $event = new CakeEvent('Controller.Home.adminIndex.Statistic', $this, array('passParams' => true));
            $this->getEventManager()->dispatch($event);

            if(!empty($event->result)){
                usort($event->result['statistics'], function($a, $b) {
                    return $a['ordering'] - $b['ordering'];
                });
            }
                     
            $this->set('plugin_statistics', $event->result['statistics']);
	}
	
	public function admin_login()
	{
            if ($this->request->is('post')){
                if (!empty($this->request->data['User'])) {
                    $this->loadModel('User');

                    $admin_email = isset($this->request->data['User']['email']) ? $this->request->data['User']['email'] : "";
                    $admin_password = isset($this->request->data['User']['password']) ? $this->request->data['User']['password'] : "";

                    // find the user
                    $user = $this->User->find('first', array('conditions' => array('email' => trim($admin_email))));

                    $checked = false;
                    if (!empty($user)){ // found
                        $passwordHasher = new MooPasswordHasher();
                        if ($user['User']['password'] == $passwordHasher->hash($admin_password,$user['User']['salt'])) // wrong password
                        {
                            if (!$user['User']['salt'])
                            {
                                $this->User->id = $user['User']['id'];
                                $this->User->save(array('password'=> $admin_password));
                            }

                            if ($user['Role']['is_admin']) {
                                $checked = true;
                                $this->Session->write('admin_login', 1);
                            }
                        }
                    }

                    if (!$checked) {
                        $this->Session->setFlash(__('Invalid email or password'), 'default', array('class' => 'error-message'));
                    }

                    $this->redirect('/admin/');
                }
            }
        }
    public function admin_phpinfo(){
        phpinfo();die();
    }
    public function landing(){
        //$this->index();
    }
    public  function currentUri(){

        $uri = empty($this->params['controller'])?"":$this->params['controller'];
        $uri.= empty($this->params['action'])?"":".".$this->params['action'];

        if($uri =='pages.display'){
            $uri.= empty($this->params['pass'][0])?"":".".$this->params['pass'][0];
        }
        if(!$this->Auth->user('id') && ($uri =='home.index')){
            $uri = "home.landing";
        }
        return $uri;
    }
    public function getActivities(){
        if($this->request->is('requested')){
            $uid = $this->Auth->user('id');
            $this->loadModel( 'Activity' );
            $activity_feed = Configure::read('core.default_feed');

            // save activity feed that you selected
            if ( !empty( $uid ) && Configure::read('core.feed_selection') && $this->Cookie->read('activity_feed') )
                $activity_feed = $this->Cookie->read('activity_feed');

            $activities = $this->Activity->getActivities( $activity_feed, $uid );
            return array($activity_feed, $activities);
        }
    }
    public function getSettings(){
        return $this->_getSettings();
    }

}

