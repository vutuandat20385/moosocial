<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Sanitize', 'Utility');
class UpgradeController extends Controller {
    public $components = array(
        'Auth' => array(
            'loginRedirect' => array(
                'controller' => 'upgrade',
                'action' => 'index'
            ),
            'loginAction' => array(
                'controller' => 'upgrade',
                'action' => 'login',

            ),
            'authenticate' => array(
                'Form' => array(
                    'fields' => array('username' => 'email'),
                    'contain' => 'Role',
                    'passwordHasher' => array(
                        'className' => 'Moo',
                    ),
					'userModel' => 'UserUpgrade'
                )
            ),
            'authorize' => array(
                'Actions' => array('actionPath' => 'controllers')
            )
        ),
        'Cookie',
        'Session',
        'RequestHandler'
    );
    public $viewClass = '';
    public $helpers = array('Html', 'Text', 'Form', 'Session', 'Time');
    public $settings = null;
    public function beforeFilter()
    {
        $this->Auth->allow();

        Configure::write('debug', 1);
        Configure::write('Cache.disable', true);
        Cache::clear();
        $this->theme = 'install';
        
        // get the current logged in user, temporary
        $uid = $this->Auth->user('id');

        if ( $uid != ROOT_ADMIN_ID ){
            if(strpos($this->here,'upgrade/login') === false){
                $this->redirect('/upgrade/login');
               
            }
        }
    }

    public function _getSettings()
    {
        $current_settings = $this->Session->read('current_upgrade_settings');
        if ($current_settings == null)
        {
            $this->loadModel('Setting');
            $this->Setting->cacheQueries = true;
            $settings = $this->Setting->find('list', array( 'fields' => array('field', 'value') ) );
            $current_settings = json_encode($settings);
            $this->Session->write('current_upgrade_settings', $current_settings);
        }

        return json_decode($current_settings, true);
    }

    public function _getCurrentVersion(){
        if ($this->current_version == null)
        {
            $this->loadModel('Setting');
            $this->Setting->cacheQueries = true;
            $settings = $this->Setting->find('list', array( 'fields' => array('field', 'value') ) );
            $this->current_version = !empty($settings['version']) ? $settings['version'] : '';
        }

        return $this->current_version;
    }
    
    public function _getMenus(){
        $current_menus = $this->Session->read('current_upgrade_menus');
        if ($current_menus == null)
        {
            $this->loadModel('Plugin');
            $this->Plugin->cacheQueries = true;
            $menus = $this->Plugin->find('all');
            $current_menus = json_encode($menus);
            $this->Session->write('current_upgrade_menus', $current_menus);
        }

        return json_decode($current_menus, true);
    }

    protected function _logMeIn( $email, $password, $remember = false )
    {
        if ( !is_string( $email ) || !is_string( $password ) )
            return false;

        $this->loadModel('User');
        $this->User->unbindModel(array('belongsTo'=>'ProfileType'));

        // find the user
        $user = $this->User->find( 'first', array( 'conditions' => array( 'email' => trim( $email ) ) ) );

        if (!empty($user)) // found
        {
            $passwordHasher = new MooPasswordHasher();
            $salt = '';
            if (isset($user['User']['salt']))
            	$salt = $user['User']['salt'];
            	
            if ($user['User']['password'] != $passwordHasher->hash($password,$salt)) // wrong password
                return false;

            if ( !$user['User']['active'] )
            {
                $this->Session->setFlash( __('This account has been disabled'), 'default', array( 'class' => 'error-message') );
                return false;
            }
            else
            {
                // update last login
                //$this->User->id = $user['User']['id'];
                //$this->User->save( array( 'last_login' => date("Y-m-d H:i:s") ) );

                return $user['User']['id'];
            }
        }
        else
            return false;
    }

    public function index( $state = null )
    {
        $content = file_get_contents( APP . 'Config' . DS . 'install' . DS . 'upgrade.xml' );
        $xml = new SimpleXMLElement($content);
        $setting = $this->_getSettings();
        
        $checkPermissionGarbageFiles = $this->checkPermissionGarbageFiles();
        $garbageFilesDirectory = ROOT . DS . 'app' . DS . 'webroot' . DS . 'theme' . DS . 'default';
        
        
        $checkPermissionPluginsXml = $this->checkPermissionPluginsXml();
        $checkPermissionSettings = $this->checkPermissionSettings();
        
        
        $permissionPluginsXmlFile = ROOT . DS . 'app' . DS . 'Config' . DS . 'plugins' . DS . 'plugins.xml';
        $permissionSettingsFile = ROOT . DS . 'app' . DS . 'Config' . DS . 'settings.php';
        
        $this->set(compact('checkPermissionGarbageFiles', 'garbageFilesDirectory', 'checkPermissionPluginsXml', 
                'checkPermissionSettings', 'permissionPluginsXmlFile', 'permissionSettingsFile'));
        
        $this->set('current_version',$setting['version']);
        $this->set('latest_version', $xml->version[count($xml->version) - 1]->number);

        if ( $state == 'done' ){
        	$this->loadModel('Setting');
        	$this->Setting->save_boot_settings();
        	Cache::clear();
            $this->render('done');
            $this->Session->delete('uid');
        }
        elseif($state == 'fail')
            $this->render('fail');
    }

