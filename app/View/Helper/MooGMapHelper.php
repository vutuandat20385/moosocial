<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooGMapHelper extends AppHelper{
    public function loadGoogleMap($address = '', $width = 530, $height = 300,$isAjaxModal = false)
    {
        $this->_View->viewVars['address'] = $address;
        $this->_View->viewVars['isAjaxModal'] = $isAjaxModal;
        $this->_View->loadLibarary('googleMap');
        return '<div id="map_canvas" style="width:'.$width.'px; height:'.$height.'px"></div>';

    }
}