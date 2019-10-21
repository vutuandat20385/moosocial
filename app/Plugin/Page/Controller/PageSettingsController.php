<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PageSettingsController extends PageAppController{
    public $components = array('QuickSettings');

    public function admin_index()
    {
        $this->QuickSettings->run($this, array("Page"));
        $this->set('title_for_layout', __('Pages Setting'));
    }
}