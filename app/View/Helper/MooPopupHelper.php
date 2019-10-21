<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooPopupHelper extends AppHelper implements CakeEventListener {
    protected $_eventManager = null;
    public function implementedEvents() {
        return array();
    }
    public function getEventManager()
    {
        if (empty($this->_eventManager)) {
            $this->_eventManager = new CakeEventManager();
            $this->_eventManager->attach($this);
        }
        return $this->_eventManager;
    }
    private $htmlDefaultPopup=<<<html
<section class="{section_class}" id="{section_id}" role="{role}" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="{section_div_class}">
            <div class="{section_div_div_class}"></div>
        </div>
    </section>
html;
    private $htmlPopups = array();
    public function register($params=array()){
        if(!is_array($params) && !empty($params)) $params = array('id'=>$params);
        if(empty($params['id'])) return false;
        if(isset($this->htmlPopups[$params['id']])) return false;
        $params['section_id'] = $params['id'];
        $default = array(
            'section_class'=>'modal fade',
            'section_id'=>'defaultModel',
            'role'=>'basic',
            'section_div_class'=>'modal-dialog',
            'section_div_div_class'=>'modal-content',
        );
        $replace = array_merge($default,$params);
        $search= array('{section_class}','{section_id}','{role}','{section_div_class}','{section_div_div_class}');
        $this->htmlPopups[$params['id']] = str_replace($search,$replace,$this->htmlDefaultPopup);
    }
    public function get($id=null){
        if(empty($id)) return $this->htmlPopups;
        if(empty($this->htmlPopups[$id])) return false;
        return $this->htmlPopups[$id];
    }
    public function html($autoEcho=true){
        if(!empty($this->htmlPopups)){
            $div = '';
            foreach($this->htmlPopups as $html){
                $div .= $html;
            }
            if($autoEcho){
                echo $div;
                return true;
            }else{
                return $div;
            }
        }
        return false;
    }
    public function tag($params,$autoEcho=true){
        if(empty($params['target'])){
            $params['target'] =  'themeModal';
        }
        $this->register($params['target']);
        $params['data-target'] = '#'.$params['target'];
        $default= array(
            'id'=>'',
            'href'=>'#',
            'data-target'=>'#themeModal',
            'data-toggle'=>'modal',
            'class'=>'',
            'title'=>'',
            'innerHtml'=>'',
            'data-dismiss' => '',
            'data-backdrop' => 'true',
            'style' => ''
        );
        $replace = array_merge($default,$params);
        $aOtherAtt = array_diff_key($replace,$default);
        $otherAtt = '';
        if(!empty($aOtherAtt)){
            foreach($aOtherAtt as $key => &$value){
                if($key == 'target')
                    continue;
                $otherAtt .= " ".$key.'="'.$value.'"';
            }
        }
        //debug($otherAtt);
        $search  = array('#id', '#href','#data-target','#data-toggle','#class','#title','#innerHtml', '#data-dismiss', '#data-backdrop', '#style');
        $a = "<a id='#id' href='#href' data-target='#data-target' data-toggle='#data-toggle' class='#class' title='#title' data-dismiss='#data-dismiss' data-backdrop='#data-backdrop' style='#style' #otherAtt>#innerHtml</a>";
        
        $a = str_replace($search,$replace,$a);
        $a = str_replace('#otherAtt',$otherAtt,$a);

        $event = new CakeEvent('MooPoupHelper.tag', $this,array('params' => $replace));
        $this->getEventManager()->dispatch($event);
        if(!empty($event->result['a'])){
            $a = $event->result['a'];
        }

        if($autoEcho){
            echo $a;
            return true;
        }else{
            return $a;
        }
        return false;
    }
}