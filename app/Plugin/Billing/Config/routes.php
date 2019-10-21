<?php
Router::connect('/admin/billing/billing_settings', array('plugin' => 'billing', 'controller' => 'currencies', 'action' => 'index', 'admin' => true));
