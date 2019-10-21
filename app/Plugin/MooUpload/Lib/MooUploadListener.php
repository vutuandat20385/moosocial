<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class MooUploadListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'AppController.doBeforeFilter' => 'doBeforeFilter',

        );
    }

    public function doBeforeFilter($event)
    {
        $e = $event->subject();
        // max upload file size
        $post_max_size = ini_get('post_max_size');
        $upload_max_filesize = ini_get('upload_max_filesize');
        $file_max_upload = (int) $post_max_size > (int) $upload_max_filesize ? $upload_max_filesize : $post_max_size;
        $e->set(compact('file_max_upload'));

    }


}