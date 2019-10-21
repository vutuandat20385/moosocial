<?php
App::uses('BillingListener','Billing.Lib');
CakeEventManager::instance()->attach(new BillingListener());