<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Cron.Task','CronTaskAbstract');
class CronTaskJobs extends CronTaskAbstract
{
	protected $_isShutdownRegistered;
	  
	protected $_isExecuting;
	
	protected $_executingJob;
	
	protected $_executingJobType;
	
	protected $Job;
	protected $Jobtype;
	
	public function __construct( $task,$cron = null)  	
	{  		
		parent::__construct($task,$cron);
  		$this->init();
  	}
  	
	public function init()
	{
		//App::import('Model', 'Cron.Job');	
		//$this->Job = new Job();
		//App::import('Model', 'Cron.Jobtype');
		//$this->Jobtype = new Jobtype();
	}
	
	public function getTotal()
	{
		return $this->Job->find('count',array('conditions' => array('is_complete = ?'=>0)));		
	}
	
	public function execute()
	{
		CakeEventManager::instance()->dispatch(new CakeEvent('Cron.Task.CronTaskJobs.Start'),$this);
		return;
		// Timeout jobs that have been executing for more than 10 minutes
		$this->Job->gc();
		
		// Get max time limit information
		$start = TIME_START;
		$limit = $this->_cron->getParam('cron_time', 120);
		$jobs = $this->_cron->getParam('cron_jobs', 3);
		$eTime = $start + $limit;
		$count = 0;
		$offset = 0;		
		// Run jobs
		$job = true;
		while( $count < $jobs &&
			($cTime = time()) <= $eTime &&
			($job = $this->_getNextJob($offset)) ) {
		  // Execute
		  $this->_executeJob($job);
		  // Increment count
		  $count++;
		  // Increment offset if getNextJob might select it again
		  if( !$job['is_complete']) {
			$offset++;
		  }
		}
		
		// Log reason for loop cancel
		if( Configure::read('debug')) {
		  if( $cTime > $eTime ) {
			$this->log(sprintf('Job Execution Loop Cancelled - Out of time: %d > %d',
				$cTime, $eTime));
			//$this->getLog()->log(sprintf('Job Execution Loop Cancelled - Out of time: NOW(%d) > END(START(%d) + LIMIT(%d) = %d)',
			//    $cTime, $start, $limit, $eTime), Zend_Log::DEBUG);
		  } else if( $count >= $jobs ) {
			$this->log(sprintf('Job Execution Loop Cancelled - Limit reached: %d >= %d',
				$count, $jobs));
		  } else if( !$job ) {
			$this->log('Job Execution Loop Cancelled - Nothing to do');
		  } else {
			$this->log('Job Execution Loop Cancelled - Unknown');
		  }
		}
		
		// Clear shutdown
		$this->_isExecuting = false;
		
		// Set idle
		if( $count <= 0 ) {
		  $this->_setWasIdle(true);
		}
	}
	
	
	
	// Utility
	
