<?php
/**
 * Minify Controller
 *
 * @package	Minify.Controller
 */
class MinifyController extends Controller {
	public function beforeFilter()
	{
		$this->loadUnBootSetting();
	}
	
	/**
	 * Index method.
	 *
	 * @return void
	 */
	public function index($name = null) {
		if ($name)
		{
			$minifyModel = MooCore::getInstance()->getModel("Minify.MinifyUrl");
			$minify = $minifyModel->getMinify($name);
			if ($minify)
			{
				$paths = json_decode($minify['MinifyUrl']['url'],true);
				$_GET['f'] = implode(',', $paths);
			}
		}
		
		if (!empty($this->request->base)) {
			$this->_adjustFilenames();
		}
		
		App::import('Vendor', 'Minify.minify/index');
		
		$this->response->statusCode('304');
		exit();
	}
	
	private function _adjustFilenames() {
		$baseUrl = substr($this->request->base, 1) . '/';
		$baseLen = strlen($baseUrl);
		$files = explode(',', $_GET['f']);
		foreach ($files as &$file) {
			if (!strncmp($file, $baseUrl, $baseLen)) {
				$file = substr($file, $baseLen);
			}
		}
		$_GET['f'] = implode(',', $files);
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
}
?>