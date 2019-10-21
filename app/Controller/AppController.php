<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('Controller', 'Controller');
App::uses('String', 'Utility');
App::uses('WidgetCollection', 'Lib');

class AppController extends Controller
{
    public $components = array(
        'Auth' => array(
            'loginRedirect' => array(
                'controller' => 'home',
                'action' => 'index'
            ),
            'logoutRedirect' => array(
            	'plugin' => '',
                'controller' => 'users',
                'action' => 'member_login',

            ),
            'loginAction' => array(
                'controller' => 'users',
                'action' => 'member_login',

            ),
            'authenticate' => array(
                'Form' => array(
                    'fields' => array('username' => 'email'),
                    'contain' => 'Role',
                    'passwordHasher' => array(
                        'className' => 'Moo',
                    )
                )
            ),
            'authorize' => array(
                'Actions' => array('actionPath' => 'controllers')
            )
        ),
        'Cookie',
        'Session',
        'RequestHandler',
        'Feeds',
        'Flash'
    );
    public $helpers = array(
        'Html' => array('className' => 'MooHtml'),
        'Text',
        'Form' => array('className' => 'MooForm'),
        'Session',
        'Time' => array('className' => 'AppTime'),
        'Moo',
        'Menu.Menu',
        'MooGMap',
        'Text' => array('className' => 'MooText'),
        'MooPeople',
    	'MooTranslate',
        'MooPhoto',
        'MooTime',
        'MooActivity',
        'MooPopup',
        'MooRequirejs',
        'Minify.Minify',
        'Storage.Storage'
    );
    public $viewClass = 'Moo';
    public $check_subscription = true;
	public $check_force_login = true;

    /*
    * Initialize the system
    */
    public function __construct($request = null, $response = null)
    {
    	//CakeLog::write('ajax', print_r($request->data,true));
        if (!empty($request)) {
            $request->addDetector('api', array('callback' => array($this, 'isApi')));
            $request->addDetector('androidApp', array('callback' => array($this, 'isAndroidApp')));
            $request->addDetector('iosApp', array('callback' => array($this, 'isIOSApp')));
			$request->addDetector('mobile', array('callback' => array($this, 'isMobile')));
        }
        parent::__construct($request, $response);
        $this->Widgets = new WidgetCollection($this);

    }

    public function isApi($request)
    {
        if (isset($request['ext']) && $request['ext'] == "json" && strpos($request->url, 'api') !== false) {
            return true;
        }
        return false;
    }

    public function isApp()
    {
        return $this->request->is('androidApp') || $this->request->is('iosApp');
    }

    public function isAndroidApp($request)
    {  
        if (strpos($this->request->header('User-Agent'), 'mooAndroid/1.0') !== false ||
            strpos($this->request->header('User-Agent'), 'Crosswalk/') !== false
        ) {
            return true;
        }
        return false;
    }

    public function isIOSApp($request)
    {
        if (strpos($this->request->header('User-Agent'), 'mooIOS/1.0') !== false ) {
            return true;
        }
        return false;
    }


    public function initialize()
    {
        echo "initialize";
    }

    public function beforeFilter()
    {
        $this->Auth->allow();
        if ($this->request->is('requested')) {
            // MOOSOCIAL-1366 - Duplicate query on landing page
            // Do nothing
            return;
        }

        //Todo Refactor
        // 1. Loading Application Settings process
        // 2. Identifying viewer process
        // 2.1 Loading viewer settings process
        // 3. Executing ban process
        // 4. Executing theme process
        // 5. Executing viewer process
        $this->loadingApplicationSettings();
        $this->identifyingViewer();
        $this->doBanUsersProcess();
        $this->doThemeProcess();
        $this->doViewerProcess();

        //disable cache respone
        $this->response->disableCache();
        
        $this->getEventManager()->dispatch(new CakeEvent('AppController.afterFilter', $this));
    }

    private function loadComponent()
    {
        $components = MooComponent::getAll();
        if (count($components)) {
            foreach ($components as $class => $settings) {
                list($plugin, $name) = pluginSplit($class);
                $this->{$name} = $this->Components->load($class, $settings);
            }
        }
    }

    private function loadUnBootSetting()
    {
        $this->loadModel('Setting');
        Configure::write('core.prefix', $this->Setting->tablePrefix);

        $settingDatas = Cache::read('site.settings');
        if (!$settingDatas) {
            $this->loadModel('SettingGroup');

            //load all unboot setting
            $settings = $this->Setting->find('all', array(
                'conditions' => array('is_boot' => 0),
            ));
            //parse setting value
            $settingDatas = array();
            if ($settings != null) {
                foreach ($settings as $k => $setting) {
                    //parse value
                    $value = $setting['Setting']['value_actual'];
					$hasValue  = array();
                    switch ($setting['Setting']['type_id']) {
                        case 'radio':
                        case 'select':
                            $value = '';
                            $multiValues = json_decode($setting['Setting']['value_actual'], true);
                            if ($multiValues != null) {
                                foreach ($multiValues as $multiValue) {
                                    if ($multiValue['select'] == 1) {
                                        $value = $multiValue['value'];
                                    }
                                }
                            }
                            break;
                        case 'checkbox':
                            $value = '';
                            $multiValues = json_decode($setting['Setting']['value_actual'], true);
                            if ($multiValues != null) {
                                $isHaveValue = false;
                                foreach ($multiValues as $multiValue) {
                                    if ($multiValue['select'] == 1) {
                                        $hasValue[] = $multiValue['value'];
                                        $isHaveValue = true;
                                    }
                                }
                                //if (is_array($value) && count($value) == 1) {
                                if($isHaveValue && count($hasValue) == 1){
                                    $value = $hasValue[0];
                                }
                            }
                            break;
                    }

                    //parse module
                    $data['module_id'] = $setting['SettingGroup']['module_id'];
                    $data['name'] = $setting['Setting']['name'];
                    $data['value'] = (count($hasValue) > 1)?$hasValue:$value;
                    $settingDatas[] = $data;
                }
            }
            Cache::write('site.settings', $settingDatas);
        }

        if ($settingDatas != null) {
            foreach ($settingDatas as $setting) {
                Configure::write($setting['module_id'] . '.' . $setting['name'], $setting['value']);
            }
        }
        
        Configure::write('core.photo_image_sizes','75_square|150_square|300_square|250|450|850|1500');
    }

