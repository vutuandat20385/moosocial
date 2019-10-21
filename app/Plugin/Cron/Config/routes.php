<?php
Router::connect('/admin/cron/cron_settings', array('plugin' => 'cron', 'controller' => 'task', 'action' => 'settings', 'admin' =>true));
