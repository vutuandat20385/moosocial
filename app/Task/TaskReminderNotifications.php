<?php 
App::import('Cron.Task','CronTaskAbstract');
class TaskReminderNotifications extends CronTaskAbstract
{
    public function execute()
    {
    	// send notifications summary emails
		$emails = array();
		$notificationModel = MooCore::getInstance()->getModel('Notification');		
		
		$notifications = $notificationModel->getRecentNotifications();
		$mailComponent = MooCore::getInstance()->getComponent('Mail.MooMail');
		
		foreach ( $notifications as $noti )
			$emails[$noti['User']['email']][] = $noti;		

		foreach ( $emails as $email => $data )
		{			
			$params = array(				
				'element' => 'email/notification',
				'data' => $data,
				'mail_queueing' => true
			);
			$mailComponent->send( $email, 'notifications_summary',$params);
		}
    }
}