    /**
     * Get the current logged in user
     * @return array
     */
    public function _getUser() {
        // Hacking MOOSOCIAL-2298, cache issue of Auth Component
        $uid = $this->Auth->user('id');
        $cuser = array();
        if (!empty($uid)) { // logged in users

            $this->loadModel('User');
            $this->User->cacheQueries = true;

            $user = $this->User->findById($uid);
            if (!$user)
            {
            	return $this->redirect($this->Auth->logout());
            }

            $cuser = $user['User'];
            $cuser['Role'] = $user['Role'];
            $cuser['ProfileType'] = $user['ProfileType'];
            
        }
        
        return $cuser;
    }

    /**
     * Get the current logged in user's role id
     * @return int
     */
    protected function _getUserRoleId()
    {
        $cuser = $this->_getUser();
        $role_id = (empty($cuser)) ? ROLE_GUEST : $cuser['role_id'];

        return $role_id;
    }

    /**
     * Get the current logged in user's role params
     * @return array
     */
    public function _getUserRoleParams()
    {
        $cuser = $this->_getUser();

        if (!empty($cuser)) {
            $params = explode(',', $cuser['Role']['params']);
        } else {
            $params = Cache::read('guest_role');

            if (empty($params)) {
                $this->loadModel('Role');
                $guest_role = $this->Role->findById(ROLE_GUEST);

                $params = explode(',', $guest_role['Role']['params']);
                Cache::write('guest_role', $params);
            }
        }

        return $params;
    }

    /**
     * Get global site settings
     * @return array
     */
    public function _getSettings()
    {
        $this->loadModel('Setting');
        $this->Setting->cacheQueries = true;

        $settings = $this->Setting->find('list', array('fields' => array('field', 'value')));

        return $settings;
    }
    public function isAllowedPermissions($rule){
        $rule = is_array($rule)?$rule:array($rule);
        $acos = $this->_getUserRoleParams(); 
        return count(array_intersect($rule, $acos)) == count($rule);
    }
    /**
     * Check if user has permission to view page
     * @param array $options - array( 'roles' => array of role id to check
     *                                  'confirm' => boolean to check email confirmation
     *                                  'admins' => array of user id to check ownership
     *                                  'admin' => boolean to check if logged in user is admin
     *                                  'super_admin' => boolean to check if logged in user is super admin
     *                                'aco' => string of aco to check against user's role
     *                                 )
     */
    protected function _checkPermission($options = array())
    {
        $viewer = MooCore::getInstance()->getViewer();
        if (!empty($viewer) && $viewer['Role']['is_admin']) {
            return true;
        }

        $cuser = $this->_getUser();
        $authorized = true;
        $hash = '';
        $return_url = '?redirect_url=' . base64_encode(FULL_BASE_URL.$this->request->here);

        //check normal subscription
        $this->options = $options;
        //$this->getEventManager()->dispatch(new CakeEvent('AppController.validNormalSubscription', $this));

        // check aco
        $check_aco = false;
        if (!empty($options['aco'])) {
            $acos = $this->_getUserRoleParams();

            if (!in_array($options['aco'], $acos)) {
                $authorized = false;
                $check_aco = true;
                $msg = __('Access denied');
            }
        } else if (!empty($options['user_block'])) {
        	if ($cuser && !$cuser['Role']['is_admin'] && !$cuser['Role']['is_super'])
        	{        	
	            $user_blocks = $this->getBlockedUsers($cuser['id']);     
	            if (in_array($options['user_block'], $user_blocks)) {
	                $authorized = false;
	                $msg = __('Access denied');
	                $return_url = '';
	            }
        	}
        } else {
            // check login
            if (!$cuser) {
                $authorized = false;
                $msg = __('Please login or register');
            } else {
                // check role
                if (!empty($options['roles']) && !in_array($cuser['role_id'], $options['roles'])) {
                    $authorized = false;
                    $msg = __('Access denied');
                }

                // check admin
                if (!empty($options['admin']) && !$cuser['Role']['is_admin']) {
                    $authorized = false;
                    $msg = __('Access denied');
                }

                // check super admin
                if (!empty($options['super_admin']) && !$cuser['Role']['is_super']) {
                    $authorized = false;
                    $msg = __('Access denied');
                }


                // check approval
                if (Configure::read('core.approve_users') && !$cuser['approved']) {
                    $authorized = false;
                    $msg = __('Your account is pending approval.');
                }

                // check confirmation
                if (Configure::read('core.email_validation') && !empty($options['confirm']) && !$cuser['confirmed']) {
                    $authorized = false;
                    $msg = __('You have not confirmed your email address! Check your email (including junk folder) and click on the validation link to validate your email address');
                }

                // check owner
                if (!empty($options['admins']) && !in_array($cuser['id'],
                        $options['admins']) && !$cuser['Role']['is_admin']
                ) {
                    $authorized = false;
                    $msg = __('Access denied');
                }
                
                //event check permission
                if ($authorized)
                {
                	$msg = '';
                	$cakeEvent = new CakeEvent('Controller.App.checkPermission', $this,array(
                			'authorized' => &$authorized,
                			'msg' => &$msg,
                			'options' => $options
                	));
	                $this->getEventManager()->dispatch($cakeEvent);
                }
            }
        }

        if (!$authorized && empty($options['no_redirect'])) {
            if (empty($this->layout)) {              
                $this->autoRender = false;
                echo $msg;
            } else {
                if ($this->request->is('ajax')) {
                    $this->set(compact('msg'));
                    if (!$check_aco)
                    {
                    echo $this->render('/Elements/error');
                    }
                    else
                    {
                    	echo $this->render('/Elements/error-role');
                    }

                } else {
                	if (!$check_aco)
                	{
                    if (!empty($msg)) {
                        $this->Session->setFlash($msg, 'default', array('class' => 'error-message'));
                    }

                    $this->redirect('/pages/no-permission' . $return_url);
                }
                	else
                	{
                		$this->redirect('/pages/no-permission-role' . $return_url);
                	}
                }
            }
            exit;
        }
    }

