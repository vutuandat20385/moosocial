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

class StorageBlogListener implements CakeEventListener
{
    public function implementedEvents()
    {
        return array(
            'StorageHelper.blogs.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.blogs.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.blogs.getFilePath' => 'storage_amazon_get_file_path',
            'StorageTaskAwsCronTransfer.execute' => 'storage_task_transfer',
            'StorageAmazon.photos.putObject.success.Blog' => 'storage_amazon_photo_put_success_callback',
        );
    }
    public function storage_geturl_local($e)
    {
        $v = $e->subject();
        $request = Router::getRequest();
        $oid = $e->data['oid'];
        $thumb = $e->data['thumb'];
        $prefix = $e->data['prefix'];
        if ($e->data['thumb']) {
            $url = FULL_BASE_LOCAL_URL . $request->webroot . 'uploads/blogs/thumbnail/' . $oid . '/' . $prefix . $thumb;
        } else {
            //$url = FULL_BASE_LOCAL_URL . $v->assetUrl('Blog.noimage/blog.png', array('prefix' => rtrim($prefix, "_"), 'pathPrefix' => Configure::read('App.imageBaseUrl')));
            $url = $v->getImage("blog/img/noimage/blog.png");
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "blogs", $e->data['prefix'], $e->data['thumb']);
    }

    public function storage_amazon_get_file_path($e)
    {

        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb = $e->data['thumb'];
        $path = false;
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . "blogs" . DS . "thumbnail" . DS . $objectId . DS . $name . $thumb;
        }
        $e->result['path'] = $path;
    }

    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $blogModel = MooCore::getInstance()->getModel('Blog.Blog');
        $blogs = $blogModel->find('all', array(
                'conditions' => array("Blog.id > " => $v->getMaxTransferredItemId("blogs")),
                'limit' => 10,
                'fields'=>array('Blog.id','Blog.thumbnail'),
                'order' => array('Blog.id'),
            )
        );

        if($blogs){
            $photoSizes = $v->photoSizes();
            foreach($blogs as $blog){
                if (!empty($blog["Blog"]["thumbnail"])) {
                    foreach ($photoSizes as $size){
                        $v->transferObject($blog["Blog"]['id'],"blogs",$size.'_',$blog["Blog"]["thumbnail"]);
                    }
                    $v->transferObject($blog["Blog"]['id'],"blogs",'',$blog["Blog"]["thumbnail"]);
                }
            }
        }
    }
    public function storage_amazon_photo_put_success_callback($e){
        $photo = $e->data['photo'];
        $path= $e->data['path'];
        $url= $e->data['url'];
        if (Configure::read('Storage.storage_cloudfront_enable') == "1"){
            $url = rtrim(Configure::read('Storage.storage_cloudfront_cdn_mapping'),"/")."/".$e->data['key'];
        }
        $blogModel = MooCore::getInstance()->getModel('Blog.Blog');
        $blogModel->clear();
        $blog = $blogModel->find("first",array(
            'conditions' => array("Blog.id"=>$photo['Photo']['target_id']),
        ));
        if($blog){
            $findMe = str_replace(WWW_ROOT,"",$path);
            $isReplaced = false;
            $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
            if(preg_match_all("/$regexp/siU", $blog['Blog']['body'], $matches)) {
                foreach ($matches[2] as $match){
                    if(strpos($match, $findMe) !== false){
                        $isReplaced = true;
                        $blog['Blog']['body'] = str_replace($match,$url,$blog['Blog']['body']);
                    }
                }
            }
            $regexp = "<img\s[^>]*src=(\"??)([^\" >]*?)\\1[^>]*>";
            if(preg_match_all("/$regexp/siU", $blog['Blog']['body'], $matches)) {
                foreach ($matches[2] as $match){
                    if(strpos($match, $findMe) !== false){
                        $isReplaced = true;
                        $blog['Blog']['body'] = str_replace($match,$url,$blog['Blog']['body']);
                    }
                }
            }
            if($isReplaced){
                $blogModel->clear();
                $blogModel->save($blog);
            }
        }

    }
}
