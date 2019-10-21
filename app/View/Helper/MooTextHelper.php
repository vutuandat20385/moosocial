<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('TextHelper', 'View/Helper');
App::uses('HtmlHelper', 'View/Helper');
class MooTextHelper extends TextHelper{
    public $helpers = array('Html');
    protected function _insertPlaceHolder($matches) {
        if(preg_match('/(w{3}).*/i',$matches[0],$found))
        {
            if(strpos($found[0],'.') === false)
                return $matches[0];
        }
        if(preg_match('/(http|https).*/i',$matches[0],$found))
        {
            if(strpos($found[0],'.') === false)
                return $matches[0];
        }
        return parent::_insertPlaceHolder($matches);
    }
    public function autoLinkUrls($text, $options = array()){
        //$text = parent::autoLinkUrls($text, $options);
        /*$enable_hashtag_activity = Configure::read('core.enable_hashtag_activity');
        if(!$enable_hashtag_activity)
        {
            if(!$this->request->params['plugin'])
            {
                if($this->request->params['controller'] != 'comments' || ($this->request->params['controller'] == 'comments' && !empty($this->request->data['activity']) ) )
                    return parent::autoLinkUrls($text,$options);
            }
        }*/

        $text = $this->convert_clickable_links_for_hashtags($text);
        return parent::autoLinkUrls($text, $options);
    }
    function convert_clickable_links_for_hashtags($message,$enable = true)
    {
        $options = array('escape' => false,'no_replace_ssl' => 1);
        
        //mention user
        $message = preg_replace_callback(REGEX_MENTION,array($this,'mentionLink'),$message);
        
        if(!$enable)
            return parent::autoLinkUrls($message, $options);
        /*if($this->request->params['plugin'] ||($this->request->params['controller'] == 'comments' && empty($this->request->data['activity']) ) )
        {
            if($this->request->params['controller'] == 'comments')
            {
                if($this->request->params['action'] == 'browse'){
                    $expl = explode('_',$this->request->params['pass'][0]);
                    $plugin = isset($expl[0]) ? $expl[0] : '';
                }
                elseif($this->request->params['action'] == 'ajax_share'){
                    $expl = explode('_',$this->request->data['type']);
                    $plugin = isset($expl[0]) ? $expl[0] : '';
                }
                elseif($this->request->params['action'] == 'ajax_editComment')
                {
                    $id = $this->request->params['pass'][0];
                    $commentModel = MooCore::getInstance()->getModel('Comment');
                    $comment = $commentModel->findById($id);
                    $expl = explode('_',$comment['Comment']['type']);
                    $plugin = isset($expl[0]) ? $expl[0] : '';
                }
            }
            else
                $plugin = $this->request->params['plugin'];
            
            $plugin = ucfirst($plugin);
            $plugin_hastag_enabled = Configure::read("$plugin.".lcfirst($plugin)."_hashtag_enabled");
            if(!$plugin_hastag_enabled )
                return parent::autoLinkUrls($message, $options);
        }*/

        $link = $this->Html->url(array(
            "plugin" => "",
            "controller" => "search",
            "action" => "hashtags",
            "param"
        ));
        $link = str_replace('param','$0',$link);
        $message = str_replace('&#039;','&apos;',$message);

        $parsedMessage = $message;
        preg_match_all('/(#\w+)/u', strip_tags($message), $matchedHashtags);
        $hashtag = '';
        // For each hashtag, strip all characters but alpha numeric
        if (!empty($matchedHashtags[0])) {

            usort($matchedHashtags[0],array($this,'sort_length'));
            $tmp = array();
            foreach ($matchedHashtags[0] as $match) {
                $match = trim($match);
                $key = md5(rand());
                while (isset($tmp[$key]))
                {
                    $key = md5(rand());
                }
                $hashtag_name = str_replace('#', '', $match);
                $tmp[$key] = '<a href="'.  str_replace('$0', $hashtag_name, $link).'">'.$match.'</a>';
                $parsedMessage = str_replace($match, $key, $parsedMessage);
            }
            foreach ($tmp as $key=>$value)
            {
                $parsedMessage = str_replace($key, $value, $parsedMessage);
            }
        }

        return $parsedMessage;
    }
    public function mentionLink($match){
        $class = '';
        $show_popup = MooCore::getInstance()->checkViewPermission(null,$match[1]);
        if($show_popup){
            $class = 'moocore_tooltip_link';
        }
        $link =$this->Html->link($match[2],array('plugin' => false, 'controller' => 'users','action' => 'view',$match[1]),array('escapeTitle'=>false,"class" => $class, "data-item_type" => "user", "data-item_id" => $match[1]));
        return $link;
    }

    public function sort_length($a,$b){
        return (strlen($b)-strlen($a));
    }

    public function formatText($text, $truncate = false, $parse_smilies = true) {}
    public function cleanHtml($text) {}
    public function parseSmilies($text) {}
}