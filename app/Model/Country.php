<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
class Country extends AppModel {

    public $order = 'Country.order ASC, Country.name ASC';
    public $validate = array(
        'country_iso' => array(
            'rule' => 'notBlank',
            'message' => 'ISO is required'
        ),
        'name' => array(
            'rule' => 'notBlank',
            'message' => 'Name is required'
        )
    );
    public $actsAs = array(
        'Translate' => array('name' => 'nameTranslation')
    );
    public $recursive = 1;
    private $_default_locale = 'eng' ;
    
    public $hasMany = array(
        'State' => array(
            'className' => 'State',
            'foreignKey' => 'country_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ),
    );
    
    public function setLanguage($locale) {
        $this->locale = $locale;
    }

    function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('Config.language');
    }
    
    public function getItemById($id) {
        $country = $this->findById($id);
        if (empty($country)) {
            $country = $this->findById($id);
        }
        return $country ;
    }
    public function getCountries() {
        $countries = $this->find('all', array('conditions' => array()));
        return $countries ;
    }
    public function getCountrySelect() {        
        $data = array();
        $countries = $this->find('all', array('conditions' => array()));
        
        if(!empty($countries)) {
            foreach($countries as  $country) {
                foreach($country['nameTranslation'] as $country_transalte) {
                    if($this->locale == $country_transalte['locale']) {
                        $data[$country_transalte['foreign_key']] = $country_transalte['content'];
                    }
                }
            }
        }
        return $data ;
    }
    public function getCountryById($id) {
        $data = array();
        $country = $this->findById($id);
        
        if(!empty($country['nameTranslation'])) {
            foreach($country['nameTranslation'] as $translate) {
                if($this->locale == $translate['locale']) {
                    $country['Country']['nameTranslation'] = $translate['content'];
                }
            } 
        }
        return $country;
    }
    public function afterSave($created, $options = array()) {

    }
    public function beforeDelete($cascade = true){

    }

}
