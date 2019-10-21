<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('PageAppModel', 'Page.Model');
class Page extends PageAppModel
{
    /*public $actsAs = array(
        'Acitivity' => array(
            'type' => APP_USER,
            'action_afterCreated'=>'page_create',
            'item_type'=>APP_PAGE,
            'query'=>1,
            'params' => 'item'
        ),
        'Containable'
    );*/
	
	public $mooFields = array('title', 'href', 'plugin', 'type', 'url');

    public $actsAs = array(
        'Translate' => array(
            'title' => 'titleTranslation',
            'content' => 'contentTranslation'
        )
    );

    public $recursive = 2;
    private $_default_locale = 'eng' ;

    function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('Config.language');
    }

    public $validate = array(   
                        'title' =>   array(   
                            'rule' => 'notBlank',
                            'message' => 'Title is required'
                        ),
                        'alias' =>   array(   
                            'rule' => 'notBlank',
                            'message' => 'Alias is required'
                        ),
                        /*'content' =>   array(
                            'rule' => 'notBlank',
                            'message' => 'Content is required'
                        ),*/


    );
    
    public $order = 'Page.weight asc';
    
	public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (isset($row['alias']))
    		return $request->base.'/pages/'.($row['alias']);
    	else 
    		return '';
    }
    
    public function loadMenuPages( $role_id )
    {        
        $pages = Cache::read('pages_' . $role_id);          
                
        if (empty($pages))
        {
            $pages = $this->find('all', array( 'conditions' => array('menu' => 1) ) );
    
            foreach ( $pages as $key => $page )
            {
                $permissions = explode(',', $page['Page']['permission']);
                
                if ( $page['Page']['permission'] !== '' && !in_array( strval($role_id), $permissions, true ) )
                    unset($pages[$key]);
            }
            
            Cache::write('pages_' . $role_id, $pages, '_cache_group_');
        }
        
        return $pages;
    }
    ///////==========/////////////
    //public $tablePrefix = 'ms_';
    public $belongsTo = array(
        'MyCoreTheme'=>array(
            'className' => 'Theme',
            'foreignKey' => 'theme_id',
            'counterCache' => true
        )
    );
    public $hasMany = array(
        'CoreContent' => array(
            'className' => 'CoreContent',
            'foreignKey' => 'page_id',
            'dependent' => true,
        )
    );
    public $findMethods = array('pages' => true);

    public function getCorePageByTheme($id = null){
        $site_core_pages = $this->find('list', array(
                'conditions' => array('Page.theme_id'=>$id),
            )
        );
        return $site_core_pages;
    }
    public function getCorePageList(){//get a list of core page to use in select box
        $site_core_pages = Cache::read('site_core_pages_list');

        if ( empty($site_core_pages) )
        {
            $site_core_pages = $this->find('list', array( 'fields' => array( 'Page.id', 'Page.title')));
            Cache::write('site_core_pages_list', $site_core_pages);
        }

        return $site_core_pages;
    }
    public function getCorePageAll(){
        $site_core_pages = Cache::read('site_core_pages_all');

        if ( empty($site_core_pages) )
        {
            $site_core_pages = $this->find('list', array( 'fields' => array( 'Page.id', 'Page.title')));
            Cache::write('site_core_pages_al', $site_core_pages);
        }

        return $site_core_pages;
    }

    public function getCorePage($name = null, $pageId = null){
        return $this->find('first',array(
            'conditions' => array(
                'OR' => array(
                    'name' => $name,
                    'page_id' => $pageId,
                )
            ),
        ));
    }

    protected  function _findPages($state, $query, $results = array()){
        if($state === 'before'){
            /*if(!$query['conditions']['CorePage.id']){
                throw new NotFoundException(_('Invalid page'));
            }*/
            $this->contain();//this or method unbindModel below
            /*$this->unbindModel(array(
                'belongsTo' => array('MyCoreTheme'),
                'hasMany' => array('CoreContent')
            ));*/
            //$query['limit'] = 1;
            return $query;
        }
        return $results;
    }

    public function getContent($id=null){//to get only contents of a pages, not thing else
        //if(!$id){
        //    throw new NotFoundException(_('Invalid Id'));
        //}
        $contents =  $this->CoreContent->getCoreContentByPage($id);
        //$contents['Children']
        return $contents;
    }

    public function savePage($data,$id = null){
        Cache::delete('site_core_pages_list');
        Cache::delete('site_core_pages_all');
        if($id !== null){//it's an update
            $this->id = $id;
        }
        else{
            $this->create();
        }
        $this->save($data);
    }

    public function beforeDelete($cascade = true){
        if(!$this->id){
            throw new NotFoundException(_('Invalid page'));
            return false;
        }
        Cache::delete('site_core_pages_list');
        Cache::delete('site_core_pages_all');
        return true;
    }

    public function afterSave($created, $options = array()) {
        Cache::delete('site_core_pages_list');
        Cache::delete('site_core_pages_all');
        if (!empty($this->data['Page']['alias'])){
            Cache::delete("pages.".$this->data['Page']['alias'].".blocks");
        }
        Cache::clearGroup('page');
        if($created === true){//new record was added, otherwise it's an update

        }
    }
    public function afterDelete(){
        Cache::clearGroup('page');
    }

    public function saveContent($data, $id = null){
        if($id !== null){
            $this->CoreContent->id = $id;
            $this->CoreContent->save($data);
        }
        else{
            $this->CoreContent->savePageContent($data);
        }


        /*$this->CoreContent->save($data, array(
            'conditions' => array('CoreContent.core_page_id'=>$pageId)
        ));*/
    }

    public function clearContent($id = null){
        Cache::delete('site_core_contents_list');
        Cache::delete('site_core_contents_all');
        $this->CoreContent->deleteAll(array('CoreContent.page_id'=>$id), false, false);
        $this->CoreContent->updateCounterCache(array('page_id'=>$id));
    }
    public function updatePageColumn($data = null, $pageId = null, $region = null){
        $this->unbindModel(array('belongsTo' => array('MyCoreTheme')));
        $this->updateAll(array('layout' => $data),array('Page.id' => $pageId));
    }

	public function getPageById($id) {
        $category = $this->findById($id);
        if (empty($category)) {
            $this->locale = $this->_default_locale;
            $category = $this->findById($id);
        }
        return $category ;
    }
}
