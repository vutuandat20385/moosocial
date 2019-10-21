<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('GroupAppModel', 'Group.Model');
class Group extends GroupAppModel {

    public $mooFields = array('title','href','plugin','type','url', 'thumb', 'privacy');
    
    public $belongsTo = array( 'User' => array('counterCache' => true),
							   'Category' => array( 'counterCache' => 'item_count', 
												 	'counterScope' => array( 'Group.type <> ?' => PRIVACY_PRIVATE,
							   												 'Category.type' => 'Group' ) ) );
    
    public $actsAs = array(
        'Activity' => array(
            'type' => 'user',
            'action_afterCreated'=>'group_create',
            'item_type'=>'Group_Group',
            'query'=>1,
            'share' => true,
            'params' => 'item',
            'privacy_field' => 'type',
    		'default_privacy' => array(
    			'1' => PRIVACY_EVERYONE,
				'2' => PRIVACY_ME,
    			'3' => PRIVACY_EVERYONE
    		)
        ),
        'MooUpload.Upload' => array(
            'photo' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}groups{DS}{field}{DS}',
            )
        ),
        'Hashtag'=>array(
            'field_created_get_hashtag'=>'description',
            'field_updated_get_hashtag'=>'description',
        ),
        'Storage.Storage' => array(
            'type'=>array('groups'=>'photo'),
        ),
    );
    
    public $hasMany = array('Activity' => array(
            'className' => 'Activity',
            'foreignKey' => 'target_id',
            'conditions' => array('Activity.type' => 'Group_Group'),
            'dependent' => true
        ),
        'GroupUser' => array(
            'className' => 'Group.GroupUser',
            'dependent' => true
        ),
    );
    public $validate = array(
        'name' => array(
            'rule' => 'notBlank',
            'message' => 'Name is required'
        ),
        'category_id' => array(
            'rule' => 'notBlank',
            'message' => 'Category is required'
        ),
        'description' => array(
            'rule' => 'notBlank',
            'message' => 'Description is required'
        ),
        'user_id' => array('rule' => 'notBlank')
    );

    public function getGroups($type = null, $param = null, $page = 1, $limit = null, $role_id = null, $group_id = null) {
        $cond = array();
        $limit = Configure::read('Group.group_item_per_pages');
        $viewer = MooCore::getInstance()->getViewer();
        $viewer_id = MooCore::getInstance()->getViewer(true);
        $isAdmin = isset($viewer['Role']['is_admin']) ? $viewer['Role']['is_admin'] : false;
        
        switch ($type) {
            case 'category':
                if($isAdmin)
                    $cond = array('Group.category_id' => $param);
                else
                    $cond = array('Group.category_id' => $param, 'Group.type <> ?' => PRIVACY_PRIVATE);
                break;

            case 'search':
                if($isAdmin){
                    $cond = array('MATCH(Group.name, Group.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param));
                }
                else{

                    $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
                    $joinedGroup = $groupUserModel->getMyGroupsList($viewer_id);
                    $cond = array(
                            'OR' => array(
                                array(
                                    'MATCH(Group.name, Group.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 
                                    'Group.type <> ?' => PRIVACY_PRIVATE
                                    ),
                                array(
                                    'MATCH(Group.name, Group.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 
                                    'Group.id' => $joinedGroup
                                )
                            )
                        );
                }
                
                break;

            case 'user':
                if ($param) {
                    $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
                    $joinedGroup = $groupUserModel->getMyGroupsList($param);
                    if ($isAdmin || $param == $viewer_id){ //viewer is admin or owner himself
                        $cond = array(
                            'OR' => array(
                                array(
                                    'Group.user_id' => $param
                                    ),
                                array(
                                    'Group.id' => $joinedGroup
                                )
                            )
                        );
                    }
                    else{ // normal viewer
                        $cond = array(
                            'OR' => array(
                                array(
                                    'Group.user_id' => $param, 
                                    'Group.type <> ?' => PRIVACY_PRIVATE
                                    ),
                                array(
                                    'Group.id' => $joinedGroup,
                                    'Group.type <> ?' => PRIVACY_PRIVATE
                                )
                            )
                        );
                    }
                }
                break;

            default:
                if($isAdmin){
                    $cond = array();
                }
                else{
                    $cond = array(
                        'OR' => array(
                            array(
                                'Group.type <> ?' => PRIVACY_PRIVATE
                            ),
                            array(
                                'Group.user_id' => $param
                            ),
                            array(
                                'Find_In_Set(Group.id,"'.$group_id.'")',
                            )
                        ),
                    );
                }
        }

        //get groups of active user
        $cond['User.active'] = 1;
        //$cond = $this->addBlockCondition($cond);
        $groups = $this->find('all', array('conditions' => $cond,
            'limit' => $limit,
            'page' => $page,
            'order' => 'Group.id desc'
                ));
        App::import('Model', 'NotificationStop');
        App::import('Group.Model', 'GroupUser');
        $groupUser = new GroupUser();
        $notificationStop = new NotificationStop();
        $uid = CakeSession::read('uid');
        foreach ($groups as $key => $group){
            $my_status = $groupUser->getMyStatus($uid, $group['Group']['id']);
            $groups[$key]['Group']['my_status'] = $my_status;
            
            $notification_stop = $notificationStop->find('count', array('conditions' => array('item_type' => 'Group_Group',
                    'item_id' => $group['Group']['id'],
                    'user_id' => $uid)
                    ));
            $groups[$key]['Group']['notification_stop'] = $notification_stop;
            
            $groups[$key]['Group']['group_user_count'] = $groupUser->getBlockedUserCount($groups[$key]['Group']['id']);
        }

        return $groups;
    }
    
    // MOOSOCIAL-2764 include my joined group AND group created
    public function getMyGroupCount($uid){
        $cond = array();

        $viewer = MooCore::getInstance()->getViewer();
        $viewer_id = MooCore::getInstance()->getViewer(true);
        $isAdmin = isset($viewer['Role']['is_admin']) ? $viewer['Role']['is_admin'] : false;
        
        if ($uid) {
            
            $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
            $joinedGroup = $groupUserModel->getMyGroupsList($uid);
            
            if ($isAdmin || $uid == $viewer_id){ //viewer is admin or owner himself
                $cond = array(
                    'OR' => array(
                        array(
                            'Group.user_id' => $uid
                            ),
                        array(
                            'Group.id' => $joinedGroup
                        )
                    )
                );
            }
            else{ // normal viewer
                $cond = array(
                    'OR' => array(
                        array(
                            'Group.user_id' => $uid, 
                            'Group.type <> ?' => PRIVACY_PRIVATE
                            ),
                        array(
                            'Group.id' => $joinedGroup,
                            'Group.type <> ?' => PRIVACY_PRIVATE
                        )
                    )
                );
            }
        }
        
        $groups_count = $this->find('count', array('conditions' => $cond));
        
        return $groups_count;
    }

    public function getPopularGroups($limit = 5, $days = null) {
        $cond = array('Group.type <> ?' => PRIVACY_PRIVATE);

        if (!empty($days))
            $cond['DATE_SUB(CURDATE(),INTERVAL ? DAY) <= Group.created'] = intval($days);

        //get groups of active user
        $cond['User.active'] = 1;
        //$cond = $this->addBlockCondition($cond);
        $groups = $this->find('all', array('conditions' => $cond,
            'order' => 'Group.group_user_count desc',
            'limit' => intval($limit)
                ));
        
        return $groups;
    }

    public function getMyGroupsCount($uid) {
        $groups = $this->find('count', array('conditions' => array('Group.user_id' => $uid,'User.active' => 1)));

        return $groups;
    }
    
    public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (isset($row['name']) && isset($row['id']))
    		return $request->base.'/groups/view/'.$row['id'].'/'.seoUrl($row['name']);
    	else 
    		return '';
    }
    
    public function getTitle(&$row)
    {
    	if (isset($row['name']))
    	{
    		$row['name'] = htmlspecialchars($row['name']);
    		return $row['name'];
    	}
    	return '';
    }
    
    public function getThumb($row){
        return 'photo';
    }
    
    public function getPrivacy($row){
        if (isset($row['type'])){
            return $row['type'];
        }
        return false;
    }

    public function getGroupSuggestion($q, $limit = RESULTS_LIMIT,$page = 1){
        
        $viewer = MooCore::getInstance()->getViewer();
        $viewer_id = MooCore::getInstance()->getViewer(true);
        $isAdmin = isset($viewer['Role']['is_admin']) ? $viewer['Role']['is_admin'] : false;
        
        if($isAdmin){
        	$cond = array('Group.name LIKE' => $q . "%");
        }
        else{
            $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
            $joinedGroup = $groupUserModel->getMyGroupsList($viewer_id);
            $cond = array(
                    'OR' => array(
                        array(
                        	'Group.name LIKE' => $q . "%", 
                            'Group.type <> ?' => PRIVACY_PRIVATE
                            ),
                        array(
                        	'Group.name LIKE' => $q . "%", 
                            'Group.id' => $joinedGroup
                        )
                    )
                );
        }
        
        //get groups of active user
        $cond['User.active'] = 1;
        //$cond = $this->addBlockCondition($cond);
        $groups = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page,'order' => 'Group.id desc' ) );
        return $groups;
    }

    public function getGroupHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Group.id' => $qid,
         
        );

        //get groups of active user
        $cond['User.active'] = 1;
        //$cond = $this->addBlockCondition($cond);
        $groups = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $groups;
    }

    public function findPrivateGroup($gIds = null, $viewer = null)
    {
        $privateGroupIds = array();
        if(is_array($gIds))
        {
            $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
            foreach($gIds as $id)
            {
                $group = $this->findById($id);
                if(!$viewer['Role']['is_admin'] && !$groupUserModel->isMember($viewer['User']['id'],$id) )
                {
                    if(!empty($group) && $group['Group']['type'] == PRIVACY_PRIVATE)
                    {
                        $privateGroupIds[] = $id;
                    }
                }
            }
        }
        return $privateGroupIds;
    }
    public function afterSave($created, $options = array()) {
        Cache::clearGroup('group', 'group');
    }

    public function afterDelete() {
        Cache::clearGroup('group', 'group');
    }

    public function beforeDelete($cascade = true)
    {
    	$notificationModel = MooCore::getInstance()->getModel('Notification');
    	$rows = $notificationModel->find('all',array('conditions'=>array(
    		'Notification.plugin' => 'Group',
    		'Notification.params' => $this->id
    	)));
    	foreach ($rows as $row)
    	{
    		$notificationModel->delete($row['Notification']['id']);
    	}
    	parent::beforeDelete($cascade);
    }
}
