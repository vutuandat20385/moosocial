<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class BlogUploadController extends BlogAppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->autoRender = false;
    }

    public function avatar() {
        $uid = $this->Auth->user('id');

        if (!$uid)
            return;

        // save this picture to album
        $path = 'uploads' . DS . 'tmp';
        $url = 'uploads/tmp/';

        $this->_prepareDir($path);

        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();
        
        $maxFileSize = MooCore::getInstance()->_getMaxFileSize();
        
        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions, $maxFileSize);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $result = $uploader->handleUpload($path);

        if (!empty($result['success'])) {
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
                        
            $ext = $this->_getExtension($result['filename']);
            if(in_array(strtolower($ext), array('jpg', 'jpeg')))
            {
            	@ini_set('memory_limit', '500M');
            	$photo = PhpThumbFactory::create($path . DS . $result['filename']);
            	$this->_rotateImage($photo, $path . DS . $result['filename']);
            }
            
            $result['thumb'] = FULL_BASE_URL . $this->request->webroot . $url . $result['filename'];
            $result['file'] = $path . DS . $result['filename'];
        }
        // to pass data through iframe you will need to encode all html tags
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }

    public function images() {
        $error = false;

        $uid = $this->Auth->user('id');

        if (!$uid) {
            return;
        }

        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();
        
        $maxFileSize = MooCore::getInstance()->_getMaxFileSize();

        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions, $maxFileSize);

        $path = 'uploads' . DS . 'tmp';

        $result = $uploader->handleUpload($path);

        if (!empty($result['success'])) {
        	
        	App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));        	
        	$ext = $this->_getExtension($result['filename']);
        	if(in_array(strtolower($ext), array('jpg', 'jpeg')))
        	{
        		@ini_set('memory_limit', '500M');
        		$photo = PhpThumbFactory::create($path . DS . $result['filename']);
        		$this->_rotateImage($photo, $path . DS . $result['filename']);
        	}
        	
            $this->loadModel('Photo.Photo');
            $this->Photo->create();
            $this->Photo->set(array(
                'target_id' => 0,
                'type' => 'Blog',
                'user_id' => $uid,
                'thumbnail' => $path . DS . $result['filename']
            ));
            $this->Photo->save();

            $photo = $this->Photo->read();

            $view = new View($this);
            $mooHelper = $view->loadHelper('Moo');
            $result['photo_id'] = $photo['Photo']['id'];
            $result['thumb'] = $mooHelper->getImageUrl($photo, array('prefix' => '450'),true);
            $result['large'] = $mooHelper->getImageUrl($photo, array('prefix' => '1500'),true);
        }

        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }

    public function _getExtension($filename = null) {
        $tmp = explode('.', $filename);
        $re = array_pop($tmp);
        return $re;
    }

    private function _prepareDir($path) {
        $path = WWW_ROOT . $path;

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            file_put_contents($path . DS . 'index.html', '');
        }
    }

}

?>