<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Cron.Task','CronTaskAbstract');
class MailTaskCron extends CronTaskAbstract
{
	protected $_max;

  	protected $_count;

  	protected $_break;

  	protected $_offset;
  
	public function __construct( $task,$cron = null)  	
	{  		
		parent::__construct($task,$cron);
  		$this->init();
  	}
  	
	public function init()
	{
		App::import('Component', 'Mail.MooMailComponent');
        $this->MooMailComponent = new MooMailComponent(new ComponentCollection());
		$this->Mailrecipient = ClassRegistry::init('Mailrecipient');
	}
	
    public function execute()
    {
    	$mailSetting = Configure::read('Mail');
    	$this->_max = isset($mailSetting['mail_count']) ? $mailSetting['mail_count'] : 10;
	    $this->_count = 0;
	    $this->_break = false;
	    $this->_offset = 0;
	    $db = $this->Mailrecipient->getDataSource();
	    // Loop until no mail left or count is reached
	    while( $this->_count <= $this->_max && $this->_offset <= $this->_max && !$this->_break ) {
			// We should run each mail in a try-catch-transaction, not all at once
	      	$db->begin();
	      	try {
	        	$this->_processOne();
	        	$db->commit();
	      	} catch( Exception $e ) {
				$db->rollBack();
	      	}
	      	$this->_offset++;
	    }
    }
    
	protected function _processOne()
	{
	    // Select a single mail item	    
	    $mailRow = $this->Mailrecipient->find('first', array(
	        'order' => array('Mailrecipient.priority' => 'desc','id'=>'ASC')
	    ));
	    if( !$mailRow ) {
			$this->_break = true;
			return;
	    }else 
	    {
	    	$mailRow = $mailRow['Mailrecipient'];
			$params = unserialize($mailRow['params']);
			
			$this->MooMailComponent->sendRow($mailRow['recipient'], $mailRow['type'], $params);
			$this->Mailrecipient->delete($mailRow['id']);
	    }
  }
}