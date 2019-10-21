<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('FormHelper', 'View/Helper');

class MooFormHelper extends FormHelper
{
    public $helpers = array('Html');
    private $isLoaded = array(
        'javascript'=>array(
            'autoGrow'=>false,
            'mention' => false,
            'emoji' => false,
        ),
        'mooInit'=>array(
            'autoGrow'=>false,
        )
    );
    private $_userTaggingScript = <<<javaScript
    
        var friends_str_replace_userTagging = new Bloodhound({
                        datumTokenizer:function(d){
                            return Bloodhound.tokenizers.whitespace(d.name);
                        },
                        queryTokenizer: Bloodhound.tokenizers.whitespace,
                        prefetch: {
                            url: '#urlSuggestion',
                            cache: false,
                            filter: function(list) {
            
                                return $.map(list.data, function(obj) {
                                    return obj;
                                });
                            }
                        },
                        
                        identify: function(obj) { return obj.id; },
        });
            
        friends_str_replace_userTagging.initialize();

        $('#str_replace_userTagging').tagsinput({
            freeInput: false,
            itemValue: 'id',
            itemText: 'name',
            typeaheadjs: {
                name: 'friends_str_replace_userTagging',
                displayKey: 'name',
                highlight: true,
                limit:10,
                source: friends_str_replace_userTagging.ttAdapter(),
                templates:{
                    notFound:[
                        '<div class="empty-message">',
                            '#str_replace_typeadadjs_notFound',
                        '</div>'
                    ].join(' '),
                    suggestion: function(data){
                    if($('#userTagging').val() != '')
                    {
                        var ids = $('#str_replace_userTagging').val().split(',');
                        if(ids.indexOf(data.id) != -1 )
                        {
                            return '<div class="empty-message" style="display:none">#str_replace_typeadadjs_notFound</div>';
                        }
                    }
                        return [
                            '<div class="suggestion-item">',
                                '<img alt src="'+data.avatar+'"/>',
                                '<span class="text">'+data.name+'</span>',
                            '</div>',
                        ].join('')
                    }
                }
            }
        });
javaScript;
    
    private $_tinyMCE= <<<javaScript
require(["jquery","tinyMCE"], function($, tinyMCE) {	                
	$(document).ready(function(){
                tinyMCE.remove("#selector");
	        tinyMCE.init({
	            selector: "#selector",
                    language : mooConfig.tinyMCE_language,
	            theme: "#modern",
	            skin: '#light',
	            plugins: ["#plugins"],
	            toolbar1: "#toolbar1",
	            image_advtab: #image_advtab,
	            directionality: "#site_rtl",
	            width: #width,
	            height: #height,
	            menubar: #menubar,
	            forced_root_block : '#forced_root_block',
	            relative_urls : #relative_urls,
	            remove_script_host : #remove_script_host,
	            document_base_url : '#document_base_url',
	            browser_spellcheck: true,
                contextmenu: false,
				entity_encoding: 'raw'
	        });
	});
});
javaScript;

    private $_editTexarea = <<<javaScript
    <script></script>

javaScript;

    public function userTagging($friends="", $id = "userTagging" ,$hide_icon_add = false,$is_app = false,$token = null)
    {
        $this->_View->loadLibrary('userTagging');

        if($is_app) {
            $urlSuggestion = $this->Html->url(array("controller"=>"users","action"=>"friends","plugin"=>false),true).".json?access_token=" . $token ;
        }else {
            $urlSuggestion = $this->Html->url(array("controller"=>"users","action"=>"friends","plugin"=>false),true).".json";
        }
        $jsReplace = str_replace('#urlSuggestion',$urlSuggestion,$this->_userTaggingScript);
        $jsReplace = str_replace('str_replace_userTagging',$id,$jsReplace);
        $jsReplace = str_replace('#str_replace_typeadadjs_notFound',  addslashes(__('unable to find any friend')),$jsReplace);
        $jsReplace = str_replace('#str_replace_container_userTagging_id','#userTagging-id-'.$id,$jsReplace);
        
        if($this->_View->isEnableJS('Requirejs')){
            $jsReplace = "require(['jquery','typeahead','bloodhound','tagsinput'], function($){ \$(document).ready(function(){ ".$jsReplace."}); });";
        }              
        $out = $this->input('userTagging', array(
            'id' => $id,
            'value' => $friends,
            'type' => 'text',
            'label' => false,
            'placeholder'=>  addslashes(__('Who are you with ?')),
            'div' => array(
                'class' => 'user-tagging-container',
                'id' => 'userTagging-id-'.$id,
            ),
            'before'=>'<i '.($hide_icon_add ? 'style="display:none;"':'').' class="" onclick="$(this).parent().find(\'.userTagging-'.$id.'\').toggleClass(\'hidden\')"><em class="material-icons">person_add</em></i> <div class="userTagging-'.$id.' hidden">',
            'after' =>'</div>',
        ));
        if ($this->request->is('ajax'))
        {
        	 $out.='<script>'.$jsReplace.'</script>';
        }
        else
        {
            $this->_View->Helpers->Html->scriptBlock(
                $jsReplace,
                array(
                    'inline' => false,

                )
            );
        }
        return $out;
    }
    
