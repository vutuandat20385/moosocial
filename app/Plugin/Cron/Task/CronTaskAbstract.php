<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
abstract class CronTaskAbstract
{
  protected $_task;
  protected $_cron;

  /**
   * @var boolean
   */
  protected $_wasIdle = false;
  
  // Main

  /**
   * Constructor
   *
   * @param Zend_Db_Table_Row_Abstract $task
   */
  public function __construct( $task , $cron = null)
  {    
    $this->_task = $task;
    $this->_cron = $cron;
  }

  /**
   * @return Zend_Db_Table_Row_Abstract
   */
  public function getTask()
  {
    return $this->_task;
  }


  // Informational

  /**
   * @return null|integer
   */
  public function getTotal()
  {
    return null;
  }

  /**
   * @return boolean
   */
  public function wasIdle()
  {
    return $this->_wasIdle;
  }

  /**
   * @param boolean $flag
   * @return Core_Plugin_Task_Abstract
   */
  protected function _setWasIdle($flag = true)
  {
    $this->_wasIdle = (bool) $flag;
    return $this;
  }

  public function log($msg, $type = 'task', $scope = null) {
	if (!is_string($msg)) {
		$msg = print_r($msg, true);
	}

	return CakeLog::write($type, $msg, $scope);
  }

  // Execution

  /**
   * @return Core_Plugin_Job_Abstract
   */
  abstract public function execute();
}