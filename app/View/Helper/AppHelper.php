<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('Helper', 'View');


class AppHelper extends Helper {
    public function getUri($params = 'here'){
        if($params == 'here'){
            $request = Router::getRequest();
            $uri = empty($request->params['controller']) ? "" : $request->params['controller'];
            $uri .= empty($request->params['action']) ? "" : "." . $request->params['action'];
            return $uri;
        }
    }
}
