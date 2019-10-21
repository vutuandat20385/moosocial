<?php
App::uses('AppController', 'Controller');
App::import('Vendor', 'aws', array('file' => 'aws/aws-autoloader.php'));

use Aws\S3\S3Client;

App::uses('StorageAmazon', 'Storage.Lib');

class StorageAmazonController extends StorageAppController
{
    protected $lines = array();
    protected $isSDKPassed = true;
    protected $startCheckingSDK = true;
    public $components = array('QuickSettings');

    public function __construct($request = null, $response = null)
    {
        parent::__construct($request, $response);
    }

    public function endTest()
    {

        return implode("\n", $this->lines);
    }

    public function title($text)
    {
        $this->lines[] = "<h3 class=\"block\">{$text}</h3>";
    }

    public function write($text)
    {
        $this->lines[] = $text;
    }

    public function quote($text)
    {
        return implode("\n", array_map(function ($t) {
            return '    ' . $t;
        }, explode("\n", $text)));
    }

    public function check($info, $func, $text, $required)
    {
        $level = $func() ? 'OK' : ($required ? 'FAIL' : 'WARNING');

        $text = $level == 'OK'
            ? "<li style='margin-bottom:1px' class=\"list-group-item bg-blue bg-font-blue\">{$info} <span style='font-weight: bolder;font-size: larger;'> [" . __('OK') . "]</span></li>"
            : (($level == 'FAIL') ? "<li style='margin-bottom:1px' class=\"list-group-item bg-red bg-font-red\">{$info} <span style='font-weight: bolder;font-size: larger;'>[" . __('FAIL') . "] </span> <br/>=> {$text}</li>" : "<li style='margin-bottom:1px' class=\"list-group-item bg-yellow bg-font-yellow\">{$info} <span style='font-weight: bolder;font-size: larger;'> [" . __('WARNING') . "] </span> <br/>=> {$text}</li>");
        if ($this->startCheckingSDK) {
            if ($level == 'FAIL') {
                $this->isSDKPassed = false;
            }
        }
        $this->write($text);
    }

    public function addRecommend($info, $func, $text)
    {
        $this->check($info, $func, $text, false);
    }

    public function addRequire($info, $func, $text)
    {
        $this->check($info, $func, $text, true);
    }

    public function iniCheck($info, $setting, $expected, $required = true, $help = null)
    {
        $current = ini_get($setting);
        $cb = function () use ($current, $expected) {
            return is_callable($expected)
                ? call_user_func($expected, $current)
                : $current == $expected;
        };

        $message = sprintf(
        		__('%s in %s is currently set to %s but %s be set to %s.'),
                $setting,
                php_ini_loaded_file(),
                var_export($current, true),
                $required ? __('must') : __('should'),
                var_export($expected, true)
            ) . ' ' . $help;

        $this->check($info, $cb, trim($message), $required);
    }

    public function extCheck($ext, $required = true, $help = '')
    {
        $info = sprintf(__('Checking if the %s extension is installed'), $ext);
        $cb = function () use ($ext) {
            return extension_loaded($ext);
        };
        $message = $help ?: sprintf(__('The %s extension %s be installed'), $ext, $required ? __('must') : __('should'));
        $this->check($info, $cb, $message, $required);
    }

    public function startSectionCheck()
    {
        $this->write("<ul class=\"list-group\">");
    }

    public function endSectionCheck()
    {
        $this->write("</ul>");
    }

