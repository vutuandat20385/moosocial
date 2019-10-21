<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MenuAppModel', 'Menu.Model');

class CoreMenu extends MenuAppModel
{
    public $recursive = 2;

    public $hasMany = array('CoreMenuItem' => array(
        'className' => 'CoreMenuItem',
        'foreignKey' => 'menu_id',
        'order' => 'CoreMenuItem.menu_order ASC',
        'conditions' => array('CoreMenuItem.is_active' => true),
        'dependent' => true
    ));
    
    public function getMenuByAlias($alias){
        $menus = array();
        $menus = $this->find('first', array('conditions' => array('alias' => $alias)));
        
        foreach ($menus['CoreMenuItem'] as $key => $menuItem) {
            if (!empty($menuItem['nameMenuTranslation'])){    
                foreach ($menuItem['nameMenuTranslation'] as $parentCat) {
                    if ($parentCat['locale'] == Configure::read('Config.language')) {
                        $menus['CoreMenuItem'][$key]['name'] = $parentCat['content'];
                        break;
                    }
                }
            }
        }
        
        return $menus;
    }
    
    public function getMenuById($menu_id){
        $menus = array();
        $menus = $this->find('first', array('conditions' => array('id' => $menu_id)));
        
        foreach ($menus['CoreMenuItem'] as $key => $menuItem) {
            if (!empty($menuItem['nameMenuTranslation'])){   
                foreach ($menuItem['nameMenuTranslation'] as $parentCat) {
                    if ($parentCat['locale'] == Configure::read('Config.language')) {
                        $menus['CoreMenuItem'][$key]['name'] = $parentCat['content'];
                        break;
                    }
                }
            }
        }
        
        return $menus;
    }


    public function afterSave($created, $options = array()) {
        parent::afterSave($created, $options);
        Cache::clearGroup('menu');
    }
    
    public function afterDelete() {
        parent::afterDelete();
        Cache::clearGroup('menu');
    }


}
