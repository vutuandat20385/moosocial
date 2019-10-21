<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class LayoutController extends AppController
{

    public $components = array('RequestHandler');

    public function admin_index($id = null)
    {
        $this->loadModel('Page.Page');
        $this->loadModel('CoreBlock');
        $aPages  = $this->Page->find('all',array('order' => 'Page.title'));
        $aPageHF = array();
        foreach($aPages as $key=>$page){
            if($page['Page']['title']=='Site Header' || $page['Page']['title']=='Site Footer'){
                $aPageHF[] = $page;
                unset($aPages[$key]);
            }
            $landingPage = 63;

        }
        $aBlocks = $this->CoreBlock->find('all',array(
            'conditions' => array('CoreBlock.is_active' => '1')));

        if($id !== null){
            $this->set('currentPage',$id);
            $pageType = $this->Page->findById($id,array('type'));
            $this->set('pageType',$pageType['Page']['type']);
        }
        // Stop letting the behavior handle our model callbacks
        $this->CoreBlock->Behaviors->unload('Translate');
        $aGroups = $this->CoreBlock->find('all',array(
            'fields' => 'DISTINCT CoreBlock.group',
            'conditions' => array('CoreBlock.group !='=>'')
        ));

        $this->set('title_for_layout', __('Admin Home - Layout Editor'));
        $this->set('aBlocks', $aBlocks);
        $this->set('aPages',$aPages);
        $this->set('aGroups',$aGroups);
        $this->set('aPageHF',$aPageHF);
        $this->set('landingPage',$landingPage);
    }
    
    public function admin_ajax_translate($id = null)
    {
    	$this->loadModel('Page.Page');
    	if (!empty($id)) {
            $page = $this->Page->findById($id);
            $this->set('page', $page);
            $this->set('languages', $this->Language->getLanguages());
        } else {
            // error
        }
    }
    
    public function admin_ajax_translate_save()
    {
    	$this->autoRender = false;
    	$this->loadModel('Page.Page');
        if ($this->request->is('post') || $this->request->is('put')) {
            if (!empty($this->request->data)) {
                // we are going to save the german version
                $this->Page->id = $this->request->data['id'];
                $row = $this->Page->read();
                foreach ($this->request->data['name'] as $lKey => $sContent) {
                    $this->Page->locale = $lKey;
                    if ($this->Page->saveField('title', $sContent)) {
                        $response['result'] = 1;
                    } else {
                        $response['result'] = 0;
                    }
                    Cache::delete($row['Page']['uri'].".blocks".$lKey);
                }
            } else {
                $response['result'] = 0;
            }
        } else {
            $response['result'] = 0;
        }
        echo json_encode($response);
    }

    public function admin_editPageInfo($id = null)
    {
        // request
        // model
        $this->loadModel('Page.Page');
        $page=$errors = null;

        if ($this->request->is('post') || $this->request->is('put')) { // do saving
            $this->request->data['Page']['url'] = '/pages/' . $this->request->data['Page']['alias'];
            $this->Page->set($this->request->data);
            if ($this->Page->validates()) {
                $errors = null;
                $this->Page->set($this->request->data);
                $this->Page->id = $this->request->data['Page']['id'];
                $this->Page->save();
            } else {
                $errors = $this->Page->validationErrors;
            }
            $data = array(
                'data' => $this->request->data,
                'error' => $errors,
            );
            $this->set('data', $data);
            $this->set('_serialize', array('data'));

        }

        if($this->request->is('get')){ //do loading

            $this->data = $this->Page->findById($id);
            $this->set('data', $this->data);
        }
    }

    public function admin_deletePage($id = null)
    {
        $this->loadModel('Page.Page');
        if ($this->request->is('get')) { // do saving
            $this->autoRender = false;

            $page = $this->Page->findById( $id );
            if($page['Page']['type']!='core'){
                $this->Page->delete( $id );

                $this->Session->setFlash(__('Page deleted'),'default',
                    array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $this->redirect(array('action'=>'admin_index'));

                Cache::clearGroup('cache_group', '_cache_group_');
            }else{
                $this->Session->setFlash(__("Can't delete Core page"),'default',
                    array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }

    }

    public function admin_createPage()
    {
        $this->loadModel('Page.Page');

		if($this->request->is('post')||$this->request->is('put')){
			$dataSave = array();
			$data = $this->request->data;
			
			foreach($data['Page'] as $key => $dataReceive){
				$dataSave[$key] = $dataReceive;
			}
            $this->Page->savePage($dataSave);
            $dataSave['insertId'] = $this->Page->getLastInsertID();

			$this->set('data', $dataSave);
			$this->set('_serialize', array('data'));
		}
        
    }

    public function admin_getPages()
    {
        // request
        // model
        $this->loadModel('Page.Page');
        $page=$errors = null;

        if ($this->request->is('get')){ //do loading

            $result = $this->Page->find('pages');
            $pages = '';
            foreach ($result as &$data){
                $pages .= "<div data-value=".$data['Page']['id'].">".$data['Page']['title']."</div>";
            }
            $data = array(
                'data' => $pages,
                'error' => $errors,
            );
            $this->set('data', $pages);
            $this->set('_serialize', array('data'));
        }
    }

    public function admin_savePage()
    {
        $this->loadModel('Page.Page');
        $this->loadModel('CoreContent');
        $this->autoRender = false;
        $this->layout = 'ajax';
        $info = $infoAssociated = $update =null;
        $errors = null;
        if($this->request->is('post') || $this->request->is('put')){
            $data =$this->request->data;
            $pageId = $data['pageId'];
            if(isset($data['columnStyle'])){
                $columnStyle = $data['columnStyle'];
                $this->_admin_saveColumn($columnStyle,$pageId);
            }
            
            $removeComponent = isset($data['removeComponent']) ? explode(',', $data['removeComponent']) : array();
            
            foreach ($removeComponent as $block_id){
                if (!empty($block_id)){
                    $this->CoreContent->delete($block_id);
                }
            }
            
            //loop through regions from request
            foreach($data as $key=>$value){
                if($key!='pageId'&& $key !='columnStyle'&& $key !='removeComponent'){
                    //get container at specific region in a page
                    $container = $this->CoreContent->getContainer($pageId,$key);
                    $infoAssociated['Parent'] = array('page_id'=>$pageId,'type'=>'container','name'=>strtolower($key),'parent_id'=>null,'component' => '','column' => 0);
                    //check if container have existed
                    if(!$container){
                        $this->CoreContent->create();
                        $this->CoreContent->save($infoAssociated['Parent']);

                        $this->_saveWidgetLang($this->CoreContent->id);


                        $parent_id = $this->CoreContent->getLastInsertID();
                    }else{
                        $parent_id = $container['CoreContent']['id'];
                    }
                    //loop through contents in container
                    foreach($value as $order => $data){
                        /*** save content ***/
                        $params = null;
                        $plugin = null;
                        $role_access = null;
                        $core_block_title = '';
                        if(isset($data['param'])){
                            $params = json_decode($data['param'],true);
                            foreach($params as $key=>$value){
                                $params[$key] = htmlspecialchars($value);
                            }
                            $core_block_title = $params['title'];
                            $plugin = !empty($params['plugin'])?$params['plugin']:'';
                            $role_access = !empty($params['role_access'])?$params['role_access']:'';
                            $params = json_encode($params);
                        }
                        $core_content = $this->Page->CoreContent->findById($data['id']);
                        if(!$core_content){ //create new widget
                            $infoAssociated['Children'] = array ('page_id'=>$pageId,'type'=>'widget','order'=>$order+1,'name'=>$data['name'],'params'=>$params,'core_block_id'=>$data['core_block_id'],'parent_id'=>$parent_id,'component' => '','column' => 0,'role_access'=>$role_access,'plugin' => $plugin);
                            $this->CoreContent->create();
                            $this->CoreContent->save($infoAssociated['Children']);

                            $i18Model = Moocore::getInstance()->getModel('I18nModel');
                            if(!empty($core_block_title))
                            {
                                $this->_saveWidgetLang($this->CoreContent->id,$core_block_title);
                            }
                        }
                        else{ // update widget
                            $this->_updateWidgetLang($core_content,$core_block_title);
                            $this->Page->saveContent(array('parent_id'=>$parent_id,'params'=>$params,'role_access'=>$role_access,'order'=>$order+1, 'component' => '','column' => 0),$data['id']);
                        }
                        /*** save tabs content ***/
                        if(!empty($data['tabs_content']))
                        {
                            if(empty($data['id']))
                                $tabs_id = $this->CoreContent->id;
                            else
                                $tabs_id = $data['id'];
                            foreach($data['tabs_content'] as $tabs_order => $tabs_data){
                                $tabs_params = null;
                                if(isset($tabs_data['param'])){
                                    $tabs_params = json_decode($tabs_data['param'],true);
                                    $tabs_plugin = null;
                                    foreach($tabs_params as $key=>$value){
                                        $tabs_params[$key] = htmlspecialchars($value);
                                    }
                                    $core_block_title = $tabs_params['title'];
                                    $tabs_plugin = !empty($tabs_params['plugin'])?$tabs_params['plugin']:'';
                                    $tabs_params = json_encode($tabs_params);
                                }
                                if(!$this->Page->CoreContent->findById($tabs_data['id'])){

                                    $infoAssociated['Children'] = array ('page_id'=>$pageId,'type'=>'widget','order'=>$tabs_order+1,'name'=>$tabs_data['name'],'params'=>$tabs_params,'core_block_id'=>$tabs_data['core_block_id'],'parent_id'=>$tabs_id,'component' => '','column' => 0,'plugin' => $tabs_plugin);
                                    $this->CoreContent->create();
                                    $this->CoreContent->save($infoAssociated['Children']);
                                }
                                else{
                                    $this->Page->saveContent(array('parent_id'=>$tabs_id,'params'=>$tabs_params,'order'=>$tabs_order+1, 'component' => '','column' => 0),$tabs_data['id']);
                                }
                            }
                        }

                    }
                }
            }

            $this->Session->setFlash(__('Changes have been saved'),'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            // Clear cache
            $row = $this->Page->find('first', array(
                'conditions' => array('Page.id' => $pageId),
                'recursive'=>1
            ));

            Cache::delete($row['Page']['uri'].".blocks".Configure::read('Config.language'),"1_day");
        }
        echo json_encode($data);
    }

    public function admin_getBlocks()
    {
    }

    public function admin_getContents($id = null)
    {
        $this->loadModel('Page.Page');
		
        if($this->request->is('get')){
            $errors = null;
            $contents = $this->Page->getContent($id);
            foreach ($contents as &$content)
            {
                if (isset($content['children']) && count($content['children']))
                {
                    foreach ($content['children'] as &$child)
                    {
                        if (!$child['CoreContent']['role_access'])
                            $child['CoreContent']['role_access'] = 'all';

                        $params = json_decode($child['CoreContent']['params'],true);
                        $params+=array('role_access'=>$child['CoreContent']['role_access']);
                        $child['CoreContent']['params'] = json_encode($params);
                    }
                }
            }
            $data = array(
                'data' => $contents,
                'error' => $errors,
            );
            $this->set('data', $data);
            $this->set('_serialize', array('data'));
        }
    }
    protected function _admin_saveColumn ($id = null,$pageId = null){
        $this->loadModel('Page.Page');
        $this->loadModel('CoreContent');
        $this->autoRender = false;
        $this->layout = 'ajax';
        $this->Page->updatePageColumn("'$id'",$pageId);

    }
    public function admin_deleteComponent($id = null){
        $this->loadModel('CoreContent');
        $this->autoRender = false;
        $this->layout = 'ajax';

        $component = $this->CoreContent->findById($id);
        if($this->request->is('post')||$this->request->is('put')){
            if(!$this->CoreContent->delete($id))
            {
                echo json_encode(array('error'=>'An error has occurred'));
                return;
            }
        }
        echo json_encode($component);
    }
    public function admin_getPageStyle($id = null){
        $this->loadModel('Page.Page');
        $this->autoRender = false;
        $this->layout = 'ajax';
        $pageStyle = $pageInfo = null;
        if($this->request->is('post') || $this->request->is('put')){
            if($pageInfo = $this->Page->findById($id)){
                $pageStyle = $pageInfo['Page']['layout'];
            }
        }
        echo $pageStyle;
    }

    public function admin_getContentInfo($id = null,$blockId = null){

        $this->loadModel('CoreBlock');
        $this->loadModel('Role');
        $data=$info=$errors=$blockFormat = null;
        $roles = $this->Role->find('all');
        $tmp = array('all'=>__('Everyone'));
        foreach ($roles as $role)
        {
            $tmp[$role['Role']['id']] = $role['Role']['name'];
        }

        if($this->request->is('get')){ //do loading
            $blockFormat = $this->CoreBlock->findById($blockId,array('params','path_view','plugin','name','id'));

                $data =array(array('CoreContent'=>array(
                        'contentId' => $id,
                        'blockId' => $blockFormat['CoreBlock']['id'],
                        'blockName' => $blockFormat['CoreBlock']['name'],
                        'blockFormat'=> $blockFormat['CoreBlock']['params'],
                        'blockPathView' => $blockFormat['CoreBlock']['path_view']
                    )

                ));

            $this->data = $data;
            $this->set('data', $data);
            $this->set('plugin', $blockFormat['CoreBlock']['plugin']);
            $this->set('roles', $tmp);
        }
    }
    public function admin_filter(){
        $this->loadModel('CoreBlock');
        $this->autoRender = false;
        $this->layout = 'ajax';
        $result = '';
        $aa = '';
        $display = '';
        if($this->request->is('post') || $this->request->is('put')){
            if($this->request->data['value'] != 'All'){
                $set = "(CoreBlock.group,'".$this->request->data['value']."')";
                $result = $this->CoreBlock->find('all',array(
                    'conditions'=>array('FIND_IN_SET'.$set)
                ));
            }else{
                $result = $this->CoreBlock->find('all');
            }
            foreach($result as $value){
                if($value['CoreBlock']['restricted'] != ''){
                    $display = "style='display:none' data-uri='".$value['CoreBlock']['restricted']."'";
                }
                $aa.= '<li class="dd-item ui-draggable" data-id="'.$value['CoreBlock']['id'].'"'.$display.'><div class="dd-handle">'.$value['CoreBlock']['name'].'</div></li>';
                $display ='';
            }
           echo $aa;
        }
    }

    private function _saveWidgetLang($id,$title = ''){
        $langs = $this->Language->getLanguages();
        foreach($langs as $key => $lang)
        {
            $this->CoreContent->id = $id;
            $this->CoreContent->locale = $key;
            $this->CoreContent->saveField('core_block_title', $title);

        }
    }
    private function _updateWidgetLang($core_content = null,$title = null)
    {
        $current_lang = Configure::read('Config.language');
        foreach($core_content['nameTranslation'] as $lang){
            if($lang['locale'] == $current_lang)
            {
                $this->CoreContent->id = $core_content['CoreContent']['id'];
                $this->CoreContent->locale = $current_lang;
                $this->CoreContent->saveField('core_block_title', $title);
            }
        }
    }
}