    public function friendSuggestion($friends="", $id = "friendSuggestion"){
        $this->_View->loadLibrary('userTagging');
		$token = "";
		if(!empty($this->request->query['access_token']['access_token']))
		{
			$token = "?access_token=".$this->request->query['access_token']['access_token'];
		}
        $urlSuggestion = $this->Html->url(array("controller"=>"users","action"=>"friends","plugin"=>false),true).".json".$token;
        $jsReplace = str_replace('#urlSuggestion',$urlSuggestion,$this->_userTaggingScript);
        $jsReplace = str_replace('str_replace_userTagging',$id,$jsReplace);
        $jsReplace = str_replace('#str_replace_typeadadjs_notFound',  addslashes(__('unable to find any friend')),$jsReplace);
        $jsReplace = str_replace('#str_replace_container_userTagging_id','#userTagging-id-'.$id,$jsReplace);
        
        if($this->_View->isEnableJS('Requirejs')){
            
            $jsReplace = "require(['jquery','typeahead','bloodhound','tagsinput'], function($){".$jsReplace."});";
        }
        $this->_View->addInitJs($jsReplace);
        $out = $this->input('friendSuggestion', array(
            'id' => $id,
            'value' => $friends,
            'type' => 'text',
            'label' => false,
            'placeholder'=>  __('Friend\'s name ?'),
            'div' => array(
                'class' => 'user-tagging-container',
            ),
          //  'after' =>'</div>',
        ));
        return $out;
    }
    
    public function groupSuggestion($friends="", $id = "groupSuggestion"){
        $this->_View->loadLibrary('userTagging');
		$token = "";
		if(!empty($this->request->query['access_token']['access_token']))
		{
			$token = "?access_token=".$this->request->query['access_token']['access_token'];
		}
        $urlSuggestion = $this->Html->url(array(
            "controller" => "groups",
            "action" => "my_joined_group",
            "plugin" => 'group'
            ),true).".json".$token;
        $jsReplace = str_replace('#urlSuggestion',$urlSuggestion,$this->_userTaggingScript);
        $jsReplace = str_replace('str_replace_userTagging',$id,$jsReplace);
        $jsReplace = str_replace('#str_replace_typeadadjs_notFound',__('unable to find any group'),$jsReplace);
        $jsReplace = str_replace('#str_replace_container_userTagging_id','#userTagging-id-'.$id,$jsReplace);
        
        if($this->_View->isEnableJS('Requirejs')){
            
            $jsReplace = "require(['jquery','typeahead','bloodhound','tagsinput'], function($){".$jsReplace."});";
        }
        $this->_View->addInitJs($jsReplace);
        $out = $this->input('groupSuggestion', array(
            'id' => $id,
            'value' => $friends,
            'type' => 'text',
            'label' => false,
            'placeholder'=>  (__('Group\'s name')),
            'div' => array(
                'class' => 'user-tagging-container',
            ),
        ));
        return $out;
    }

