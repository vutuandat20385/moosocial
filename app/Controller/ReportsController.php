<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ReportsController extends AppController 
{
	public $check_force_login = false;
	public function ajax_create( $type = null, $target_id = null )
	{
		$target_id = intval($target_id);
		$this->_checkPermission();
		$this->set( 'type', $type );
		$this->set( 'target_id', $target_id );
                $this->set('title_for_layout', __('Reports'));
	}
		
	public function ajax_save($isRedirect = true)
	{
		$this->_checkPermission();
		$uid = $this->Auth->user('id');
		
		if ( !empty( $this->request->data ) )
		{
			$this->autoRender = false;
			$uid = $this->Auth->user('id');

			$this->request->data['user_id'] = $uid;
			$this->Report->set( $this->request->data );
			$this->_validateData( $this->Report );

			$count = $this->Report->find( 'count', array( 'conditions' => array( 'type' => $this->request->data['type'],
																				 'target_id' => $this->request->data['target_id'],
																				 'user_id' => $uid )
										 ) 	);
			if ( $count > 0 )
			{
                            if($isRedirect) {
				$response['result'] = 0;
                                    $response['message'] = __('Duplicated report');
                                    echo json_encode($response);
				return;
                            }
                            else {
                                return $error = array(
                                    'code' => 400,
                                    'message' => __('Duplicated report'),
                                );
                            }
			}


			$item = MooCore::getInstance()->getItemByType($this->request->data['type'],$this->request->data['target_id']);
			
			if ( $this->Report->save() ) // successfully saved	
			{
				$this->loadModel('AdminNotification');

				if($this->request->data['type'] == 'activity') {
					$url = $this->request->base.'/users/view/'.$item['Activity']['user_id'].'/activity_id:'.$item['Activity']['id'];
				}
				else {
					$url = $item[key($item)]['moo_href'];
				}

				if(!empty($uid))
                {
				$this->AdminNotification->save( array( 'user_id' => $uid,
													   'message' => $this->request->data['reason'],
													   'text' => __('reported a %s', key($item)),
													   'url' => $url,
											) );
                }
                if($isRedirect) {
                    $response['result'] = 1;
                    $response['message'] = __('Thank you! Your report has been submitted');
                    echo json_encode($response);
                }
			}
		}
	}
}

