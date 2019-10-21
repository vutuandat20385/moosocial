<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class EventSettingsController extends EventAppController{
    public $components = array('QuickSettings');
    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
    }
    public function admin_index()
    {
        $event_enabled = Configure::read('Event.event_enabled');
        if($event_enabled == 0)
        {
            $this->loadModel('Menu.CoreMenuItem');
            $events_menu = $this->CoreMenuItem->find('first',array(
                'conditions'=>array('url'=>'/events','type'=>'page')
            ));

            if ($events_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $events_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 0));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Events',
                    'url' => '/events',
                    'is_active' => 0,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        }
        elseif($event_enabled == 1)
        {
            $this->loadModel('Menu.CoreMenuItem');
            $events_menu = $this->CoreMenuItem->find('first',array(
                'conditions'=>array('url'=>'/events','type'=>'page')
            ));

            if ($events_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $events_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 1));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Events',
                    'url' => '/events',
                    'is_active' => 1,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        }
        // clear cache menu
        Cache::clearGroup('menu', 'menu');
        Cache::clearGroup('event');

        $this->QuickSettings->run($this, array("Event"));

    }

}