    private function checkAmazonSDKCompact()
    {
        $c = $this;
        $c->title(__('System requirements'));
        $c->startSectionCheck();
        $c->addRequire(
        	__('Ensuring that the version of PHP is >= 5.5.0'),
            function () {
                return version_compare(phpversion(), '5.5.0', '>=');
            },
            __('You must update your version of PHP to 5.5.0 to run the AWS SDK for PHP')
        );

        $c->iniCheck(__('Ensuring that detect_unicode is disabled'), 'detect_unicode', false, true, __('Enabling detect_unicode may cause errors when using phar files. See https://bugs.php.net/bug.php?id=42396'));
        $c->iniCheck(__('Ensuring that session.auto_start is disabled'), 'session.auto_start', false);

        if (extension_loaded('suhosin')) {
            $c->addRequire(
            		__('Ensuring that phar files can be run with the suhosin patch'),
                function () {
                    return false !== stripos(ini_get('suhosin.executor.include.whitelist'), 'phar');
                },
                sprintf(__('suhosin.executor.include.whitelist must be configured to include "phar" in %s so that the phar file works correctly'), php_ini_loaded_file())
            );
        }

        foreach (array('pcre', 'spl', 'json', 'dom', 'simplexml', 'curl') as $ext) {
            $c->extCheck($ext, true);
        }

        if (function_exists('curl_version')) {
            $c->addRequire(__('Ensuring that cURL can send https requests'), function () {
                $version = curl_version();
                return in_array('https', $version['protocols'], true);
            }, __('cURL must be able to send https requests'));
        }

        $c->addRequire(__('Ensuring that file_get_contents works'), function () {
            return function_exists('file_get_contents');
        }, __('file_get_contents has been disabled'));
        $c->endSectionCheck();
        $this->startCheckingSDK = false;
        $c->title(__('System recommendations'));
        $c->startSectionCheck();
        $c->addRecommend(__('Checking if you are running on a 64-bit platform'), function () {
            return PHP_INT_MAX === 9223372036854775807;
        }, __('You are not running on a 64-bit installation of PHP. You may run into issues uploading or downloading files larger than 2GB.'));

        $c->iniCheck(__('Ensuring that zend.enable_gc is enabled'), 'zend.enable_gc', true, false);

        $c->check(__('Ensuring that date.timezone is set'), function () {
            return (bool)ini_get('date.timezone');
        }, __('The date.timezone PHP ini setting has not been set in') . php_ini_loaded_file(), false);

        if (extension_loaded('xdebug')) {
            $c->addRecommend(__('Checking if Xdebug is installed'), function () {
                return false;
            }, __('Xdebug is installed. Consider uninstalling Xdebug to make the SDK run much faster.'));
            $c->iniCheck(__('Ensuring that Xdebug\'s infinite recursion detection does not erroneously cause a fatal error'), 'xdebug.max_nesting_level', 0, false);
        }

        $c->extCheck('openssl', false);
        $c->extCheck('zlib', false);
        $c->iniCheck(__('Checking if OPCache is enabled'), 'opcache.enable', 1, false);
        $c->endSectionCheck();
        return $c->endTest();
    }

    public function admin_confirm_enable()
    {
        $this->set("checkAmazonSDKCompact", $this->checkAmazonSDKCompact());
        $this->set("isSDKPassed", $this->isSDKPassed);

    }

    private function getSettingGuide($key)
    {
        $settingGuide = '';
        $setupPath = sprintf(PLUGIN_FILE_PATH, $key, $key);
        if (file_exists($setupPath)) {
            require_once($setupPath);
            $classname = $key . 'Plugin';
            if (class_exists($classname)) {
                $cl = new $classname();
                if (method_exists($classname, 'settingGuide')) {
                    $settingGuide = $cl->settingGuide();
                }
            }
        }
        return $settingGuide;
    }

    private function checkIsSDKPassed()
    {
        $this->set("checkAmazonSDKCompact", $this->checkAmazonSDKCompact());
        if (!$this->isSDKPassed) {
            $this->redirect(array(
                'plugin' => 'storage',
                'controller' => 'StorageAmazon',
                'action' => 'admin_confirm_enable'));
        }
    }

    public function admin_edit()
    {
        $this->checkIsSDKPassed();
        //$this->QuickSettings->run($this, array("Storage"));
        $setting_groups = $settings = null;
        $controller = $this;
        $module = array("Storage");
        $id = null;
        if ($module != null) {
            $module_id = array();
            foreach ($module as $item) {
                $module_id[] = array("module_id" => $item);
            }

            //group setting
            $setting_groups = $this->SettingGroup->find('all', array(
                'conditions' => array(
                    'OR' => $module_id
                )
            ));
        }

        //settings
        $settingGuides = array();
        if ((int)$id < 1) {
            $groupId = array();
            if ($setting_groups != null) {
                foreach ($setting_groups as $setting_group) {
                    $setting_group = $setting_group['SettingGroup'];
                    $groupId[] = $setting_group['id'];
                    $settingGuides[] = $this->getSettingGuide($setting_group['module_id']);
                }
                $settings = $this->Setting->find('all', array(
                    //'conditions' => array('group_id' => $groupId),
                    'conditions' => array('Setting.name like' => "%storage_amazon%"),
                ));
            }
        } else {
            $settings = $this->Setting->find('all', array(
                //'conditions' => array('group_id' => $id),
                'conditions' => array('Setting.name like' => "%storage_amazon%"),
            ));
            if ($setting_groups != null) {
                foreach ($setting_groups as $setting_group) {
                    $setting_group = $setting_group['SettingGroup'];
                    if ($setting_group['id'] == $id) {
                        $settingGuides[] = $this->getSettingGuide($setting_group['module_id']);
                        break;
                    }
                }
            }
        }

        $controller->set('setting_groups', $setting_groups);
        $controller->set('settings', $settings);
        $controller->set('settingGuides', $settingGuides);
        $controller->set('acive_group', $id);
        //$controller->set('isPost', $this->request->is('post'));
        $controller->set('isPost', true);
        //if($this->request->is('post')){
        $controller->set('isShowNextButton', true);
        $controller->set('url_redirect', Router::url(array(
            'plugin' => 'storage',
            'controller' => 'StorageAmazon',
            'action' => 'admin_api_test'), true));
        //}

        // Support for special option : storage_amazon_delete_image_after_adding
        $deleteImageOption = $this->Setting->find('first',array(

            'conditions' => array('Setting.name' => "storage_amazon_delete_image_after_adding"),
        ));
        $controller->set('deleteImgOptionId',$deleteImageOption["Setting"]['id']);

        // End support
    }


