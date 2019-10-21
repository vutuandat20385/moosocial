<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PhotoUploadController extends PhotoAppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->autoRender = false;
    }

    public function album($type, $target_id, $save_original = 0) {
        $uid = $this->Auth->user('id');
        
        if (!$type || !$target_id || !$uid){
            return;
        }
        
        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();
        
        $maxFileSize = MooCore::getInstance()->_getMaxFileSize();

        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions, $maxFileSize);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $path = 'uploads' . DS . 'tmp';
        $url = 'uploads/tmp/';
        $this->_prepareDir($path);
        $result = $uploader->handleUpload(WWW_ROOT . $path);

        if (!empty($result['success'])) {
            // resize image
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

            $ext = $this->_getExtension($result['filename']);
            if(in_array(strtolower($ext), array('png','jpg', 'jpeg')))
            {
            	@ini_set('memory_limit', '500M');
            	$photo = PhpThumbFactory::create($path . DS . $result['filename']);
            	$this->_rotateImage($photo, $path . DS . $result['filename']);
            }


            if ($save_original) {
                $original_photo = $url . $result['filename'];
                $medium_photo = 'm_' . $result['filename'];
            } else {
                $original_photo = '';
                $medium_photo = $result['filename'];
            }

            $photo->resize(PHOTO_WIDTH, PHOTO_HEIGHT)->save($path . DS . $medium_photo);

            $result['photo'] = $path . DS . $medium_photo;
        }

        // to pass data through iframe you will need to encode all html tags
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }
    
    protected function _prepareDir($path)
    {
        $path = WWW_ROOT . $path;

        if (!file_exists($path))
        {
            mkdir($path, 0755, true);
            file_put_contents( $path . DS . 'index.html', '' );
        }
    }

}
