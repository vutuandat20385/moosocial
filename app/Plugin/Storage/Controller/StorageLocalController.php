<?php 
App::uses('AppController', 'Controller');
class StorageLocalController extends StorageAppController{

    public function admin_confirm_enable( )
    {
        $this->active_service("local");
    }
    public function admin_cdn(){
        if ($this->request->is('post')) {

            if(isset($this->request->data['url'])){
                $oMapping = $this->Setting->findByName('storage_local_cdn_mapping');
                if($oMapping){
                    $oMapping["Setting"]["value_actual"] = $this->request->data['url'];
                    $this->Setting->save($oMapping);

                }
            }

            $oEnabled = $this->Setting->findByName('storage_localcdn_enable');
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