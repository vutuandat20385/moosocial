<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class CronListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'MooView.beforeRender' => 'beforeRender',
        );
    }

    public function beforeRender($event) {
        $v = $event->subject();
        $url = $v->request->base . '/cron/task/run?key=' . Configure::read('Cron.cron_key');
        if (Configure::read('Cron.cron_javascript')) {
            
            if (isset($v->request->params['admin']) && $v->request->params['admin']) {
                // do not call cron ajax on Admincp
            } else {
                if ($v instanceof MooView) {
                    $v->Helpers->Html->scriptBlock(
                            "require(['jquery','mooAjax'], function($, mooAjax) {\$(document).ready(function(){ mooAjax.get({'url':'$url'}, function(data) { }); });});", array(
                        'inline' => false,
                            )
                    );
                }
            }
        }
    }

}
