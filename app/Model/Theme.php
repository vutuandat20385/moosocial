<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Theme extends AppModel 
{
    //public $tablePrefix = 'ms_';
    public $hasMany = array(
        'MyCorePage'=>array(
            'className' => 'CorePage',
            'foreignKey' => 'theme_id',
            
        )
    );

	public $validate = array(	
						'name' => 	array( 	 
							'rule' => 'notBlank',
							'message' => 'Name is required'
						),
						'key' => 	array( 	 
							'key' => array(
								  'rule' => 'alphaNumeric',
								  'allowEmpty' => false,
								  'message' => 'Key must only contain letters and numbers'
							),
							'uniqueKey' => array(
								  'rule' => 'isUnique',
								  'message' => 'Key already exists'
						    )
						)											
	);
	
	public function getThemes()
	{
		$site_themes = Cache::read('site_themes');
        
        if ( empty($site_themes) ) 
        {
            $site_themes = $this->find('list', array( 'fields' => array( 'Theme.key', 'Theme.name' ) ) );
            Cache::write('site_themes', $site_themes);
        }
		
		return $site_themes;
	}
        
        public function getCustomEnable($theme_key)
	{
		$theme_custom_enable = Cache::read('theme_custom_enable_' . $theme_key);
                
                if (!$theme_custom_enable) 
                {
                    $theme_custom_enable = 2;
                    $site_theme = $this->findByKey($theme_key);
                   
                    if($site_theme && $site_theme['Theme']['custom_css_enable']){
                        $theme_custom_enable = 1;
                        Cache::write('theme_custom_enable_' . $theme_key, $theme_custom_enable);
                    }
                }
        
		return $theme_custom_enable;
	}
        
        public function getCustomCss($color_arr){
            $page_background = $color_arr['page_background'];
            $header_background = $color_arr['header_background'];           
            $header_background_image = $color_arr['header_background_image'];
            if(!empty($header_background_image)){
                $header_background_image = 'url("../../../' . $header_background_image . '")';
            }
            $header_icons_color = $color_arr['header_icons_color'];
            $search_bar_color = $color_arr['search_bar_color'];
            $search_bar_icon_text_color = $color_arr['search_bar_icon_text_color'];
            $navigation_background_color = $color_arr['navigation_background_color'];
            $navigation_text_color = $color_arr['navigation_text_color'];
            $navigation_active_background_color = $color_arr['navigation_active_background_color'];
            $navigation_active_text_color = $color_arr['navigation_active_text_color'];
            $dropdown_background_color = $color_arr['dropdown_background_color'];
            $dropdown_hover_background_color = $color_arr['dropdown_hover_background_color'];
            $dropdown_text_color = $color_arr['dropdown_text_color'];
            $block_header_background = $color_arr['block_header_background'];
            $footer_text_color = $color_arr['footer_text_color'];
            $footer_background = $color_arr['footer_background'];
            $footer_text_link_color = $color_arr['footer_text_link_color'];
            $bottom_bar_background = $color_arr['bottom_bar_background'];
            $bottom_bar_icons_color = $color_arr['bottom_bar_icons_color'];
            $body_no_landing = '';
            $no_landing_page = '';
            if(!$color_arr['apply_to_landing_page']){
                $body_no_landing = ':not(#page_guest_home-index)';
                $no_landing_page = 'body:not(#page_guest_home-index)';
            }
         $content = "/*Page Background*/
             ";
         if(!empty($page_background)){
            $content .= "body$body_no_landing{
                background:$page_background !important;
            }
            ";
         }
         
         if(!empty($header_background) || !empty($header_background_image)){
            $content .= "/*Header Background*/
		$no_landing_page #header_mobi,
                $no_landing_page #header,
                $no_landing_page .header-bg{
                    background:$header_background $header_background_image !important ;
                }
		 @media (max-width:991px){
                $no_landing_page .main-menu-content{
                    background:$header_background $header_background_image !important ;
                }
            }
            ";
         }
         
         if(!empty($header_icons_color)){
            $content .= " /*Header Icons Color:*/
               
                        $no_landing_page .notify_content > a i{
                                color:$header_icons_color !important;
                        }                
            ";
         }
         
         if(!empty($search_bar_color)){
            $content .= "/*Search Bar Color:*/
	     @media (min-width: 992px){
                $no_landing_page .global-search{
                        background-color:$search_bar_color !important;
                }
            $no_landing_page #global-search{
                    background-color:$search_bar_color !important;
            }
        }
            ";
         }
         
         if(!empty($search_bar_icon_text_color)){
            $content .= " /*Search Bar Icon & Text Color:*/
            $no_landing_page .global-search > i,
            $no_landing_page .global-search input#global-search{
                    color:$search_bar_icon_text_color !important;
            }
            $no_landing_page .global-search input#global-search:-ms-input-placeholder {
                color:$search_bar_icon_text_color !important;
            }
            $no_landing_page .global-search input#global-search::-webkit-input-placeholder {
                color:$search_bar_icon_text_color !important;
            }
            $no_landing_page .global-search input#global-search::-moz-placeholder {
                color:$search_bar_icon_text_color !important;
                opacity :1;
            }
            ";
         }
         
         if(!empty($navigation_background_color)){
            $content .= "/*Navigation Background Color*/
	       $no_landing_page #mobi_menu,
            $no_landing_page #header,
            $no_landing_page .sl-navbar{
                    background:$navigation_background_color !important;
            }
            ";
         }
         
          if(!empty($navigation_text_color)){
            $content .= " /*Navigation Text Color:*/
                   $no_landing_page .menu-account.menu_top_list > li > a,
                   $no_landing_page #main_menu > li > span,
                    $no_landing_page #main_menu > li > span > i,
                    $no_landing_page #main_menu > li > a > i,
                    $no_landing_page #main_menu > li > a{
                        color:$navigation_text_color !important;
                    }
           
            ";
         }
                
         if(!empty($navigation_active_background_color)){
            $content .= "/*Navigation Active Background Color:*/
                    $no_landing_page .menu-account.menu_top_list > li > a:hover,
                    $no_landing_page .menu-account.menu_top_list > li:hover,
                    $no_landing_page #main_menu > li > a.active, 
                    $no_landing_page #main_menu > li:hover, 
                    $no_landing_page #main_menu > li > span:hover, 
                    $no_landing_page #main_menu > li.current > a, 
                    $no_landing_page #main_menu > li > a:hover,
                    $no_landing_page #main_menu > li > a:active{
                        background:$navigation_active_background_color !important;
                    }
            
            ";
         }
         
         if(!empty($navigation_active_text_color)){
            $content .= "/*Navigation Active Text Color:*/
            
                    $no_landing_page #main_menu > li > a.active, 
                    $no_landing_page #main_menu > li:hover > a, 
                    $no_landing_page #main_menu > li > span:hover, 
                    $no_landing_page #main_menu > li.current > a, 
                    $no_landing_page #main_menu > li > a:hover,
                    $no_landing_page #main_menu > li > a:active{
                        color:$navigation_active_text_color !important;
                    }
            
            ";
         }
         
          if(!empty($dropdown_background_color)){
            $content .= " /*Dropdown Background Color:*/
           
                    $no_landing_page ul#main_menu li ul{
                            background:$dropdown_background_color !important;
                    }
           
            ";
         }
         
           if(!empty($dropdown_hover_background_color)){
            $content .= " /*Dropdown Hover Background Color:*/
            
                    $no_landing_page #main_menu ul li a.active, 
                    $no_landing_page #main_menu ul li:hover, 
                    $no_landing_page #main_menu ul li span:hover, 
                    $no_landing_page #main_menu ul li.current a, 
                    $no_landing_page #main_menu ul li a:hover,
                    $no_landing_page #main_menu ul li a:active{
                            background:$dropdown_hover_background_color !important;
                    }
           
            ";
         }
         
          if(!empty($dropdown_text_color)){
            $content .= "/*Dropdown Text Color:*/
           
                    $no_landing_page #main_menu ul li a{
                        color:$dropdown_text_color !important;
                    }
            
            ";
         }
         
          if(!empty($block_header_background)){
            $content .= "/*Block Header Background:*/
            $no_landing_page .box2 h3{
                    background:$block_header_background !important;
            }
            ";
         }
         
          if(!empty($footer_text_color)){
            $content .= "/*Footer Text Color:*/
            $no_landing_page div#footer * {
                color:$footer_text_color;
            }
            ";
         }
         
         if(!empty($footer_background)){
            $content .= "/*Footer Background:*/
            $no_landing_page #footer{
                    background:$footer_background !important;
            }
            ";
         }
         
         if(!empty($footer_text_link_color)){
            $content .= "/*Footer Text Link Color:*/
            $no_landing_page #footer ul.navbar-nav > li > span, $no_landing_page #footer ul.navbar-nav > li > a{
                    color:$footer_text_link_color !important;
            }
            ";
         }
         
         if(!empty($bottom_bar_background)){
            $content .= "/*Mobile*/
            /*Bottom Bar Background:*/
            $no_landing_page .mobile-footer{
                    background:$bottom_bar_background !important;
            }
            ";
         }
         
         if(!empty($bottom_bar_icons_color)){
            $content .= " /*Bottom Bar Icons Color:*/
            @media (max-width: 991px){
                    $no_landing_page .mobile-footer i,
                    $no_landing_page .notify_content > a i{
                            color:$bottom_bar_icons_color !important;
                    }
            }
            ";
         }
            return $content;
        }

}
