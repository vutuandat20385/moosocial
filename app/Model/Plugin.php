<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Plugin extends AppModel 
{
    public $validate = array(   
        'name' =>   array(   
            'rule' => 'notBlank',
            'message' => ' Name is required'
        ),
        'key' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => ' Key is required'
            ),
            'characters' => array(
                'rule'     => array('custom', '/^[a-z0-9]*$/i'),
                'message'  => ' Invalid Key'
            ),
        ),
        'version' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => ' Version is required'
            ),
            'characters' => array(
                'rule'     => array('custom', '/^[0-9]+(?:\.[0-9]+)*$/'),
                'message'  => ' Invalid Version'
            ),
        ),
        'description' =>   array(   
            'rule' => 'notBlank',
            'message' => ' Description is required'
        ),
        'author' =>   array(   
            'rule' => 'notBlank',
            'message' => ' Author is required'
        ),
        'website' =>   array(   
            'rule' => 'notBlank',
            'message' => ' Website is required'
        )                                              
    );

    public $order = 'Plugin.weight asc';
    
    public function loadAll( $role_id )
    {        
        $plugins = Cache::read('plugins_' . $role_id);        
		        
        if (empty($plugins))
        {
            $plugins = $this->find('all', array( 'conditions' => array('enabled' => 1, 'menu' => 1) ) );
    
            foreach ( $plugins as $key => $plugin )
            {
                $permissions = explode(',', $plugin['Plugin']['permission']);
                
                if ( $plugin['Plugin']['permission'] !== '' && !in_array( strval($role_id), $permissions, true ) )
                    unset($plugins[$key]);
            }
            
            Cache::write('plugins_' . $role_id, $plugins, '_cache_group_');
        }
        
        return $plugins;
    }
    
    public function isIdExist($id)
    {
        return $this->hasAny(array('id' => $id));
    }
    
    public function isKeyExist($key)
    {
        return $this->hasAny(array('key' => $key));
    }
    
    public function PluginType()
    {
        return array('payment' => __('Payment'));
    }
}