    public function run()
    {
        $callback = $this->Session->read('current_upgrate_callback');
        if(!empty($callback)){
            $this->Session->delete('current_upgrade_settings');
            $this->Session->delete('current_upgrate_callback');
            $this->{$callback}();
        }

        // backup setting
        $this->_getSettings();

        // get current version upgraded
        $current_version = $this->_getCurrentVersion();

        $this->_getMenus();
        $this->loadModel('Setting');

        $content = file_get_contents( APP . 'Config' . DS . 'install' . DS . 'upgrade.xml' );
        $xml = new SimpleXMLElement($content);

        $latest_version = $xml->version[count($xml->version) - 1]->number;

        $base_version = $this->Session->read('base_version');
        if(empty($base_version))
        {
            $this->Session->write('base_version',$current_version);
            $base_version = $current_version;
        }

        if ( $latest_version == $current_version )
        {
            $this->Session->delete('current_upgrate_callback');
            $this->Session->delete('current_upgrade_settings');
            $this->Session->delete('base_version');
            $this->Session->delete('current_step');
            $this->Session->delete('total_step');
            $this->redirect('/upgrade/index/done');
            exit;
        }

        $i = $j = 1;

        foreach ( $xml->version as $key => $version )
        {
            if($version->number == $base_version)
            {
                $total_step = $xml->count() - $i;
                $this->Session->write('total_step',$total_step);
            }
            $i++;

            if ( $version->number > $current_version )
            {
                $current_step = $this->Session->read('current_step');
                if(empty($current_step))
                {
                    $current_step = $j;
                }
                $total_step = !empty($total_step) ? $total_step : $this->Session->read('total_step');
                if(intval($total_step)>0)
                    $percent_complete = (intval($current_step)/intval($total_step))*100;
                else
                    $percent_complete = 0;
                $this->Session->write('current_step',intval($current_step)+1);
                $this->set('percent_complete', $percent_complete);

                $this->set('version', $version->number);
                $this->set('latest_version', $latest_version);

                try{
                // run queries
                    if ( !empty( $version->queries->query ) )
                    {
                        foreach ( $version->queries->query as $query )
                        {
                            $query = str_replace('{PREFIX}', $this->Setting->tablePrefix, $query);
                            $this->Setting->query( $query );
                        }
                    }
                }
                catch(Exception $e)
                {
                    $this->redirect('/upgrade/index/fail');
                }
                // update version
                if ($version->number < "2.2.0"){
                    $this->Setting->query( "UPDATE " . $this->Setting->tablePrefix . "settings SET value = '" . $version->number . "' WHERE field = 'version'" );
                }
                else{
                    $this->Setting->query( "UPDATE " . $this->Setting->tablePrefix . "settings SET value = '" . $version->number . "', value_actual = '" . $version->number . "'  WHERE field = 'version'" );
                }
				$this->loadModel('Plugin');
				$this->Plugin->updateAll(
					array(
						'Plugin.version' =>  "'".$version->number."'"
					),
					array(
						'Plugin.core' => 1
					)
				);
				
                // clear cache folders
                $models_path = CACHE . DS . 'models';
                $files = scandir( $models_path );

                foreach ( $files as $file )
                    if ( $file != '.' && $file != '..' )
                        @unlink( $models_path . DS . $file );

                $persistent_path = CACHE . DS . 'persistent';
                $files = scandir( $persistent_path );

                foreach ( $files as $file )
                    if ( $file != '.' && $file != '..' ){
                        @chown( $persistent_path . DS . $file , 0755);
                        @unlink( $persistent_path . DS . $file );
                    }

                Cache::clear();
                $this->Setting->clear();
                $callback = "callback_".str_replace('.','_',$version->number);
                if(method_exists($this,$callback)){
                    $this->Session->write('current_upgrate_callback',$callback);
                    $this->redirect('/upgrade/run');
                    //$this->{$callback}();
                }
                return;
            }
        }

    }
    public function login(){
        if(!$this->request->is('post')){

        }elseif($this->request->is('post')){
            if ($this->Auth->login())
            {
                if (!$this->_logMeIn($this->request->data['UserUpgrade']['email'], $this->request->data['UserUpgrade']['password'])) {
                    $this->Session->setFlash(__('Invalid email or password'), 'default', array('class' => 'error-message'));
                    $this->redirect('/upgrade/login');
                } else {
                    $this->redirect('/upgrade');
                }
            }
        }
    }
    public function callback_2_1_3(){
        Cache::clear();
        $this->loadModel('Page.Page');

       
        $this->loadModel('CoreContentUpgrade');
        
        $this->Page->unbindModel(array(
            'belongsTo' => array('MyCoreTheme')
        ));
        
        $userPages = $this->Page->find('all',array(
            'conditions'=> array(
                'OR' => array(
                    'Page.type !=' => 'core',
                    'Page.type IS NULL'
                )
            )
        ));

        // blank page
        if(!empty($userPages)){
            foreach($userPages as $page){
                $checkPageContent = $this->CoreContentUpgrade->find('first',array(
                    'conditions' => array(
                        'CoreContentUpgrade.page_id' => $page['Page']['id'],
                        'CoreContentUpgrade.name' => 'invisiblecontent'
                    )
                ));
                if(empty($checkPageContent)){
                    $this->CoreContentUpgrade->create();
                    $parent = array('page_id'=>$page['Page']['id'],'type'=>'container','name'=>'center','parent_id'=>null);
                    $this->CoreContentUpgrade->save($parent);
                    $parent_id = $this->CoreContentUpgrade->getLastInsertID();

                    $child = array ('page_id'=>$page['Page']['id'],'type'=>'widget','name'=>'invisiblecontent','params'=>'{"title":"Page Content","maincontent":"1"}','core_block_id'=>0,'parent_id'=>$parent_id);
                    $this->CoreContentUpgrade->create();
                    $this->CoreContentUpgrade->save($child);
                }

            }
        }

        //fix uri
        if(!empty($userPages)){
            foreach($userPages as $page){
                //pages.test-page
                if(empty($page['Page']['uri'])){
                    $this->Page->id = $page['Page']['id'];
                    $this->Page->save(array(
                        'uri' => 'pages.'.$page['Page']['alias'],
                        'url' => '/pages/' . $page['Page']['alias']
                            ));
                }
            }

        }
    }

    public function callback_2_2_0()
    {
        Cache::clear();    
        //Activity feed
        $this->loadModel('Activity');
        $this->loadModel('ActivityFetchVideo');
        $videos = $this->ActivityFetchVideo->find('all');
        if (count($videos))
        {
            foreach ($videos as $video)
            {
                $params = array(
                    'source' => $video['ActivityFetchVideo']['source'],
                    'source_id' => $video['ActivityFetchVideo']['source_id'],
                    'title' => $video['ActivityFetchVideo']['title'],
                    'description' => $video['ActivityFetchVideo']['description'],
                    'thumb' => $video['ActivityFetchVideo']['thumb'],
                );
                $this->Activity->id = $video['ActivityFetchVideo']['activity_id'];
                $this->Activity->save(array('modified' => false, 'action'=>'video_activity', 'plugin'=>'Video','params' => json_encode($params)));
            }
        }
        $settings = $this->_getSettings();
        
        
        //Config mail setting
        $this->loadModel('Setting');
        $this->Setting->clear();
        $arra_next = array('date_format');
        foreach ($settings as $key => $value) {
            if (in_array($key, $arra_next)) {
                continue;
            }
            $this->Setting->unbindModel(
                array('belongsTo' => array('SettingGroup'))
            );
            
            if ($key == 'fb_app_id'){
                $this->Setting->updateAll(
                    array(
                        'Setting.value_actual' => "'" . $value . "'"
                    ), array(
                        'Setting.name = ?' => 'facebook_app_id'
                    )
                );
            }
            
            if ($key == 'fb_app_secret'){
                $this->Setting->updateAll(
                    array(
                        'Setting.value_actual' => "'" . $value . "'"
                    ), array(
                        'Setting.name = ?' => 'facebook_app_secret'
                    )
                );
            }

            $setting = $this->Setting->findByName($key);

            if ($setting) {

                if (in_array($setting['Setting']['type_id'], array('checkbox', 'radio', 'select'))) {

                    $array = json_decode($setting['Setting']['value_actual'], true);
                    switch ($setting['Setting']['type_id']) {

                        case 'checkbox':
                            $array[0]['select'] = $value;
                            break;
                        default:

                            foreach ($array as $i => $j) {
                                switch ($key) {
                                    case 'time_format':
                                        $value = str_replace('h', '', $value);
                                        $bool = $j['value'] == $value;
                                        break;

                                    default:
                                        $bool = $j['value'] == $value;
                                        break;
                                }

                                if ($bool) {
                                    $array[$i]['select'] = 1;
                                } else {
                                    $array[$i]['select'] = 0;
                                }
                            }
                            break;
                    }
                    $this->Setting->clear();
                    $this->Setting->updateAll(
                        array(
                            'Setting.value_actual' => "'" . json_encode($array) . "'"
                        ), array(
                            'Setting.name = ?' => $key
                        )
                    );

                } else {
                    $this->Setting->clear();
                    if($key != 'version')
                    {
                        $this->Setting->updateAll(
                            array(
                                'Setting.value_actual' => "'" . Sanitize::escape($value) . "'"
                            ), array(
                                'Setting.name = ?' => $key
                            )
                        );
                    }
                }
            }
        }
        $this->Setting->clear();
        $this->Setting->updateAll(array('Setting.value_actual'=>"'".$settings['site_name']."'"),array('Setting.name'=>'mail_name'));
        $this->Setting->clear();
        $this->Setting->updateAll(array('Setting.value_actual'=>"'".$settings['site_email']."'"),array('Setting.name'=>'mail_from'));

        if (isset($settings['mail_transport']) && $settings['mail_transport'] == 'Smtp')
        {
            $this->Setting->clear();
            $this->Setting->updateAll(array('Setting.value_actual'=>'\'[{"name":"Use the built-in mail() function","value":"0","select":1},{"name":"Send emails through an SMTP server","value":"1","select":1}]\''),array('Setting.name'=>'mail_smtp'));
            $this->Setting->clear();
            $this->Setting->updateAll(array('Setting.value_actual'=>"'".$settings['smtp_host']."'"),array('Setting.name'=>'mail_smtp_host'));
            $this->Setting->clear();
            $this->Setting->updateAll(array('Setting.value_actual'=>"'".$settings['smtp_username']."'"),array('Setting.name'=>'mail_smtp_username'));
            $this->Setting->clear();
            $this->Setting->updateAll(array('Setting.value_actual'=>"'".$settings['smtp_password']."'"),array('Setting.name'=>'mail_smtp_password'));
            $this->Setting->clear();
            $this->Setting->updateAll(array('Setting.value_actual'=>"'".$settings['smtp_port']."'"),array('Setting.name'=>'mail_smtp_port'));
        }
        
        // update menu setting
        $menus = $this->_getMenus();
        $this->Session->delete('current_upgrade_menus');
        foreach ($menus as $item){
            $this->loadModel('Menu.CoreMenuItem');
            if (!empty($item['Plugin']['url'])){
                $this->CoreMenuItem->clear();
                $this->CoreMenuItem->updateAll(array('CoreMenuItem.is_active' => $item['Plugin']['enabled'], 'CoreMenuItem.menu_order' => $item['Plugin']['weight'] + 1), array('CoreMenuItem.url' => $item['Plugin']['url']));
            }
        }
        
         // fixed Translation MOOSOCIAL-1491
        $this->loadModel('CoreContentUpgrade');
        $this->CoreContentUpgrade->fixTranslation();
        
        // fixed menu of custom pages
        $this->fixCustomPageMenu();

        //upgrade tag 2.2.0
        $this->tag();
        //upgrade category 2.2.0
        $this->category();
        
        //upgrade cronjob
        $this->loadModel('Cron.Task');
        $this->Task->updateAll(
		    array(		     
		      'completed_last' => time(),		      
		    ),
		    array(
		    	'class = "Task_Reminder_Notifications"' => '',
		   	)
		);
        
        // upgrade Event feed
        $this->upgradeEventFeed();
        
        // upgrade Group feed
        $this->upgradeGroupFeed();

        // upgrade photo 2.2.0
        $this->photo();    
    }
    