    public function tinyMCE($fieldName, $options = array()){
        $this->_View->loadJs(array('tinymce/tinymce.min.js'));
        $search = array(
            '#document_base_url',
            '#selector',
            '#modern',
            '#light',
            '#plugins',
            '#toolbar1',
            '#image_advtab',
            '#width',
            '#height',
            '#menubar',
            '#forced_root_block',
            '#relative_urls',
            '#remove_script_host',
            '#site_rtl'
        );
        
        $plugins = 'advlist autolink lists link image charmap print preview hr anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table  directionality emoticons template paste textcolor';
        
        if (MooCore::getInstance()->isMobile(null))
        {
            $plugins = 'textcolor emoticons fullscreen';
        }
        
        $replace = array(
            FULL_BASE_URL . $this->_View->request->root,
            ((isset($options['id']) ? 'textarea#'.$options['id'] : 'textarea')),
            ((isset($options['modern']) ? $options['modern'] : 'modern')),
            ((isset($options['light']) ? $options['light'] : 'light')),
            ((isset($options['plugins']) ? $options['plugins'] : $plugins)),
            ((isset($options['toolbar1']) ? $options['toolbar1'] : 'styleselect | bold italic | bullist numlist outdent indent | forecolor backcolor emoticons | link unlink anchor image | preview fullscreen code')),
            ((isset($options['image_advtab']) ? $options['image_advtab'] : 'true')),
            ((isset($options['width']) ? $options['width'] : '580')),
            ((isset($options['height']) ? $options['height'] : '150')),
            ((isset($options['menubar']) ? $options['menubar'] : 'false')),
            ((isset($options['forced_root_block']) ? $options['forced_root_block'] : 'div')),
            ((isset($options['relative_urls']) ? $options['relative_urls'] : 'false')),
            ((isset($options['remove_script_host']) ? $options['remove_script_host'] : 'true')),
            ((!empty($this->_View->viewVars['site_rtl']) ? 'rtl' : 'ltr')),
        );

        $jsReplace = str_replace($search,$replace,$this->_tinyMCE);
        $this->_View->addInitJs($jsReplace);

        return $this->textarea($fieldName, $options);
    }

    public function textarea1($fieldName, $options = array()) {
        // Feature 1 : This textarea is going to grow when you fill it with text. Just type a few more words in it and you will see.
        // --- Check feature 1 is enable
        // --- Register for including the script moocore/lib/jquery-elastic.js
        // --- Reigster for mooinit
        $autoGrow = isset($options['autoGrow']) ? $options['autoGrow'] : true;
        if(isset($options['class']) && strpos('no-grow',$options['class'])!== false)
            $autoGrow = false;
        if($autoGrow){
            // load script and make sure that  load it one
            if(!$this->isLoaded['javascript']['autoGrow']){
                $this->isLoaded['javascript']['autoGrow'] = true;
                $this->_View->Helpers->Html->script(
                    array('moocore/lib/jquery-elastic'), array('block' => 'mooScript')
                );
            }
            if(!$this->isLoaded['mooInit']['autoGrow']){
                $this->isLoaded['mooInit']['autoGrow'] = true;
                $this->_View->addInitJs('$(function() {$("textarea.autoGrow").autogrow();});');
            }
            if(!isset($options['class'])){
                $options['class'] = "autoGrow";
            }else{
                $options['class'] .= " autoGrow";
            }

        }
        return parent::textarea($fieldName,$options);
    }
    public function textarea($fieldName, $options = array(), $userMention = false, $userEmoji = true){

        $options = $this->_initInputField($fieldName, $options);
        $name = $options['name'];
        $textarea_id = !empty($options['id']) ? $options['id'] : $name;
        $script = '';

        if($userMention){
            $this->_View->loadLibrary('mentionOverLay');

            $this->_View->loadLibrary('userMention');
            if(!$this->isLoaded['javascript']['mention']){
                $this->isLoaded['javascript']['mention'] = true;
                $this->_View->Helpers->Html->scriptBlock(
                    "require(['jquery','mooMention','mooEmoji'], function($,mooMention,mooEmoji) {\$(document).ready(function(){ var textAreaId = '" .$textarea_id. "'; var type = 'activity'; mooMention.init(textAreaId,type); mooEmoji.init(textAreaId,type); });});",
                    array(
                        'inline' => false,

                    )
                );
            }
        }
        
        return parent::textarea($fieldName, $options).$script;
    }
}