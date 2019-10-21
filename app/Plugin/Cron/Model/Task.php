<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Task extends AppModel {
  
  	protected $_pid;

  	protected $_config;

  	protected $_external;
  	
  	protected $_tasks;

  	protected $_isExecuting = false;

  	protected $_isShutdownRegistered = false;

  	protected $_executingTask;

  	protected $_runCount = 0;
  	
  	protected $Processe;
  	
  	public function __construct($id = false, $table = null, $ds = null)
  	{  		
  		parent::__construct($id = false, $table = null, $ds = null);
  		$this->init();
  	}
  	
	public function isIdExist($id)
    {
        return $this->hasAny(array('id' => $id));
    }
  	
	public function init()
	{
		// Get configuration
		$this->_config = Configure::read('Cron');
        if(empty($this->_config)){
            $this->_config = array();
        }
		$this->_config = array_merge(array(
		  'cron_count'     => 1,       // Max number of tasks run per request
		  'cron_countidle' => false,   // Count idle tasks towards the tasks per request
		  'cron_interval'  => 15,      // Minimum interval between triggers
		  'cron_jobs'      => 3,       // Max number of jobs run per request
		  'cron_key'       => '',      // Access key
		  'cron_last'      => '',      // Last time trigger was run (or execute in cron mode)
		  'cron_pid'       => '',      // Random id for trigger and execution mutex
		  'cron_processes' => 2,       // Number of allowed concurrent processes
		  // @todo add usec support?
		  'cron_sleeppre'  => 0,       // (For debug) Seconds to sleep between acquiring task lock and executing task
		  'cron_sleepint'  => 0,       // (For debug) Seconds to sleep inside tasks between sub-tasks
		  'cron_sleeppost' => 0,       // (For debug) Seconds to sleep after executing task and clearing lock
		  'cron_time'      => 120,     // Max time allowed per request (auto adjusts if ini_get('max_execution_time') is available)
		  'cron_timeout'   => 900,     // Time before a process is considered rogue
		), array_filter($this->_config));
	
		// Make pid
		$this->_pid = $this->_generatePid();
	
	
		// Generate key if missing
		if( empty($this->_config['key']) ) {
		  $this->_config['key'] = $this->_generatePid(true);
		  //add key 
		  
		}
	
		// Adjust time limit if possible
		if( empty($this->_config['cron_time']) || $this->_config['cron_time'] <= 0 ) {
		  $this->_config['cron_time'] = 120;
		}
		if( function_exists('ini_get') &&
			($max_execution_time = ini_get('max_execution_time')) &&
			'cli' !== PHP_SAPI ) {
		  if( -1 == $max_execution_time ) {
			// What should we do here
			//$this->_config['time'] = 600;
		  } else if( $max_execution_time < $this->_config['cron_time'] ) {
			$this->_config['cron_time'] = floor(0.8 * $max_execution_time);
		  }
		}
	
		// Get default external
		$this->_external = array(
		  'key' => null,    // The external access key passed to this request
		  'pid' => null,    // The external process id passed to this request
		);
		
		App::import('Model', 'Cron.Processe');	
		$this->Processe = new Processe();
	}
	
	public function log($msg, $type = LOG_ERR, $scope = null) 
	{
		parent::log($msg,'task');
	}
	
	protected function _generatePid($asHex = false)
	{
	    $max = min(mt_getrandmax(), 2147483647); // Do not allow more than 32bit unsigned
	    $val = mt_rand(0, $max);
	    if( $asHex ) {
	      return str_pad(base_convert($val, 10, 16), 8, '0', STR_PAD_LEFT);
	    } else {
	      return str_pad(sprintf('%u', $val), 10, '0', STR_PAD_LEFT);
	    }
	}
	
	public function run($params = array())
	{		
		// Benchmark execute
	    if( Configure::read('debug')) {
	      $start = microtime(true);
	    }
	    
	    set_time_limit(0);
    	$prev = ignore_user_abort(true);
        
    	
		// Register fatal error handler
	    if( !$this->_isShutdownRegistered ) {
	      register_shutdown_function(array($this, 'handleShutdown'));
	      $this->_isShutdownRegistered = true;
	    }
	    
	    // Signal execution start
	    $this->_isExecuting = true;
	
	    // Process params
	    $this->_external = array_merge(
	      $this->_external,
	      array_intersect_key(array_merge($_GET, $_POST, (array) $params), $this->_external)
	    );
	    
		// Execute
	    if( $this->_executeCheck() ) {
	
	      // Inject current process identifier
	      $this->Processe->save(array(
	        'pid' => $this->_pid,
	        'parent_pid' => (int) $this->_external['pid'],
	        'system_pid' => (int) ( function_exists('posix_getpid') ? posix_getpid() : 0 ),
	        'started' => time(),
	        'timeout' => 0, // @todo
	        'name' => '',
	      ));	      
	      $tasks = $this->getTasks();
	      
	      // Run tasks
	      foreach($tasks  as $item ) {
	        // Check if they were run in the background while other tasks were executing	        
			$task = $this->findById($item['Task']['id']);
			$task = $task['Task'];
	        if( $this->_executeTaskCheck($task) ) {
	          $this->_executeTask($task);
	        }
	      }
	      
	      // Log
	      if( Configure::read('debug')) {
	        $this->log(sprintf('Execution Complete [%d] [%d]', $this->_pid, $this->_external['pid']));
	      }
	    }
	}
	
	public function getParam($key, $default = null)
	{
		if( isset($this->_config[$key]) ) {
		  return $this->_config[$key];
		} else {
		  return $default;
		}
	}
	
	protected function _executeTask($task)
	{
		// Log
		if( Configure::read('debug')) {
		  $this->log(sprintf('Task Execution Check [%d] : %s', $this->_pid, $task['title']));
		}
		
		$this->updateAll(
		    array(
		    	'started_last' => time(),
			  	'started_count' => 'started_count + 1',
			  	'semaphore' => 'semaphore + 1',
		    ),
		    array(
		    	'id = ?' => $task['id'],
		 		'semaphore < processes',
		    )
		);
	
		// Task execution semaphore
		$affected = $this->getAffectedRows();		
		if( 1 !== $affected ) {
		  if( Configure::read('debug')) {
			$this->log(sprintf('Task Execution Failed Semaphore [%d] : %s', $this->_pid, $task['title']));
		  }
		  return false;
		}
	
		// Update process identifier
		$this->Processe->updateAll(
			array(
			  'name' => "'".$task['class']."'",
			), array(
			  'pid = ?' => $this->_pid,
			)
		);
		$affected = $this->getAffectedRows();
		
		if( 1 !== $affected ) {
		  // Wth?
		  if( Configure::read('debug')) {
			$this->log(sprintf('Execution Failed Process Update [%d] : %s', $this->_pid, $task['plugin']));
		  }
		}
	
		// Debug: sleeppre
		$slept = 0;
		if( $this->getParam('cron_sleeppre', 0) > 0 ) {
		  $slept += $this->getParam('cron_sleeppre');
		  sleep($this->getParam('cron_sleeppre'));
		}
	
		// Refresh
		$task = $this->findById($task['id']);
		$task = $task['Task'];
	
		// Log
		if( Configure::read('debug')) {
		  $this->log(sprintf('Task Execution Pass [%d] : %s', $this->_pid, $task['title']));
		}
	
	
	
		// ----- MAIN -----
	
		// Set executing task
		$this->_executingTask = $task;
	
		// Invoke plugin
		$status = false;
		$isComplete = true;
		$wasIdle = false;
	
		try {
		  // Get plugin object		 
		  $class = $this->getTaskClass($task);
	
		  // Execute
		  $class->execute();
	
		  // Check was idle
		  $wasIdle = $class->wasIdle();
	
		  // Ok
		  $status = true;
	
		} catch( Exception $e ) {
		  // Log exception
		  $this->log($e->__toString());
		  $status = false;
		}
		// ----- MAIN -----
	
	
	
		// Debug: sleeppost
		if( $this->getParam('cron_sleeppre', 0) > 0 ) {
		  $slept += $this->getParam('cron_sleeppre');
		  sleep($this->getParam('cron_sleeppre'));
		}
		if( !isset($this->slept) ) {
		  $this->slept = 0;
		}
		$this->slept += $slept;
	
		// Update process identifier
		$this->Processe->updateAll(
			array(
			  'name' => "''",
			), array(
			  'pid = ?' => $this->_pid,
			)
		);
		$affected = $this->getAffectedRows();		
	
		if( 1 !== $affected ) {
		  // Wth?
		  if( Configure::read('debug')) {
			$this->log(sprintf('Execution Failed Process Update (post) [%d] : %s', $this->_pid, $task->plugin));
		  }
		}
	
		// Update task and release semaphore
		$statusKey = ($status ? 'success' : 'failure');
		$this->updateAll(array(
		  'semaphore' => 'semaphore - 1',
		  'completed_last' => time(),
		  'completed_count' =>'completed_count + 1',
		  $statusKey . '_last' => time(),
		  $statusKey . '_count' => $statusKey . '_count + 1',
		), array(
		  'id = ?' => $task['id'],
		  'semaphore > ?' => 0,
		));
		
		$affected = $this->getAffectedRows();
		
		if( 1 !== $affected ) {
			if( Configure::read('debug')) {
			$this->log(sprintf('Task Execution Failed Semaphore Release [%d] : %s', $this->_pid, $task['title']));
		  }
		  return false;
		}
	
		// Update count
		if( !$wasIdle ) {
		  $this->_runCount++;
		}
	
		// Remove executing task
		$this->_executingTask = null;
	
		// Log
		if( Configure::read('debug')) {
		  if( $status ) {
			$this->log(sprintf('Task Execution Complete [%d] : %s', $this->_pid, $task['title']));
		  } else {
			$this->log(sprintf('Task Execution Complete with errors [%d] : %s', $this->_pid, $task['title']));
		  }
		}
	
		return $this;
	}
	
	protected function _executeTaskCheck($task)
	{
		// We've executed at least as many tasks as count
		if( $this->_runCount >= $this->_config['cron_count'] ) {
		  return false;
		}
	
		// We've reached the time limit for this request
		if( microtime(true) >= TIME_START + $this->_config['cron_time'] ) {
		  return false;
		}
	
		// Task is not ready to be executed again yet
		if( $task['timeout'] > 0 ) {
		  if( time() < $task['started_last'] + $task['timeout'] ) {
			return false;
		  }
		  if( time() < $task['completed_last'] + $task['timeout'] ) {
			return false;
		  }
		}
	
		// If semaphore limit is reached, and the timeout
		// has been reached, check if lock needs to be cleared
		if( $task['semaphore'] >= $task['processes'] ) {
		  // Sanity - wth is this?
		  if( $task['processes'] < 1 ) {
			$this->updateAll(
			    array('processes' => 1),
			    array('id' => $task['id'])
			);
	        return false;
		  }
	
		  // Get all processes matching task plugin
		  $taskProcesses = $this->Processe->find('all',array('conditions' => array('name = ?'=>$task['class'])));
	
		  // There was nothing, flush mutexes
		  if( !count($taskProcesses) ) {
		  	
		  	$this->updateAll(
			    array(
			      'semaphore' => 'semaphore - '.$task['semaphore']
			    ),
			    array(
			    	'id =' => $task['id']
			   	)
			);
		    
		    $affected = $this->getAffectedRows();
			
			if( 1 !== $affected ) {
			  // Log
			  if( Configure::read('debug')) {
				$this->log(sprintf('Execution Mutex Flush Failed [%d] : %s', $this->_pid, $task['title']));
			  }
			  return false;
			}
		  }
	
		  // Check each process
		  else {
			$activeProcesses = 0;
			foreach( $taskProcesses as $item ) {
			  $process = $item['Processe'];
			  $started = ( !empty($process['started']) ? $process['started'] : 0 );
			  $timeout = ( !empty($process['timeout']) ? $process['timeout'] : $this->_config['cron_timeout'] );
	
			  // It's timed out
			  if( time() > $started + $timeout ) {
				// Delete
				$this->Processe->deleteAll(array('pid' => $process['pid']),false);
				// Log
				if( Configure::read('debug')) {
				  $this->log(sprintf('Process Timeout [%d] : %d ', $this->_pid, $process['pid']));
				}
				continue;
			  }
			  
			  $activeProcesses++;
			}
			if( $activeProcesses >= $task['processes'] ) {
			  // Log
			  if( Configure::read('debug')) {
				$this->log(sprintf('Execution Process Flush Failed [%d] : %d ', $this->_pid, $activeProcesses));
			  }
			  return false;
			}
		  }
		}
	
		// Task is ready
		return true;
	}
	
	public function getTaskClass($task)
	{
		$class = $task['class'];
		$class = explode('_', $class);
		$class_tmp = implode('', $class);
				
		if ($task['plugin'] != 'Core')
		{
			App::import($class[0].'.'.$class[1],$class_tmp);
		}
		else
		{
			App::import($class[0],$class_tmp);
		}
		
		return new $class_tmp($task,$this);
	}
	
	protected function _executeCheck()
	{
		// Log
		if( Configure::read('debug')) {
		  $this->log(sprintf('Execution Check [%d] [%d] ', $this->_pid, $this->_external['pid']));
		}
				
		// Check passkey
		if($this->_external['key'] != $this->_config['cron_key'] ) {
		  return false;
		}
	
		// Check processes?
		$activeProcesess = $this->Processe->find('count');		
		// Process limit reached
		if( $activeProcesess >= $this->getParam('cron_processes', 2) ) {
		  // Log
		  if( Configure::read('debug')) {
				$this->log(sprintf('Process Limit Reached [%d] : %d ', $this->_pid, $activeProcesess));
		  }
		  return false;
		}
	
		// Log
		if( Configure::read('debug')) {
		  $this->log(sprintf('Execution Pass [%d] [%d] ', $this->_pid, $this->_external['pid']));
		}
	
		return true;
	}
	
	public function handleShutdown()
	{
	    // Clear process identifier
	    try {	    	
	      $this->Processe->deleteAll(array('pid' => $this->_pid),false);
	    } catch( Exception $e ) {
	      $this->log('Error clearing pid: ' . $e->__toString());
	    }
	
	    // There was no error during execution
	    if( !$this->_isExecuting ) {
	      return;
	    }
	    $this->_isExecuting = false;
	
	    // This means there was a fatal error during execution
	    $db = $this->getDataSource();
	
	    // Log
	    //if( APPLICATION_ENV == 'development' ) {
	      $message = '';
	      if( function_exists('error_get_last') ) {
	        $message = error_get_last();
	        if ($message)	        
	        	$message = $message['type'] . ' ' . $message['message'] . ' ' . $message['file'] . ' ' . $message['line'];
	      }
	      if ($message)
	      	$this->log('Execution Error: ' . $this->_pid . ' - ' . $message);
	    //}
	
	    // Let's call rollback just in case the fatal error happened inside a transaction
	    // This will restore autocommit
	    try {
	      $db->rollBack();
	    } catch( Exception $e ) {
	    	if( Configure::read('debug')) {
	        	$this->log(sprintf('Shutdown failed rollback [%d]', $this->_pid));
	      	}
	    }
	
	    // There was no task executing during error
	    if( !($this->_executingTask) ) {
	      return;
	    }
	
	    // Cleanup executing task
	    $task = $this->_executingTask;
	
	    // Update task and release semaphore
	    $statusKey = (false ? 'success' : 'failure');
	    
	    $this->updateAll(
		    array(
		      'semaphore' => 'semaphore - 1',
		      'completed_last' => time(),
		      'completed_count' => 'completed_count + 1',
		      $statusKey . '_last' => time(),
		      $statusKey . '_count' => $statusKey . '_count + 1',
		    ),
		    array(
		    	'id =' => $task['id'],
	      		'semaphore >' => 0
		   	)
		);
	    
	    $affected = $this->getAffectedRows();
	
	    if( 1 !== $affected ) {
	      if( Configure::read('debug')) {
	        $this->log(sprintf('Task Execution Failed Semaphore Release [%d] : %s', $this->_pid, $task['title']));
	      }
	      return false;
	    }
	}
	
	public function getTasks()
	{
	    if( null === $this->_tasks ) {
	  		App::import('Model', 'Plugin');	
			$plugin = new Plugin();
			
			$plugins = $plugin->find( 'all', array('order' => 'id DESC'));
	        $installed_plugins = array('Core');
	        
	        foreach ( $plugins as $plugin )
	            $installed_plugins[] = $plugin['Plugin']['key'];
	                	        
	        $this->_tasks = $this->find('all',array('conditions' => array('enable'=>1,'plugin'=>$installed_plugins)));
	    }
	    return $this->_tasks;
	}
}