    public function callback_2_2_1() {
        // Fix number of friend on user menu
        $this->fixFriendNumber();
        
        // MOOSOCIAL-1916
        $this->deleteGarbageFiles();

        //install bright theme and dark theme
        $this->installThemes(array('bright','dark'));

        //change popular tag widget to tag widget
        $this->changePopularTagWidget();
    }
    
    public function callback_2_3_0(){
        $this->updatePluginXml();
        
        //add show member only option in who's online widget
        $this->showMemberOnly();

        //update counter cache core content
        $this->updateCounterCacheCoreContent();
        
        // update share action
        $this->updateShareAction();
        
        // MOOSOCIAL-2574
        // delete controller , it already move to plugin.
        // if not remove, we can not disable a plugin.
        $this->deleteGarbageController();
        
    }
    
    public function callback_2_3_1(){
        $this->updatePluginXml();
        $this->loadModel('Setting');
        $this->Setting->save_boot_settings();
        
    }

    public function callback_2_4_0(){
        $this->updatePluginXml();
        $this->loadModel('Setting');
        $this->Setting->save_boot_settings();

        $pageModel = MooCore::getInstance()->getModel("Page.Page");
        $i18nModel = MooCore::getInstance()->getModel('I18nModel');
        $languageModel = MooCore::getInstance()->getModel('Language');

        $languages = $languageModel->find('all');
        $tmp = array();
        foreach ($languages as $language)
        {
            $tmp[$language['Language']['key']] = $language;
        }
        $languages = $tmp;
        $pageModel->Behaviors->unload('Translate');
        $pages = $pageModel->find('all');
        foreach ($pages as $page)
        {
            foreach (array_keys($languages) as $key)
            {
                $i18nModel->clear();
                $i18nModel->save(array(
                    'locale' => $key,
                    'model' => 'Page',
                    'foreign_key' => $page['Page']['id'],
                    'field' => 'title',
                    'content' => $page['Page']['title']
                ));

                $i18nModel->clear();
                $i18nModel->save(array(
                    'locale' => $key,
                    'model' => 'Page',
                    'foreign_key' => $page['Page']['id'],
                    'field' => 'content',
                    'content' => $page['Page']['content']
                ));
            }
        }
    }

    public function callback_2_4_1(){
        $this->updatePluginXml();
        $this->loadModel('Setting');
        $this->Setting->save_boot_settings();
    }
    