    /**
     * Check if an item exists
     * @param mixed $item - array or object to check
     */
    protected function _checkExistence($item = null)
    { 
        if (empty($item)) {
            $this->_showError(__('Item does not exist'));
            return;
        }
    }

    protected function _showError($msg)
    {
        $this->Session->setFlash($msg, 'default', array('class' => 'error-message'));
        $this->redirect(array("plugin" => "page", 
                      "controller" => "pages",
                      "action" => "error"));
        return;
    }

    protected function _jsonError($msg)
    {
        $this->autoRender = false;

        $response['result'] = 0;
        $response['message'] = $msg;

        echo json_encode($response);
        return;
    }

    /**
     * Validate submitted data
     * @param object $model - Cake model
     */
    protected function _validateData($model = null)
    {
        if (!$model->validates()) {
            $errors = $model->invalidFields();
            if($this->request->params['plugin'] == 'api' || $this->request->params['plugin'] == 'Api' ) {
                throw new ApiBadRequestException(current(current($errors)));
            }
            else {
                $response['result'] = 0;
                $response['message'] = current(current($errors));

                echo json_encode($response);
                exit;
            }
        }
    }

    /**
     * Check if current user is allowed to view item
     * @param string $privacy - privacy setting
     * @param int $owner - user if of the item owner
     * @param boolean $areFriends - current user and owner are friends or not
     */
    public function _checkPrivacy($privacy, $owner, $areFriends = null, $redirect = true)
    {
        $uid = $this->Auth->user('id');
        if ($uid == $owner) // owner
        {
            return true;
        }

        $viewer = MooCore::getInstance()->getViewer();
        if (!empty($viewer) && $viewer['Role']['is_admin']) {
            return true;
        }

        switch ($privacy) {
            case PRIVACY_FRIENDS:
                if (empty($areFriends)) {
                    $areFriends = false;

                    if (!empty($uid)) //  check if user is a friend
                    {
                        $this->loadModel('Friend');
                        $areFriends = $this->Friend->areFriends($uid, $owner);
                    }
                }

                if (!$areFriends) {
                	if ($redirect)
                	{
	                    $this->Session->setFlash(__('Only friends of the poster can view this item'), 'default',
	                        array('class' => 'error-message'));
	                    $this->redirect('/pages/no-permission');
                	}
                	else
                	{
                		return false;
                	}
                }

                break;

            case PRIVACY_ME:
            	if ($redirect)
                {
	                $this->Session->setFlash(__('Only the poster can view this item'), 'default',
	                    array('class' => 'error-message'));
	                $this->redirect('/pages/no-permission');
                }
                else 
                {
                	return false;
                }
                break;
        }
        
        return true;
    }

    /**
     * Log the user in
     * @param string $email - user's email
     * @param string $password - user's password
     * @param boolean $remember - remember user or not
     * @return uid if successful, false otherwise
     */
    protected function _logMeIn($email, $password, $remember = false)
    {
        if (!is_string($email) || !is_string($password)) {
            return false;
        }

        $this->loadModel('User');

        // find the user
        if(preg_match('/[^\x20-\x7f]/', $email))
        {        	
        	$email = '';
        }
        $user = $this->User->find('first', array('conditions' => array('email' => trim($email))));

        if (!empty($user)) // found
        {
            $passwordHasher = new MooPasswordHasher();
            if ($user['User']['password'] != $passwordHasher->hash($password,$user['User']['salt'])) // wrong password
            {
                return false;
            }

            if (!$user['User']['salt'])
            {
                $this->User->id = $user['User']['id'];
                $this->User->save(array('password'=> $password));
            }

            $auto_disable_reach_max_age = false;
            $max_age = Configure::read('core.max_age_restriction');
            $min_age = Configure::read('core.min_age_restriction');
            
            if (!$user['User']['active']) {
                
                $this->Session->setFlash(__('This account has been disabled'), 'default',
                    array('class' => 'error-message'));
                $this->logout();
                return $this->redirect($this->Auth->logout());
            }
        	elseif (!$user['User']['approved']) {
                
                $this->Session->setFlash(__('Your account is pending for approval'), 'default',
                    array('class' => 'error-message'));
                $this->logout();
                return $this->redirect($this->Auth->logout());
            } elseif ($user['User']['role_id'] != 1 && !empty($auto_disable_reach_max_age) && (!empty($max_age) || !empty($min_age)) && !empty($user['User']['birthday'])) {                 
                $dt = new DateTime($user['User']['birthday']);
                $dt1 = new DateTime($user['User']['birthday']);
                $not_valid = false;
                $current_date = new DateTime(date('Y-m-d'));
                if(!empty($min_age)){
                    date_add($dt, date_interval_create_from_date_string("$min_age years"));  
                    if($current_date <= $dt){
                        $not_valid = true;
                    }
                }
                
               
                if(!$not_valid && !empty($max_age)){
                    date_add($dt1, date_interval_create_from_date_string("$max_age years"));
                    if($current_date >= $dt1){
                        $not_valid = true;
                    }
                }
                
                if ($not_valid){ 
                    $this->User->id = $user['User']['id'];
                    $this->User->save(array('active' => 0));
                    $ssl_mode = Configure::read('core.ssl_mode');
                    $http = (!empty($ssl_mode)) ? 'https' :  'http';
                    $mail_params = array('recipient_title' => $user['User']['name'],
    					'recipient_link' => $http.'://'.$_SERVER['SERVER_NAME'].$user['User']['moo_href'],
    					'site_name'=>Configure::read('core.site_name'));
                    
                    if(!empty($min_age)){ 
                        $mail_params['subject_age_alert'] = __('Your account has been auto disabled because it does not match age Restriction of the site.');
                        $mail_params['age_alert'] = __('Your account has been auto disabled because it does not match age Restriction of the site.');
            } else {
                        $mail_params['subject_age_alert'] = __('Your account has been auto disabled because it reached max age Restriction of the site.');
                        $mail_params['age_alert'] = __('Your account has been auto disabled because it does not match age Restriction of the site.');
                    }
                    
                    $this->MooMail->send($user, 'max_age_restriction',$mail_params);
                                       
                    $this->Session->setFlash( $mail_params['subject_age_alert'], 'default',
                        array('class' => 'error-message'));
                    $this->logout();
                    return $this->redirect($this->Auth->logout());
                }
            } else {
                // save user id and user data in session
                //$this->Session->write('uid', $user['User']['id']);


                // handle cookies
                if ($remember) {
                    $this->Cookie->write('email', $email, true, 60 * 60 * 24 * 30);
                    $this->Cookie->write('password', $password, true, 60 * 60 * 24 * 30);
                }

                //renew allow cookie
                $accepted_cookie = $this->Cookie->read('accepted_cookie');
                if($accepted_cookie)
                    $this->Cookie->write('accepted_cookie',1,true,60*60*24*30);

                // update last login
                //$this->User->id = $user['User']['id'];
               // $this->User->save(array('last_login' => date("Y-m-d H:i:s")));
                return $user['User']['id'];
            }
        } else {
            return false;
        }
    }
    
