<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('DatabaseSession', 'Model/Datasource/Session');

class MooSession extends DatabaseSession implements CakeSessionHandlerInterface {


    public function __construct() {
        parent::__construct();
    }

    // read data from the session.
    public function read($id) {
        return parent::read($id);
    }

    // write data into the session.
    public function write($id, $data) {
        if (!$id) {
            return false;
        }
        $expires = time() + $this->_timeout;
        $user_id = MooCore::getInstance()->getViewer(true);        
        if(!$user_id)
        {
            $user_id = "guest_".$id;
        }
		
        $record = compact('id', 'data', 'expires' , 'user_id');
        $record[$this->_model->primaryKey] = $id;

        $options = array(
            'validate' => false,
            'callbacks' => false,
            'counterCache' => false
        );
        try {
            return (bool)$this->_model->save($record, $options);
        } catch (PDOException $e) {
            return (bool)$this->_model->save($record, $options);
        }
    }

    // destroy a session.
    public function destroy($id) {
        return parent::destroy($id);
    }

    // removes expired sessions.
    public function gc($expires = null) {
        //return parent::gc($expires);
    }
}