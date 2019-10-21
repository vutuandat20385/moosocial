<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class CoreContentsController extends AppController
{
    public $helpers  = array('Form','Html');
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => 1));
    }

    public function admin_ajax_translate($id) {

        if (!empty($id)) {
            $core_content = $this->CoreContent->getCoreContentById($id);
            $this->set('core_content', $core_content);
            $this->set('languages', $this->Language->getLanguages());
        } else {
            // error
        }
    }

    public function admin_ajax_translate_save() {

        $this->autoRender = false;
        if ($this->request->is('post') || $this->request->is('put')) {
            if (!empty($this->request->data)) {
                // we are going to save the german version
                $this->CoreContent->id = $this->request->data['id'];
                foreach ($this->request->data['name'] as $lKey => $sContent) {
                    $this->CoreContent->locale = $lKey;
                    if ($this->CoreContent->saveField('core_block_title', $sContent)) {
                        $response['result'] = 1;
                    } else {
                        $response['result'] = 0;
                    }
                }
                $response['lang'] = $this->request->data['name'];
            } else {
                $response['result'] = 0;
            }
        } else {
            $response['result'] = 0;
        }
        echo json_encode($response);
    }

}