    protected function logout() {
        // delete session from database
        $current_session = $this->Session->id();
        $this->loadModel('CakeSession');
        $this->CakeSession->delete($current_session);

        // Process provider logout
        $this->Social = $this->Components->load('SocialIntegration.Social');
        if ($this->Session->read('provider')) {
            $this->Social->socialLogout($this->Session->read('provider'));
            SocialIntegration_Auth::storage()->set("hauth_session.{$this->Session->read('provider')}.is_logged_in", 0);
            $this->Session->delete('provider');
        }

        // clean the sessions
        $this->Session->delete('uid');
        $this->Session->delete('admin_login');
        $this->Session->delete('Message.confirm_remind');

        // delete cookies
        $this->Cookie->delete('email');
        $this->Cookie->delete('password');
        $cakeEvent = new CakeEvent('Controller.User.afterLogout', $this);
        $this->getEventManager()->dispatch($cakeEvent);
    }

    private function _runCron()
    {


    }

    /**
     * System wide send email method
     * @param string $to - recipient's email address
     * @param string $subject
     * @param string $template - email template to use
     * @param array $vars - array of vars to set in email
     * @param string $from_email - sender's email address
     */
    protected function _sendEmail($to, $subject, $template, $vars, $from_email = '', $from_name = '', $body = '')
    {
        App::uses('CakeEmail', 'Network/Email');

        $vars['request'] = $this->request;

        if (empty($from_email)) {
            $from_email = Configure::read('core.site_email');
        }

        if (empty($from_name)) {
            $from_name = Configure::read('core.site_name');
        }

        $email = new CakeEmail();
        $email->from($from_email, $from_name)
            ->to($to)
            ->subject($subject)
            ->template($template)
            ->viewVars($vars)
            ->helpers(array('Moo'))
            ->emailFormat('html')
            ->transport(Configure::read('core.mail_transport'));

        if (Configure::read('core.mail_transport') == 'Smtp') {
            $config = array('host' => Configure::read('core.smtp_host'), 'timeout' => 30);
            $smtp_username = Configure::read('core.smtp_username');
            $smtp_password = Configure::read('core.smtp_password');
            $smtp_port = Configure::read('core.smtp_port');
            if (!empty($smtp_username) && !empty($smtp_password)) {
                $config['username'] = $smtp_username;
                $config['password'] = $smtp_password;
            }

            if (!empty($smtp_port)) {
                $config['port'] = $smtp_port;
            }

            $email->config($config);
        }
        try {
            $email->send($body);
        } catch (Exception $ex) {
            $ret_msg = $ex->getMessage();
            $this->log($ex->getLine(), 'emailError');
        }

    }

    private function _getLocales($lang)
    {
    	//hack ces cze
    	if (in_array($lang, array('ces','cze')))
    	{
    		return array(
    				'cs_CZ.UTF8',
    				// fr_FR.UTF8
    				'cs_CZ',
    				// fr_FR
    				$lang,
    				// fre
    				$lang,
    				// fre
    				'cs'
    				// fr
    		);;
    	}
    	
        // Loading the L10n object
        App::uses('L10n', 'I18n');
        $l10n = new L10n();

        // Iso2 lang code
        $iso2 = $l10n->map($lang);
        $catalog = $l10n->catalog($lang);

        $locales = array(
            $iso2 . '_' . strtoupper($iso2) . '.' . strtoupper(str_replace('-', '', $catalog['charset'])),
            // fr_FR.UTF8
            $iso2 . '_' . strtoupper($iso2),
            // fr_FR
            $catalog['locale'],
            // fre
            $catalog['localeFallback'],
            // fre
            $iso2
            // fr
        );
        return $locales;
    }

    public function currentUri()
    {
        $uri = empty($this->params['controller']) ? "" : $this->params['controller'];
        $uri .= empty($this->params['action']) ? "" : "." . $this->params['action'];

        if ($uri == 'pages.display') {
            $uri .= empty($this->params['pass'][0]) ? "" : "." . $this->params['pass'][0];
        }
        return $uri;
    }

