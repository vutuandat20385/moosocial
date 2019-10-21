<?php
App::import('Cron.Task','CronTaskAbstract');
class SubscriptionTaskSubscription extends CronTaskAbstract
{
    public function execute()
    {
    	$subscribeModel = MooCore::getInstance()->getModel('Subscription.Subscribe');
    	$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
        //Do expire        
        $items = $subscribeModel->find('all',
        	array(	
        		'conditions' => array(
        			'Subscribe.active' => 1,
        			'Subscribe.expiration_date <' => date("Y-m-d H:i:s",strtotime('- 2 hours')),
        			'Subscribe.expiration_date <>' => 'NULL',
        		),
        		'limit'=>10
        	)
        );
        foreach ($items as $subscribe)
        {
        	$helper->onExpire($subscribe);
        }
        
        //Do remember
        $items = $subscribeModel->find('all',
        	array(	
        		'conditions' => array(
        			'Subscribe.active' => 1,
        			'is_warning_email_sent' => 0,
        			'Subscribe.reminder_date <>' => 'NULL',
        			'Subscribe.reminder_date <' => date("Y-m-d H:i:s"),
        		),
        		'limit'=>10
        	)
        );
        $timeHelper = MooCore::getInstance()->getHelper('Core_Time');
        $request = Router::getRequest();
    	foreach ($items as $subscribe)
        {
        	$plan = $subscribe['SubscriptionPackagePlan'];
			$package = $subscribe['SubscriptionPackage'];
        	//Send email
			$ssl_mode = Configure::read('core.ssl_mode');
	        $http = (!empty($ssl_mode)) ? 'https' :  'http';
	        $mailComponent = MooCore::getInstance()->getComponent('Mail.MooMail');
	        $current_language = Configure::read('Config.language');
	        if ($subscribe['User']['lang'])
				Configure::write('Config.language',$subscribe['User']['email']);	        
	        $params = array(
	        	'subscription_title' => $package['name'],
	        	'subscription_description' => $package['description'],
	        	'link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.'/subscription/subscribes/upgrade',
	        	'expire_time' =>  $timeHelper->format($subscribe['Subscribe']['expiration_date'],Configure::read('core.date_format'),null,$subscribe['User']['timezone']),
	        	'plan_title' => $plan['title'],
				'plan_description' => $helper->getPlanDescription($plan, $subscribe['Subscribe']['currency_code'])
	        );
	        
	        if ($subscribe['User']['lang'])
	        	Configure::write('Config.language',$current_language);
	        
	        $mailComponent->send($subscribe['User']['email'],'subscription_reminder',$params);
        	
        	$subscribeModel->id = $subscribe['Subscribe']['id'];
        	$subscribeModel->save(array('is_warning_email_sent'=>1));
        }
    }
}