    public function callback_2_5_0(){
        $this->updatePluginXml();
        $this->loadModel('Setting');
        $this->Setting->save_boot_settings();

        $this->loadModel('Category');
        $this->loadModel('I18nModel');
        $this->loadModel('Language');
        $site_langs = $this->Language->getLanguages();
        $this->Category->Behaviors->unload('Translate');
        $blog_category = $this->Category->find('first', array('conditions' => array('type' => 'Blog')));

        if(!empty($blog_category)){
            foreach (array_keys($site_langs) as $lKey) {  
                $title['locale'] = $lKey;
                $title['model'] = 'Category';
                $title['foreign_key'] = $blog_category['Category']['id'];
                $title['field'] = 'name';
                $title['content'] = $blog_category['Category']['name'];
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        $this->loadModel("Mail.Mailtemplate");
        $this->Mailtemplate->Behaviors->unload('Translate');
        $private_message = $this->Mailtemplate->find('first', array('conditions' => array('type' => 'private_message')));
        if ($private_message)
        {
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $private_message['Mailtemplate']['id'];
                $title['field'] = 'content';
                $title['content'] = '<p>[header]</p><p>&nbsp;</p><p><a href="[sender_link]">[sender_title]</a> sent you a private message</p><p><a href="[message_link]">Click here</a> to view your message</p><p>&nbsp;</p><p>[footer]</p>';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);

                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $private_message['Mailtemplate']['id'];
                $title['field'] = 'subject';
                $title['content'] = '[sender_title] sent your a message on [site_name]';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        
    	$share_message = $this->Mailtemplate->find('first', array('conditions' => array('type' => 'shared_item')));
        if (!$share_message)
        {
        	$this->Mailtemplate->clear();
        	$this->Mailtemplate->save(array(
        		'type'=> 'shared_item',
        		'plugin' => 'Mail'
        	));
        	$share_message = $this->Mailtemplate->read();
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $share_message['Mailtemplate']['id'];
                $title['field'] = 'content';
                $title['content'] = '<p>Hi [shared_user]</p><p>[user_shared] shared for you a link: <a href="[shared_link]">[shared_link]</a></p><p>[shared_content]</p><p>Please see my link guy</p><p>[footer]</p>';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);

                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $share_message['Mailtemplate']['id'];
                $title['field'] = 'subject';
                $title['content'] = '[user_shared] shared for you a link';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        
    	$filenames = array(
    		APP_PATH . DS . 'Plugin'.DS.'Mail'.DS.'Locale'.DS.'eng'.DS.'LC_MESSAGES'.DS.'mail.po',
    		APP_PATH . DS . 'Plugin'.DS.'Group'.DS.'Locale'.DS.'eng'.DS.'LC_MESSAGES'.DS.'group.po',
    		APP_PATH . DS . 'Plugin'.DS.'Event'.DS.'Locale'.DS.'eng'.DS.'LC_MESSAGES'.DS.'event.po',
    		APP_PATH . DS . 'Plugin'.DS.'Subscription'.DS.'Locale'.DS.'eng'.DS.'LC_MESSAGES'.DS.'subscription.po'
    	);
    	
    	foreach ($filenames as $filename)
    	{
	        if (file_exists($filename)){
	            @unlink($filename);
	        }
    	}

        $this->loadModel("Country");
        $this->Country->Behaviors->unload('Translate');
        $countries = $this->Country->find('all');
        foreach ($countries as $us_country)
        {
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Country';
                $title['foreign_key'] = $us_country['Country']['id'];
                $title['field'] = 'name';
                $title['content'] = $us_country['Country']['name'];
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        $this->loadModel("State");
        $this->State->Behaviors->unload('Translate');
        $state_list = $this->State->find('list', array('fields' => array('State.id', 'State.name')));
        if ($state_list)
        {
            foreach ($state_list as $state_id => $state_name) {
                foreach (array_keys($site_langs) as $lKey) {
                    $title = array();
                    $title['locale'] = $lKey;
                    $title['model'] = 'State';
                    $title['foreign_key'] = $state_id;
                    $title['field'] = 'name';
                    $title['content'] = $state_name;
                    $this->I18nModel->clear();
                    $this->I18nModel->create($title);
                    $this->I18nModel->save($title);
                }
            }         
        }
        $welcome_templates = $this->Mailtemplate->find('all', array('conditions' => array('OR' => array(array('type' => 'welcome_user'),
                                                                                                  array('type' => 'welcome_user_confirm'))),
                                                                                            ));
        if ($welcome_templates)
        {
            foreach ($welcome_templates as $wt) {
                if($wt['Mailtemplate']['type'] == 'welcome_user'){
                    $this->I18nModel->updateAll(
                        array('content' => "'" . '<p>[header]</p>\r\n<p>Thank you for joining our social network. Click the following link and enter your information below to login:</p>\r\n<p><a href="[login_link]">[login_link]</a></p>\r\n<p>Email: [email]</p><p>Password: [password]</p>\r\n<p>[footer]</p>' . "'"),
                            array('model' => 'Mailtemplate',
                            'foreign_key' => $wt['Mailtemplate']['id'],
							'field' => 'content'
                        )
                    );
                }else{
                    $this->I18nModel->updateAll(
                        array('content' => "'" . '<p>[header]</p>\r\n<p>Thank you for joining our social network. Please click the link below to validate your email:</p>\r\n<p><a href="[confirm_link]">[confirm_link]</a></p>\r\n<p>Email: [email]</p><p>Password: [password]</p>\r\n<p>[footer]</p>'. "'"),
                            array('model' => 'Mailtemplate',
                            'foreign_key' => $wt['Mailtemplate']['id'],
							'field' => 'content'
                        )
                    );
                }
            }
            
        }
    }
    
	public function callback_2_5_1(){
        $this->updatePluginXml();
        $this->loadModel('Setting');
        $this->Setting->save_boot_settings();
        
        $this->loadModel('I18nModel');
        $this->loadModel('Language');
        $site_langs = $this->Language->getLanguages();
        
		$this->loadModel("Mail.Mailtemplate");
        $this->Mailtemplate->Behaviors->unload('Translate');
        $approve_user = $this->Mailtemplate->find('first', array('conditions' => array('type' => 'approve_user')));
        if ($approve_user)
        {
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $approve_user['Mailtemplate']['id'];
                $title['field'] = 'content';
                $title['content'] = '<p>[header]</p><p>Your account has been approved by site admin. Enjoy our social network!</p><p><a href="[link]">[link]</a></p><p>[footer]</p>';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);

                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $approve_user['Mailtemplate']['id'];
                $title['field'] = 'subject';
                $title['content'] = 'Your account has been approved';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        
		$unapprove_user = $this->Mailtemplate->find('first', array('conditions' => array('type' => 'unapprove_user')));
        if ($unapprove_user)
        {
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $unapprove_user['Mailtemplate']['id'];
                $title['field'] = 'content';
                $title['content'] = '<p>[header]</p><p>Your account has been Unapproved by site admin. Contact site admin at the below link for more details.</p><p><a href="[link]">[link]</a></p><p>[footer]</p>';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);

                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $unapprove_user['Mailtemplate']['id'];
                $title['field'] = 'subject';
                $title['content'] = 'Your account has been Unapproved';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        
		$active_user = $this->Mailtemplate->find('first', array('conditions' => array('type' => 'active_user')));
        if ($active_user)
        {
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $active_user['Mailtemplate']['id'];
                $title['field'] = 'content';
                $title['content'] = '<p>[header]</p><p>Your account has been enabled by site admin. Enjoy our social network!</p><p><a href="[link]">[link]</a></p><p>[footer]</p>';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);

                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $active_user['Mailtemplate']['id'];
                $title['field'] = 'subject';
                $title['content'] = 'Your account has been enabled';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
        
		$inactive_user = $this->Mailtemplate->find('first', array('conditions' => array('type' => 'inactive_user')));
        if ($inactive_user)
        {
            foreach (array_keys($site_langs) as $lKey) {
                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $inactive_user['Mailtemplate']['id'];
                $title['field'] = 'content';
                $title['content'] = '<p>[header]</p><p>Your account has been disabled by site admin. Contact site admin at the below link for more details.</p><p><a href="[link]">[link]</a></p><p>[footer]</p>';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);

                $title = array();
                $title['locale'] = $lKey;
                $title['model'] = 'Mailtemplate';
                $title['foreign_key'] = $inactive_user['Mailtemplate']['id'];
                $title['field'] = 'subject';
                $title['content'] = 'Your account has been disabled';
                $this->I18nModel->clear();
                $this->I18nModel->create($title);
                $this->I18nModel->save($title);
            }
        }
    }
    public function callback_2_6_0(){
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
    	
    	$this->loadModel("Role");
    	
    	$role = $this->Role->findById(ROOT_ADMIN_ID);
    	if ($role)
    	{
    		$params = $role['Role']['params'].',activity_view';
    		$this->Role->id = ROOT_ADMIN_ID;
    		$this->Role->save( array('params'=>$params) );
    	}
    }
    public function callback_2_6_1(){
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
    	
    	$this->Setting->updateAll(
    		array('Setting.value_actual'=>'"'.$_SERVER['SERVER_NAME'].$this->request->base.'"'),
    		array('Setting.name' => 'site_domain')
    	);
    	
    	$this->loadModel('Language');
    	$this->loadModel('ProfileField');
    	$this->loadModel('I18nModel');
    	$site_langs = $this->Language->getLanguages();
    	$this->ProfileField->Behaviors->unload('Translate');
    	$fields = $this->ProfileField->find('all');
    	
    	foreach ($fields as $field)
    	{
	    	foreach (array_keys($site_langs) as $lKey) {
	    		$title = array();
	    		$title['locale'] = $lKey;
	    		$title['model'] = 'ProfileField';
	    		$title['foreign_key'] = $field['ProfileField']['id'];
	    		$title['field'] = 'name';
	    		$title['content'] = $field['ProfileField']['name'];
	    		$this->I18nModel->clear();
	    		$this->I18nModel->create($title);
	    		$this->I18nModel->save($title);
	    	}
    	}
    }
	
	public function callback_2_6_2(){
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
	}
	
    public function callback_3_0_0(){
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
    	
    	$this->loadModel('Language');    	
    	$this->loadModel('I18nModel');
    	$site_langs = $this->Language->getLanguages();
    	$this->loadModel('SubscriptionPackage');
    	$packages = $this->SubscriptionPackage->find('all');
    	
    	foreach ($packages as $package)
    	{
    		foreach (array_keys($site_langs) as $lKey) {
    			$title = array();
    			$title['locale'] = $lKey;
    			$title['model'] = 'SubscriptionPackage';
    			$title['foreign_key'] = $package['SubscriptionPackage']['id'];
    			$title['field'] = 'name';
    			$title['content'] = $package['SubscriptionPackage']['name'];
    			
    			$this->I18nModel->clear();
    			$this->I18nModel->create($title);
    			$this->I18nModel->save($title);
    			
    			$title = array();
    			$title['locale'] = $lKey;
    			$title['model'] = 'SubscriptionPackage';
    			$title['foreign_key'] = $package['SubscriptionPackage']['id'];
    			$title['field'] = 'description';
    			$title['content'] = $package['SubscriptionPackage']['description'];
    			
    			$this->I18nModel->clear();
    			$this->I18nModel->create($title);
    			$this->I18nModel->save($title);
    		}
    	}
    	
    	$this->loadModel('SubscriptionPackagePlan');
    	$plans = $this->SubscriptionPackagePlan->find('all');
    	
    	foreach ($plans as $plan)
    	{
    		foreach (array_keys($site_langs) as $lKey) {
    			$title = array();
    			$title['locale'] = $lKey;
    			$title['model'] = 'SubscriptionPackagePlan';
    			$title['foreign_key'] = $plan['SubscriptionPackagePlan']['id'];
    			$title['field'] = 'title';
    			$title['content'] = $plan['SubscriptionPackagePlan']['title'];
    			
    			$this->I18nModel->clear();
    			$this->I18nModel->create($title);
    			$this->I18nModel->save($title);
    		}
    	}
    	
    	$this->loadModel('SubscriptionCompare');
    	$plans = $this->SubscriptionCompare->find('all');
    	
    	foreach ($plans as $plan)
    	{
    		foreach (array_keys($site_langs) as $lKey) {
    			$title = array();
    			$title['locale'] = $lKey;
    			$title['model'] = 'SubscriptionCompare';
    			$title['foreign_key'] = $plan['SubscriptionCompare']['id'];
    			$title['field'] = 'compare_name';
    			$title['content'] = $plan['SubscriptionCompare']['compare_name'];
    			
    			$this->I18nModel->clear();
    			$this->I18nModel->create($title);
    			$this->I18nModel->save($title);
    			
    			$title = array();
    			$title['locale'] = $lKey;
    			$title['model'] = 'SubscriptionCompare';
    			$title['foreign_key'] = $plan['SubscriptionCompare']['id'];
    			$title['field'] = 'compare_value';
    			$title['content'] = $plan['SubscriptionCompare']['compare_value'];
    			
    			$this->I18nModel->clear();
    			$this->I18nModel->create($title);
    			$this->I18nModel->save($title);
    		}
    	}
    	
    }
    public function callback_3_0_1(){
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
    }
    public function callback_3_0_2(){
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
    }
    
    private function upgrade_profile_value($id)
    {
    	$this->loadModel("ProfileFieldValue");
    	$this->loadModel("ProfileFieldOption");
    	
    	$options = $this->ProfileFieldOption->find('list', array(
    			'conditions' => array('ProfileFieldOption.profile_field_id' => $id),
    			'fields' => array('ProfileFieldOption.name')
    	));
    	
    	$values = $this->ProfileFieldValue->find('list', array(
    			'conditions' => array('ProfileFieldValue.profile_field_id' => $id),
    			'fields' => array('ProfileFieldValue.value')
    	));
    	
    	$data = array();
    	foreach ($values as $key => $value)
    	{
    		if (!empty($value))
    		{
    			$data[$key] = explode(', ', $value);
    		}
    	}
    	
    	$values = $data;
    	
    	if (!empty($values) && !empty($options))
    	{
    		$data2 = array();
    		foreach ($values as $k_v => $value)
    		{
    			foreach ($value as $item)
    			{
    				foreach ($options as $k_o => $option)
    				{
    					if ($option == $item)
    					{
    						$data2[$k_v][] = $k_o;
    					}
    				}
    			}
    			
    		}
    		
    		foreach ($data2 as $key => $data)
    		{
    			$value = implode(', ', $data);
    			$this->ProfileFieldValue->clear();
    			$this->ProfileFieldValue->id = $key;
    			$this->ProfileFieldValue->save(array('value' => $value));
    		}
    	}
    }
    
    public function callback_3_1_0(){
    	@ini_set("max_execution_time", "5000");
    	@ini_set("max_input_time",     "5000");
    	@set_time_limit(0);
    	
    	$this->updatePluginXml();
    	$this->loadModel('Setting');
    	$this->Setting->save_boot_settings();
    	$this->loadModel("ProfileField");
    	$columns = $this->ProfileField->find('list', array(
    			'conditions' => array('ProfileField.type !=' => 'heading'),
    			'fields' => 'ProfileField.id',
    			'order' => 'ProfileField.id ASC'
    	));
    	$db = ConnectionManager::getDataSource("default");
    	foreach ($columns as $column)
    	{
    		try {
    			$db->query("ALTER TABLE `" . $this->Setting->tablePrefix. "profile_field_searches` ADD COLUMN `field_". $column ."` VARCHAR(255) NULL");
    		}
    		catch(Exception $e)
    		{
    			
    		}
    	}
    	
    	
    	$this->loadModel("Language");
    	$this->loadModel("ProfileFieldOption");
    	$table_prefix = $this->ProfileFieldOption->tablePrefix;
    	
    	//save options and change field search type == list
    	$list = $this->ProfileField->find('list', array(
    			'conditions' => array('ProfileField.type' => 'list'),
    			'fields' => 'ProfileField.id, ProfileField.values'
    	));
    	foreach ($list as $key => $value)
    	{
    		$field_values = explode( "\n", $value );
    		foreach ($field_values as $field_value)
    		{
    			$data = array(
    					'profile_field_id' => $key,
    					'name' => trim($field_value)
    			);
    			$this->ProfileFieldOption->clear();
    			if($this->ProfileFieldOption->save($data)){
    				foreach (array_keys($this->Language->getLanguages()) as $lKey) {
    					$this->ProfileFieldOption->locale = $lKey;
    					$this->ProfileFieldOption->saveField('name', $data['name']);
    				}
    			}
    		}
    		
    		$option_ids = $this->ProfileFieldOption->find('list', array(
    				'conditions' => array(
    					'ProfileFieldOption.profile_field_id' => $key
    				),
    				'fields' => 'ProfileFieldOption.id'
    		));
    		
    		if ($option_ids)
    		{
    			$option_ids = array_map('strval',$option_ids);
    			$option_ids = "'" . implode("','", $option_ids). "'";
    			try {
    				$db->query("ALTER TABLE `" . $table_prefix . "profile_field_searches` CHANGE `field_". $key ."` `field_". $key ."` ENUM(". $option_ids .") NULL");
    			}
    			catch(Exception $e)
    			{
    				
    			}
    		}
    		
    		$this->upgrade_profile_value($key);
    	}
    	
    	//save options and change field search type == multilist
    	$list = $this->ProfileField->find('list', array(
    			'conditions' => array('ProfileField.type' => 'multilist'),
    			'fields' => 'ProfileField.id, ProfileField.values'
    	));
    	foreach ($list as $key => $value)
    	{
    		$field_values = explode( "\n", $value );
    		foreach ($field_values as $field_value)
    		{
    			$data = array(
    					'profile_field_id' => $key,
    					'name' => trim($field_value)
    			);
    			$this->ProfileFieldOption->clear();
    			if($this->ProfileFieldOption->save($data)){
    				foreach (array_keys($this->Language->getLanguages()) as $lKey) {
    					$this->ProfileFieldOption->locale = $lKey;
    					$this->ProfileFieldOption->saveField('name', $data['name']);
    				}
    			}
    		}
    		
    		$option_ids = $this->ProfileFieldOption->find('list', array(
    				'conditions' => array(
    						'ProfileFieldOption.profile_field_id' => $key
    				),
    				'fields' => 'ProfileFieldOption.id'
    		));
    		
    		if ($option_ids)
    		{
    			$option_ids = array_map('strval',$option_ids);
    			$option_ids = "'" . implode("','", $option_ids). "'";
    			try {
    				$db->query("ALTER TABLE `" . $table_prefix . "profile_field_searches` CHANGE `field_". $key ."` `field_". $key ."` SET(". $option_ids .") NULL");
    			}
    			catch(Exception $e)
    			{
    				
    			}
    		}
    		
    		$this->upgrade_profile_value($key);
    	}
    	$this->loadModel("User");
    	$this->loadModel("ProfileFieldSearch");
    	$user_ids = $this->User->find('list', array('fields' => 'id'));
    	foreach ($user_ids as $user_id)
    	{
    		$this->ProfileFieldSearch->saveSearchValue($user_id);
    	}
    	
    	
    	$languageModel = MooCore::getInstance()->getModel('Language');
    	
    	$languages = $languageModel->find('all');
    	$tmp = array();
    	foreach ($languages as $language)
    	{
    		$tmp[$language['Language']['key']] = $language;
    	}
    	$languages = $tmp;
    	$profileTypeModel = MooCore::getInstance()->getModel('ProfileType');
    	$profileTypeModel->Behaviors->unload('Translate');
    	$profileTypes = $profileTypeModel->find('all');
    	$i18nModel = MooCore::getInstance()->getModel('I18nModel');
    	foreach ($profileTypes as $profileType)
    	{
    		foreach (array_keys($languages) as $key)
    		{
    			$i18nModel->clear();
    			$i18nModel->save(array(
    					'locale' => $key,
    					'model' => 'ProfileType',
    					'foreign_key' => $profileType['ProfileType']['id'],
    					'field' => 'name',
    					'content' => $profileType['ProfileType']['name']
    			));
    		}
    	}
    	
    	$this->loadModel('Setting');
    	$setting = $this->Setting->findByName('storage_amazon_s3_region');
    	if ($setting)
    	{
    		$this->Setting->id = $setting['Setting']['id'];
    		$selects = json_decode($setting['Setting']['value_actual'],true);
    		$selects[] = array(
    			'name' => 'Canada (Central)',
    			'value' => 'ca-central-1',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'China (Beijing)',
    			'value' => 'cn-north-1',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'China (Beijing)',
    			'value' => 'cn-north-1',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'China (Ningxia)',
    			'value' => 'cn-northwest-1',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'EU (London)',
    			'value' => 'eu-west-2',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'EU (Paris)',
    			'value' => 'eu-west-3',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'South America (São Paulo)',
    			'value' => 'sa-east-1',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'AWS GovCloud (US-East)',
    			'value' => 'us-gov-east-1',
    			'select' => 0,
    		);
    		$selects[] = array(
    			'name' => 'AWS GovCloud (US)',
    			'value' => 'us-gov-west-1',
    			'select' => 0,
    		);
    		foreach ($selects as &$value)
    		{
    			$value['name'] = utf8_encode($value['name']);
    		}
    		$this->Setting->save(array('value_actual'=>json_encode($selects)));
    	}
    	
    	$this->loadModel("Role");
    	
    	$role = $this->Role->findById(ROOT_ADMIN_ID);
    	if ($role)
    	{
    		$params = $role['Role']['params'].',message_send_non_member';
    		$this->Role->id = ROOT_ADMIN_ID;
    		$this->Role->save( array('params'=>$params) );
    	}
    	
    	$db = ConnectionManager::getDataSource('default');
    	if (isset($db->config['prefix'])) {
    		$userTable = $db->config['prefix'] . "users";
    		if(!$this->isColumnExist("who_can_see_gender",$userTable)){
    			$query= "ALTER TABLE `$userTable` ADD `who_can_see_gender` INT NOT NULL DEFAULT '0' ";
    			try{
    				$db->fetchAll($query);
    			} catch(Exception $ex) {
    				echo $ex->getMessage();
    				die();
    			}
    		}
    	}
    }
    private function deleteGarbageController(){
        // remove BlogsController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'BlogsController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
        
        // remove AlbumsController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'AlbumsController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
        
        // remove EventsController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'EventsController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
        
        // remove GroupsController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'GroupsController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
        
        // remove GroupsController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'GroupsController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
        
        // remove TopicsController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'TopicsController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
        
        // remove VideosController.php
        $filename = APP_PATH . DS . 'Controller' . DS . 'VideosController.php';
        if (file_exists($filename)){
            unlink($filename);
        }
    }


    private function updateShareAction(){
        $this->loadModel('Activity');
        
        
        // update blog_create
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'blog_create',
            'Activity.privacy IN ' => array(PRIVACY_EVERYONE, PRIVACY_FRIENDS) 
        ));
        
        // update event_create
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'event_create',
            'Activity.privacy' => PRIVACY_PUBLIC
        ));
        
        // update group_create
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'group_create',
            'Activity.privacy' => PRIVACY_PUBLIC
        ));
        
        // update photos_add
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'photos_add',
            'Activity.privacy IN ' => array(PRIVACY_EVERYONE, PRIVACY_FRIENDS)
        ));
        
        // update topic_create
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'topic_create'
        ));
        
        // update video_activity
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'video_activity',
        ));
        
        // update video_create
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'video_create',
            'Activity.privacy IN ' => array(PRIVACY_PUBLIC, PRIVACY_FRIENDS)
        ));
        
        // update wall_post
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'wall_post',
            'Activity.privacy' => PRIVACY_PUBLIC
        ));
        
        // update wall_post_link
        $this->Activity->clear();
        $this->Activity->updateAll(array('Activity.share' => 1), array(
            'Activity.action' => 'wall_post_link',
        ));
    }

    private function updateCounterCacheCoreContent() {
        $this->loadModel('CoreContent');
        $this->CoreContent->Behaviors->disable('Translate');
        foreach(array_keys($this->CoreContent->find('list',array('conditions' => array('CoreContent.parent_id IS NULL') ) )) as $id) {
            $dummy = $this->CoreContent->findByParentId($id);
            if (!empty($dummy)) {
                $this->CoreContent->clear();
                $this->CoreContent->id = $dummy['CoreContent']['id'];
                $this->CoreContent->save(array('parent_id' => $id));
            }
        }
        $this->CoreContent->Behaviors->enable('Translate');
    }

    private function updatePluginXml(){
        $content = '<?xml version="1.0" encoding="utf-8"?>
                    <info></info>';
        file_put_contents(PLUGIN_CONFIG_PATH, $content);
        $xml = new SimpleXMLElement($content);  

        //add plugins to xml
        $this->loadModel('Plugin');
        $plugins = $plugins = $this->Plugin->find('all');
        if($plugins != null)
        {
            $pluginsXml = $xml->addChild('plugins');
            foreach($plugins as $plugin)
            {
                $pluginXml = $pluginsXml->addChild('plugin');
                $pluginXml->addChild('name', $plugin['Plugin']['key']);
                $pluginXml->addChild('enabled', $plugin['Plugin']['enabled'] == 1 ? 1: 0);
                $pluginXml->addChild('bootstrap', $plugin['Plugin']['bootstrap'] == 1 ? 1: 0);
                $pluginXml->addChild('routes', $plugin['Plugin']['routes'] == 1 ? 1: 0);
            }
        }
        $xml->saveXML(PLUGIN_CONFIG_PATH);
        
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save(PLUGIN_CONFIG_PATH);
    }

    public function changePopularTagWidget(){
        $this->autoRender = false;
        $this->loadModel('CoreContent');

        $this->CoreContent->Behaviors->disable('Translate');
        $tagWidgets = $this->CoreContent->find('all',array('conditions' => array('CoreContent.name' => 'core.tags') ));
        $this->CoreContent->Behaviors->enable('Translate');

        if(!empty($tagWidgets)){
            $aPlugins = array('blogs' => 'blog', 'albums' => 'album', 'topics' => 'topic', 'videos' => 'video');
            foreach($tagWidgets as &$widget){
                $params = json_decode($widget['CoreContent']['params'],true);
                $titleEnable = $params['title_enable'];
                unset($params['title_enable']);
                $params['order_by'] = 'popular';
                //$params['style'] = 'classic';
                if(in_array($params['type'],$aPlugins)){
                    $params['type'] = array_search($params['type'],$aPlugins);
                }
                $params['title_enable'] = $titleEnable;
                $new_params = json_encode($params);
                $this->CoreContent->clear();
                $this->CoreContent->id = $widget['CoreContent']['id'];
                $this->CoreContent->save(array('params' => $new_params));
            }
        }
    }

    public function showMemberOnly(){
        $this->autoRender = false;
        $this->loadModel('CoreContent');

        $this->CoreContent->Behaviors->disable('Translate');
        $whoOnlineWidgets = $this->CoreContent->find('all',array('conditions' => array('CoreContent.name' => 'user.onlineUsers') ));
        $this->CoreContent->Behaviors->enable('Translate');

        if(!empty($whoOnlineWidgets)){
            foreach($whoOnlineWidgets as &$widget){
                $params = json_decode($widget['CoreContent']['params'],true);
                $titleEnable = $params['title_enable'];
                unset($params['title_enable']);
                $params['member_only'] = '1';

                $params['title_enable'] = $titleEnable;
                $new_params = json_encode($params);
                $this->CoreContent->clear();
                $this->CoreContent->id = $widget['CoreContent']['id'];
                $this->CoreContent->save(array('params' => $new_params));
            }
        }
    }

    public function installThemes($key_list){
        $this->autoRender = false;
        $this->loadModel('Theme');
        $this->loadModel('Setting');

        foreach($key_list as &$key){
            if ( file_exists( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'info.xml' ) )
            {
                $content = file_get_contents( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'info.xml' );
                $info = new SimpleXMLElement($content);
                $this->Theme->create();
                if ( $this->Theme->save( array( 'name' => $info->name, 'key' => $info->key ) ) )
                {
                    Cache::delete('site_themes');
                }
            }

            // update theme setting select
            $themes = $this->Theme->find('all');
            $data = array();
            foreach ($themes as $item){
                $data[] = array(
                    'name' => $item['Theme']['name'],
                    'value' => $item['Theme']['key'],
                    'select' => $item['Theme']['core']
                );
            }
            $this->Setting->updateAll(array('Setting.value_actual' => "'" . json_encode($data) . "'") , array('Setting.name' => 'default_theme'));
        }
    }

    public function fixFriendNumber() {
        $this->autoRender = false;
        $this->loadModel('User');
        $this->loadModel('Friend');
        $users = $this->User->find('all');
        foreach ($users as $item) {
            $uid = $item['User']['id'];
            $friend_count = count($this->Friend->find('list', array("joins" => array(
                                array(
                                    "table" => "users",
                                    "alias" => "users",
                                    "conditions" => array(
                                        'users.id = Friend.friend_id' 
                                    )
                                )), 'conditions' => array('Friend.user_id' => $uid, "users.active" => true))));
            $this->User->clear();
            $this->User->id = $uid;
            $this->User->set(array(
                'friend_count' => $friend_count
            ));
            $this->User->save();
        }
    }
    
    public function deleteGarbageFiles() {
        $this->autoRender = false;
        $path = ROOT . DS . 'app' . DS . 'webroot' . DS . 'theme' . DS . 'default';
        $dirs = scandir($path);
        foreach ($dirs as $dir) {

            if ($dir == "." || $dir == ".." || $dir == 'info.xml') {
                continue;
            }

            if ($dir == 'css') {






                $cssScan = scandir($path . DS . $dir);

                foreach ($cssScan as $cssDir) {

                    if ($cssDir == "." || $cssDir == "..") {
                        continue;
                    }

                    if ($cssDir == 'custom.css') {
                        continue;
                    }

                    if (is_dir($path . DS . $dir . DS . $cssDir)) {
                        $this->rrmdir($path . DS . $dir . DS . $cssDir);
                    } else {
                        unlink($path . DS . $dir . DS . $cssDir);
                    }
                }
            } else if ($dir == 'img') {

                $imgScan = scandir($path . DS . $dir);

                foreach ($imgScan as $imgDir) {

                    if ($imgDir == "." || $imgDir == "..") {
                        continue;
                    }

                    if ($imgDir == 'logo.png') {
                        continue;
                    }

                    if (is_dir($path . DS . $dir . DS . $imgDir)) {
                        $this->rrmdir($path . DS . $dir . DS . $imgDir);
                    } else {
                        unlink($path . DS . $dir . DS . $imgDir);
                    }
                }
            } else {
                if (is_dir($path . DS . $dir)) {
                    $this->rrmdir($path . DS . $dir);
                } else {
                    unlink($path . DS . $dir);
                }
            }
        }
    }

    public function checkPermissionGarbageFiles(){
        
        if (is_writable(ROOT . DS . 'app' . DS . 'webroot' . DS . 'theme' . DS . 'default')){
            return true;
        }
        
        return false;
    }
    
    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . DS . $object) == "dir")
                        $this->rrmdir($dir . DS . $object);
                    else
                        unlink($dir . DS . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    // upgrade photo for new version 2.2.0
    public function photo(){
        ini_set('max_execution_time', 0);
        // load photo size setting
        $this->loadModel('Setting');
        $setting = $this->Setting->findByName('photo_image_sizes');
        Configure::write('core.photo_image_sizes', $setting['Setting']['value']);

        $listUpgrade = array(
            'photo_user' => 'User',
            'photo_blog' => 'Blog',
            'photo_photo' => 'Photo',
            'photo_album' => 'Album',
            'photo_event' => 'Event',
            'photo_group' => 'Group',

            'photo_topic' => 'Topic',
            'photo_video' => 'Video',
        );

        $is_complete_upgrade_photo = false;
        foreach ($listUpgrade as $key => $plugin){
            $this->{$key}();
            unset($listUpgrade[$key]);

            if (empty($listUpgrade)){
                $is_complete_upgrade_photo = true;
            }
        }

        if ($is_complete_upgrade_photo){
        //    $this->redirect('/upgrade/index/done');
        //    exit;
        }
    }


    public function photo_blog(){
        $this->loadModel('Blog.Blog');
        $this->Blog->Behaviors->disable('Hashtag');
        $blogs = $this->Blog->find('all');
        $this->Blog->Behaviors->detach('Activity');
        foreach ($blogs as $item){
            if (empty($item['Blog']['thumbnail'])){
                continue;
            }
            $this->Blog->Behaviors->detach('Activity');
            $thumbnail = FULL_BASE_URL . $this->request->webroot . str_replace('t_', '', $item['Blog']['thumbnail']);

            $this->Blog->clear();
            $this->Blog->id = $item['Blog']['id'];
            $this->Blog->set(array(
                'thumbnail' => ''
            ));
            $this->Blog->save();
            $this->Blog->Behaviors->detach('Activity');
            $this->Blog->clear();
            $this->Blog->id = $item['Blog']['id'];
            $this->Blog->save(array('thumbnail' => 'uploads' . DS  . 'blog' . DS . 'thumbnail' . DS . array_pop(explode('/', $item['Blog']['thumbnail']))));

        }
    }

    public function photo_album(){
        $this->loadModel('Photo.Album');
        $this->Album->Behaviors->disable('Hashtag');
        $albums = $this->Album->find('all');
        $this->Album->Behaviors->detach('Activity');
        foreach ($albums as $item){
            if (empty($item['Album']['cover'])){
                continue;
            }

            $this->Album->Behaviors->detach('Activity');
            $this->Album->clear();
            $this->Album->id = $item['Album']['id'];
            $this->Album->set(array(
                'cover' => ''
            ));
            $this->Album->save();
            $this->Album->Behaviors->detach('Activity');
            $this->Album->clear();
            $this->Album->id = $item['Album']['id'];
            $this->Album->save(array('cover' => array_pop(explode('/', str_replace('t_', '', $item['Album']['cover'])))));

        }
    }

    public function photo_event(){
        $this->loadModel('Event.Event');
        $this->Event->Behaviors->disable('Hashtag');
        $this->Event->Behaviors->detach('Activity');
        $events = $this->Event->find('all');

        foreach ($events as $item){
            if (empty($item['Event']['photo'])){
                continue;
            }
            $this->Event->Behaviors->detach('Activity');
            $photo = FULL_BASE_URL . $this->request->webroot . 'uploads/events/' . str_replace('t_', '', $item['Event']['photo']);
            $this->Event->clear();
            $this->Event->id = $item['Event']['id'];
            $this->Event->set(array(
                'photo' => ''
            ));
            $this->Event->save();
            $this->Event->Behaviors->detach('Activity');
            $this->Event->clear();
            $this->Event->id = $item['Event']['id'];
            $this->Event->save(array('type' => $item['Event']['type'],'photo' => 'uploads' . DS  . 'events' . DS . str_replace('t_', '', $item['Event']['photo'])));

        }
    }

    public function photo_group(){
        $this->loadModel('Group.Group');
        $this->Group->Behaviors->disable('Hashtag');
        $this->Group->Behaviors->detach('Activity');
        $groups = $this->Group->find('all');
        foreach ($groups as $item){
            if (empty($item['Group']['photo'])){
                continue;
            }
            $this->Group->Behaviors->detach('Activity');
            $photo = FULL_BASE_URL . $this->request->webroot . 'uploads/groups/' . str_replace('t_', '', $item['Group']['photo']);
            $this->Group->clear();
            $this->Group->id = $item['Group']['id'];
            $this->Group->set(array(
                'photo' => ''
            ));
            $this->Group->save();
            $this->Group->Behaviors->detach('Activity');
            
            $this->Group->clear();
            $this->Group->id = $item['Group']['id'];
            $this->Group->save(array('type' => $item['Group']['type'], 'photo' => 'uploads' . DS  . 'groups' . DS . str_replace('t_', '', $item['Group']['photo'])));

        }
    }

    public function photo_photo(){
        $this->loadModel('Photo.Photo');
        $this->Photo->Behaviors->disable('Hashtag');
        $this->Photo->Behaviors->detach('Activity');
        $photos = $this->Photo->find('all');
        foreach ($photos as $item){
            if (empty($item['Photo']['thumbnail'])){
                continue;
            }

            $thumb = FULL_BASE_URL . $this->request->webroot . str_replace('t_', '', $item['Photo']['thumbnail']);
            $this->Photo->Behaviors->detach('Activity');
            $this->Photo->clear();
            $this->Photo->id = $item['Photo']['id'];
            $this->Photo->set(array(
                'thumbnail' => ''
            ));
            $this->Photo->save();

            if (file_exists(WWW_ROOT .  $item['Photo']['path'])){
                $this->Photo->Behaviors->detach('Activity');
                $this->Photo->clear();
                $this->Photo->id = $item['Photo']['id'];
                $this->Photo->save(array('thumbnail' => $item['Photo']['path']));
            }

        }
    }

    public function photo_topic(){
        $this->loadModel('Topic.Topic');
        $this->Topic->Behaviors->disable('Hashtag');
        $this->Topic->Behaviors->detach('Activity');
        $topics = $this->Topic->find('all');
        foreach ($topics as $item){

            if (empty($item['Topic']['thumbnail'])){
                continue;
            }
            $this->Topic->Behaviors->detach('Activity');
            $thumbnail = FULL_BASE_URL . $this->request->webroot . str_replace('t_', '', $item['Topic']['thumbnail']);
            $this->Topic->clear();
            $this->Topic->id = $item['Topic']['id'];
            $this->Topic->set(array(
                'thumbnail' => ''
            ));
            $this->Topic->save();

            $this->Topic->Behaviors->detach('Activity');
            $this->Topic->clear();
            $this->Topic->id = $item['Topic']['id'];
            $this->Topic->save(array('thumbnail' => 'uploads' . DS  . 'topics' . DS . 'thumbnail' . DS . array_pop(explode('/', str_replace('t_', '', $item['Topic']['thumbnail'])))));

        }
    }

    public function photo_user(){
        $this->loadModel('User');
        $users = $this->User->find('all');
        foreach ($users as $item){
            if (empty($item['User']['avatar'])){
                continue;
            }

            $avatar = FULL_BASE_URL . $this->request->webroot . 'uploads/avatars/' . $item['User']['photo'];
            $cover = FULL_BASE_URL . $this->request->webroot . 'uploads/covers/' . $item['User']['cover'];
            $this->User->clear();
            $this->User->id = $item['User']['id'];
            $this->User->set(array(
                'avatar' => ''
            ));
            $this->User->save();

            if (!empty($item['User']['avatar'])){
                $this->User->clear();
                $this->User->id = $item['User']['id'];
                $this->User->save(array('avatar' => 'uploads' . DS  . 'avatars' . DS  . $item['User']['photo']));

            }
        }
    }

    public function photo_video(){
        $this->loadModel('Video.Video');
        $this->Video->Behaviors->disable('Hashtag');
        $this->Video->Behaviors->detach('Activity');
        $videos = $this->Video->find('all');
        foreach ($videos as $item){
            if (empty($item['Video']['thumb'])){
                continue;
            }
            $this->Video->Behaviors->detach('Activity');
            $thumb = FULL_BASE_URL . $this->request->webroot . 'uploads/videos/' . $item['Video']['thumb'];
            $this->Video->clear();
            $this->Video->id = $item['Video']['id'];
            $this->Video->set(array(
                'thumb' => ''
            ));
            $this->Video->save();
            $this->Video->Behaviors->detach('Activity');
            
            $this->Video->clear();
            $this->Video->id = $item['Video']['id'];
            $this->Video->save(array('privacy' => $item['Video']['privacy'], 'thumb' => 'uploads' . DS  . 'videos' . DS . $item['Video']['thumb']));
        }
    }

    public function category()
    {
        $this->loadModel('Category');
        $this->loadModel('I18nModel');
        $this->Category->Behaviors->disable('Translate');
        $categories = $this->Category->find('all', array('recursive'=>-1, 'order'=>'id'));
        foreach($categories as $v){
            $title['locale'] = 'eng';
            $title['model'] = 'Category';
            $title['foreign_key'] = $v['Category']['id'];
            $title['field'] = 'name';
            $title['content'] = $v['Category']['name'];
            $this->I18nModel->clear();
            $this->I18nModel->create($title);
            $this->I18nModel->save($title);
        }
    }
    public function tag()
    {
        $this->loadModel('Tag');
        $tags = $this->Tag->find('all');
        foreach($tags as $tag)
        {
            $tag = $tag['Tag'];
            $new_type = '';
            if($tag['type'] != 'album')
                $new_type = ucfirst($tag['type']).'_'.ucfirst($tag['type']);
            elseif($tag['type'] == 'album')
                $new_type = 'Photo_Album';
            $this->Tag->clear();
            $this->Tag->updateAll( array('Tag.type' => "'".$new_type."'"),array('Tag.id' => $tag['id']) );
        }
    }
    
    public function fixCustomPageMenu(){
        $this->loadModel('Page.Page');
        $this->loadModel('Menu.CoreMenuItem');
        $customPages = $this->Page->find('all', array('conditions' => array('Page.id >= ' => 100, 'Page.menu' => 1)));
        $this->loadModel('Role');
        
        $roles = $this->Role->find('all');
        $roleIds = array();
        foreach ($roles as $item){
            $roleIds[] = $item['Role']['id'];
        }
        foreach ($customPages as $item){
            $this->CoreMenuItem->clear();
            $this->CoreMenuItem->create();
            $this->CoreMenuItem->set(array(
                'name' => $item['Page']['title'],
                'original_name' => $item['Page']['title'],
                'url' => '/pages/' . $item['Page']['alias'],
                'is_active' => 1,
                'role_access' => json_encode($roleIds),
                'menu_id' => 1,
                'type' => 'page',
                'menu_order' => 999
            ));
            $this->CoreMenuItem->save();
        }
    }
    
    public function upgradeEventFeed(){
        $this->loadModel('Activity');
        $this->Activity->Behaviors->disable('UserTagging');
        $this->Activity->Behaviors->disable('Hashtag');
        $eventFeed = $this->Activity->find('all', array('conditions' => array('Activity.type' => 'event')));
        
        foreach ($eventFeed as $item){
            $this->Activity->clear();
            $this->Activity->id = $item['Activity']['id'];
            $this->Activity->set(array(
                'modified' => false, 
                'type' => 'Event_Event'
            ));
            $this->Activity->save();
        }
    }
    
    public function upgradeGroupFeed(){
        $this->loadModel('Activity');
        $this->Activity->Behaviors->disable('UserTagging');
        $this->Activity->Behaviors->disable('Hashtag');
        $groupFeed = $this->Activity->find('all', array('conditions' => array('Activity.type' => 'group')));
        
        foreach ($groupFeed as $item){
            $this->Activity->clear();
            $this->Activity->id = $item['Activity']['id'];
            $this->Activity->set(array(
                'modified' => false, 
                'type' => 'Group_Group'
            ));
            $this->Activity->save();
        }
        
        
        $groupFeedPhoto = $this->Activity->find('all', array('conditions' => array('Activity.type' => 'Group_Group', 'Activity.action' => 'photo_create')));
        foreach ($groupFeedPhoto as $item){
            $this->Activity->clear();
            $this->Activity->id = $item['Activity']['id'];
            $this->Activity->set(array(
                'modified' => false, 
                'action' => 'photos_add'
            ));
            $this->Activity->save();
        }
    }
    
    // check write permission of /app/Config/plugins/plugins.xml
    // it need for update 3rd plugin
    public function checkPermissionPluginsXml(){
        
        if (is_writable(ROOT . DS . 'app' . DS . 'Config' . DS . 'plugins' . DS . 'plugins.xml')){
            return true;
        }
        
        return false;
    }
    
    // check write permission of /app/Config/settings.php
    // it need for update 3rd plugin
    public function checkPermissionSettings(){
        
        if (is_writable(ROOT . DS . 'app' . DS . 'Config' . DS . 'settings.php')){
            return true;
        }
        
        return false;
    }
    
    private function isColumnExist($name, $table)
    {
    	$db = ConnectionManager::getDataSource('default');
    	$result = $db->fetchAll("SHOW COLUMNS FROM $table LIKE '$name'");
    	if ($result) {
    		return true;
    	}
    	
    	return false;
    }
}