	protected function _executeJob($job)
	{
		// Get job info				
		$jobType = $this->Jobtype->findById($job['jobtype_id']);
		$jobType = $jobType['Jobtype'];
		
		// Prepare data
		$data = array();
		$where = array(
		  'id = ?' => $job['id'],
		  'state = ?' => $job['state'],
		);
		if( $job['state'] == 'pending' ) {
		  $data['state'] = "'active'";
		  $data['started_date'] = 'NOW()';
		  $data['modified_date'] = 'NOW()';
		} else if( $job['state'] == 'sleeping' ) {
		  $data['state'] = "'active'";
		  $data['modified_date'] = 'NOW()';
		} else {
		  // wth is this?
		  $this->log('Job Execution Duplicate: ' . $jobType['title'] . ' ' . $job['state']);
		  return;
		}
		
		// Attempt lock
		
		$this->Job->updateAll($data, $where);
		$affected = $this->Job->getAffectedRows();
		if( 1 !== $affected ) {
		  $this->log('Job Execution Failed Lock: ' . $jobType['title']);
		  return;
		}
		
		// Refresh
		$job = $this->Job->findById($job['id']);
		$job = $job['Job'];
		
		// Register fatal error handler
		if( !$this->_isShutdownRegistered ) {
		  register_shutdown_function(array($this, 'handleShutdown'));
		  $this->_isShutdownRegistered = true;
		}
		
		// Signal execution
		$this->_isExecuting = true;
		$this->_executingJob = $job;
		$this->_executingJobType = $jobType;
		
		// Log
		if( Configure::read('debug')) {
		  $this->log('Job Execution Start: ' . $jobType['title']);
		}
		
		// Initialize
		$isComplete = true;
		$messages = array();
		$progress = null;
		
		try {
		  
		  // Check job type
		  if( !$jobType || !$jobType['plugin'] ) {
			throw new Exception(sprintf('Missing job type with ID "%1$d"', $job['jobtype_id']));
		  }
		
		  // Get plugin
		  $class = $this->getJobClass($job, $jobType);
		
		  // Execute
		  $class->execute();
		
		  // Cleanup
		  $isComplete = (bool) $class->isComplete();		  
		
		  // If job set itself to failed, it failed. Otherwise, job may have not
		  // set a status
		  $job = $class->getJob();
		  if( $job['state'] == 'failed' || $job['state'] == 'cancelled' ) {
			$status = false;
		  } else {
			$status = true;
		  }
		
		} catch( Exception $e ) {
		  $messages[] = $e->getMessage();
		  $this->log(sprintf('Job Execution Error: [%d] [%s] %s %s', $job['id'], $jobType['type'], $jobType['title'], $e->__toString()));
		  $status = false;
		}
		
		// Log
		if( Configure::read('debug')) {
		  if( $status ) {
			$this->log(sprintf('Job Execution Complete: [%d] %s', $job['id'], $jobType['title']));
		  } else {
			$this->log(sprintf('Job Execution Complete (with errors): [%d] %s', $job['id'], $jobType['title']));
		  }
		}
		
		// Update job
		$tmp = array();
		$tmp['messages'] = $job['messages']."'".ltrim(join("\n", $messages) . "\n", "\n")."'";
		$tmp['modified_date'] = 'NOW()';
		if( !$isComplete ) {
		  $tmp['is_complete'] = false;
		  $tmp['state'] = "'sleeping'";
		} else {
		  $tmp['is_complete'] = true;
		  $tmp['state'] = ( $status ? "'completed'" : "'failed'" );
		  $tmp['completion_date'] = 'NOW()';
		}
		$this->Job->updateAll(
			$tmp, 
			array(
			  'id' => $job['id'],
			)
		);
		
		// Cleanup
		$this->_executingJobType = null;
		$this->_executingJob = null;
		$this->_isExecuting = false;
	}
	
	protected function getJobClass($job,$jobtype)
	{
		$class = $jobtype['class'];
		$class = explode('_', $class);
		$class_tmp = $class[0].$class[1].$class[2].$class[3];
		
		App::import($class[0].'.'.$class[1].'/'.$class[2],$class_tmp);
		
		return new $class_tmp($job,$jobtype);
	}
	
	protected function _getNextJob($offset = 0)
	{
		$results = $this->Job->find('all',array(
			'conditions' => array(
				'is_complete'=>0,
			),
			'order' => array('priority desc','id ASC'),
            'limit' =>  1,
			'offset' =>(int) $offset)
		);		
		if (count($results))
			return $results[0]['Job'];
		return false;
	}
	
	public function handleShutdown()
	{
		if( $this->_isExecuting &&
			$this->_executingJob ) {
		
		  // Get error
		  $message = '';
		  if( function_exists('error_get_last') ) {
			$message = error_get_last();
			$message = $message['type'] . ' ' . $message['message'] . ' ' . $message['file'] . ' ' . $message['line'];
		  }
		  
		  // Log
		  if( Configure::read('debug')) {
			$title = '';
			if( $this->_executingJobType ) {
			  $title = $this->_executingJobType['title'];
			}
			$this->log('Job Execution Failure: ' . $title . ' ' . $message);
		  }
		
		  // Cleanup
		  try {
			$job = $this->_executingJob;
			$tmp = array();
			$tmp['state'] = "'failed'";
			$tmp['is_complete'] = true;
			$tmp['completion_date'] = 'NOW()';
			$tmp['messages'] = $job['messages'].$message;
			
			$this->Job->updateAll(
				$tmp, 
				array(
				  'id = ?' => $job['id'],
				)
			);
			
		  } catch( Exception $e ) {
			$this->log('Job Cleanup Failure: ' . $e->__toString());
		  }
		  
		  $this->_isExecuting = false;
		  $this->_executingJob = null;
		  $this->_executingJobType = null;
		}
	}
}