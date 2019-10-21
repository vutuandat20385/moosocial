<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
abstract class CronTaskJobAbstract
{
	protected $_job;
	
	protected $_jobType;
	
	/**
	* @var boolean
	*/
	protected $_wasIdle = false;
	
	/**
	* @var boolean
	*/
	protected $_isComplete = false;
	
	/**
	* @var Zend_Log
	*/
	protected $_log;
	
	/**
	* @var array
	*/
	protected $_data;
	
	public function isComplete()
  	{
    	return (bool) $this->_isComplete;
  	}
  	
	protected function _setIsComplete($flag)
  	{
	    $this->_isComplete = (bool) $flag;
    	$this->_job['progress'] = 1;
	    //if( $flag ) {
	    //  $this->_data = null;
	    //}
    	return $this;
  	}
	
	// Main
	
	/**
	* Constructor
	* 	
	*/
	public function __construct($job, $jobType = null)
	{
		$this->_job = $job;
		$this->_jobType = $jobType;
	}
	
	public function getJob()
	{
		return $this->_job;
	}
	
	public function getJobType()
	{
		return $this->_jobType;
	}
	
	public function log($msg, $type = 'task', $scope = null) {
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}

		return CakeLog::write($type, $msg, $scope);
  	}		
	// Execution
	
	public function execute()
	{
		$this->_execute();
	}
	
	abstract protected function _execute();  
}