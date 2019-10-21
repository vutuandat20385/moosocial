<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Component', 'Controller');
class Widget extends Component {
    protected $params;
    protected $data = array();
    public function beforeRender(Controller $controller) {

    }
    public function set($params){
        $this->params = $params;
    }
    public function get($params){
        return $this->params;
    }
    
    public function setData($key,$data)
    {
    	$this->data = array_merge($this->data,array($key=>$data));
    	$controller = $this->_Collection->getController();
    	$controller->set('data_block'.$this->params['content_id'],$this->data);
    }
}