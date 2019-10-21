<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class UploadController extends AppController 
{
	public $uses = array();

	public function beforeFilter()
	{
            parent::beforeFilter();
		$this->autoRender = false;
      
	}

	public function thumb()
	{
            $uid = $this->Auth->user('id');

            if (!$uid || ( !$_POST['x'] && !$_POST['y'] && !$_POST['w'] && !$_POST['y'] )){
                return;
            }

            $this->loadModel( 'User' );
            $user = $this->User->findById($uid);


            if ( empty( $user['User']['avatar'] ) ){
                return;
            }

            $path = WWW_ROOT . 'uploads' . DS . 'users' . DS . 'avatar' . DS . $user['User']['id'];

            $ext = $this->_getExtension($user['User']['avatar']);
            $thumbname = md5(microtime()) . '.' . $ext;

            $thumbloc = WWW_ROOT . 'uploads' . DS  . 'tmp' . DS . $thumbname;
            
            $thumbloc_temp = WWW_ROOT . 'uploads' . DS  . 'tmp' . DS . 'temp_' . $thumbname;

            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

            $myCurrentAvatar = $path . DS . '600_' . $user['User']['avatar'];
            // Hacking for cdn
            if(!file_exists($myCurrentAvatar)){
                $view = new View($this);
                $url = $view->Moo->getImageUrl($user,array('prefix' => '600'));
                file_put_contents($myCurrentAvatar, fopen($url, 'r'));
            }
            // End hacking for cdn
            $thumb = PhpThumbFactory::create($myCurrentAvatar, array('jpegQuality' => 100));
            $thumb->crop($_POST['x'], $_POST['y'], $_POST['w'], $_POST['h'])->resize(AVATAR_THUMB_WIDTH, AVATAR_THUMB_HEIGHT)->save($thumbloc);		

            if (file_exists($path . DS . '600_' . $user['User']['avatar'])){
                copy($path . DS . '600_' . $user['User']['avatar'], $thumbloc_temp);
            }
            
            // update user pic in db
            $this->User->id = $uid;
            $this->User->save( array( 'avatar' => 'uploads' . DS  . 'tmp' . DS . $thumbname ) );
            
            // keep original file for cropping
            if (file_exists($thumbloc_temp)){
                copy($thumbloc_temp, $path . DS . '600_' . $thumbname);
                unlink($thumbloc_temp);
            }
            
            $user = $this->User->findById($uid);
            $result['thumb'] = $this->request->webroot . 'uploads' . DS . 'users' . DS . 'avatar' . DS . $user['User']['id'] . DS . '50_square_' . $thumbname;
            $result['avatar'] = $this->request->webroot . 'uploads' . DS . 'users' . DS . 'avatar' . DS . $user['User']['id'] . DS . '600_' . $thumbname;
            $result['avatar_mini'] = $this->request->webroot . 'uploads/users/avatar/'. $user['User']['id'] . '/200_square_' . $thumbname;
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	}

    public function thumb_cover()
    {
        $uid = $this->Auth->user('id');

        if (!$uid || ( !$_POST['x'] && !$_POST['y'] && !$_POST['w'] && !$_POST['y'] ))
            return;

        $this->loadModel( 'User' );
        $user = $this->User->findById($uid);

        if ( empty( $user['User']['cover'] ) )
            return;

        $path = WWW_ROOT . 'uploads' . DS . 'covers';

        $ext = $this->_getExtension($user['User']['cover']);
        $thumbname = md5(microtime()) . '.' . $ext;

        $thumbloc = $path . DS . $thumbname;

        App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
        
        $this->loadModel('Photo.Photo');
        $photo = $this->Photo->find( 'first', array( 'conditions' => array(  'Album.type' => 'cover',
                                                                             'Album.user_id' => $uid ),
                                                     'limit' => 1,
                                                     'order' => 'Photo.id desc'
                                   ) );
        if(empty($photo))
            return;
        $created_date = strtotime($photo['Photo']['created']);
        $photo_path = 'uploads'. DS . 'photos' . DS . 'thumbnail'.  DS . date('Y', $created_date) . DS . date('m',$created_date)  . DS . date('d',$created_date) . DS .$photo['Photo']['id'] . DS . $photo['Photo']['thumbnail'] ;        
        if (!file_exists($photo_path)) {
           $photo_path = 'uploads'. DS . 'photos' . DS . 'thumbnail'.  DS .$photo['Photo']['id'] . DS . $photo['Photo']['thumbnail'] ;
        }
        $thumb = PhpThumbFactory::create(WWW_ROOT . DS . $photo_path, array('jpegQuality' => 100));

        $current_dimension = $thumb->getCurrentDimensions();
        $ratio_w = $current_dimension['width'] / $_POST['jcrop_width'] ;
        $ratio_h = $current_dimension['height'] / $_POST['jcrop_height'] ;

        $_POST['w'] = $_POST['w'] * $ratio_w;
        $_POST['x'] = $_POST['x'] * $ratio_w;
        $_POST['h'] = $_POST['h'] * $ratio_h;
        $_POST['y'] = $_POST['y'] * $ratio_h;

        $thumb->crop($_POST['x'], $_POST['y'], $_POST['w'], $_POST['h'])->resize(COVER_WIDTH, COVER_HEIGHT)->save($thumbloc);
        
        // delete old file
        if ($user['User']['cover'] && file_exists($path . DS . $user['User']['cover']))
            unlink($path . DS . $user['User']['cover']);

        // update user cover in db
        $this->User->id = $uid;
        $this->User->save( array( 'cover' => $thumbname ) );

        $result['thumb'] = $this->request->webroot . 'uploads/covers/' . $thumbname;

        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }

    public function avatar($save_original = 0 , $echo=true) {
        $uid = $this->Auth->user('id');
       
        if (!$uid)
            return;

        $this->loadModel('Photo.Album');

        $album = $this->Album->getUserAlbumByType($uid, 'profile');
        $title = 'Profile Pictures';

        if (empty($album)) {
            $this->Album->save(array('user_id' => $uid, 'type' => 'profile', 'title' => $title), false);
            $album_id = $this->Album->id;
        } else {
            $album_id = $album['Album']['id'];
        }

        $path = 'uploads' . DS . 'tmp' . DS;
        $url = 'uploads/tmp/';

        $this->_prepareDir($path);

        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();

        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $result = $uploader->handleUpload(WWW_ROOT . $path);
        
        if (!empty($result['success'])) {
        	App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
        	
            $newTmpAvatar = '';
            $file = $result['filename'];
            $epl = explode('.', $file);
            $extension = $epl[count($epl) - 1];
            
            if(in_array(strtolower($extension), array('jpg', 'jpeg')))
            {
            	@ini_set('memory_limit', '500M');
            	$photo = PhpThumbFactory::create($path . DS . $result['filename']);
            	$this->_rotateImage($photo, $path . DS . $result['filename']);
            }
            
            $avatarNewName = $epl[0] . '_tmp.' . $extension;
            $newTmpAvatar = $path . $avatarNewName;
            copy(WWW_ROOT . $path . $file, WWW_ROOT . $newTmpAvatar);            
            
            // save to db
            $this->loadModel('Photo.Photo');
            $this->Photo->create();
            $this->Photo->set(array('user_id' => $uid,
                'target_id' => $album_id,
                'type' => 'Photo_Album',
                'thumbnail' => $path . DS . $result['filename'],
            ));
            $this->Photo->save();

            $this->Album->id = $album_id;
            $this->Album->save(array('cover' => $result['filename']));

            $this->loadModel('User');
            $user = $this->User->findById($uid);
            
            $this->User->id = $uid;
            $this->User->set(array('avatar' => $newTmpAvatar));
            $this->User->save();
            
            // insert into activity feed
            $this->loadModel('Activity');
            $activity = $this->Activity->getRecentActivity('user_avatar', $uid);

            if (empty($activity)) {
                $this->Activity->save(array('type' => 'user',
                    'action' => 'user_avatar',
                    'user_id' => $uid
                ));

                //activitylog event
                $cakeEvent = new CakeEvent('Controller.Upload.afterUploadAvatar', $this, array('uid' => $uid,'activity_id' => $this->Activity->id));
                $this->getEventManager()->dispatch($cakeEvent);
            }
            $user['User']['avatar'] = $avatarNewName;
            $view = new View($this);
            $mooHelper = $view->loadHelper('Moo');
            $result['avatar'] = $mooHelper->getImageUrl($user, array('prefix' => '600'),true);
            $result['avatar_mini'] = $mooHelper->getImageUrl($user, array('prefix' => '200_square'),true);
			$result['avatar_100'] = $mooHelper->getImageUrl($user, array('prefix' => '100_square'),true);
            $result['thumb'] = $mooHelper->getImageUrl($user, array('prefix' => '50_square'),true);
            $this->getEventManager()->dispatch(new CakeEvent('UploadController.doAfterSaveAvatar', $this));
        }
        if ($echo) {
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        }else{
            return $result;
        }

    }

    public function cover()
    {
        $uid = $this->Auth->user('id');
        $isFirstTimeCreatedAlbum = false;
        if (!$uid)
            return;
        
        $this->loadModel( 'Photo.Album' );

        $album = $this->Album->getUserAlbumByType( $uid, 'cover' );
        $title = 'Cover Pictures';
        
        if ( empty( $album ) )
        {
            $this->Album->save( array( 'user_id' => $uid, 'type' => 'cover', 'title' => $title ), false );
            $album_id = $this->Album->id;
            $album = (array) $this->Album;
            $isFirstTimeCreatedAlbum = true;
        }
        else{
            $album_id = $album['Album']['id'];
            $isFirstTimeCreatedAlbum = false;
        }

        @ini_set('memory_limit', '500M');

        // save this picture to album
        $path = 'uploads' . DS . 'albums' . DS . $album_id;
        $url  = 'uploads/albums/' . $album_id . '/';
        
        $this->_prepareDir($path);
        $path = WWW_ROOT.$path;
        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();
            
        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions);
        
        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $result = $uploader->handleUpload($path);
        
        if ( !empty( $result['success'] ) )
        {
            // resize image
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
                        
            $ext = $this->_getExtension($result['filename']);
            if(in_array(strtolower($ext), array('jpg', 'jpeg')))
            {
            	@ini_set('memory_limit', '500M');
            	$photo = PhpThumbFactory::create($path . DS . $result['filename']);
            	$this->_rotateImage($photo, $path . DS . $result['filename']);
            }
            

            $original_photo = '';
            $medium_photo = $result['filename'];

            /* Add to cover photo album*/
            $photo->resize(PHOTO_WIDTH, PHOTO_HEIGHT)->save($path . DS . $medium_photo);
            
            $photo = PhpThumbFactory::create($path . DS . $medium_photo);
            $photo->adaptiveResize(PHOTO_THUMB_WIDTH, PHOTO_THUMB_HEIGHT)->save($path . DS . 't_' . $result['filename']);
            
            // save to db
            $photo_path =  $path . DS . $result['filename'];
            $newTmpAvatar = 'uploads' . DS . 'tmp' . DS . 'tmp_' .$result['filename'];
            $newTmpAvatarPath = WWW_ROOT . $newTmpAvatar;
            copy($photo_path, $newTmpAvatarPath);
            
            $this->loadModel( 'Photo.Photo' );
            $this->Photo->create();
            $this->Photo->set( array('user_id'   => $uid, 
                                     'target_id' => $album_id, 
                                     'type'      => 'Photo_Album', 
                                    'thumbnail' => $newTmpAvatar,
            ) );
            $this->Photo->save();
            
            // save album cover
            if (isset($album['Album']['cover']) && !$album['Album']['cover']){
                $this->Album->read(null,$album_id);
                $this->Album->set('cover','tmp_' .$result['filename']);
                $this->Album->save();
            }

            if($isFirstTimeCreatedAlbum){
                $this->Album->set('cover','tmp_' .$result['filename']);
                $this->Album->save();
            }
            
            /* Create and update cover */
            
            $cover_path       = WWW_ROOT . 'uploads' . DS . 'covers';
            $cover_loc        = $cover_path . DS . $result['filename'];
            
            if (!file_exists( $cover_path ))
            {
                mkdir( $cover_path, 0755, true );
                file_put_contents( WWW_ROOT . $path . DS . 'index.html', '' );
            }

            // resize image
            $cover = PhpThumbFactory::create($path . DS . $medium_photo, array('jpegQuality' => PHOTO_QUALITY));
            $cover->adaptiveResize(COVER_WIDTH, COVER_HEIGHT)->save($cover_loc);
            
            $this->loadModel('User');
            $user = $this->User->findById($uid);

            // delete old files
            $this->User->removeCoverFile( $user['User'] );     
            
            $awsModel = MooCore::getInstance()->getModel("Storage.StorageAwsObjectMap");
            $cachingName = $awsModel->getCacheName($uid, "moo_covers", "");
            Cache::delete($cachingName, 'storage');

            // update user cover pic in db
            $this->User->id = $uid;
            $this->User->save( array('cover' => $result['filename']) );     
            
            $result['cover'] = $this->request->webroot .  'uploads/covers/' . $result['filename'];
            $result['photo'] = $this->request->webroot .  $url . $medium_photo;
        }
        
        // to pass data through iframe you will need to encode all html tags
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }

    public function wall($echo=true)
    {
    	$uid = $this->Auth->user('id');

        if (!$uid)
            return;

        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();

        @ini_set('memory_limit', '500M');

        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $path = 'uploads' . DS . 'tmp';
        $url = 'uploads/tmp/';
        $this->_prepareDir($path);
        $path = WWW_ROOT . $path;
        $result = $uploader->handleUpload($path);

        if (!empty($result['success'])) {
            // resize image
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

            $photo = PhpThumbFactory::create($path . DS . $result['filename']);

            if(!empty($this->request->query['qqfile'])){
                $original_filename = $this->request->query['qqfile'];
                $ext = $this->_getExtension($original_filename);
            }else{
                $file = $result['filename'];
                $epl = explode('.', $file);
                $ext = $epl[count($epl) - 1];
            }


            if(in_array(strtolower($ext), array('jpg', 'jpeg')))
                $this->_rotateImage($photo, $path . DS . $result['filename']);

            $medium_photo = $result['filename'];
            
            $result['photo'] = $url . $medium_photo;
            
            $result['file_path'] = FULL_BASE_URL . $this->request->webroot . $url . $medium_photo;
        }
        // to pass data through iframe you will need to encode all html tags
        if ($echo){
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        }else{
            return $result;
        }

    }

	public function photos($type, $target_id, $save_original = 0)
	{
		$uid = $this->Auth->user('id');
            
		if (!$type || !$target_id || !$uid)
			return;
        
        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();
            
        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $path = 'uploads/photo' . DS . strtolower(Inflector::pluralize($type)) . DS . $target_id;
        $url  = 'uploads/photo/' . strtolower(Inflector::pluralize($type)) . '/' . $target_id. '/';
        $this->_prepareDir($path);
        $path = WWW_ROOT.$path;
        $result = $uploader->handleUpload($path);

        if ( !empty( $result['success'] ) )
        {
            // resize image
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
            
            $photo = PhpThumbFactory::create($path . DS . $result['filename']);         
 
            
            if ( $save_original )
            {
                $original_photo = $url . $result['filename'];
                $medium_photo = 'm_' . $result['filename'];
            }
            else
            {
                $original_photo = '';    
                $medium_photo = $result['filename'];
            }
            
            
            $photo->resize(PHOTO_WIDTH, PHOTO_HEIGHT)->save($path . DS . $medium_photo);
            
            $photo = PhpThumbFactory::create($path . DS . $medium_photo);
            $photo->adaptiveResize(PHOTO_THUMB_WIDTH, PHOTO_THUMB_HEIGHT)->save($path . DS . 't_' . $result['filename']);

            // save to db
            $this->loadModel( 'Photo.Photo' );
            $this->Photo->create();
            $this->Photo->set( array('user_id'   => $uid, 
                                     'target_id' => $target_id, 
                                     'type'      => $type, 
                                     'path'      => $url . $medium_photo, 
                                     'thumb'     => $url . 't_' . $result['filename'],
                                     'original'  => $original_photo
            ) );
            $this->Photo->save();
            
            $result['photo_id'] = $this->Photo->id;
            $result['thumb'] = $this->request->webroot .  $url . 't_' . $result['filename'];
        }

        // to pass data through iframe you will need to encode all html tags
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	}

	public function attachments($plugin_id, $target_id = 0) {
            $uid = $this->Auth->user('id');

            if (!$plugin_id || !$uid)
                return;

            $allowedExtensions = MooCore::getInstance()->_getFileAllowedExtension();

            App::import('Vendor', 'qqFileUploader');
            $uploader = new qqFileUploader($allowedExtensions);

            // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
            $path = 'uploads' . DS . 'attachments';
            $url = 'uploads/attachments';

            $original_filename = $this->request->query['qqfile'];
            $ext = $this->_getExtension($original_filename);

            $result = $uploader->handleUpload($path);

            if (!empty($result['success'])) {
                if (in_array(strtolower($ext), array('jpg', 'jpeg', 'png', 'gif'))) {
                    // resize image
                    App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

                    $photo = PhpThumbFactory::create($path . DS . $result['filename']);

                    $photo->resize(PHOTO_WIDTH, PHOTO_HEIGHT)->save($path . DS . $result['filename']);

                    $photo = PhpThumbFactory::create($path . DS . $result['filename']);
                    $photo->adaptiveResize(PHOTO_THUMB_WIDTH, PHOTO_THUMB_HEIGHT)->save($path . DS . 't_' . $result['filename']);
                }

                // save to db
                $this->loadModel('Attachment');
                $this->Attachment->create();
                $this->Attachment->set(array('user_id' => $uid,
                    'target_id' => $target_id,
                    'plugin_id' => $plugin_id,
                    'filename' => $result['filename'],
                    'original_filename' => $original_filename,
                    'extension' => $ext
                ));
                $this->Attachment->save();

                $result['attachment_id'] = $this->Attachment->id;
            }

            // to pass data through iframe you will need to encode all html tags
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        }

        public function images() {
            $error = false;

            $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();

            App::import('Vendor', 'qqFileUploader');
            $uploader = new qqFileUploader($allowedExtensions);

            $path = 'uploads' . DS . 'images';

            $result = $uploader->handleUpload($path);

            if (!empty($result['success'])) {
                // resize image
                App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

                $photo = PhpThumbFactory::create($path . DS . $result['filename']);

                $photo->resize(PHOTO_WIDTH, PHOTO_HEIGHT)->save($path . DS . $result['filename']);

                $photo = PhpThumbFactory::create($path . DS . $result['filename']);
                $photo->resize(IMAGE_WIDTH, IMAGE_HEIGHT)->save($path . DS . 't_' . $result['filename']);
            }

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

    //custom
    public function avatar_tmp()
    {
        $path = 'uploads' . DS . 'tmp';
        $url = 'uploads/tmp';

        $this->_prepareDir($path);
        $path = WWW_ROOT.$path;
        $allowedExtensions = MooCore::getInstance()->_getPhotoAllowedExtension();

        App::import('Vendor', 'qqFileUploader');
        $uploader = new qqFileUploader($allowedExtensions);

        // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
        $result = $uploader->handleUpload($path);

        if ( !empty( $result['success'] ) )
        {
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

            $photo = PhpThumbFactory::create($path . DS . $result['filename']);

            $original_filename = $this->request->query['qqfile'];
            $ext = $this->_getExtension($original_filename);

            if(in_array(strtolower($ext), array('jpg', 'jpeg')))
                $this->_rotateImage($photo, $path . DS . $result['filename']);

            // resize image
            $original_photo = '';
            $medium_photo = $result['filename'];

            $photo->resize(PHOTO_WIDTH, PHOTO_HEIGHT)->save($path . DS . $medium_photo);

            $result['filepath']  = 'uploads' . DS . 'tmp' . DS . $result['filename'];

        }
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
    }

}
?>
