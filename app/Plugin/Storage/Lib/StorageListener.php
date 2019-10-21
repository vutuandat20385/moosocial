<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

//App::uses('StorageAmazon', 'Storage.Lib');

class StorageListener implements CakeEventListener
{
    public $missingObjectNotExist = array();
    public function implementedEvents()
    {
        return array(
            'StorageHelper.moo_photos.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.moo_photos.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageHelper.moo_covers.getUrl.local' => 'storage_geturl_local_cover',
            'StorageHelper.moo_covers.getUrl.amazon' => 'storage_geturl_amazon_cover',
            'StorageAmazon.moo_covers.getFilePath' => 'storage_amazon_get_file_path_cover',
            //'StorageHelper.users.getUrl.local'=>'storage_geturl_local',
            //'StorageHelper.users.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.users.getFilePath' => 'storage_amazon_get_file_path',
            'StorageAmazon.comments.getFilePath' => 'storage_amazon_get_file_path',
            'StorageAmazon.activitycomments.getFilePath' => 'storage_amazon_get_file_path',
            'StorageTaskAwsCronTransfer.execute' => 'storage_task_transfer',
            'StorageHelper.css.getUrl.local' => 'storage_geturl_local_css',
            'StorageHelper.css.getUrl.amazon' => 'storage_geturl_amazon_css',
            'StorageAmazon.css.getFilePath' => 'storage_amazon_get_file_path_css',
            'StorageHelper.js.getUrl.local' => 'storage_geturl_local_js',
            'StorageHelper.js.getUrl.amazon' => 'storage_geturl_amazon_js',
            'StorageAmazon.js.getFilePath' => 'storage_amazon_get_file_path_js',
            'StorageHelper.img.getUrl.local' => 'storage_geturl_local_img',
            'StorageHelper.img.getUrl.amazon' => 'storage_geturl_amazon_img',
            'StorageAmazon.img.getFilePath' => 'storage_amazon_get_file_path_img',
            'StorageHelper.font.getUrl.local' => 'storage_geturl_local_img',
            'StorageHelper.font.getUrl.amazon' => 'storage_geturl_amazon_img',
            'StorageAmazon.font.getFilePath' => 'storage_amazon_get_file_path_img',
            'StorageHelper.links.getUrl.local' => 'storage_geturl_local_link',
            'StorageHelper.links.getUrl.amazon' => 'storage_geturl_amazon_link',
            'StorageAmazon.links.getFilePath' => 'storage_amazon_get_file_path_link',
        	'Plugin.Minify.beforeReplaceImgUrl' => 'beforeReplaceImgUrl' ,
        	'StorageHelper.beforeReturn.getUrl' => 'beforeReturnGetURL' ,
            'StorageAmazon.addMissingObject.fileNotExist'=>'storage_amazon_addMissingObject_false'
        );
    }
    
    public function beforeReplaceImgUrl($event)
    {
    	$imgUrl= &$event->data['imgUrl'];
    	$imgUrl = FULL_BASE_LOCAL_URL.Router::getRequest()->webroot;
    	
    	if (Configure::read('Storage.storage_current_type') == 'amazon')
    	{
    		if (Configure::read('Storage.storage_cloudfront_enable'))
    		{
    			$imgUrl = Configure::read('Storage.storage_cloudfront_cdn_mapping').'/webroot/';
    		}
    		else
    		{
    			$awsModel = MooCore::getInstance()->getModel("Storage.StorageAwsObjectMap");
    			$row = $awsModel->find('first');
    			if ($row)
    			{
    				$tmp = str_replace($row['StorageAwsObjectMap']['key'],'',$row['StorageAwsObjectMap']['url']);
    				$imgUrl = $tmp.'webroot/';
    			}
    		}
    	}
    }

