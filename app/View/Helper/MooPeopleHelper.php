<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooPeopleHelper extends AppHelper {
    public $helpers = array('Text');
    public $isLoadedTooltipJs = false;
    public function initTooltipJs(){
        if($this->isLoadedInitJs) return true;
        $this->isLoadedTooltipJs = true;

        if( get_class($this->_View)== "MooView"){
            $this->_View->addInitJs('$(function(){$(\'[data-toggle="tooltip"]\').tooltip()});');
        }

    }
    public function with($tagging_id = null, $ids=array(),$autoRender=true){
        if(!($ids =$this->_convertToArray($ids))) return false;
        /*$uid = MooCore::getInstance()->getViewer(true);
        if(!empty($uid)){
            $block_modal = MooCore::getInstance()->getModel('UserBlock');
            $user_blocks = $block_modal->getBlockedUsers($uid);     
            foreach ($ids as $key=>$val) {
                if (in_array($val, $user_blocks)) {
                    unset($ids[$key]);
                }
            }
            if (in_array($uid, $user_blocks)) {
                 $per = false;
            }
        }*/
        $ids = array_values($ids);
        $count = count($ids);
        $with1 = addslashes(__(' — with %s'));
        $with2 = addslashes(__(' — with %s and %s'));
        $with3 = addslashes(__(' — with %s and %d others.'));
        $with = "";
    
        if($count){
            switch($count){
                case 1:
                    $with= sprintf($with1,$this->getName($ids[0]));
                    break;
                case 2:
                    $with = sprintf($with2,$this->getName($ids[0]),$this->getName($ids[1]));
                    break;
                case 3:
                default:
                $with3a = explode('%d',$with3);
                $tooltipText = sprintf('%d'.$with3a[1],$count-1);
                $with3 = str_replace('%d'.$with3a[1],$this->tooltip($tagging_id, $ids,$tooltipText),$with3);

                $with = sprintf($with3,$this->getName($ids[0]));
            }
            if($autoRender) {
                echo $with;
                return true;
            }
        }
        return $with;
    }
    public function getWithNotUrl($tagging_id = null, $ids=array(),$autoRender=true){
        if(!($ids =$this->_convertToArray($ids))) return false; 
        $count = count($ids);
        $with1 = addslashes(__(' — with %s'));
        $with2 = addslashes(__(' — with %s and %s'));
        $with3 = addslashes(__(' — with %s and %d others.'));
        $with = "";
        switch($count){
            case 1:
                $with= sprintf($with1,$this->getName($ids[0],false,true,true));
                break;
            case 2:
                $with = sprintf($with2,$this->getName($ids[0],false,true,true),$this->getName($ids[1],false,true,true));
                break;
            case 3:
            default:
            $with3a = explode('%d',$with3);
            $tooltipText = sprintf('%d'.$with3a[1],$count-1);
            $with3 = str_replace('%d'.$with3a[1],  $tooltipText ,$with3);
            $with = sprintf($with3,$this->getName($ids[0],false,true,true));
        }
        if($autoRender) {
            echo $with;
            return true;
        }
        return $with;
    }
    public function getUserTagged ($ids=array()) {
        $tagUsers = explode(',', $ids);
        foreach ($tagUsers as $tagUser):
            $tagArray = MooPeople::getInstance()->get($tagUser);
            $tagUserArray[] = array (
                'name' => $tagArray['User']['name'],
                'url' => FULL_BASE_URL . $tagArray['User']['moo_href'],
                'id' => $tagArray['User']['id'],
                'type' => $tagArray['User']['moo_type'],
            ) ;
        endforeach;
        return $tagUserArray;
    }
    
    public function isTagged($uid, $item_id, $item_type){
        if($item_type == 'Photo_Album')
            $item_type = 'activity';
        $UserTagging = MooCore::getInstance()->getModel('UserTagging');
        return $UserTagging->isTagged($uid, $item_id, $item_type);
    }

    public function isMentioned($uid, $item_id){
        $activityModel = MooCore::getInstance()->getModel('Activity');
        return $activityModel->isMentioned($uid, $item_id);
    }

    public function getName($data, $bold = true ,$idOnly=true,$textOnly=false) {
        if($idOnly){ 
            $user = MooPeople::getInstance()->get($data);
            
        }else{
            $user = $data;
        }
        
        if (!empty($user)) {
            $name = $this->Text->truncate($user['User']['name'], 30, array('html'=>true));
            if($textOnly)
                return $name;
            $url = $user['User']['moo_href'];
            
            $class = '';
            $show_popup = MooCore::getInstance()->checkViewPermission($user['User']['privacy'],$user['User']['id']);
            if($show_popup){
                $class = 'moocore_tooltip_link';
            }
            
            if ($bold)
                return '<a href="' . $url . '"  class="' . $class . '" data-item_type="user" data-item_id="' . $user['User']['id'] . '"><b>' . $name . '</b></a>';
            else
                return '<a href="' . $url . '" class="' . $class . '" data-item_type="user" data-item_id="' . $user['User']['id'] . '">' . $name . '</a>';
        }
    }
    
    public function get($uid = null){
        if (!empty($uid)){
            $user = MooPeople::getInstance()->get($uid);
            return $user;
        }
        
        return false;
    }


    public function tooltip($tagging_id, $ids=array(),$tooltipText){
        if(!($ids =$this->_convertToArray($ids))) return false;
        $this->initTooltipJs();
        $title = '';
        unset($ids[0]);
        foreach($ids as $id){
            $title .= $this->getName($id,true,true,true)."<br/>";
        }
        return '<a data-toggle="modal" data-target="#themeModal" href="' . Router::url(array('plugin'=>false,'controller'=>'users', 'action'=>'tagging', 'tagging_id' => $tagging_id)) .'">' . '<span class="tip" original-title="'.$title.'"><b>' . $tooltipText . '</b></span>' . '</a>';
    }
    private function _convertToArray($data){
        if(empty($data)) return false;
        if(!is_array($data)){
            return explode(',',$data);
        }else{
            return $data;
        }
        return false;
    }
    
    
    // check current viewer is friend with user_id
    // return boolean
    public function isFriend($user_id){
        $viewer = MooCore::getInstance()->getViewer();
        $viewer_id = MooCore::getInstance()->getViewer(true);
        $friendModel = MooCore::getInstance()->getModel('Friend');
        
        if ($friendModel->areFriends($viewer_id, $user_id)){
            return true;
        }
        
        return false;
    }
    
    // check current viewer sent request to user_id
    // return boolean
    public function sentFriendRequest($user_id){
        $viewer = MooCore::getInstance()->getViewer();
        $viewer_id = MooCore::getInstance()->getViewer(true);
        $friendRequestModel = MooCore::getInstance()->getModel('FriendRequest');
        $requests = $friendRequestModel->getRequestsList( $viewer_id );
        if (in_array($user_id, $requests) && $viewer_id != $user_id){
            return true;
        }
        
        return false;
    }
    
    // check current viewer is sent request from user_id
    // return boolean
    public function respondFriendRequest($user_id){
        $viewer = MooCore::getInstance()->getViewer();
        $viewer_id = MooCore::getInstance()->getViewer(true);
        $friendRequestModel = MooCore::getInstance()->getModel('FriendRequest');
        $respond = $friendRequestModel->getRequests( $viewer_id );
        $respond = Hash::extract($respond,'{n}.FriendRequest.sender_id');
        
        if(in_array($user_id, $respond) && $viewer_id != $user_id){
            return true;
        }
        
        return false;
    }
    
    

}