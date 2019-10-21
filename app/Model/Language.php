<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Language extends AppModel {

    public $validate = array(
        'name' => array(
            'rule' => 'notBlank',
            'message' => 'Name is required'
        ),
        'key' => array(
            'key' => array(
                'rule' => 'alphaNumeric',
                'allowEmpty' => false,
                'message' => 'Key must only contain letters and numbers'
            ),
            'uniqueKey' => array(
                'rule' => 'isUnique',
                'message' => 'Key already exists'
            )
        )
    );
    public $order = 'Language.name asc';

    public function getLanguages() {
        $site_langs = Cache::read('site_langs');

        if (empty($site_langs)) {
            $site_langs = $this->find('list', array('fields' => array('Language.key', 'Language.name')));
            Cache::write('site_langs', $site_langs);
        }

        return $site_langs;
    }

    public function getLanguageKeys() {
        $site_langs = $this->getLanguages();
        return array_keys($site_langs);
    }

    public function getRtlOption(){
        $site_rtl = Cache::read('site_rtl');
        if(empty($site_rtl)){
            $site_rtl = $this->find('all');
            Cache::write('site_rtl', $site_rtl);
        }
        return $site_rtl;
    }

}