    public function storage_geturl_local($e)
    {
        $v = $e->subject();
        $oid = $e->data['oid'];
        $thumb = $e->data['thumb'];
        $prefix = $e->data['prefix'];
        $extra = $e->data['extra'];
        $url = false;
        if (key($extra) == 'User') {
            if (!$extra['User']) {
                if ($prefix == '50_square_') {
                    $url = FULL_BASE_LOCAL_URL . $v->request->webroot . strtolower(key($extra)) . '/img/noimage/Male-' . strtolower(key($extra)) . '-sm.png';
                } else {
                    $url = FULL_BASE_LOCAL_URL . $v->request->webroot . strtolower(key($extra)) . '/img/noimage/Male-' . strtolower(key($extra)) . '.png';
                }
            } else {
                if ($thumb) {
                    $field = $extra[key($extra)]['moo_thumb'];
                    $url = FULL_BASE_LOCAL_URL . $v->request->webroot . 'uploads/' . strtolower(Inflector::pluralize(key($extra))) . '/' . $field . '/' . $extra[key($extra)]['id'] . '/' . $prefix . $thumb.'?'.rand(1,9999);
                } else {
                    $gender = $extra[key($extra)]['gender'];
                    if (!$gender)
                        $gender = 'Unknown';
                    if ($prefix == '50_square_') {
                        //$url = FULL_BASE_LOCAL_URL . $v->request->webroot . strtolower(key($extra)) . '/img/noimage/' . $gender . '-' . strtolower(key($extra)) . '-sm.png';
                        //$url = $v->getImage("user/img/noimage/".$gender . '-user-sm.png');
                        $url = $v->getNoAvatar($gender,true);
                    } else {
                        //$url = FULL_BASE_LOCAL_URL . $v->request->webroot . strtolower(key($extra)) . '/img/noimage/' . $gender . '-' . strtolower(key($extra)) . '.png';
                        //$url = $v->getImage("user/img/noimage/".$gender . '-user.png');
                        $url = $v->getNoAvatar($gender,false);
                    }
                }
            }
        } else {
            if ($thumb) {
                $obj = $extra;
                $field = $extra[key($extra)]['moo_thumb'];
                if (isset($obj[key($obj)]['year_folder']) && $obj[key($obj)]['year_folder']) { // hacking for MOOSOCIAL-277L
                    $year = date('Y', strtotime($obj[key($obj)]['created']));
                    $month = date('m', strtotime($obj[key($obj)]['created']));
                    $day = date('d', strtotime($obj[key($obj)]['created']));
                    $url = FULL_BASE_LOCAL_URL . $v->request->webroot . "uploads/photos/thumbnail/$year/$month/$day/" . $obj[key($obj)]['id'] . '/' . $prefix . $obj[key($obj)]['thumbnail'];
                } else {
                    $url = FULL_BASE_LOCAL_URL . $v->request->webroot . 'uploads/' . strtolower(Inflector::pluralize(key($obj))) . '/' . $field . '/' . $obj[key($obj)]['id'] . '/' . $prefix . $thumb;
                }
                //$url = FULL_BASE_LOCAL_URL . $v->request->webroot . 'uploads/' . strtolower(Inflector::pluralize(key($extra))) . '/' . $field . '/' . $extra[key($extra)]['id'] . '/' . $prefix . $thumb;
            } else {
                //$url = FULL_BASE_LOCAL_URL . $v->request->webroot . strtolower(key($extra)) . '/img/noimage/' . strtolower(key($extra)) . '.png';
                $url = $v->getImage(strtolower(key($extra)) . '/img/noimage/' . strtolower(key($extra)) . '.png');
            }
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_local_cover($e)
    {
        $v = $e->subject();
        $thumb = $e->data['thumb'];
        $url = false;
        if (!empty($thumb)) {
            $url = FULL_BASE_LOCAL_URL . $v->request->webroot . '/uploads/covers/' . $thumb;
        } else {
            // Get cover photo from admincp .
            //$url = $v->getImage("img/cover.jpg");
            $url = $v->defaultCoverUrl();
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon_cover($e)
    {
        $v = $e->subject();
        if (!$e->data['thumb'])
        {
        	$e->result['url'] = $v->defaultCoverUrl();
        	return;
        }
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "moo_covers", $e->data['prefix'], $e->data['thumb']);
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $extra = $e->data['extra'];
        $field = '';
        if(isset($extra[key($extra)]['moo_thumb'])){
            $field = $extra[key($extra)]['moo_thumb'];
        }

        $extra1 = array(
            'objectName' => strtolower(Inflector::pluralize(key($extra))),
            'field' => $field
        );
        
        if (key($extra) == 'User') {
        	if (!$e->data['thumb'])
        	{
        		$gender = $extra[key($extra)]['gender'];
        		if (!$gender)
        			$gender = 'Unknown';
        		
        		$prefix = $e->data['prefix'];
        		if ($prefix == '50_square_') {
        			$e->result['url'] = $v->getNoAvatar($gender,true);
        		} 
        		else 
        		{
        			$e->result['url'] = $v->getNoAvatar($gender,false);
        		}
        		return;
        	}
        }
        
        //$e->result['url'] = $v->getAwsURL($e->data['oid'], "moo_photos", $e->data['prefix'], $e->data['thumb'], $extra1);
        $e->result['url'] = $v->getAwsURL($e->data['oid'], $extra1["objectName"], $e->data['prefix'], $e->data['thumb'], $extra1);
    }


    public function storage_amazon_get_file_path($e)
    {
        $path = false;
        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb = $e->data['thumb'];
        $extra = $e->data['extra'];
        $objectName = $extra["objectName"];
        $field = $extra["field"];
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . $objectName . DS . $field . DS . $objectId . DS . $name . $thumb;
        }
        $e->result['path'] = $path;
    }

    public function storage_amazon_get_file_path_cover($e)
    {
        $path = false;
        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb = $e->data['thumb'];
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . 'covers' . DS . $thumb;
        }
        $e->result['path'] = $path;
    }

    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $userModel = MooCore::getInstance()->getModel('User');
        $users = $userModel->find('all', array(
                'conditions' => array("User.id > " => $v->getMaxTransferredItemId("users")),
                'limit' => 10,
                'fields' => array('User.id', 'User.avatar'),
                'order' => array('User.id'),
            )
        );

        if ($users) {
            //$photoSizes = $v->photoSizes();
            $photoSizes = array('50_square', '100_square', '200_square', '600');
            $extra = array(
                'objectName' => 'users',
                'field' => 'avatar',
            );
            foreach ($users as $user) {
                if (!empty($user["User"]["avatar"])) {
                    foreach ($photoSizes as $size) {
                        $v->transferObject($user["User"]['id'], "users", $size . '_', $user["User"]["avatar"], $extra);
                    }
                    $v->transferObject($user["User"]['id'], "users", '', $user["User"]["avatar"], $extra);
                }
            }
        }

        $commentModel = MooCore::getInstance()->getModel('Comment');
        $comments = $commentModel->find('all', array(
                'conditions' => array("Comment.id > " => $v->getMaxTransferredItemId("comments")),
                'limit' => 100,
                'fields' => array('Comment.id', 'Comment.thumbnail'),
                'order' => array('Comment.id'),
            )
        );

        if ($comments) {
            //$photoSizes = $v->photoSizes();
            $photoSizes = array('200');
            $extra = array(
                'objectName' => 'comments',
                'field' => 'thumbnail',
            );
            foreach ($comments as $comment) {
                if (!empty($comment["Comment"]["thumbnail"])) {
                    foreach ($photoSizes as $size) {
                        $v->transferObject($comment["Comment"]['id'], "comments", $size . '_', $comment["Comment"]["thumbnail"], $extra);
                    }
                    $v->transferObject($comment["Comment"]['id'], "comments", '', $comment["Comment"]["thumbnail"], $extra);
                }
            }
        }
    }

