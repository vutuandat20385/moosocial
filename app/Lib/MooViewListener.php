<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class MooViewListener implements CakeEventListener
{
    public $v;
    public $libraryLoaded = array();

    public function implementedEvents()
    {
        return array(
            'mooView.loadLibrary' => 'loadLibrary',

        );
    }

    public function setView($v)
    {
        $this->v = $v;
    }

    public function getView()
    {
        return $this->v;
    }

    public function loadLibrary($event)
    {
        $version = Configure::read('core.version');
        $v = $event->subject();
        $this->setView($v);
        $libs = $event->data['libs'];
        foreach ($libs as $lib) {
            if (!$this->isLoaded($lib)) {
                $this->setLoaded($lib);
                switch ($lib) {
                    case 'requireJS': 
                        $this->loadRequireJs();
                        break;
                    case 'bootstrap':
                        $v->Helpers->Html->script(
                            array('global/bootstrap/js/bootstrap.min.js'), array('block' => 'mooScript')
                        );
                        break;
                    case 'foundation':
                        break;
                    case 'jquery':
                        $v->Helpers->Html->script(
                            array('global/jquery-1.11.1.min.js'), array('block' => 'mooScript')
                        );
                        break;
                    case 'mooCore':
                        $this->initMentionOverLay();

			if (!$v->request->is('mobile'))
			{				
                            $this->initUserMention();
                            $v->Helpers->Html->script(
                                array('moocore/mention.js?v=1'), array('block' => 'mooScript')
                            );
                            
			}
                        
                        if ($v->Helpers->Auth->user('id')) {
                            $viewer = MooCore::getInstance()->getViewer();
                            $confirmed = $viewer['User']['confirmed'] ? $viewer['User']['confirmed'] : 0;
                            $approved = $viewer['User']['approved'] ? $viewer['User']['approved'] : 0;
                            $v->Html->scriptBlock(
                                'var mooViewer = {'
                                . '"is_confirmed":' . $confirmed . ','
                                . '"is_approved":' . $approved
                                . '};',
                                array('inline' => false)
                            );
                        }
                        $require_email_validation = Configure::read('core.email_validation') ? Configure::read('core.email_validation') : 0;
                        $approve_users = Configure::read('core.approve_users') ? Configure::read('core.approve_users') : 0;
                        $v->Html->scriptBlock(
                            'var mooCore = {'
                            . '"setting.require_email_validation":' . $require_email_validation . ','
                            . '"setting.approve_users":' . $approve_users . ','
                            . '};',
                            array('inline' => false)
                        );
                        $this->initPhraseJs();
                        $v->Helpers->MooPopup->register('themeModal');
                        if($v->isEnableJS('Requirejs')){
                            return $this->loadRequireJs();
                        }
                        
                        $js = $this->initJss($version);
                        $js .= $this->initJssBootstrap($version);
                        $this->getView()->prepend('mooScript', $js);
                        $css = $this->initCssBootstrap($version);
                        $css .= $v->Helpers->Html->css(array(
                                'fontello/css/fontello.css'
                            )
                        );
                        $css .= $this->initCss($version);
                        $this->getView()->prepend('css', $css);
                        if (!empty($v->viewVars['uid'])) {
                            $v->addInitJs('$(function() { MooNotification.init(); });');
                        }
                        $v->addInitJs('ServerJS.init();');
                        $this->renderPhraseJS();
                        
                        $v->addInitJs('$(function() { MooPhoto.init(); });');
                        $v->addInitJs('$(function() { mooBehavior.initAutoLoadMore(); });');
                        $v->getEventManager()->dispatch(new CakeEvent('MooView.afterLoadMooCore', $v, $version));
                        break;
                    case 'googleMap':
                        $data =
                            'var map;'
                            . 'var myLatlng;'
                            . 'var geocoder = new google.maps.Geocoder();'
                            . 'geocoder.geocode( { "address": "' . htmlspecialchars($v->viewVars['address']) . '"}, function(results, status) {'
                            . 'if (status == google.maps.GeocoderStatus.OK) {'
                            . '    myLatlng = new google.maps.LatLng(results[0].geometry.location.lat(),results[0].geometry.location.lng());'
                            . '}else{'
                            . '    myLatlng = new google.maps.LatLng(0,0);'
                            . '}';
                        $afterGetGeoCode =
                            '});';
                        if (!$v->request->is('ajax')) {
                            if (!($v->viewVars['isAjaxModal'])) {
                                $v->Helpers->Html->scriptBlock(
                                    $data . $afterGetGeoCode, array('block' => 'mooScript')
                                );
                            }
                        } else {
                            $data .= 'if (typeof initialize == \'function\') {initialize();}';
                            echo $v->Helpers->Html->scriptBlock($data . $afterGetGeoCode);
                        }
                        break;
                    case 'userTagging':
                        $this->initUserTagging();
                        break;
                    case 'userMention':
                        $this->initUserMention();
                        break;
                    case 'userEmoji':
                        $this->initUserEmoji();
                        break;
                    case 'mentionOverLay':
                        $this->initMentionOverLay();
                        break;
                    case 'tagCloud':
                        $this->initTagCloud();
                        break;
                    case 'adm':
                        $this->initPhraseJs();
                        $this->renderPhraseJS();
                        break;

                }
            }

        }
    }

// Initialing the mooSocial phrases for javascript functions
    public function initPhraseJs()
    {
        
        return $this->getView()->addPhraseJs(array(
            'btn_ok' => __("OK"),
            'btn_done' => __("Done"),
            'message' => __('Message'),
            'btn_cancel' => __("Cancel"),
            'users' => __('users'),
            'btn_upload' => __("Upload a file"),
            'btn_retry' => __("Retry"),
            'failed_upload' => __("Upload failed"),
            'drag_zone' => __("Drag Photo Here"),
            'format_progress' => __("of"),
            'waiting_for_response' => __("Processing..."),
            'loading' => __("Loading..."),
            'warning' => __("Warning"),
            'comment_empty' => __("Comment can not empty"),
            'share_whats_new_can_not_empty' => __("Share whats new can not empty"),
            'please_login' => __("Please login to continue"),
            'please_confirm' => __("Please confirm"),
            'please_confirm_your_email' => __("Please confirm your email address."),
            'your_account_is_pending_approval' => __("Your account is pending approval."),
            'confirm_title' => __("Please Confirm"),
            'send_email_progress' => __('Adding emails to temp place for sending.....'),
            'fineupload_uploadbutton' => __('Upload a file'),
            'fineupload_cancel' => __('Cancel'),
            'fineupload_retry' => __('Retry'),
            'fineupload_title_file' => __('Attach a photo'),
            'fineupload_failupload' => __('Upload failed'),
            'fineupload_dragzone' => __('Drop files here to upload'),
            'fineupload_dropprocessing' => __('Processing dropped files...'),
            'fineupload_formatprogress' => __('{percent}% of {total_size}'),
            'fineupload_waitingforresponse' => __('Processing...'),
        	'fineupload_typeerror' => __('{file} has an invalid extension. Valid extension(s): {extensions}.'),
        	'fineupload_sizeerror' => __('{file} is too large, maximum file size is {sizeLimit}.'),
        	'fineupload_minsizeerror' => __('{file} is too small, minimum file size is {minSizeLimit}.'),
        	'fineupload_emptyerror' => __('{file} is empty, please select files again without it.'),
        	'fineupload_nofileserror' => __('No files to upload.'),
        	'fineupload_onleave' => __('The files are being uploaded, if you leave now the upload will be cancelled.'),
            'confirm_delete_comment' => __('Are you sure you want to remove this comment?'),
            'confirm_login_as_user' => __('Are you sure you want to login as this user?'),
            'are_you_sure_leave_this_page' => __("The files are being uploaded, if you leave now the upload will be cancelled."),
            'processing_video' => __("Processing Video"),
            'processing_video_msg' => __("Your video is uploaded successfully, please standby while we converting your video."),
            'birthday_wish_is_sent' => __('Birthday wish is sent'),
            'cancel_a_friend_request' => __('Cancel a friend request'),
            'cancel_request' => __('Cancel Request'),
            'please_select_area_for_cropping' => __('Please select area for cropping'),
            'you_have_to_agree_with_term_of_service' => __('You have to agree with term of service'),
            'per_selected' => __('% selected'),
            'are_you_sure_you_want_to_delete_these' => __('Are you sure you want to delete these'),
            'your_invitation_has_been_sent' => __('Your invitation has been sent'),
            'your_message_has_been_sent' => __('Your message has been sent'),
            'please_choose_an_image_that_s_at_least_400_pixels_wide_and_at_least_150_pixels_tall' => __("Please choose an image that's at least 400 pixels wide and at least 150 pixels tall"),
            'cannot_determine_dimensions_for_image_may_be_too_large' => __('Cannot determine dimensions for image. May be too large.'),
            'join_group_request' => __('Join Group Request'),
            'your_request_to_join_group_sent_successfully' => __('Your request to join group sent successfully'),
            'turn_on_notifications' => __('Turn on notifications'),
            'stop_notifications' => __('Stop notifications'),
            'please_select_friends_to_share' => __('Please select friends to share.'),
            'please_select_groups_to_share' => __('Please select groups to share.'),
            'please_input_emails_to_share' => __('Please input emails to share.'),
            'status' => __('Status'),
            'validation_link_has_been_resend' => __('Validation link has been resent.'),
            'confirm_deactivate_account' => __('Are you sure you want to deactivate your account? Your profile will not be accessible to anyone and you will not be able to login again!'),
            'confirm_delete_account' => __('Are you sure you want to permanently delete your account? All your contents (including groups, topics, events...) will also be permanently deleted!'),
            'text_follow' => __('Follow'),
            'text_unfollow' => __('Unfollow'),
            'the_user_has_been_blocked' => __('The user has been blocked'),
        	'text_your_change_save' => __('Your changes have been saved'),
            'open_comment' => __('Open Comment'),
            'close_comment' => __('Close Comment'),
        	'upload_error' => __('An error occurred during uploading file.'),
        	'drag_or_click_here_to_upload_photo' => __("Drag or click here to upload photo"),
        ));
    }

    // Initialing the core css need to be loaded
    public function initCss($version = 1)
    {
    	$v = "?v=".Configure::read('core.link_version');
        if (Configure::read('debug') == 0){
            $v = '';
        }
        else
        {
            $this->getView()->Helpers->Html->css(array(
                'sqllog.css' . $v
            ));
        }
                     
        $css_init = array(
                'common.css' . $v,
                'feed.css' . $v,
                'video.css' . $v,
                'blog.css' . $v,
                'event.css' . $v,
                'group.css' . $v,
                'photo.css' . $v,
                'topic.css' . $v,
                'button.css' . $v,
                'subscription.css' . $v,
                'main.css' . $v,
                'custom.css' . $v,
                'elastislide.css' . $v,
                'fineuploader.css' . $v,
                'pickadate.css' . $v,
                'jquery.Jcrop.css' . $v,
                'jquery.mp.css' . $v,
                'token-input.css' . $v,
                'qtip.css' . $v,
        );
        
        $themeModel =  MooCore::getInstance()->getModel('Theme');
        $custom_css_enable = $themeModel->getCustomEnable($this->getView()->theme);
        
        if($custom_css_enable == 1){        
           $css_init[] = 'theme-setting.css' . $v;
        }
        
        return $this->getView()->Helpers->Html->css($css_init);
    }

    public function initCssBootstrap($version = 1)
    {
    	$v = "?v=".Configure::read('core.link_version');
        return $this->getView()->Helpers->Html->css(array(
                //'font-awesome/css/font-awesome.min.css' . $v,
                'bootstrap.3.2.0/css/bootstrap.min.css' . $v,
            ),
            array('minify'=>false)
        );
    }

    // Initialing the core javascripts need to be loaded
    public function initJss($version = 1)
    {
        $js = array(
            'global/jquery-1.11.1.min.js',
            'mooajax.js',
            'jquery.kinetic.min.js',
            'vendor/jquery.autogrow-textarea.js',
            'vendor/jquery.tipsy.js',
            'vendor/tinycon.min.js',
            'vendor/jquery.multiselect.js',
            'vendor/jquery.menubutton.js',
            'vendor/spin.js',
            'vendor/spin.custom.js',
            'vendor/jquery.placeholder.js',
            'vendor/jquery.simplemodal.js',
            'vendor/jquery.hideshare.js',
            'global.js',
            'moocore/ServerJS.js',
            'notification.js',
			'photo_theater.js',
        	'photo.js',
            'elastislide/jquerypp.custom.js',
            'elastislide/modernizr.custom.17475.js',
            'elastislide/jquery.elastislide.js',
            
        );


        if ($this->getView()->ngController) {
            $js[] = 'global/angular.min.js';
            $js[] = 'global/angular-route.min.js';
            $js[] = 'global/angular-sanitize.min.js';
            $js[] = 'global/angular-lodash.compat.min.js';
            $js[] = 'global/angular-restangular.min.js';

            $js[] = 'angular/app.js?' . $version;
            $js[] = "angular/" . (empty($this->getView()->params['plugin']) ? "" : $this->getView()->params['plugin'] . "/") . $this->getView()->ngController . ".js";
        }
        $js = $this->getView()->Helpers->Html->script($js);
        return $js;

    }

    // Initialing the bootstrap logic javascripts need to be loaded
    public function initJssBootstrap($version = 1)
    {
        return $this->getView()->Helpers->Html->script(
            array(
                'global/bootstrap/js/bootstrap.min.js',
                'jquery.slimscroll.js',
                'responsive.js',
            )
        );
    }

    // Initialing the typehead and tag manager for user tagging
    public function initUserTagging()
    {
        $v = $this->getView();
        $v->Helpers->Html->css(array(
            'global/typehead/bootstrap-tagsinput.css',
        ),
            array('block' => 'css','minify'=>false)
        );
        if(!$v->isEnableJS('Requirejs')){
            $v->Helpers->Html->script(
                array(
                    'global/typeahead/typeahead.bundle.js',
                    'global/typeahead/bootstrap-tagsinput.js',
                ),
                array('block' => 'mooScript')
            );
        }else{
            $v->Helpers->MooRequirejs->addPath(array(
                'typeahead'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/typeahead.jquery.js'),
                'bloodhound'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/bloodhound.min.js'),
                'tagsinput'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/bootstrap-tagsinput.js'),
            ));
            $v->Helpers->MooRequirejs->addShim(array(
                'tagsinput'=>array("deps" =>array('jquery','typeahead','bloodhound')),
            ));
        }

    }

    // Initialing the typehead and mention manager for user mention
    public function initUserMention()
    {
        $v = $this->getView();

        if(!$v->isEnableJS('Requirejs')){
            $v->Helpers->Html->script(
                array(
                    'global/typeahead/typeahead.bundle.js',
                    'global/jquery-textcomplete/jquery.textcomplete.js',
                ),
                array('block' => 'mooScript')
            );
        }else{
            $v->Helpers->MooRequirejs->addPath(array(
                    'typeahead'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/typeahead.jquery.js'),
                    'bloodhound'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/bloodhound.min.js'),
                    'textcomplete'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/jquery-textcomplete/jquery.textcomplete.js'),
                ));
            $v->Helpers->MooRequirejs->addShim(array(
                    'typeahead'=>array("deps" =>array('jquery'),'exports'=> 'typeahead'),
                ));
        }

    }

    public function initUserEmoji()
    {
        $v = $this->getView();
        if(!$v->isEnableJS('Requirejs')){
            $v->Helpers->Html->script(
                array(
                    'global/jquery-textcomplete/jquery.textcomplete.js',
                ),
                array('block' => 'mooScript')
            );
        }else{
            $v->Helpers->MooRequirejs->addPath(array(
                    'textcomplete'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/jquery-textcomplete/jquery.textcomplete.js'),
                ));
        }

    }

    public function initMentionOverLay()
    {
        $v = $this->getView();
        if(!$v->isEnableJS('Requirejs')){
            $v->Helpers->Html->script(
                array(
                    'global/jquery-overlay/jquery.overlay.js'
                ),
                array('block' => 'mooScript')
            );
        }else{
            $v->Helpers->MooRequirejs->addPath(array(
                    'overlay'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/jquery-overlay/jquery.overlay.js'),
                ));
        }
    }

    // Initialing the typehead and tag manager for user tagging
    public function initTagCloud()
    {
        $this->getView()->Helpers->Html->css(array(
                'jqcloud.css',
            ),
            array('block' => 'css')
        );
        $this->getView()->Helpers->Html->script(
            array(
                'jqcloud-1.0.4.min.js',
            ),
            array('block' => 'mooScript')
        );
    }
    public function initRequireJs(){
        return $this->getView()->Helpers->Html->script('moocore/require.js',
            array('block' => 'mooScript')//
        );
    }
    public function isLoaded($name)
    {
        if (empty($this->libraryLoaded[$name])) return false;
        return $this->libraryLoaded[$name];
    }

    public function setLoaded($name)
    {
        $this->libraryLoaded[$name] = true;
    }

    public function removeLoaded($name)
    {
        $this->libraryLoaded[$name] = false;
    }
    
    public function renderPhraseJS(){
        if(!empty($this->getView()->phraseJs)){
            $v = $this->getView();
            if($v->isEnableJS('Requirejs')){
                $v->Helpers->Html->scriptBlock(
                    "define('mooPhrase',['jquery','rootPhrase'], function($,mooPhrase) {\$(document).ready(function(){ mooPhrase.set(".json_encode($v->phraseJs,true).") });var __ = function(name){ return mooPhrase.__(name) ; }; return { __:__ }});",
                    array(
                        'inline' => false,
                        'block' => 'mooScript'
                    )
                );
            }else{
                $v->Helpers->Html->scriptBlock(
                    "mooPhrase.set(".json_encode($v->phraseJs,true).")",
                    array(
                        'inline' => false,
                        'block' => 'mooPhrase'
                    )
                );
            }
        }
    }
    
    public function loadRequireJs(){ 
        $version = Configure::read('core.version');
        $v = $this->getView();
        $this->initRequireJs();
        $css = $this->initCssBootstrap($version);
        /*$css .= $v->Helpers->Html->css(array(
                'fontello/css/fontello.css'
            ),
            array('minify'=>false)
        );*/
        $css .= $this->initCss($version);
        $v->prepend('css', $css);
        // Hacking for minify

        if (Configure::read('debug') == 0){
            $min="min.";
        }else{
            $min="";
        }
        $v->Helpers->MooRequirejs->addPath(array(
            
            // moo amd js
            'jquery'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/jquery-1.11.1.min.js'),
            'bootstrap'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/bootstrap/js/bootstrap.min.js'),
            'server'=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/ServerJS.{$min}js"),
            'multiselect'=>$v->Helpers->MooRequirejs->assetUrlJS('js/vendor/jquery.multiselect.js'),
            'hideshare'=>$v->Helpers->MooRequirejs->assetUrlJS('js/vendor/jquery.hideshare.js'),
            'simplemodal'=>$v->Helpers->MooRequirejs->assetUrlJS("js/vendor/jquery.simplemodal.{$min}js"),
            'spin'=>$v->Helpers->MooRequirejs->assetUrlJS('js/vendor/spin.js'),
            'spinner'=>$v->Helpers->MooRequirejs->assetUrlJS("js/vendor/spin.custom.{$min}js"),
            'autogrow'=>$v->Helpers->MooRequirejs->assetUrlJS("js/vendor/jquery.autogrow-textarea.{$min}js"),
            'tipsy'=>$v->Helpers->MooRequirejs->assetUrlJS("js/vendor/jquery.tipsy.{$min}js"),
            'tinycon'=>$v->Helpers->MooRequirejs->assetUrlJS('js/vendor/tinycon.min.js'),
            'magnificPopup'=>$v->Helpers->MooRequirejs->assetUrlJS('js/jquery.mp.min.js'),
            'Jcrop'=>$v->Helpers->MooRequirejs->assetUrlJS('js/jquery.Jcrop.min.js'),
            'tinyMCE'=>$v->Helpers->MooRequirejs->assetUrlJS('js/tinymce/tinymce.min.js'),
            'picker'=>$v->Helpers->MooRequirejs->assetUrlJS('js/pickadate/picker.js'),
            'picker_date'=>$v->Helpers->MooRequirejs->assetUrlJS('js/pickadate/picker.date.js'),
            'picker_time'=>$v->Helpers->MooRequirejs->assetUrlJS('js/pickadate/picker.time.js'),
            'picker_legacy'=>$v->Helpers->MooRequirejs->assetUrlJS('js/pickadate/legacy.js'),
            'tokeninput'=>$v->Helpers->MooRequirejs->assetUrlJS('js/jquery.tokeninput.js'),
            'slimScroll'=>$v->Helpers->MooRequirejs->assetUrlJS('js/jquery.slimscroll.js'),
            'textcomplete'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/jquery-textcomplete/jquery.textcomplete.js'),
            'qtip'=>$v->Helpers->MooRequirejs->assetUrlJS('js/qtip/jquery.qtip.min.js'),        
            'jquerypp'=>$v->Helpers->MooRequirejs->assetUrlJS('js/elastislide/jquerypp.custom.js'),
            'modernizr'=>$v->Helpers->MooRequirejs->assetUrlJS('js/elastislide/modernizr.custom.17475.js'),
            'elastislide'=>$v->Helpers->MooRequirejs->assetUrlJS('js/elastislide/jquery.elastislide.js'),
        	'typeahead'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/typeahead.jquery.js'),
            'bloodhound'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/bloodhound.min.js'),
            'tagsinput'=>$v->Helpers->MooRequirejs->assetUrlJS('js/global/typeahead/bootstrap-tagsinput.js'),
            
            // moo core js
            "mooResponsive"=>$v->Helpers->MooRequirejs->assetUrlJS("js/responsive.{$min}js"),
            "mooAjax"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/ajax.{$min}js"),
            "mooTab"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/tab.{$min}js"),
            "mooAlert"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/alert.{$min}js"),
            "rootPhrase"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/phrase.{$min}js"),
            "mooOverlay"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/overlay.{$min}js"),
            "mooBehavior"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/behavior.{$min}js"),
            "mooButton"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/button.{$min}js"),
            "mooMention"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/mention.{$min}js"),
            "mooAttach"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/attach.{$min}js"),   
            "mooActivities"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/activity.{$min}js"), 
            "mooComment"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/comment.{$min}js"),
            "mooEmoji"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/emoji.{$min}js"),
            "mooNotification"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/notification.{$min}js"),
            "mooSearch"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/search.{$min}js"),
            "mooFileUploader"=>$v->Helpers->MooRequirejs->assetUrlJS("js/jquery.fileuploader.{$min}js"),
            "mooShare"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/share.{$min}js"),
            "mooUser"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/user.{$min}js"),
            "mooGlobal"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/global.{$min}js"),
            "mooLike"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/like.{$min}js"),
            'mooTooltip'=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/tooltip.{$min}js"),
            'mooToggleEmoji' =>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/toggle-emoji.{$min}js"),
            'mooBsModal' =>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/bootstrap-modal.{$min}js"),
            
            // moo plugin js
            "mooBlog"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/plugins/blog.{$min}js"),
            "mooEvent"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/plugins/event.{$min}js"),
            "mooGroup"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/plugins/group.{$min}js"),
            "mooPhoto"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/plugins/photo.{$min}js"),
            "mooPhotoTheater"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/photo_theater.{$min}js"), 
            "mooTopic"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/plugins/topic.{$min}js"),
            "mooVideo"=>$v->Helpers->MooRequirejs->assetUrlJS("js/moocore/plugins/video.{$min}js"),
            
        ));
        $v->Helpers->MooRequirejs->addShim(array(
            'global'=>array("deps" =>array(
                'jquery',
                'magnificPopup',
                'autogrow',
                'spin',
                'tipsy',
                'tokeninput',
                'tinycon',
                'multiselect',
                'vendor/jquery.menubutton',
                'vendor/jquery.placeholder',
                'simplemodal',
                'hideshare',
                'jquerypp',
                'modernizr',
                'elastislide',
                'Jcrop'
        )),
            'tinyMCE' => array("exports"=>'tinyMCE'),
            'server'=>array("exports"=>'server'),
            'bootstrap'=>array("deps" =>array('jquery')),
            'autogrow'=>array("deps" =>array('jquery')),
            'spin'=>array("deps" =>array('jquery')),
            'magnificPopup'=>array("deps" =>array('jquery')),
            'tipsy'=>array("deps" =>array('jquery')),
            'jquery.slimscroll'=>array("deps" =>array('jquery')),
            'multiselect'=>array("deps" =>array('jquery')),        	
            'hideshare'=>array("deps" =>array('jquery')),
            'simplemodal'=>array("deps" =>array('jquery','mooPhrase')),
            
            'jquerypp'=>array("deps" =>array('jquery')),
            'modernizr'=>array("deps" =>array('jquery')),
            'Jcrop'=>array("deps" =>array('jquery')),
            'tokeninput'=>array("deps" =>array('jquery')),
            'elastislide'=>array("deps" =>array('jquery', 'modernizr')),
            // Chat solution
            'babel'=>array("deps" =>array('polyfill')),
        	'tagsinput'=>array("deps" =>array('jquery','typeahead','bloodhound')),
        	'mooToggleEmoji'=>array("deps" =>array('jquery')),
        ));
        $v->Helpers->MooRequirejs->addToFirst(array('jquery','bootstrap','server'));
        $v->getEventManager()->dispatch(new CakeEvent('MooView.beforeRenderRequreJsConfig', $v, $version));
        $this->renderPhraseJS();
        $v->Helpers->Html->scriptBlock( "requirejs.config({$v->Helpers->MooRequirejs->config()});require({$v->Helpers->MooRequirejs->first()}, function($){require(['server','mooBsModal'],function(server,mooBsModal){server.init();mooBsModal.init();});});", array( 'inline' => false, 'block' => 'mooScript' ) );
        $v->getEventManager()->dispatch(new CakeEvent('MooView.afterLoadMooCore', $v, $version));
        return true;
    }
    
    public function renderTooltipJS(){
        if(!empty($this->getView()->phraseJs)){
            $v = $this->getView();
            if($v->isEnableJS('Requirejs')){
                $v->Helpers->Html->scriptBlock(
                     "require(['jquery','mooTooltip'], function($, mooTooltip) {\$(document).ready(function(){ mooTooltip.init(); });});", array(
                        'inline' => false,
                    )
                );
            }
        }
    }
}
