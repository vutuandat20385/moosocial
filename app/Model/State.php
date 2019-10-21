<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
class State extends AppModel {

    public $order = 'State.order asc, State.name asc';
    public $validate = array(
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
    
    
    public $belongsTo = array(
        'Country' => array(
            'className' => 'Country',
            'foreignKey' => 'country_id'
        )
    );
    
    public function setLanguage($locale) {
        $this->locale = $locale;
    }

    function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('Config.language');
    }
    
    public function getItemById($id) {
        $state = $this->findById($id);
        if (empty($state)) {            
            $state = $this->findById($id);
        }
        return $state ;
    }
    public function getStateByCountryId($country_id) {
        $states = $this->find('all', array('conditions' => array('State.country_id' => $country_id)));
        return $states ;
    }
     public function getStateSelect($country_id) {
        $data = array();
        $states = $this->find('all', array('conditions' => array('State.country_id' => $country_id)));
        if(!empty($states)) {
            foreach($states as  $state) {
                foreach($state['nameTranslation'] as $state_transalte) {
                    if($this->locale == $state_transalte['locale']) {
                        $data[$state_transalte['foreign_key']] = $state_transalte['content'];
                    }
                }
            }
        }
        return $data ;
    }
    public function getStateById($id) {
        $state = $this->findById($id);
        if(!empty($state['nameTranslation'])) {
            foreach($state['nameTranslation'] as $translate) {
                if($this->locale == $translate['locale']) {
                    $state['State']['nameTranslation'] = $translate['content'];
                }
            } 
        }
        return $state;
    }
    public function afterSave($created, $options = array()){

    }
    public function beforeDelete($cascade = true){

    }

}