    public function storage_geturl_local_css($e)
    {
        $e->result['url'] = $e->data['thumb'];
    }

    public function storage_geturl_amazon_css($e)
    {
        if (Configure::read('Storage.storage_amazon_use_css_js_path') != "1") {
            $v = $e->subject();
            $extra = $e->data['extra'];
            if (isset($extra['realPath'])) {
                $strpos = strpos($extra['realPath'], '?');
                if ($strpos !== false) {
                    $path = substr($extra['realPath'], 0, strpos($extra['realPath'], '?'));
                } else {
                    $path = $extra['realPath'];
                }

                //$path = str_replace($v->request->base, '', $path);
                $path = substr($path,strlen($v->request->base));
                $path = ltrim($path, "/");
                $path = str_replace("/", DS, $path);
                $e->result['url'] = $v->getAwsURL($e->data['oid'], "css", $path, $path, array('key' => "webroot/" . $path));
            }
            // If webroot is synning , url will be false
        }


    }

    public function storage_amazon_get_file_path_css($e)
    {
        $path = false;

        $thumb = $e->data['thumb'];
        if (!empty($thumb)) {
            $path = WWW_ROOT  . $thumb;
        }

        $e->result['path'] = $path;
    }

    public function storage_geturl_local_js($e)
    {
        $e->result['url'] = $e->data['thumb'];
    }

