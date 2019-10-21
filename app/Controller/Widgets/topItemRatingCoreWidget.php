<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class topItemRatingCoreWidget extends Widget {
    public $controller;
    public function beforeRender(Controller $controller) {
        $type = $this->params['type'];
        $controller->loadModel('Rating');
        $this->controller = $controller;

        $ratingSettingModel = MooCore::getInstance()->getModel('RatingSetting');
        $ratingEnableList = $ratingSettingModel->getRatingEnableList();
        $ratings = $controller->Rating->getTopRating( $type,$this->params['num_item_show'],$ratingEnableList);

        if(!empty($ratings))
            $ratings = $this->_getAllowRatings($ratings);
        if(!empty($ratings))
            $ratings = $this->_attach_info($ratings);

        $this->setData('ratings',$ratings);
    }

    private function _attach_info($ratings){
        foreach($ratings as &$rating){
            $className = Inflector::classify($rating['Rating']['type']);
            $pluginName = (!empty($rating['Rating']['plugin'])) ? $rating['Rating']['plugin'].'_' : '';
            $controllerClassName = $rating['Rating']['type'];
            $ratingModel = MooCore::getInstance()->getModel( $pluginName.$className );

            $info = $ratingModel->findById($rating['Rating']['type_id']);
            $rating = array_merge($rating,$info);
            if(!empty($info)){
                if(!isset($info[$className]['name']) || !isset($info[$className]['title']) ){
                    if(isset($info[$className]['name']))
                        $rating['name'] = $info[$className]['name'];
                    elseif(isset($info[$className]['title']))
                        $rating['name'] = $info[$className]['title'];
                    else
                        $rating['name'] = 'Photo';
                }
                if(!isset($info[$className]['cover']) || !isset($info[$className]['photo']) || !isset($info[$className]['thumbnail']) || !isset($info[$className]['thumb']) ){
                    if(isset($info[$className]['cover']) )
                        $rating['thumb'] = $info[$className]['cover'];
                    elseif(isset($info[$className]['photo']) )
                        $rating['thumb'] = $info[$className]['photo'];
                    elseif(isset($info[$className]['thumb']) )
                        $rating['thumb'] = $info[$className]['thumb'];
                    else
                        $rating['thumb'] = $info[$className]['thumbnail'];
                }
                $rating['link'] = $controllerClassName.'/view/'.$rating['Rating']['type_id'];
                $rating['plugin'] = $pluginName;
                if(empty($info[$className]['moo_title']) || empty($info[$className]['moo_href']) || empty($info[$className]['moo_thumb']) ){
                    if(isset($info[$className]['moo_title']) )
                        $info[$className]['moo_title'] = $rating['name'];
                    if(isset($info[$className]['moo_href']) )
                        $info[$className]['moo_href'] = $rating['link'];
                    if(isset($info[$className]['moo_thumb']) )
                        $info[$className]['moo_thumb'] = $rating['thumb'];
                }

            }

            // Hook for addition info
            $cakeEvent = new CakeEvent('Controller.Widgets.topItemRatingCoreWidget.attachInfo', $this->controller,array('type' => $rating['Rating']['type'],'type_id' => $rating['Rating']['type_id'] ));
            $result = $this->controller->getEventManager()->dispatch($cakeEvent);
            if(!empty($result->result) && is_array($result->result) ){
                $rating = array_merge($rating,$result->result);
            }
        }
        return $ratings;
    }

    private function _getAllowRatings($ratings)
    {
        $aRatings = Hash::combine($ratings,'{n}.Rating.type_id','{n}.Rating.id','{n}.Rating.type');
        $intersectRating = array();
        foreach($aRatings as $type => &$rating)
        {
            if($type != 'topics'){
                $modelClass = Inflector::classify($type);
                // Get all the items this user can view
                $allowRating = $this->_getAllowItems($modelClass);
                $allRatings = array_keys($rating);
                $intersectRating[$type] = array_intersect($allRatings,$allowRating);
            }
        }
        foreach($ratings as $key => &$rating){
            if(!empty($intersectRating) && $rating['Rating']['type'] != 'topics' && !in_array($rating['Rating']['type_id'],$intersectRating[$rating['Rating']['type']]))
                unset($ratings[$key]);
        }
        return $ratings;
    }

    private function _getAllowItems($modelClass)
    {
        $uid = MooCore::getInstance()->getViewer(true);
        $sFriendsList = $this->_getFriendList($uid);
        $model = MooCore::getInstance()->getModel($modelClass);
        $cond = array(
            'OR' => array(
                array(
                    $modelClass.'.privacy' => PRIVACY_EVERYONE,
                ),
                array(
                    $modelClass.'.user_id' => $uid
                ),
                array(
                    'Find_In_Set('.$modelClass.'.user_id,"'.$sFriendsList.'")',
                    $modelClass.'.privacy' => PRIVACY_FRIENDS
                )
            ),
        );
        if($modelClass == 'User')
        {
            $cond['OR'][1] = array(
                $modelClass.'.id' => $uid
            );
            $cond['OR'][2] = array(
                'Find_In_Set('.$modelClass.'.id,"'.$sFriendsList.'")',
                $modelClass.'.privacy' => PRIVACY_FRIENDS
            );
        }elseif($modelClass == 'Group'){
            $cond['OR'][0] =  array(
                $modelClass.'.type' => PRIVACY_EVERYONE
            );
            $cond['OR'][2] = array(
                'Find_In_Set('.$modelClass.'.user_id,"'.$sFriendsList.'")',
                $modelClass.'.type' => PRIVACY_FRIENDS
            );
        }
        $aData = $model->find('all',array(
                'conditions' => $cond,
                'fields' => array('id')
            ));
        return Hash::extract($aData,'{n}.'.$modelClass.'.id');
    }

    private function _getFriendList($uid)
    {
        $friendModel = MooCore::getInstance()->getModel('Friend');
        $sFriendsList = '';
        $aFriendListId =  array_keys($friendModel->getFriendsList($uid));
        $sFriendsList = implode(',',$aFriendListId);
        return $sFriendsList;
    }
}