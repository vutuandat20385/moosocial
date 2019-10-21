<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class RatingsController extends  AppController
{
    public function beforeFilter(){
        parent::beforeFilter();
        //disable rating feature
        exit;
    }
    public function do_rate(){
        if($this->request->is('post') && MooCore::getInstance()->getViewer(true))
        {
            $this->loadModel('RatingUser');
            $this->loadModel('RatingSetting');
            $this->autoRender = false;
            $params = explode('_',$this->request->data['params']);
            $type = $params[1];
            if(count($params) > 3){
                $plugin = $params[2];
                $type_id = $params[3];
            }else{
                $plugin = null;
                $type_id = $params[2];
            }
            //get re-rating setting
            $allow_re_rating = $this->RatingSetting->find('first',array('conditions' => array('RatingSetting.name' => 're_rating')));
            $allow_re_rating = $allow_re_rating['RatingSetting']['value'];

            //check if rating for this item has existed
            $ratings =  $this->Rating->getRatings(array('Rating.type' => $type, 'Rating.type_id' => $type_id, 'Rating.plugin' => $plugin));
            if(empty($ratings)){ // if it hasn't ,add this item into rating table
                $this->Rating->save(array(
                        'type' => $type,
                        'type_id' => $type_id,
                        'plugin' => $plugin,
                        'score' => $this->request->data['score'],
                    )
                );
                $parent_id = $this->Rating->id;
                $total_scores = 0;//$this->request->data['score'];
                $total_users = 0;
            }
            else{ //if it has existed, get it id and its total scores
                $parent_id = $ratings['Rating']['id'];
                $total_scores = $ratings['Rating']['total_score'];
                $total_users = $ratings['Rating']['rating_user_count'];
            }

            // check if this user rated this item
            $user_rating = $this->RatingUser->find('first',array(
                'conditions' => array(
                    'RatingUser.rating_id' => $parent_id,
                    'RatingUser.user_id' => $this->request->data['uid'],
                )
            ));
            $user_old_score = 0;

            $this->RatingUser->create();
            if(!empty($user_rating))
            {
                //do not allow user re-rating
                if(empty($allow_re_rating))
                    return false;
                $user_old_score = $user_rating['RatingUser']['score'];
                //update user's rating info
                $this->RatingUser->id = $user_rating['RatingUser']['id'];
            }
            else{
                $total_users += 1;
            }
            //save new or update user's rating info
            $this->RatingUser->save(array(
                    'score' => $this->request->data['score'],
                    'user_id' => $this->request->data['uid'],
                    'rating_id' => $parent_id
                )
            );

            $average_score = (intval($total_scores) - $user_old_score + $this->request->data['score'])/$total_users;
            $new_total_scores = intval($total_scores) - $user_old_score + $this->request->data['score'];
            // update rating with score and total scores
            $this->Rating->create();
            $this->Rating->id = $parent_id;
            $this->Rating->save(array('score' => intval($average_score), 'total_score' => $new_total_scores));
            
            // event
            $cakeEvent = new CakeEvent('Controller.Rating.afterSave', $this, array('item' => $this->Rating->read()));
            $this->getEventManager()->dispatch($cakeEvent);

            return json_encode(array('data' => 1,'votes' => $total_users,'score' =>intval($average_score)));
        }
    }
    public function get_rating_options()
    {
        if($this->request->is('requested'))
        {
            //get the first element in data array
            $item = array_slice($this->request->data,0,1,true);
            $type = key($item);
            $type_id = $item[$type]['id'];
            $this->loadModel('Rating');
            $ratings = $this->Rating->getRatings(array('Rating.type' => $type, 'Rating.type_id' => $type_id));
            $ratings['Item']['type'] = $type;
            $ratings['Item']['type_id'] = $type_id;
            return $ratings;
        }
    }
    public function admin_index(){
        $ratingSettingModel = MooCore::getInstance()->getModel('RatingSetting');
        $settings = $ratingSettingModel->find('all');
        $this->set('settings',$settings);
    }
    public function admin_quick_save(){
       
        $this->autoRender = false;
        if($this->request->is('post')){
            $data = $this->request->data;
            foreach($data as $id => &$value){
                $newValue = $value;
                if(!empty($value['enable_rating'])){
                    $newValue = json_encode($value['enable_rating']);
                }
                if(!empty($value['input_value'])){
                    $newValue = $value['input_value'];
                }
                if(!empty($value['new_skin'])){
                    $path = WWW_ROOT . 'img' . DS . 'rating_skins' . DS;
                    $filename = md5(microtime()) . '.' . pathinfo($value['new_skin']['name'], PATHINFO_EXTENSION);
                    $file_path = $path . $filename;
                    if(move_uploaded_file($value['new_skin']['tmp_name'],$file_path)){
                        $newValue = $filename;
                    }
                }
                //delete input and don't upload new skin will reset skin to default
                if(isset($value['new_skin']) && isset($value['input_value']) && $value['new_skin'] == '' && $value['input_value'] == ''){
                    $newValue = 'skin.png';
                }

                //update table rating setting
                $this->loadModel('RatingSetting');
                $this->RatingSetting->id = $id;
                $this->RatingSetting->save(array('value' => $newValue));
                $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            }
        }
        $this->redirect($this->referer());
    }
}