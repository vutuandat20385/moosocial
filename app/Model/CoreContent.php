<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class CoreContent extends AppModel{
    //public $tablePrefix = 'ms_';
    public $belongsTo = array(
        'MyCorePage' => array(
            'className' => 'Page.Page',
            'foreignKey' => 'page_id',
            'counterCache' => true
        ),
        'Parent' => array(
            'className' => 'CoreContent',
            'foreignKey' => 'parent_id',
            'counterCache' => true
        )
    );
    public $hasMany = array(
        'Children' => array(
            'className' => 'CoreContent',
            'foreignKey' => 'parent_id',
          
        )
    );
    private $parent_id = null;
    public $findMethods = array('child' => true,'parent'=> true);
    public $actsAs = array(
        'Tree',
        'Translate' => array('core_block_title' => 'nameTranslation')
    );

    public $recursive = 1;
    private $_default_locale = 'eng' ;
    public function setLanguage($locale) {
        $this->locale = $locale;
    }

    function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('Config.language');
    }

    public function getCoreContentList(){//get a list of core content to use in selecbox
        $site_core_contents = Cache::read('site_core_contents_list');
        if ( empty($site_core_contents) )
        {
            $site_core_contents = $this->find('list', array( 'fields' => array( 'CoreContent.id', 'CoreContent.name')));
            Cache::write('site_core_content_list', $site_core_contents);
        }
        return $site_core_contents;
    }

    public function getCoreContentByPage($id =null){
        $this->unbindModel(array('belongsTo' => array('Parent','MyCorePage')));
		$core_contents = $this->find('threaded',array(
            'conditions' => array(
                'CoreContent.page_id' => $id,
            ),
            'order' => 'order ASC',
        ));

        return $core_contents;
    }
    public function getCoreContentByPageName($name =null){
        $this->unbindModel(array('belongsTo' => array('Parent','MyCorePage')));
        $core_contents = $this->find('threaded',array(
            'conditions' => array(
                'CoreContent.name' => $name,
            ),

            'recursive' => 2,
        ));
        return $core_contents;
    }
    public function getContainer($id,$container){
        $this->unbindModel(array('belongsTo' => array('Parent','MyCorePage')));
        $core_contents = $this->find('first',array(
            'conditions' => array(
                'CoreContent.page_id' => $id,
                'CoreContent.name' => $container
            )
        ));
        return $core_contents;
    }

    public function getCoreContentAll(){//get all info about all content;
        $site_core_contents = Cache::read('site_core_contents_all');
        if ( empty($site_core_contents) )
        {
            $site_core_contents = $this->find('all');
            Cache::write('site_core_content_all', $site_core_contents);
        }
        return $site_core_contents;
    }

    public function updateParentCoreContent($id =null ,$data = null){
        Cache::delete('site_core_contents_list');
        Cache::delete('site_core_contents_all');
        $this->id = $id;
        return $this->save($data, array(
            'validate'=>true,
            'fieldList'=>array(
                'parent_id' => $data['Post']['parent_id'],
                'order' => $data['Post']['order'],
                'params' => $data['Post']['params'],
            ),
        ));
    }

    public function getAContent($id = null){
        $aContent = $this->find('first',array(
            'conditions'=>array('CoreContent.id'=>$id)
        ));
        return $aContent;
    }

    public function addCoreContent($data){
        Cache::delete('site_core_contents_list');
        Cache::delete('site_core_contents_all');
        $this->create();
        return $this->save($data,array(
            'validate' => true,
            'fieldList' => array('page_id','type','name','parent_id','order','params'),
        ));
    }

    public function getContent($id){
        $this->unbindModel(array('belongsTo' => array('Parent','MyCorePage')));
        return $this->find('all',array(
            'conditions' => array('page_id'=>$id),
        ));
    }

    protected function _findChild($state, $query, $results = array()){
        if($state === 'before'){
            $query['conditions']['CoreContent.parent_id'] = $query['parent']['CoreContent.id'];

            $this->unbindModel(array(
                'belongsTo' => array('MyCorePage','Parent'),
            ));
            return $query;
        }
        return $results;
    }
    protected function _findParent($state, $query, $results = array()){
        if($state === 'before'){
            $parent_id = $this->find('first',array(
                'conditions' => array('CoreContent.id' => $query['child']['CoreContent.id']),
                'fields' => array('CoreContent.parent_id')
            ));
            $query['conditions']['CoreContent.id'] = $parent_id['CoreContent']['parent_id'];
            $this->unbindModel(array(
                'belongsTo' => array('MyCorePage','Parent'),
            ));
            return $query;
        }
        return $results;
    }
    public function beforeDelete($cascade = true){
        if(!$this->id){
            throw new NotFoundException(_('Invalid content'));
            return false;
        }

        Cache::delete('site_core_contents_list');
        Cache::delete('site_core_contents_all');
        Cache::delete('row.header');
        Cache::delete('row.footer');
        Cache::delete('users.index.blocks');
        return true;
    }
    public function afterDelete(){
        return 'Delete done';
    }

    public function deleteChild($id){
        if(!$id){
            throw new NotFoundException(_('Invalid content'));
            //return false;
        }
        $this->deleteAll(array('CoreContent.parent_id'=>$id), false, false);
        $this->updateCounterCache(array('parent_id'=>$id));
    }
    
    public function afterSave($created, $options = array()) {
        $row = $this->findById($this->getID());

        Cache::delete('row.header');
        Cache::delete('row.footer');
        Cache::clearGroup('page');

    }
    public function getCoreContentById($id)
    {
        $core_content = $this->findById($id);
        if(empty($core_content))
        {
            $this->locale = $this->default_locale;
            $core_content = $this->findById($id);
        }
        return $core_content;
    }
}