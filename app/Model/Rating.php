<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Rating extends AppModel{

    public $hasMany = array(

        'RatingUser' => array(
            'dependent' => true
        )
    );

    public function getRatings($condition = null,$params = null){
        $ratings = $this->find('first', array('conditions' => $condition));
        return $ratings;
    }
    public function getTopRating($type = 'all', $num_item = RESULTS_LIMIT, $ratingEnableList = array()){

        $cond = null;
        $ratings = null;
        if($type != 'all' && in_array($type,$ratingEnableList)){
            $cond = array('Rating.type' => $type);
        }elseif($type == 'all'){
            $cond = array('Rating.type IN ("'.implode('","',$ratingEnableList).'")');
        }
        if(!empty($cond)){
            $ratings = $this->find('all',array(
                    'conditions' => $cond,
                    'order' => 'Rating.rating_user_count DESC',
                    'limit' => $num_item,
                )
            );
        }
        return $ratings;
    }

}