    public function storage_geturl_amazon_js($e)
    {
        if (Configure::read('Storage.storage_amazon_use_css_js_path') != "1") {
            $v = $e->subject();
            $extra = $e->data['extra'];
            if (isset($extra['realPath'])) {
                $strpos = strpos($extra['realPath'], '?');
                if ($strpos !== false) {
                    $path = substr($extra['realPath'], 0, strpos($extra['realPath'], '?'));
                } else {
                    $path = $extra['realPath'];
                }

                $path = substr($path,strlen($v->request->base));
                $path = ltrim($path, "/");
                $path = str_replace("/", DS, $path);
                $e->result['url'] = $v->getAwsURL($e->data['oid'], "js", $path, $path, array('key' => "webroot/" . $path));
            }
            // If webroot is synning , url will be false
        }


    }

    public function storage_amazon_get_file_path_js($e)
    {
        $path = false;

        $thumb = $e->data['thumb'];
        if (!empty($thumb)) {
            $path = WWW_ROOT  . $thumb;
        }

        $e->result['path'] = $path;
    }

    public function storage_geturl_local_img($e)
    {
        $e->result['url'] = $e->data['thumb'];
        $e->result['url'] = FULL_BASE_LOCAL_URL . $e->subject()->assetUrl($e->data['thumb']);
    }

    public function storage_geturl_amazon_img($e)
    {
        $v = $e->subject();

        $path = $e->data['prefix'];
        $path = str_replace($v->request->base, '', $path);
        $path = ltrim($path, "/");
        $path = WWW_ROOT  . str_replace("/", DS, $path);
        $e->result['url'] = $v->getAwsURL($e->data['oid'], $e->data['type'], $e->data['prefix'], $path, array('key' => "webroot/" . $e->data['prefix']));

    }

    public function storage_amazon_get_file_path_img($e)
    {
        $path = false;

        $name = $e->data['name'];
        $path = WWW_ROOT . str_replace("/", DS, $name);

        $e->result['path'] = $path;
    }
    function storage_geturl_local_link($e){

        $v = $e->subject();
        $thumb = $e->data['thumb'];
        $url = false;
        if (!empty($thumb)) {
            $url = FULL_BASE_LOCAL_URL . $v->request->webroot . 'uploads/links/' . $thumb;
        }
        $e->result['url'] = $url;
    }
    function storage_geturl_amazon_link($e){
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "links", $e->data['prefix'], $e->data['thumb']);
    }
    function storage_amazon_get_file_path_link($e){
        $path = false;
        $thumb = $e->data['thumb'];
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . "links". DS .$thumb;
        }
        $e->result['path'] = $path;
    }
    function storage_amazon_addMissingObject_false($e){
        $this->missingObjectNotExist = $e->data;
    }
    function beforeReturnGetURL($e){
        if(!empty($this->missingObjectNotExist)){
            $oid = $this->missingObjectNotExist["oid"];
            $type = $this->missingObjectNotExist["type"];
            $name = $this->missingObjectNotExist["name"];
            $awsModel = MooCore::getInstance()->getModel("Storage.StorageAwsObjectMap");
            $cachingName = $awsModel->getCacheName($oid, $type, $name);
            if(!empty($e->data['url'])){
                Cache::write($cachingName, $e->data['url'], 'storage');
                $awsModel->clear();
                $rAwsMap = $awsModel->find('first', array(
                        'conditions' => array(
                            'StorageAwsObjectMap.oid' => $oid,
                            'StorageAwsObjectMap.name' => $name,
                            'StorageAwsObjectMap.type' => $type,
                        )
                    )
                );
                if ($rAwsMap) {
                    $rAwsMap['StorageAwsObjectMap']['url'] = $e->data['url'];
                    $rAwsMap['StorageAwsObjectMap']['bucket'] = Configure::read("Storage.storage_amazon_bucket_name");
                    $rAwsMap['StorageAwsObjectMap']['key'] = "file_not_exist";
                    $awsModel->save($rAwsMap);
                } else if($oid != null && $name != null && $type != null) {
                    $awsModel->save(array(
                        'oid' => $oid,
                        'name' => $name,
                        'type' => $type,
                        'url' => $e->data['url'],
                        'bucket' => "redirect",
                        'key' => "file_not_exist",
                        'size' => 0,
                    ));
                }
            }

        }
        $this->missingObjectNotExist = array();
    }
}
