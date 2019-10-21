<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooPopoversHelper extends AppHelper {
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
        );
        $replace = array_merge($default,$params);
        $search  = array('#id','#href','#data-target','#data-toggle','#class','#title','#innerHtml');
        $a = "<a id='#id' href='#href' data-target='#data-target' data-toggle='#data-toggle' class='#class' title='#title'>#innerHtml</a>";
        $a = str_replace($search,$replace,$a);
        if($autoEcho){
            echo $a;
            return true;
        }else{
            return $a;
        }
        return false;
    }
}