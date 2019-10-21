<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class AuthHelper extends AppHelper {

    public function user($field){
        return (empty($this->_View->viewVars['cuser'])) ? null:$this->_View->viewVars['cuser'][$field];
    }
}

?>
