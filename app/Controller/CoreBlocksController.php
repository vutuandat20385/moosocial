<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CoreBlocksController extends AppController
{
    public $helpers  = array('Form','Html');
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => 1));
    }

    public function admin_index()
    {
        $blocks = $this->CoreBlock->find('all');
        $activeBlock = array();
        $inactiveBlock = array();
        foreach($blocks as $value){
            if($value['CoreBlock']['is_active']==1){
                $activeBlock[] = $value;
            }else{
                $inactiveBlock[] = $value;
            }
        }
        $this->set('activeBlocks',$activeBlock);
        $this->set('inactiveBlocks',$inactiveBlock);

    }
    public function admin_ajax_create(){
        $filter_module = array();
        $path = APP .'view'.DS. 'elements';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename) {
            $filter_filename = $filename->getFilename();
            if ( !in_array($filter_filename, array( '.', '..', 'index.html', 'empty' ) ) )
                $filter_module[$filter_filename] = $filter_filename;
        }
        
        $this->set('filter_module',$filter_module);
    }
    public function admin_ajax_save(){
        $this->autoRender = false;
        $this->CoreBlock->set( $this->request->data );
        $this->_validateData( $this->CoreBlock );
        $group = '';
        if($this->request->data['group']!= '')
            $group = $this->request->data['group'].'.';
        $path = substr($this->request->data['path'],0,strlen($this->request->data['path'])-4);
        $this->request->data['path_view'] = $group.$path;
        if(isset($this->request->data['id'])){
            $this->CoreBlock->id = $this->request->data['id'];
        }
        
        if($this->CoreBlock->save($this->request->data)){
            $response['result'] = 1;
            echo json_encode($response);
        }
    }

    public function admin_do_uninstall($id = null){
        $block = $this->CoreBlock->findById( $id );
        $this->_checkExistence( $block );

        if ( $block['CoreBlock']['path'] != 'core' )
        {
            $this->CoreBlock->delete( $id );
            $this->Session->setFlash(__('Block has been successfully delete'));
        }
        else
            $this->Session->setFlash( __('Core block cannot be delete'), 'default', array( 'class' => 'error-message') );

        $this->redirect( $this->referer() );

    }
    public function admin_ajax_edit($id = null){
        $block = $this->CoreBlock->findById($id);
        $filter_module = array();
        $path = APP .'view'.DS. 'elements';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename) {
            $filter_filename = $filename->getFilename();
            if ( !in_array($filter_filename, array( '.', '..', 'index.html', 'empty' ) ) )
                $filter_module[$filter_filename] = $filter_filename;
        }
        $this->set('block',$block);
        $this->set('filter_module',$filter_module);
    }
    public function admin_do_active($id = null){
        if(!$id){
            throw new NotFoundException('Invalid Block');
        }
        $this->CoreBlock->id = $id;
        if($this->CoreBlock->save(array('is_active'=>1))){
            $this->redirect(array('action'=>'admin_index'));
        }
    }

    public function admin_ajax_translate($id) {

        if (!empty($id)) {
            $core_block = $this->CoreBlock->getCoreBlockById($id);
            $this->set('core_block', $core_block);
            $this->set('languages', $this->Language->getLanguages());
        } else {
            // error
        }
    }

    public function admin_ajax_translate_save() {

        $this->autoRender = false;
        if ($this->request->is('post') || $this->request->is('put')) {
            if (!empty($this->request->data)) {
                // we are going to save the german version
                $this->CoreBlock->id = $this->request->data['id'];
                foreach ($this->request->data['name'] as $lKey => $sContent) {
                    $this->CoreBlock->locale = $lKey;
                    if ($this->CoreBlock->saveField('name', $sContent)) {
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
    public function getTitle()
    {
        $core_blocks = $this->CoreBlock->find('all');
        $title = Hash::combine($core_blocks,'{n}.CoreBlock.id','{n}.CoreBlock.name');
        return $title;
    }
}