    public function admin_api_test()
    {
        $this->checkIsSDKPassed();
        $bucket = Configure::read("Storage.storage_amazon_bucket_name");
        $key = Configure::read("Storage.storage_amazon_access_key");
        $secret = Configure::read("Storage.storage_amazon_secret_key");
        // Instantiate an Amazon S3 client.
        $s3 = new S3Client(array(
            'version' => 'latest',
            'region' => Configure::read("Storage.storage_amazon_s3_region"),
            'credentials' => array(
                'key' => $key,
                'secret' => $secret,
            ),
        ));
        $this->Session->write('Storage.Amazon_api_test', 'Green');
        try {
            $s3Res = $s3->listBuckets(array());
            $s3ResA = $s3Res->toArray();
        } catch (Aws\Exception\S3Exception $e) {
        }


        if (!$s3->doesBucketExist($bucket)) {
            try {
                $s3->createBucket(array(
                    'Bucket' => $bucket
                ));
                try {
                    $s3->putBucketPolicy(array(
                        'Bucket' => $bucket,
                        'Policy' => '{
                                    "Version": "2012-10-17",                               
                                    "Statement": [
                                        {
                                            "Sid": "moosocial-to-s3",
                                            "Effect": "Allow",
                                            "Principal": "*",
                                            "Action": "s3:GetObject",
                                            "Resource": "arn:aws:s3:::' . $bucket . '/*"
                                        }
                                    ]
                                }'
                    ));
                } catch (Aws\Exception\S3Exception $e) {
                }
            } catch (Aws\Exception\S3Exception $e) {
            }
        }