    public function doLoadingBlocks($uri)
    {
        if ($this->layout != '' && $this->autoRender != false && !$this->request->is('post') && !$this->request->is('requested')) {


            $blocks = Cache::read("$uri.blocks".Configure::read('Config.language'),"1_day");
            if (!$blocks) {
                $this->loadModel('Page.Page');
                $row = $this->Page->find('first', array(
                    'conditions' => array('Page.uri' => $uri),
                    'recursive' => 2
                ));
                Cache::write("$uri.blocks".Configure::read('Config.language'), $row,"1_day");
                $blocks = $row;
            }
            $this->loadModel('CoreContent');
            $this->loadModel('CoreBlock');

            $rowHeader = Cache::read('rowHeader',"1_day");

            if (!$rowHeader) {
                $rowHeader = $this->CoreContent->getCoreContentByPageName('header');
                if (!$rowHeader) {
                    $rowHeader = array(0);
                }
                Cache::write('rowHeader', $rowHeader,"1_day");
            }

            $rowFooter = Cache::read('rowFooter',"1_day");

            if (!$rowFooter) {
                $rowFooter = $this->CoreContent->getCoreContentByPageName('footer');
                if (!$rowFooter) {
                    $rowFooter = array(0);
                }
                Cache::write('rowFooter', $rowFooter,"1_day");
            }
            if (count($blocks) > 0) {
                $rowPageDescription = $blocks['Page']['description'];
                $rowPageKeyword = $blocks['Page']['keywords'];
                $rowPageTitle = $blocks['Page']['title'];

                $this->set('mooPageDescription', $rowPageDescription);
                $this->set('mooPageKeyword', $rowPageKeyword);
                $this->set('mooPageTitle', $rowPageTitle);
                if(!$this->isApp())
                {
                    foreach ($blocks['CoreContent'] as $block) {
                        if ((isset($block['type'])) && $block['type'] == 'widget') {
                            if ($block['name'] != 'invisiblecontent') {
                                if ($block['role_access'] && trim($block['role_access']) != 'all')
                                {
                                    $role_access = explode(',',$block['role_access']);
                                    if (!in_array($this->_getUserRoleId(),$role_access))
                                        continue;

                                }

                                $widget = str_replace('.', DS, $block['name']);
                                $params = json_decode($block['params'], true);
                                $params['content_id'] = $block['id'];
                                $oWidget = false;
                                if ($block['plugin']) {
                                    $oWidget = $this->Widgets->load($block['plugin'] . '.' . $widget,
                                        array('params' => $params));
                                } else {
                                    $oWidget = $this->Widgets->load($widget, array('params' => $params));
                                }

                            }
                        }

                    }
                }
            }
            
            // hacking for footer MOOSOCIAL-2793
            if (count($rowFooter) > 0) {
                if (isset($rowFooter[0]['Children'])){
                   foreach ($rowFooter[0]['Children'] as $block) {
                        if ((isset($block['type'])) && $block['type'] == 'widget') {
                            if ($block['name'] != 'invisiblecontent') {

                                $widget = str_replace('.', DS, $block['name']);
                                $params = json_decode($block['params'], true);
                                $params['content_id'] = $block['id'];

                                $oWidget = false;
                                if ($block['plugin']) {
                                    $oWidget = $this->Widgets->load($block['plugin'] . '.' . $widget,
                                        array('params' => $params));
                                } else {
                                    $oWidget = $this->Widgets->load($widget, array('params' => $params));
                                }

                            }
                        }

                    } 
                }
            }
            
            // hacking for footer MOOSOCIAL-2793
            if (count($rowHeader) > 0) {
                if (isset($rowHeader[0]['Children'])){
                    foreach ($rowHeader[0]['Children'] as $block) {
                        if ((isset($block['type'])) && $block['type'] == 'widget') {
                            if ($block['name'] != 'invisiblecontent') {

                                $widget = str_replace('.', DS, $block['name']);
                                $params = json_decode($block['params'], true);
                                $params['content_id'] = $block['id'];

                                $oWidget = false;
                                if ($block['plugin']) {
                                    $oWidget = $this->Widgets->load($block['plugin'] . '.' . $widget,
                                        array('params' => $params));
                                } else {
                                    $oWidget = $this->Widgets->load($widget, array('params' => $params));
                                }

                            }
                        }

                    }
                }
            }
            
            $this->set('mooPage', $blocks);
            $this->set('mooHeader', $rowHeader);
            $this->set('mooFooter', $rowFooter);
        }
    }

    public function render($view = null, $layout = null)
    {
        $this->response = parent::render($view, $layout);
        $event = new CakeEvent('Controller.afterRender', $this);
        $this->getEventManager()->dispatch($event);
        return $this->response;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['MooView.beforeRender'] = 'beforeMooViewRender';

        return $events;
    }

    public function beforeMooViewRender()
    {
    }

