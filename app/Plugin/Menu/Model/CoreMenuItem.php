<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MenuAppModel', 'Menu.Model');

class CoreMenuItem extends MenuAppModel {

    public $actsAs = array('Tree', 'Translate' => array('name' => 'nameMenuTranslation'));
    public $recursive = 2;

    function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('Config.language');
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
