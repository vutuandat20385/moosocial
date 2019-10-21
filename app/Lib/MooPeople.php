<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooPeople
{
    private $status = "onBoot";// onController onBeforRender onRender
    private $ids = array();
    private $data = array();
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new MooPeople();
        }

        return $instance;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
    public function getStatus(){
        return $this->status;
    }
    public function register($data = array()){

        switch($this->getStatus()){
            case "onBoot":
            case "onController":
                // id only  (n1,n2,n3)
                $ids = $data;
                if(!is_array($ids)) $ids = array($ids);
                $this->ids = array_unique(array_merge($this->ids,$ids));
                break;
            case "onBeforeRender":
            case "onRender":
                $user = $data;
                $this->add($user);
                break;
            default:
        }

    }
    public function add($data=array()){

        switch($this->getStatus()){
            case "onBoot":
            case "onController":
                $this->register($data);
                break;
            case "onBeforeRender":
                // id and data  ()
            case "onRender":
                if (empty($this->data)){
                    $this->data = $data;
                }
                else {
                    $this->data = $data + $this->data;
                }
                break;
            default:
        }

    }

    public function get($id=null){
        switch($this->getStatus()){
            case "onBoot":
            case "onController":
                // id only  (n1,n2,n3)
                return $this->ids;
            case "onBeforeRender":
                // id and data  ()
            case "onRender":
                if(isset($this->data[$id])) return $this->data[$id];
                return false;
                break;
            default:
                return false;
        }
    }
}