    public function setNgController($event)
    {
        $v = $event->subject();
        try {
            if ($v instanceof MooView) {
                $v->setNgController();
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            die();
        }
    }

    public function get($name, $value)
    {
        return ((!empty($this->request->named[$name])) ? $this->request->named[$name] : $value);
    }

    public function getPluginModel($plugin, $model)
    {
        App::import('Model', $plugin . '.' . $model);
        return $objModel = new $model();
    }

    public function isModelInPlugin($model)
    {
        if (in_array($model, array('Blog', 'Video', 'Page', 'Photo', 'Event'))) {
            return true;
        }
        return false;
    }

    public function getEventManager()
    {
        if (empty($this->_eventManager)) {
            $this->_eventManager = new CakeEventManager();
            $this->_eventManager->attach($this->Components);
            $this->_eventManager->attach($this->Widgets);
            $this->_eventManager->attach($this);
        }
        return $this->_eventManager;
    }


    public function beforeRender()
    {
        $ids = MooPeople::getInstance()->get();
        MooPeople::getInstance()->setStatus('onBeforeRender');
        if (!empty($ids)) {
            $this->loadModel('User');
            $users = $this->User->find('all', array('conditions' => array("User.id" => $ids)));
            MooPeople::getInstance()->add(Hash::combine($users, '{n}.User.id', '{n}'));
        }
        MooPeople::getInstance()->setStatus('onRender');
    }

    public function getGuest()
    {
        return array(
            'id' => '0',
            'name' => 'Guest',
            'email' => 'guest@local.com',
            'role_id' => '3',
            'avatar' => '',
            'photo' => '',
            'created' => '2014-12-16 09:19:31',
            'last_login' => '0000-00-00 00:00:00',
            'photo_count' => '0',
            'friend_count' => '0',
            'notification_count' => '0',
            'friend_request_count' => '0',
            'blog_count' => '0',
            'topic_count' => '0',
            'conversation_user_count' => '0',
            'video_count' => '0',
            'gender' => 'Male',
            'birthday' => '2014-12-16',
            'active' => true,
            'confirmed' => true,
            'code' => '2bfd6099852afc1b09d86c27eb3c136a',
            'notification_email' => true,
            'timezone' => 'Africa/Abidjan',
            'ip_address' => '',
            'privacy' => '1',
            'username' => '',
            'about' => '',
            'featured' => false,
            'lang' => '',
            'hide_online' => false,
            'cover' => '',
            'Role' =>
                array(
                    'id' => '3',
                    'name' => 'Guest',
                    'is_admin' => false,
                    'is_super' => false,
                    'params' => 'global_search,user_username,blog_view,blog_create,album_create,album_view,event_create,event_view,group_create,group_view,group_delete,photo_upload,photo_view,topic_create,topic_view,video_share,video_view,attachment_upload,attachment_download',
                    'core' => true,
                ),
        );
    }

    private function loadingApplicationSettings()
    {
		$appAccessToken = "";
        // Todo Refactor
        if (file_exists(APP . 'Config/config.php')) {
            //load unboot settings
            $this->loadUnBootSetting();

            //load component resgister
            $this->loadComponent();

			if (!empty($this->request->query['access_token'])) {
				$appAccessToken = $this->request->query['access_token'];
			}
			else if (!empty($this->request->data['access_token'])) {
				$appAccessToken = $this->request->data['access_token'];
			}

            if ((!empty($this->request->query['access_token']) || !empty($this->request->data['access_token']) || $this->request->header('moo-access-token') !== false)) {

                $this->OAuth2 = $this->Components->load('OAuth2');

                if ($this->OAuth2->verifyResourceRequest()) {
                    $this->Auth->login($this->OAuth2->getOwnerResourceRequest(false));
                    $this->set('accessTokenData',$this->OAuth2->getAccessTokenData());
                    $this->Session->write('accessTokenData',$this->OAuth2->getAccessTokenData());
                }
            }
            $this->getEventManager()->dispatch(new CakeEvent('AppController.doBeforeFilter', $this));
        }

        // check for config file
        if (!file_exists(APP . 'Config/config.php')) {
            $this->redirect('/install');
            exit;
        }

        $this->Cookie->name = md5($this->request->base).'mooSocial';
        $this->Cookie->key = Configure::read('Security.salt');
        $this->Cookie->time = 60 * 60 * 24 * 30;

        // return url
        if (!empty($this->request->named['return_url'])) {
            $this->set('return_url', $this->request->named['return_url']);
        }

        $this->set('isMobile',MooCore::getInstance()->isMobile(null));
		$this->set('appAccessToken', $appAccessToken);
    }

    private function identifyingViewer()
    {
        // Todo Refactor

        $uid = $this->Auth->user('id');
        // auto login
        if (empty($uid) && $this->Cookie->read('email') && $this->Cookie->read('password')) {
            $uid = $this->_logMeIn($this->Cookie->read('email'), $this->Cookie->read('password'));
            if ($uid)
            {
            	$user = $this->User->findById($uid);		        
		        $cuser = $user['User'];
		        $cuser['Role'] = $user['Role'];
		        unset($cuser['password']);
		        $this->Auth->login($cuser);
		        if (Configure::read("core.link_after_login") && !$this->isApp() && !$this->request->is('api') && !$this->request->is('ajax') && !$this->request->is('post') && $this->request->params['controller'] == 'home' && $this->request->params['action'] == 'index')
		        {
		        	return $this->redirect('/'.Configure::read("core.link_after_login"));
		        }
            }
        }
        $accepted_cookie = isset($_COOKIE['accepted_cookie']) ? $_COOKIE['accepted_cookie'] : null;
        $this->set('accepted_cookie', $accepted_cookie);
    }

    private function loadingViewerSetting()
    {
        // Todo Refactor
    }

    private function doBanUsersProcess()
    {
        // Todo Refactor
        // ban ip addresses
        $ban_ips = Configure::read('core.ban_ips');
        if (!empty($ban_ips)) {
            $ips = explode("\n", $ban_ips);
            foreach ($ips as $ip) {
            	$tmp = trim($ip);
            	if (!empty($tmp) && strpos($_SERVER['REMOTE_ADDR'], $tmp) === 0) {
            		if (!$this->request->is('api'))
            		{            			
	                    $this->autoRender = false;
	                    echo __('You are not allowed to view this site');
	                    exit;
            		}
            		else 
            		{
            			throw new ApiUnauthorizedException(__('You are not allowed to view this site'));
            		}
                }
            }
        }
    }
    
    // check if $email is banned by system
    // @return : true or false
    protected function isBanned($email = null){
        
        if (empty($email)){
            return false;
        }
        
        $ban_emails = Configure::read('core.ban_emails');
        $emails = explode( "\n", $ban_emails );
        
        if (empty($ban_emails)){
            return false;
        }
        
        foreach ($emails as $item){
            if (trim($email) == trim($item)){
                return true;
            }else{
                $list1 = explode("@*", $item); //   abc@*
                $list2 = explode("*@", $item); //   *@abc.com
                $list3 = explode("@", $email);
                
                // case 1
                if (isset($list1[0]) && isset($list3[0])){
                   if (trim($list1[0]) == trim($list3[0])){ // compared name
                       return true;
                   } 
                }
                
                // case 2
                if (isset($list2[1]) && isset($list3[1])){
                    
                    if (trim($list2[1]) == trim($list3[1])){ // compared domain
                       return true;
                   }
                }
            }
        }
        
        return false;
    }

    private function doThemeProcess()
    {
        // Todo Refactor
        // get langs
        $this->loadModel('Language');
        $site_langs = $this->Language->getLanguages();

        // select lang
        if ($this->Cookie->check('language') && array_key_exists($this->Cookie->read('language'), $site_langs)) {
            $language = $this->Cookie->read('language');
        }
		
		if (isset($this->request->query['language']) && $this->request->query['language'])
		{
			$language = $this->request->query['language'];
			$this->Cookie->write('language', $language);
		}
		
		if (isset($this->request->data['language']) && $this->request->data['language'] && is_string($this->request->data['language']) && array_key_exists($this->request->data['language'], $site_langs))
		{
			$language = $this->request->data['language'];
		}

        if (empty($language)) {
            $language = Configure::read('core.default_language');
        }
        //get rtl setting
        $site_rtl = '';
        $language_rtl = $this->Language->getRtlOption();

        if (!empty($language_rtl)) {
            foreach ($language_rtl as $rtl) {
                if ($rtl['Language']['key'] == $language) {
                    $site_rtl = $rtl['Language']['rtl'];
                }
            }
        }

        Configure::write('Config.language', $language);

        MooCore::getInstance()->getModel("ProfileType")->setLanguage($language);

        // set locale
        $locales = $this->_getLocales($language);
        setlocale(LC_ALL, $locales);

        $uid = $this->Auth->user('id');

        // themes
        $this->loadModel('Theme');
        $site_themes = $this->Theme->getThemes();

        // select theme
        //none-login user
        if (Configure::read('core.select_theme') && empty($uid)) {
            if (!$this->Session->read('non_login_user_default_theme')) {
                $this->Session->write('non_login_user_default_theme', Configure::read('core.default_theme'));
            }
            if ($this->Session->read('non_login_user_theme') && array_key_exists($this->Session->read('non_login_user_theme'),
                    $site_themes) && $this->Session->read('non_login_user_default_theme') == Configure::read('core.default_theme')
            ) {
                $this->theme = $this->Session->read('non_login_user_theme');

            }
        }

        if (Configure::read('core.select_theme') && !empty($uid)) {

            if ($this->Cookie->check('theme') && array_key_exists($this->Cookie->read('theme'), $site_themes)) {
                $this->theme = $this->Cookie->read('theme');
            }
        }

        if (empty($this->theme)) {
            $this->theme = Configure::read('core.default_theme');
            if (!empty($uid) && !($this->Cookie->check('theme') && array_key_exists($this->Cookie->read('theme'),
                        $site_themes))
            ) {
                $this->Cookie->write('theme', Configure::read('core.default_theme'));
            }
        }

        if (empty($this->theme)) {
            $this->theme = 'default';
        }
        
        // site is offline?
        $site_offline = Configure::read('core.site_offline');
        $cuser = $this->_getUser();
        $array_action_pass_offline = array('users_recover','users_login','users_resetpass');
        $action = $this->request->params['controller'] . '_' . $this->request->params['action'];
        if (!empty($site_offline) && !in_array($action, $array_action_pass_offline) && empty($cuser['Role']['is_super'])) {
            $this->layout = '';
            if ($this->isApp())  $this->theme = "mooApp";
            $this->set('offline_message', Configure::read('core.offline_message'));
            $this->render('/Elements/misc/offline');
            return;
        }

        // detect ajax request
        if ($this->request->is('ajax')) {
            $this->layout = '';
        }

        if (strpos($this->request->action, 'do_') !== false) {
            $this->autoRender = false;
        }
        $this->getEventManager()->dispatch(new CakeEvent('AppController.doSetTheme', $this));

        if (isset($this->request->params['admin'])) // admin area
        {
            // v3.0.0 - Theme engine upgrade
            $this->theme = 'adm';
            $this->_checkPermission(array('admin' => true));

            if ($this->request->action != 'admin_login' && !$this->Session->read('admin_login')) {
                $this->redirect('/admin/home/login');
                exit;
            }

            if ($this->Session->read('admin_login')) {
                $this->Session->write('admin_login', 1);
            }
        }
        // hooks - refactor
        // just loading content only for determine page
        // Using $this->currentUri()
        //       $this->doLoadingComponent(uri);
        $this->doLoadingBlocks($this->currentUri());
        $this->set('site_themes', $site_themes);
        $this->set('site_langs', $site_langs);
        $this->set('site_rtl', $site_rtl);
        $this->set('current_theme',$this->theme);
    }

    private function doViewerProcess()
    {
        // Todo Refactor
        $uid = $this->Auth->user('id');
        // get current user

        $cuser = $this->_getUser();
        // Set guest user
        if (empty($cuser)) {

        }
        $event = new CakeEvent('AppController.doViewerProcess', $this,array('cuser' => $cuser));
        $this->getEventManager()->dispatch($event);
        if(!empty($event->result['cuser'])){
            $cuser = $event->result['cuser'];
        }
        $this->set('cuser', $cuser);


        // set lang to user's chosen lang
        if ($this->request->is('androidApp') || $this->request->is('api') || $this->request->is('iosApp')) {
		        
		}
		else
		{
			if (!empty($cuser['lang'])) {
				Configure::write('Config.language', $cuser['lang']);
			}
		}
        // force login
        if (empty($uid) && $this->check_force_login && ($this->request->here != $this->request->webroot)
            && Configure::read('core.force_login')
            && !in_array($this->request->controller, array('pages', 'home'))
            && !in_array($this->request->action, array(
                'preview',
                'member_verify',
                'ajax_browse',                
                'signup_step2',
                'register',
                'endpoint',
                'login',
                'member_login',
                'avatar_tmp',
                'do_logout',
                'ajax_signup_step1',
                'ajax_signup_step2',
                'fb_register',
                'do_fb_register',
                'recover',
                'resetpass',
                'do_confirm',
            	'accept_cookie'
            ))
        ) {
            $this->redirect('/users/member_login?redirect_url='.base64_encode(Router::url(null, true)));
            exit;
        }

        if (empty($uid) && Configure::read('core.force_login')) {
            $this->set('no_right_column', true);
        }

        // remind email validation
        if (!empty($cuser) && !$cuser['confirmed'] && Configure::read('core.email_validation')) {
            $this->Session->setFlash(__('An email has been sent to your email address. Please click the validation link to confirm your email<br /><br /><a class="btn btn-action" href="javascript:void(0);" id="resend_validation_link">Resend validation link</a>'),
                'default', array('class' => 'Metronic-alerts alert alert-success fade in'), 'confirm_remind');
        }

        //remind pending status
        if (!empty($cuser) && !$cuser['approved'] && Configure::read('core.approve_users')) {
            $this->Session->setFlash(__('Your account is pending approval.'),
                'default', array('class' => 'Metronic-alerts alert alert-success fade in'), 'confirm_remind');
        }

        $role_id = $this->_getUserRoleId();
        // site timezone
        $utz = (!is_numeric(Configure::read('core.timezone'))) ? Configure::read('core.timezone') : 'UTC';

        // user timezone
        if (!empty($cuser['timezone'])) {
            $utz = $cuser['timezone'];
        }
        // set viewer
        if ($uid) {
            $this->loadModel('User');
            $user = $this->User->findById($uid);
            MooCore::getInstance()->setViewer($user);
        }

        //hide dislike or not
        $hide_dislike = Configure::read('core.hide_dislike');
        //reaction
        $hide_like = 0;

        //set redirect url
        if (!$uid) {
            $redirect_url = '';
            if (!$this->request->is('post')) {
                if (isset($this->request->query['redirect_url'])) {
                    $redirect_url = $this->request->query['redirect_url'];
                } else {
                    $url = ($this->request->params['plugin'] ? $this->request->params['plugin'] . '_' : '');
                    $url .= $this->request->params['controller'] . '_' . $this->request->params['action'];
                    $array = array('users_member_login','users_register');
                    if (!in_array($url,$array)) {
                        $redirect_url = base64_encode(Router::url(null, true));
                    }
                }
            }
            $this->set('redirect_url',$redirect_url);
        }

        $this->set('role_id', $role_id);
        $this->set('uid', $uid);
        $this->set('uacos', $this->_getUserRoleParams());
        $this->set('utz', $utz);
        $this->set('hide_dislike', $hide_dislike);
        $this->set('hide_like', $hide_like);
    }
    public function afterFilter(){
        // Hacking for thrown exceptions in session::destory problem
        if ($this->request->is('api') ) {
            $this->Session->destroy();
        }
    }
    
    protected function _sendNotificationToMentionUser($content,$url,$action,$editUsers = array(), $parent_id = null)
    {
        //notification for user mention
        $sended = array();
        $this->loadModel("User");
        $uid = $this->Auth->user('id');
        preg_match_all(REGEX_MENTION,$content,$matches);
        $blocks = array();        
        if ($parent_id)
        {
	        if (!is_array($parent_id))
	        {
	        	$parent_id = array($parent_id);
	        }
        	$this->loadModel('UserBlock');
        	foreach ($parent_id as $id)
        	{
        		if ($id)
        			$blocks = array_merge($blocks,$this->UserBlock->getBlockedUsers($id));
        	}
        	
        }
        
        if(!empty($matches)){
            foreach($matches[0] as $key => $value){
                $this->loadModel('Notification');
                if(!empty($editUsers) && !in_array($matches[1][$key],$editUsers)){
                    continue;
                }
                if (in_array($matches[1][$key], $blocks))
                {
                	continue;
                }
                if($matches[1][$key] != $uid){
                    if ($this->User->checkSettingNotification($matches[1][$key],'mention_user')) {
                        $sended[] = $matches[1][$key];
                        $this->Notification->record(array('recipients' => $matches[1][$key],
                            'sender_id' => $uid,
                            'action' => $action,
                            'url' => $url
                        ));
                    }
                }
            }
        }
        return $sended;
    }
    
    protected function _getUserIdInMention($content){
        preg_match_all(REGEX_MENTION,$content,$matches);
        if(!empty($matches)){
            return $matches[1];
        }else
            return false;
    }

    public function getBlockedUsers($uid = null){
        $this->loadModel('UserBlock');
        $blockedUsers = $this->UserBlock->getBlockedUsers($uid);
        return $blockedUsers;
    }
    
    protected function _rotateImage(&$photo, $path)
    {
    	// rotate image if necessary
    	$exif = @exif_read_data($path);
    	
    	if (!empty($exif['Orientation']))
    		switch ($exif['Orientation'])
    		{
    			case 8:
    				$photo->rotateImageNDegrees(90)->save($path);
    				break;
    			case 3:
    				$photo->rotateImageNDegrees(180)->save($path);
    				break;
    			case 6:
    				$photo->rotateImageNDegrees(-90)->save($path);
    				break;
    	}
    }
    
    public function _getExtension($filename = null)
    {
    	$tmp = explode('.', $filename);
    	$re = array_pop($tmp);
    	return $re;
    }
    
    public function getDescriptionForMeta($description)
    {
    	$description = strip_tags($description);
    	$description = str_replace('"', '', $description);
    	$description = mb_ereg_replace("/&#?[a-z0-9]+;/i","",$description);
    	$description = trim(mb_ereg_replace('/\s\s+/', ' ', $description));
    	$helper = MooCore::getInstance()->getHelper('Core_Moo');
    	return $helper->convertDescriptionMeta(trim(CakeText::truncate($description,2000, array('ellipsis' => '', 'html' => false, 'exact' => false))));
    }
    
    public function getKeywordsForMeta($description)
    {    	
    	$tmp = explode(' ', trim($description));
        return implode(',', $tmp);
    }
    /* Api Plugin 
       Throw error code  */
    public function throwErrorCodeException($errorCodeText) {
        $this->request->data('apiErrorCodeText', $errorCodeText);
    }
//    protected function _uploadThumbnail() { 
//        // save this picture to album
//        $path = 'uploads' . DS . 'tmp';
//        $url = 'uploads/tmp/';
//
//        $this->_prepareDir($path);
//
//        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();
//
//        $maxFileSize = MooCore::getInstance()->_getMaxFileSize();
//
//        App::import('Vendor', 'qqFileUploader');
//        $uploader = new qqFileUploader($allowedExtensions, $maxFileSize);
//
//        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
//        $result = $uploader->handleUpload($path);
//
//        if (!empty($result['success'])) {
//            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
//
//            $result['thumb'] = FULL_BASE_URL . $this->request->webroot . $url . $result['filename'];
//            $result['file'] = $path . DS . $result['filename'];
//        }
//        // to pass data through iframe you will need to encode all html tags
//        return $result;
//    }

    /* Throw Exception error when extend from controller.*/
    protected function _throwException($respond) {
        switch ($respond['code']){
            case 400 :
                throw new ApiBadRequestException($respond['message']);
                break;
            case 401 :
                throw new ApiUnauthorizedException($respond['message']);
                break;
            case 404 :
                throw new ApiNotFoundException($respond['message']);
                break;
            case 405 :
                throw new MethodNotAllowedException($respond['message']);
                break;
            default:
                break;
        }
    }
    /* Get core type of object before action */
    protected function _getType($objectType) {
        switch ($objectType) {
            case 'Activity_Link' :
            case 'Activity' :
                $type = 'activity';
                break;
            case 'activity_comment' :
                $type = 'core_activity_comment';
                break;
            case 'blog' :
                $type = 'Blog_Blog';
                break;
            case 'album' :
                $type = 'Photo_Album';
                break;
            case 'photo' :
                $type = 'Photo_Photo';
                break;
            case 'video' :
                $type = 'Video_Video';
                break;
            case 'topic' :
                $type = 'Topic_Topic';
                break;
            case 'conversation' :
                $type = APP_CONVERSATION;
                break;
            case 'user' :
                $type = 'user';
                break;
            case 'group' :
                $type = 'Group_Group';
                break;
            case 'event' :
                $type = 'Event_Event';
                break;
            default:
                $type = $objectType;
        }
        return $type;
    }
    /* END Api Plugin */
}
