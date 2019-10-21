<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class AlbumsController extends PhotoAppController {

    
    
    public $paginate = array(
        'limit' => RESULTS_LIMIT,
        'findType' => 'translated',
    );

    public function index() {
        $this->redirect('/photos');
    }

    public function browse($type = null, $param = null,$isRedirect = true) {

            if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }
            $uid = $this->Auth->user('id');
            $role_id = $this->_getUserRoleId();
            $sFriendsList = '';
            if (!empty($this->request->named['category_id'])) {
                $type = 'category';
                $param = $this->request->named['category_id'];
            }

            $url = (!empty($param) ) ? $type . '/' . $param : $type;

            switch ($type) {
                case 'home':
                case 'my':
                case 'friends':
                    $this->_checkPermission();
                    $param = $uid;
                    break;

                case 'search':
                    $param = urldecode($param);
                    if (!Configure::read('core.guest_search') && empty($uid))
                        $this->_checkPermission();

                    break;
                case 'category':
                    break;
                default:
                    $this->loadModel('Friend');
                    $friends_list = $this->Friend->getFriendsList($uid);
                    $aFriendListId =  array_keys($friends_list);
                    $sFriendsList = implode(',',$aFriendListId);
                    $param = $uid;
            }

            $albums = $this->Album->getAlbums($type, $param, $page, RESULTS_LIMIT, $sFriendsList, $role_id);
            $this->set('albums', $albums);
            $this->set('album_more_url', '/albums/browse/' . h($url) . '/page:' . ( $page + 1 ));

            $album_more_result = 0;
            $more_albums = $this->Album->getAlbums($type, $param, $page + 1, RESULTS_LIMIT, $sFriendsList, $role_id);
            if(!empty($more_albums)){
                $album_more_result = 1;
            }
            $this->set('album_more_result', $album_more_result);
       if($isRedirect && $this->theme != "mooApp") {
            if ($page == 1 && $type == 'home'){
                $this->render('/Elements/ajax/home_album');
            }
            else{
                if ($this->request->is('ajax')){
                    $this->render('/Elements/lists/albums_list');
                }
                else{
                    $this->render('/Elements/lists/albums_list_m');
                }
            }
        }
        else {
                if($type == 'category') $this->set('categoryId', $param);
                $this->set('type', $type);
        }

    }

    public function save($isReturn = false) {
        $this->_checkPermission(array('confirm' => true));
        $this->autoRender = false;
        $uid = $this->Auth->user('id');

        if (!empty($this->request->data['id'])) {
            // check edit permission			
            $album = $this->Album->findById($this->request->data['id']);
            $this->_checkPermission(array('admins' => array($album['User']['id'])));
            $this->Album->id = $this->request->data['id'];
        } else
            $this->request->data['user_id'] = $uid;

        $this->Album->set($this->request->data);
        $this->_validateData($this->Album);

        if ($this->Album->save()) { // successfully saved	
            // save tags
            $this->loadModel('Tag');
            $this->Tag->saveTags($this->request->data['tags'], $this->Album->id, 'Photo_Album');
            $response['result'] = 1;
            $response['id'] = $this->Album->id;
            
            $event = new CakeEvent('Plugin.Controller.Album.afterSaveAlbum', $this, array(
                'uid' => $uid, 
                'id' => $this->Album->id, 
                'privacy' =>$this->request->data['privacy']
             ));

            $this->getEventManager()->dispatch($event);
            
            $album = $this->Album->read();
            $this->loadModel('Activity');
            $this->Activity->updateAll(array('privacy'=>$album['Album']['privacy']),array('action'=>'photos_add','item_type'=>'Photo_Album','item_id'=>$album['Album']['id']));
            if(!$isReturn) echo json_encode($response);
            else { return $this->Album->id; }
        }
    }

    public function create($aid = 0) {
        $this->_checkPermission(array('confirm' => true));
        $this->_checkPermission(array('aco' => 'album_create'));

        if (!empty($aid)) {
            $album = $this->Album->findById($aid);
            $this->_checkExistence($album);
            $this->_checkPermission(array('admins' => array($album['User']['id'])));

            $this->loadModel('Tag');
            $tags = $this->Tag->getContentTags($aid, 'Photo_Album');

            $this->set('tags', $tags);
        } else
            $album = $this->Album->initFields();

        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategoriesList('Photo', $role_id);

        $this->set('title_for_layout', __( 'Create New Album'));
        $this->set('album', $album);
        $this->set('categories', $categories);
       
    }

    public function edit($id = null) {
        $id = intval($id);
        $album = Cache::read('album.album_edit_'.$id, 'photo');
        if(empty($album)){
            $album = $this->Album->findById($id);
            Cache::write('album.album_edit_'.$id, $album, 'photo');
        }
        $this->_checkExistence($album);
        if (!$album){
            return;
        }
        $this->_checkPermission(array('admins' => array($album['User']['id'])));
        $this->_checkPermission(array('aco' => 'album_create'));

        $this->loadModel('Photo.Photo');
        $photos = $this->Photo->getPhotos('Photo_Album', $id, null, null);

        if ($this->request->is('post')) { // handle form submission
            // update cover
            if (isset($this->request->data['cover'])) {
                $this->Album->id = $id;
                $this->Album->save(array('cover' => $this->request->data['cover']));
            }
            foreach ($photos as $photo) {
                if (isset($this->request->data['select_' . $photo['Photo']['id']])) {
                    switch ($this->request->data['select_photos']) {
                        case 'delete':
                            $this->Photo->delete($photo['Photo']['id']);
                            break;

                        case 'move':
                            
                            $this->Photo->id = $photo['Photo']['id'];
                            $this->Photo->save(array('target_id' => $this->request->data['album_id']));
                            break;
                    }
                    
                    // update cover of album
                    $nextCoverPhoto = $this->Photo->find('first', array('conditions' => array('Photo.type' => 'Photo_Album', 'Photo.target_id' => $photo['Photo']['target_id'])));
                    $currentCoverPhoto = $this->Album->find('first', array('conditions' => array('Album.id' => $photo['Photo']['target_id'])));

                    if (!empty($nextCoverPhoto)){
                        // cond1: delete item is cover => need to update cover
                        // cond2: current album have no cover => need to update cover
                        if ($photo['Photo']['thumbnail'] == $currentCoverPhoto['Album']['cover'] || empty($currentCoverPhoto['Album']['cover'])){
                            $this->Album->id = $photo['Photo']['target_id'];
                            $this->Album->save(array(
                                'cover' => $nextCoverPhoto['Photo']['thumbnail']
                            ));
                        }

                    }else{
                        $this->Album->id = $photo['Photo']['target_id'];
                        $this->Album->save(array(
                            'cover' => ''
                        ));
                    }

                } elseif (isset($this->request->data['caption_' . $photo['Photo']['id']])) {
                    // update caption
                    $this->Photo->id = $photo['Photo']['id'];
                    $this->Photo->save(array('caption' => $this->request->data['caption_' . $photo['Photo']['id']]));
                }
            }

            $this->Session->setFlash(__( 'Your changes have been saved'));
            $this->redirect('/albums/view/' . $album['Album']['id']);
        } else {
            // if album does not have a cover yet, use the first photo as cover
            if (!$album['Album']['cover'] && count($photos) > 0) {
                $this->Album->id = $id;
                $this->Album->save(array('cover' => $photos[0]['Photo']['thumbnail']));

                $album['Album']['cover'] = $photos[0]['Photo']['thumbnail'];
            }
            $albums = Cache::read('album.album_edit_title_'.$id, 'photo');
            if(empty($albums))
            {
                $albums = $this->Album->find('list', array('conditions' => array('Album.user_id' => $album['Album']['user_id']), 'fields' => 'Album.title'));
                
                Cache::write('album.album_edit_title_'.$id, $albums, 'photo');
            }
            
            $this->set('photos', $photos);
            $this->set('album', $album);
            $this->set('albums', $albums);
            $this->set('title_for_layout', __( 'Edit Album'));
        }
    }

    public function view($id = null) {
        $data = array();
        $id = intval($id);
        

        $this->Album->recursive = 2;
        $album = $this->Album->findById($id);
        if ($album['Category']['id'])
        {
        	foreach ($album['Category']['nameTranslation'] as $translate)
        	{
        		if ($translate['locale'] == Configure::read('Config.language'))
        		{
        			$album['Category']['name'] = $translate['content'];
        			break;
        		}
        	}
        }
        $this->Album->recursive = 0;

        $this->_checkExistence($album);
        if (!$album){
            return;
        }


        $this->_checkPermission(array('aco' => 'album_view'));
        $this->_checkPermission( array('user_block' => $album['Album']['user_id']) );
        MooCore::getInstance()->setSubject($album);

        $uid = $this->Auth->user('id');
        $this->_checkPrivacy($album['Album']['privacy'], $album['User']['id']);


            $this->loadModel('Photo.Photo');
            $limit = Configure::read('Photo.photo_item_per_pages');
            $params = array();
            if ($album['Album']['type'] == 'newsfeed')
            {
                    $this->loadModel('Friend');
                    $params['newsfeed'] = true;
                    if ($uid == $album['User']['id'] || $this->_getUserRoleId() == ROLE_ADMIN || ($uid && $this->Friend->areFriends($uid,$album['User']['id'])))
                    {
                            $params['is_friend'] = true;
                    }
            }
            /* Custom for mooApp activity album view */
            if(!isset($this->request->query['photoId'])) {
                $photos = $this->Photo->getPhotos('Photo_Album', $id, 1, $limit,$params);
            }
            else {
                $photoId = $this->request->query['photoId'];
                $photo1 = $this->Photo->getPhotos('Photo_Album', $id, 1, $limit,$params);
                $photo2 = $this->Photo->find('all',array('conditions'=>array(
                            'Photo.id' => $photoId
                    )));
                $photos = array_merge($photo1,$photo2);

            }

            $this->loadModel('Tag');
            $tags = $this->Tag->getContentTags($id, 'Photo_Album');

            $this->loadModel('Comment');
            $comments = $this->Comment->getComments($id, 'Photo_Album');
            $comment_count = $album['Album']['comment_count'];

            $this->loadModel('Like');
            $likes = $this->Like->getLikes($id, 'Photo_Album');
            $dislikes = $this->Like->getDisLikes($id, 'Photo_Album');
            // get comment likes
            if (!empty($uid)) {
                $comment_likes = $this->Like->getCommentLikes($comments, $uid);

                $data['comment_likes'] = $comment_likes;
                $like = $this->Like->getUserLike($id, $uid, 'Photo_Album');
                $this->set('like', $like);
            }

            // set og:image
            if ($album['Album']['cover']) {
                $photoHelper = MooCore::getInstance()->getHelper('Photo_Photo');
                $this->set('og_image', $photoHelper->getAlbumCover($album['Album']['cover'], array('prefix' => '850') ) );
            }

            $this->set('photos', $photos);
            $this->set('tags', $tags);

            $this->set('likes', $likes);
            $this->set('dislikes', $dislikes);
            $this->set('album', $album);
            $this->set('more_url', '/photos/ajax_browse/Photo_Album/' . $id . '/page:2');

            $this->set('title_for_layout', $album['Album']['moo_title']);
            $description = $this->getDescriptionForMeta($album['Album']['description']);
            if ($description) {
                $this->set('description_for_layout', $description);
                if (count($tags))
                {
                    $tags = implode(",", $tags).' ';
                }
                else
                {
                    $tags = '';
                }
                $this->set('mooPageKeyword', $this->getKeywordsForMeta($tags.$description));
            }

            $this->set('photosAlbumCount', $this->Photo->getPhotosCount('Photo_Album', $id,$params));
            $this->set('page', 1);

            $this->set('admins', array($album['Album']['user_id']));

            $page = 1;
            $data['bIsCommentloadMore'] = $comment_count - $page * RESULTS_LIMIT;
            $data['more_comments'] = '/comments/browse/photo_album/' . $id . '/page:' . ($page + 1);
            $data['admins'] = array($album['Album']['user_id']);
            $data['comments'] = $comments;

            $this->set('data', $data);

        if($this->theme == "mooApp"){
            $this->set('albumId',$id);
            $this->set('title_for_layout', $album['Album']['moo_title']);
        }
    }

    /*
     * Delete album
     * @param int $id - album id to delete
     */

    public function do_delete($id = null,$isRedirect = true) {
        $id = intval($id);
        $album = $this->Album->findById($id);
        $this->_checkExistence($album);
        if (!$album){
            return;
        }
        $this->_checkPermission(array('admins' => array($album['User']['id'])));
        if($album['Album']['type'] != 'cover'){
            $this->Album->deleteAlbum($album);
            
            $cakeEvent = new CakeEvent('Plugin.Controller.Album.afterDeleteAlbum', $this, array('item' => $album));
            $this->getEventManager()->dispatch($cakeEvent);
            if($isRedirect) {
                $this->Session->setFlash(__( 'Album has been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            }
            else {
                $this->set(array(
                    'message' => __('Album has been deleted'),
                    '_serialize' => array('message'),
                ));
            }
        }else{
            if($isRedirect) {
                $this->Session->setFlash(__( 'You can\'t delete cover album'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            }
            else {
                return $error = array(
                        'code' => 400,
                        'message' => __('You can\'t delete cover album'),
                );
            }
        }
        if($isRedirect) {
            $this->redirect('/photos');
        }
    }

    public function admin_index() {
        if (!empty($this->request->data['keyword'])){
            $this->redirect('/admin/photo/albums/index/keyword:' . $this->request->data['keyword']);
        }
            
        $cond = array();
        if (!empty($this->request->named['keyword'])){
            $cond = array(
                'Album.title LIKE ' => '%' . $this->request->named['keyword'] . '%'
            );            
        }
        $albums = $this->paginate('Album', $cond);

        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem('Album');
        
        $this->set('albums', $albums);
        $this->set('categories', $categories);
        $this->set('title_for_layout', __('Albums Manager'));
    }

    public function admin_delete() {
        $this->_checkPermission(array('super_admin' => 1));
        
        if (!empty($_POST['albums'])) {
            $albums = $this->Album->findAllById( $_POST['albums'] );
            
            foreach ($albums as $album){
                
                $this->Album->deleteAlbum($albums);
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Album.afterDeleteAlbum', $this, array('item' => $album));
                $this->getEventManager()->dispatch($cakeEvent);
            }
            
            $this->Session->setFlash( __( 'Albums have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
            
        }

        $this->redirect($this->referer());
    }

    public function admin_move() {
        if (!empty($_POST['albums']) && !empty($this->request->data['category_id'])) {
            foreach ($_POST['albums'] as $album_id) {
                $this->Album->id = $album_id;
                $this->Album->save(array('category_id' => $this->request->data['category_id']));
            }
            $this->Session->setFlash( __( 'Albums moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
            
        }

        $this->redirect($this->referer());
    }

    public function popular() {
        if ($this->request->is('requested')) {
            App::import('Model', 'Photo.Album');
            $album = new Album();
            $num_item_show = $this->request->named['num_item_show'];
            return $album->getPopularAlbums($num_item_show, Configure::read('core.popular_interval'));
        }
    }

}
