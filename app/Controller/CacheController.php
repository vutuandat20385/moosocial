<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class CacheController extends AppController
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
	}
	
	public function admin_index()
	{
		$caches = array(
			'File' => array(
				'name' => __('File'),
			),
			/*'Apc' => array(
				'name' => 'APC',
			),*/
			'Memcached' => array(
				'name' => 'Memcached',
				'params' => array(
					'host' => array(
						'type' => 'text',
						'label' => __('Host'),
					),
					'port' => array(
						'type' => 'text',
						'label' => __('Port'),
					),
					'login' => array(
						'type' => 'text',
						'label' => __('Login'),
					),
					'password' => array(
						'type' => 'text',
						'label' => __('Password'),
					),
					'compress' => array(
						'type' => 'checkbox',
						'label' => __('Compression will decrease the amount of memory used, however will increase processor usage.'),
					)
				)
			),
			'Redis' => array(
				'name' => 'Redis',
				'params' => array(
					'host' => array(
						'type' => 'text',
						'label' => __('Host'),
					),
					'port' => array(
						'type' => 'text',
						'label' => __('Port'),
					),
					'password' => array(
						'type' => 'text',
						'label' => __('Password'),
					)
				)
			)
		);
		
		$config = array(
				'engine' => 'File'
		);
		
		if ( file_exists( APP . 'Config/cache.php' ) )
		{
			$config = include (APP.'Config/cache.php');
		}
		
		if ($this->request->is('post'))
		{
			$engine = $this->request->data['engine'];
			$params = array(
					'engine' => $engine
			);
			
			if (isset($caches[$engine]['params']))
			{
				foreach ($caches[$engine]['params'] as $key=>$param)
				{
					$params[$key] = $this->request->data[$engine.$key];
				}
			}
			file_put_contents(APP . 'Config/cache.php', '<?php return ' . var_export($params, true) . ';');
			
			$this->Session->setFlash(__('Successfully saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
			$this->redirect('/admin/cache');
		}
		
		$this->set('config',$config);
		$this->set('caches',$caches);
		$this->set('title_for_layout', __('Cache Settings'));
	}
	
	public function admin_check_apc()
	{
		$result = array('status'=>false,'message'=>__('Please enbale APC'));
		
		if((extension_loaded('apcu') || extension_loaded('apc')) && ini_get('apc.enabled'))
		{
			$result['status'] = true;
		}
		
		echo json_encode($result);die();
	}
	
	public function admin_check_memcached()
	{
		$result = array('status'=>false,'message'=>__('Please enbale Memcached'));
		
		if(class_exists('Memcached',false)){
			$host = $this->request->data['Memcachedhost'];
			$port = $this->request->data['Memcachedport'];
			$login = $this->request->data['Memcachedlogin'];
			$password = $this->request->data['Memcachedpassword'];
			
			if ($host)
			{
				App::uses('MemcachedEngine','Cache/Engine');
				$memcached = new MemcachedEngine();
				$link = $host.(($port) ? ':'.$port: '');
				$array['servers'] = array($link);
				if ($login)
				{
					$array['login'] = $login;
					$array['password'] = $password;
				}
				$result_m= $memcached->init($array);
				
				if ($result_m)
				{
					$memcached->write("moo_test", "test",0);
					if ($memcached->read("moo_test") == "test")
					{
						$result['status'] = true;
					}
					else
					{
						$result['message'] = __("Can't connect to server Memcached");
					}
				}
				else
				{
					$result['message'] = __("Can't connect to server Memcached");
				}
			}
		}
		
		echo json_encode($result);die();
	}
	
	public function admin_check_redis()
	{
		$result = array('status'=>false,'message'=>__('Please enbale Redis'));
		
		if(class_exists('Redis',false)){
			$host = $this->request->data['Redishost'];
			$port = $this->request->data['Redisport'];
			$password = $this->request->data['Redispassword'];
			if ($host)
			{
				App::uses('RedisEngine','Cache/Engine');
				$redis = new RedisEngine();
				$result_r = $redis->init(array(
						'server' => $host,
						'port' => intval($port),
						'password' => $password
				));
				
				if ($result_r)
				{
					$result['status'] = true;
				}
				else
				{
					$result['message'] = __("Can't connect to server Redis");
				}
			}
		}
		
		echo json_encode($result);die();
	}
}
