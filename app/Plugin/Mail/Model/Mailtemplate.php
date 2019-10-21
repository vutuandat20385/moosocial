<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Mailtemplate extends AppModel {	
	 public $actsAs = array(
        'Translate' => array(            
            'content' => 'contentTranslation',
	 		'subject' => 'subjectTranslation'
        )
    );
    
	function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->locale = Configure::read('core.default_language');
    }
}
