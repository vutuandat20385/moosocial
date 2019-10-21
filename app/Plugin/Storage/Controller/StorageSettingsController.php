<?php 
class StorageSettingsController extends StorageAppController{
    public function admin_index()
    {
    	$this->redirect('/admin/storage/storages');
    }
}