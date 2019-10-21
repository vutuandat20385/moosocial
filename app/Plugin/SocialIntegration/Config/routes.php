<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
Router::connect('/admin/social_integration/social_integration_settings', array('plugin' => 'social_integration', 'controller' => 'providers', 'action' => 'facebook', 'admin' => true));

Router::connect('/admin/social/:action/*', array('plugin' => 'social_integration', 'controller' => 'providers', 'admin' => true));


Router::connect(
        '/social/:controller/:action/:provider/*', array('plugin' => 'social_integration'), array('
        pass' => array('provider')
        )
);

Router::connect('/social/:controller', array('plugin' => 'social_integration'));

