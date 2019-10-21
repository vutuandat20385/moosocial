<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('ErrorHandler','Error');
class AppExceptionHandler extends ErrorHandler {
    public static function handle($error) {
        if ($error instanceof MissingTableException) {
            $attributes = $error->getAttributes();

            if(isset($attributes['table']) && strpos($attributes['table'],'setting_groups') !== false){
                $request = Router::getRequest();
                if ($request) {
                    $uri = empty($request->params['controller']) ? "" : $request->params['controller'];
                    $uri .= empty($request->params['action']) ? "" : "." . $request->params['action'];

                    $uri_filter = array('home.index','users.index','blogs.index','photos.index','videos.index','topics.index','groups.index','events.index');
                    // Upgrading  process
                    if(in_array($uri,$uri_filter)){
                        header('Location: '. Router::url("/upgrade", true));die();
                    }
                }
            }
        }
        parent::handleException($error);

    }

}