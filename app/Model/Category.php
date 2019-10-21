<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Category extends AppModel {

    public $order = 'Category.weight asc';
    public $validate = array(
        'name' => array(
            'rule' => 'notBlank',
            'message' => 'Name is required'
        ),
        'type' => array(
            'rule' => 'notBlank',
            'message' => 'Type is required'
        )
    );
    public $actsAs = array(
        'Translate' => array('name' => 'nameTranslation')
    );
    public $recursive = 2;
    private $_default_locale = 'eng' ;
    public function setLanguage($locale) {
        $this->locale = $locale;
    }

    function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('Config.language');
    }

    /*
     * Get all categories based on $type
     * @param string $type
     * @return array $categories
     */

    public function getCategories($type, $role_id = null) {

        $categories = $this->find('threaded', array('conditions' => array('Category.type' => $type, 'Category.active' => 1)));
        $i = 0;
        foreach ($categories as &$cat) {
            $removed = false;

            if (!empty($role_id) && !empty($cat['Category']['create_permission'])) {
                $roles = explode(',', $cat['Category']['create_permission']);

                if (!in_array($role_id, $roles) && $role_id != 1)
                    $removed = true;
            }

            if ($removed) {
                unset($categories[$i]);
            }
            
            $j = 0;
            foreach ($cat['children'] as &$subcat){
                $sub_removed = false;
                
                if (!empty($role_id) && !empty($subcat['Category']['create_permission'])) {
                    $sub_roles = explode(',', $subcat['Category']['create_permission']);
                    
                    if (!in_array($role_id, $sub_roles) && $role_id != 1)
                        $sub_removed = true;
                }
                
                if ($sub_removed) {
                    unset($categories[$i]['children'][$j]);
                }
                $j++;
            }
            
            $i++;
            
        }

        return $categories;
    }
    public function getCatsDefault($condition) {
        $this->locale = $this->_default_locale;
        $this->bindModel(
                array('belongsTo' => array('ParentCategory' => array(
                            'className' => 'Category',
                            'foreignKey' => 'parent_id'
        ))));
        $categories = $this->find('all', $condition);
        return $categories ;
        
    }
    public function getCats($condition = array()) {
        $this->bindModel(
                array('belongsTo' => array('ParentCategory' => array(
                            'className' => 'Category',
                            'foreignKey' => 'parent_id'
        ))));
        $categories = $this->find('all', $condition);
        if(empty($categories)){
            $categories = $this->getCatsDefault($condition);
        }
        if (!empty($categories)) {
            foreach ($categories as $key => $cat) {
                if (empty($cat['ParentCategory']['id'])) {
                    $aResult[$key]['ParentCategory'] = $cat['ParentCategory'];
                } else {
                    foreach ($cat['ParentCategory']['nameTranslation'] as $parentCat) {
                        if ($parentCat['locale'] == $this->locale) {
                            $categories[$key]['Parent']['name'] = $parentCat['content'];
                            break;
                        }
                    }
                }
            }
        }
        return $categories;
    }
    public function getCatById($id) {
        $category = $this->findById($id);
        if (empty($category)) {
            $this->locale = $this->_default_locale;
            $category = $this->findById($id);
        }
        return $category ;
    }
    /*
     * Get all categories for drop down list
     * @param string $type
     * @return array $categories
     */

    public function getCategoriesList($type, $role_id = null) {

        $categories = $this->find('threaded', array('conditions' => array('Category.type' => $type, 'Category.active' => 1)));
        $re = array();
        foreach ($categories as $cat) {
            $removed = false;

            if (!empty($role_id) && !empty($cat['Category']['create_permission'])) {
                $roles = explode(',', $cat['Category']['create_permission']);

                if (!in_array($role_id, $roles) && $role_id != 1)
                    $removed = true;
            }

            if (!$removed) {
                if ($cat['Category']['header']) {
                    $subs = array();
                    foreach ($cat['children'] as $subcat){
                        $subNameTranslation = $subcat['nameTranslation'];
                        foreach ($subNameTranslation as $item){
                            if ($item['locale'] == Configure::read('Config.language')){
                                $subcat['Category']['name'] = $item['content'];
                            }
                        }
                        $subs[$subcat['Category']['id']] = $subcat['Category']['name'];
                    }
                    
                    $nameTranslation = $cat['nameTranslation'];
                    foreach ($nameTranslation as $item){
                        if ($item['locale'] == Configure::read('Config.language')){
                            $cat['Category']['name'] = $item['content'];
                        }
                    }
                    $re[$cat['Category']['name']] = $subs;
                } else{
                    $nameTranslation = $cat['nameTranslation'];
                    foreach ($nameTranslation as $item){
                        if ($item['locale'] == Configure::read('Config.language')){
                            $re[$cat['Category']['id']] = $item['content'];
                        }
                    }
                }
            }
        }

        return $re;
    }
    public function afterSave($created, $options = array()){

    }
    public function beforeDelete($cascade = true){

    }

    public function getCategoriesListItem($type) {
    	
    	$categories = $this->find('threaded', array('conditions' => array('Category.type' => $type, 'Category.active' => 1)));
    	
    	$re = array();
    	foreach ($categories as $cat) {
    		if ($cat['Category']['header']) {
    			$subs = array();
    			foreach ($cat['children'] as $subcat){
    				$subNameTranslation = $subcat['nameTranslation'];
    				foreach ($subNameTranslation as $item){
    					if ($item['locale'] == Configure::read('Config.language')){
    						$subcat['Category']['name'] = $item['content'];
    					}
    				}
    				$re[$subcat['Category']['id']] = $subcat['Category']['name'];
    			}
    		} else{
    			$nameTranslation = $cat['nameTranslation'];
    			foreach ($nameTranslation as $item){
    				if ($item['locale'] == Configure::read('Config.language')){
    					$re[$cat['Category']['id']] = $item['content'];
    				}
    			}
    		}
    	}
    	
    	return $re;
    }
}
