<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */

class CountriesController extends AppController 
{
	public $check_subscription = false;
	public $check_force_login = false;
	
    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel('Country');
        $this->loadModel('State');
    }
    public function load_place($model) {
        $this->autoRender = false;
        $id = $this->request->data['id'] ;
        $response = array();
        if(!empty($id)) {
            if($model == 'State'){
                $response = $this->$model->getStateSelect($id);
            }
            if($model == 'Region') {
                $response = $this->$model->getRegionSelect($id);
            }
            if($model == 'City') {
                $response = $this->$model->getCitySelect($id);
            }
        }
        echo json_encode($response);
    }
    
    public function admin_index() {
        $this->_checkPermission(array('super_admin' => 1));
        $countries = $this->Country->getCountries();
        $this->set('countries', $countries);
        $this->set('title_for_layout', __('Country Manager'));
    }
    public function admin_state($country_id = null) {
        $this->_checkPermission(array('super_admin' => 1));
        $states = $this->State->getStateByCountryId($country_id);
        $country = $this->Country->getItemById($country_id);
        $this->set('country', $country);
        $this->set('states', $states);
        $this->set('title_for_layout', __('State/Province Manager'));
    }
    public function admin_save_order($model)
    {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        foreach ($this->request->data['order'] as $id => $order) {
            $this->$model->id = $id;
            $this->$model->save(array('order' => $order));
        }
        $this->Session->setFlash(__('Order saved'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
        echo $this->referer();
    }
    public function admin_translate($id, $model) {
        $this->_checkPermission(array('super_admin' => 1));
        if (!empty($id)) {
            $item = $this->$model->getItemById($id);
            $this->set('item', $item);
            $this->set('model', $model);
            $this->set('languages', $this->Language->getLanguages());
        } else {
            // error
        }
    }
    public function admin_translate_save($model) {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        if ($this->request->is('post') || $this->request->is('put')) {
            if (!empty($this->request->data)) {
                // we are going to save the german version
                $this->$model->id = $this->request->data['id'];
                foreach ($this->request->data['name'] as $lKey => $sContent) {
                    $this->$model->locale = $lKey;
                    if ($this->$model->saveField('name', $sContent)) {
                        $response['result'] = 1;
                    } else {
                        $response['result'] = 0;
                    }
                }
            } else {
                $response['result'] = 0;
            }
        } else {
            $response['result'] = 0;
        }
        echo json_encode($response);
    }
    
    public function admin_create_country($id = null) {
        $this->_checkPermission(array('super_admin' => 1));
        $bIsEdit = false;
        if (!empty($id)) {
            $country = $this->Country->getItemById($id);
            $bIsEdit = true;
        } else {
            $country = $this->Country->initFields();
        }
        $this->set('country', $country);
        $this->set('bIsEdit', $bIsEdit);
    }
    
    public function admin_create_state($country_id = null, $id = null) {
        $this->_checkPermission(array('super_admin' => 1));
        $bIsEdit = false;
        if (!empty($id)) {
            $state = $this->State->getItemById($id);
            $bIsEdit = true;
        } else {
            $state = $this->State->initFields();
        }
        $country = $this->Country->getItemById($country_id);
        $this->set('country', $country);
        $this->set('state', $state);
        $this->set('bIsEdit', $bIsEdit);
    }
    
    public function admin_save_country() {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        $bIsEdit = false;
        if (!empty($this->data['id'])) {
            $bIsEdit = true;
            $this->Country->id = $this->request->data['id'];
        }

        $this->Country->set($this->request->data);

        $this->_validateData($this->Country);

        $this->Country->save();
        if (!$bIsEdit) {
            foreach (array_keys($this->Language->getLanguages()) as $lKey) {
                $this->Country->locale = $lKey;
                $this->Country->saveField('name', $this->request->data['name']);
            }         
        }
               
        $this->Session->setFlash(__('Country has been successfully saved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        
        $response['result'] = 1;
        echo json_encode($response);
    } 
    public function admin_save_state() {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        $bIsEdit = false;
        if (!empty($this->data['id'])) {
            $bIsEdit = true;
            $this->State->id = $this->request->data['id'];
        }
        $country_id = $this->request->data['country_id'];
        $this->State->set($this->request->data);

        $this->_validateData($this->State);

        $this->State->save();
        if (!$bIsEdit) {
            
            foreach (array_keys($this->Language->getLanguages()) as $lKey) {
                $this->State->locale = $lKey;
                $this->State->saveField('name', $this->request->data['name']);
            }
            // update state_count on country
            if(!empty($country_id)) {
                $this->Country->increaseCounter($country_id, 'state_count');
            }
            $params = array(
                            'name' => $this->request->data['name'],
                           'country_id' => $country_id,
                           'state_id' =>  $this->State->id,
                           );
        }else {
			
        }
                    
        $this->Session->setFlash(__('State/Province has been successfully saved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));

        $response['result'] = 1;
        $response['url'] = '/admin/countries/state/'.$country_id;
        echo json_encode($response);
    } 
   
      
    public function admin_delete_country($id) {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;        
        $this->Country->delete($id);
        $this->Session->setFlash(__('Country have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));        
        $this->redirect($this->referer());
    }
    public function admin_delete_state($id) {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        $state = $this->State->findById($id);
        $country_id = $state['State']['country_id'] ;
        
        $this->State->delete($id);
        $this->Country->decreaseCounter($country_id, 'state_count');
        $this->Session->setFlash(__('State/Province have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));        
        $this->redirect($this->referer());
    }
    public function admin_delete_region($id) {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        $region = $this->Region->findById($id);
        $state_id = $region['Region']['state_id'] ;
        
        $this->Region->delete($id);
        $this->State->decreaseCounter($state_id, 'region_count');
        $this->Session->setFlash(__('Region deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        $this->redirect($this->referer());
    }
    public function admin_delete_city($id) {
        $this->_checkPermission(array('super_admin' => 1));
        $this->autoRender = false;
        $city = $this->City->findById($id);
        $region_id = $city['City']['region_id'] ;
        
        $this->City->delete($id);
        $this->Region->decreaseCounter($region_id, 'city_count');
        $this->Session->setFlash(__('City deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        $this->redirect($this->referer());
    }
    public function admin_import_state($country_id = null, $id = null) {
        $this->_checkPermission(array('super_admin' => 1));
        $bIsEdit = false;
        if (!empty($id)) {
            $state = $this->State->getItemById($id);
            $bIsEdit = true;
        } else {
            $state = $this->State->initFields();
        }
        $country = $this->Country->getItemById($country_id);
        $this->set('country', $country);
        $this->set('state', $state);
        $this->set('bIsEdit', $bIsEdit);
    }
    public function admin_uploads() {
        $uid = $this->Auth->user('id');

        if (!$uid)
            return;

        $this->autoRender = false;

        $allowedExtensions = array('csv', 'txt');

        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $type = 'tmp';
        $path = 'uploads' . DS . $type;
        $path = WWW_ROOT . $path;

        if (!file_exists($path)) {
            mkdir($path, 0775, true);
            file_put_contents($path . DS . 'index.html', '');
        }

        $original_filename = $this->request->query['qqfile'];
        $ext = $this->_getExtension($original_filename);

        $result = $uploader->handleUpload($path);
        if (!empty($result['success'])) {
            $result['filename'] = $result['filename'];
            //  $this->Session->write('upload_filename', $result['filename']);
        }

        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }
    public function _getExtension($filename = null)
    {
		$tmp = explode('.', $filename);
		$re = array_pop($tmp);
		return $re;
    }
    public function admin_getcsvstates() {
        $this->autoRender = false;

        $this->_checkPermission(array('super_admin' => 1));
        $data = $this->request->data;
        
        if (empty($data['filename'])) {
            return;
        }
        
        $type = 'tmp';
        $path = 'uploads' . DS . $type;
        $filebaseurl = WWW_ROOT . $path . DS;
        $filename = $data['filename'];

        $file = fopen($filebaseurl . $filename, 'r') or die("can't open file");
        $country_id = $this->request->data['country_id'];
        while(! feof($file)){
            $state_name = fgets($file);
            if(!empty($state_name)){  
                if(strpos($state_name, (chr(13)))){
                    $state_arr = explode(chr(13), $state_name);
                    foreach ($state_arr as $state_name) {
                        $data['name'] = $state_name;
                        $this->State->clear();
                        $this->State->set($data);
                        $this->State->save();
                        foreach (array_keys($this->Language->getLanguages()) as $lKey) {
                            $this->State->locale = $lKey;
                            $this->State->saveField('name', $data['name']);
                        }
                        // update state_count on country
                        if(!empty($country_id)) {
                            $this->Country->increaseCounter($country_id, 'state_count');               
                        }    
                    }
                }else{
                    $data['name'] = $state_name;
                    $this->State->clear();
                    $this->State->set($data);
                    $this->State->save();
                    foreach (array_keys($this->Language->getLanguages()) as $lKey) {
                        $this->State->locale = $lKey;
                        $this->State->saveField('name', $data['name']);
                    }
                    // update state_count on country
                    if(!empty($country_id)) {
                        $this->Country->increaseCounter($country_id, 'state_count');               
                    }
                }
            }
        }

        //CLOSING THE FILE AFTER READING. 
        fclose($file) or die("can't close file");
    
        //AFTER READING THE FILE WE ARE UNLINKING THE FILE.
        $filebaseurl = $filebaseurl . $filename;
        @unlink($filebaseurl);
        $response['result'] = 1;
        echo json_encode($response);
    }

    public function ajax_get_state($id)
    {
        $states = $this->State->find('all',array(
            'conditions'=>array('country_id'=>$id)
        ));

        $tmp = array();
        foreach ($states as $state)
        {
            $tmp[] = array('id'=>$state['State']['id'],'name'=>$state['State']['name']);
        }

        echo json_encode(array('count'=>count($tmp),'data'=>$tmp));die();
    }
}