        $this->Session->delete('Storage.Amazon_api_test');

    }

    public function admin_confirm_enable_step4()
    {
        $this->admin_api_test();
    }

    public function admin_confirm_enable_step4_ok()
    {
        $this->admin_api_test();
        $this->active_service("amazon");
        $this->loadModel("Cron.Task");
        $this->Task->clear();
        $record = $this->Task->find("first", array(
            'conditions' => array(
                'plugin' => 'Storage',
                'class' => 'Storage_Task_Aws_Cron',
            )
        ));
        if ($record) {
            $record['Task']['enable'] = 1;
            $this->Task->save($record);
        } else {
            $this->Task->save(array(
                'title' => 'Aws Task',
                'plugin' => 'Storage',
                'timeout' => ' 5',
                'processes' => 1,
                'semaphore' => 0,
                'started_last' => 0,
                'started_count' => 0,
                'completed_last' => 0,
                'completed_count' => 0,
                'failure_last' => 0,
                'failure_count' => 0,
                'success_last' => 0,
                'success_count' => 0,
                'enable' => 1,
                'class' => 'Storage_Task_Aws_Cron'
            ));
        }

        $this->redirect(array(
            'plugin' => 'storage',
            'controller' => 'Storages',
            'action' => 'admin_index'));

    }

    public function admin_clear_caches()
    {
        //$this->admin_api_test();
        Cache::clearGroup("storage");
        $this->loadModel("Storage.StorageAwsObjectMap");
        //$this->StorageAwsObjectMap->query('TRUNCATE TABLE '.$this->StorageAwsObjectMap->tablePrefix."storage_aws_object_maps");
        //$this->StorageAwsObjectMap->query('UPDATE ' . $this->StorageAwsObjectMap->tablePrefix . "storage_aws_object_maps SET url=''");
        $this->Flash->adminMessages(__('The cache of Amazon\'s S3 service has been cleared.'));
        $this->redirect(array(
            'plugin' => 'storage',
            'controller' => 'Storages',
            'action' => 'admin_index'));

    }

    public function admin_transfer()
    {
        $this->Flash->adminMessages(__('Starting transfer stored files from local service to this storage service!'));
        $this->loadModel("Cron.Task");
        $this->Task->clear();
        $record = $this->Task->find("first", array(
            'conditions' => array(
                'plugin' => 'Storage',
                'class' => 'Storage_Task_Aws_Cron_Transfer',
            )
        ));
        if ($record) {
            $record['Task']['enable'] = 1;
            $this->Task->save($record);
        } else {
            $this->Task->save(array(
                'title' => 'Aws Task Transfer',
                'plugin' => 'Storage',
                'timeout' => ' 5',
                'processes' => 1,
                'semaphore' => 0,
                'started_last' => 0,
                'started_count' => 0,
                'completed_last' => 0,
                'completed_count' => 0,
                'failure_last' => 0,
                'failure_count' => 0,
                'success_last' => 0,
                'success_count' => 0,
                'enable' => 1,
                'class' => 'Storage_Task_Aws_Cron_Transfer'
            ));
        }
        $this->redirect(array(
            'plugin' => 'storage',
            'controller' => 'Storages',
            'action' => 'admin_index'));
    }

    public function admin_sync_webroot()
    {
        if ($this->request->is('post')) {
            $patern = array(
                'img'=> '.*\.jpg|.*\.png|.*\.ico|.*\.gif',
                'js' => '.*\.js',
                'css'=> '.*\.css',
                'font'=>'.*\.otf|.*\.eot|.*\.svg|.*\.ttf|.*\.woff',
            );
            foreach ($this->request->data['sync'] as $type) {
                if(in_array("sync-webroot",$this->request->data['folders'])){
                    $this->sync_webroot($type, $patern[$type]);
                }else{
                    foreach ($this->request->data['folders'] as $folder){
                        $this->sync_webroot($type, $patern[$type],$folder);
                    }
                }
            }
        }

        $this->set('data', array('code' => 1));
        $this->set('_serialize', array('data'));
    }

    private function deleteFilesMap($type = "img")
    {
        //Cache::clearGroup("storage");
        //$this->loadModel("Storage.StorageAwsObjectMap");
        //$this->StorageAwsObjectMap->deleteAll(array('StorageAwsObjectMap.type' => $type), false);
    }

    private function sync_webroot($type, $patern,$folder=false)
    {
        //$this->deleteFilesMap($type);
        $dir = (!$folder)? new Folder(WWW_ROOT): new Folder(WWW_ROOT.$folder);
        $files = $dir->findRecursive($patern);
        $array_ignore = array(
        	WWW_ROOT . "uploads",
        	WWW_ROOT . "theme". DS . 'adm',
        	WWW_ROOT . "favicon.ico",
        );
        
        foreach ($files as $file) {
        	if (!$this->strposa($file, $array_ignore)) {
                $this->addMissingObject($file, $type);
            }
        }
    }
    
    private function strposa($haystack, $needles=array()) {
    	foreach($needles as $needle) {
    		if (strpos($haystack, $needle) !== FALSE)
    			return true;
    	}
    	
    	return false;
    }

    private function addMissingObject($path, $type)
    {
        $realPath = str_replace(WWW_ROOT, "", $path);
        $url = str_replace(DS, "/", $realPath);
        StorageAmazon::getInstance()->addMissingObject(0, $type, $url, $url, array('key' => "webroot/" . $url));
    }
    public function admin_help(){

    }
    public function admin_cloudfront(){
        if ($this->request->is('post')) {

            if(isset($this->request->data['url'])){
                $oMapping = $this->Setting->findByName('storage_cloudfront_cdn_mapping');
                if($oMapping){
                    $oMapping["Setting"]["value_actual"] = $this->request->data['url'];
                    $this->Setting->save($oMapping);

                }
            }

            $oEnabled = $this->Setting->findByName('storage_cloudfront_enable');
            $oEnabledSetting = json_decode($oEnabled['Setting']['value_actual'], true);

            if(isset($this->request->data['enable'])){
                $oEnabledSetting[0]['select'] = 1;
            }else{
                $oEnabledSetting[0]['select'] = 0;
            }
            Cache::clearGroup("storage");
            $oEnabled['Setting']['value_actual'] = json_encode($oEnabledSetting);
            $this->Setting->save($oEnabled);
            $this->update_plugin_info_xml($oEnabled["Setting"]["group_id"]);
        }
        $this->set('data', array('code' => 1));
        $this->set('_serialize', array('data'));
    }
}

