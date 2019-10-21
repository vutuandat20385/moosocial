<?php
/* General */

define('ROOT_ADMIN_ID', 1);
define('RESULTS_LIMIT', 12);
define('LIMIT_DISPLAY_COMMENT', 2);
define('USERS_BROWSE_LIMIT', 15);
define('PHOTO_BROWSE_LIMIT', 15);
define('FRIEND_LIMIT',5000);

define('MAX_PHOTOS', 300);
define('PHOTO_WIDTH', 1500);
define('PHOTO_HEIGHT', 1125);
define('PHOTO_THUMB_WIDTH', 200);
define('PHOTO_THUMB_HEIGHT', 150);
define('GROUP_AVATAR_WIDTH', 200);
define('GROUP_AVATAR_HEIGHT', 300);
define('GROUP_THUMB_WIDTH', 200);
define('GROUP_THUMB_HEIGHT', 200);
define('AVATAR_WIDTH', 170);
define('AVATAR_HEIGHT', 170);
define('AVATAR_THUMB_WIDTH', 180);
define('AVATAR_THUMB_HEIGHT', 180);
define('COVER_WIDTH', 1165);
define('COVER_HEIGHT', 310);
define('IMAGE_WIDTH', 482);
define('IMAGE_HEIGHT', 320);
define('PHOTO_QUALITY', 100);

define('APP_USER', 'user');
define('APP_FRIEND', 'friend');
define('APP_BLOG', 'blog');
define('APP_ALBUM', 'album');
define('APP_PHOTO', 'photo');
define('APP_TOPIC', 'topic');
define('APP_VIDEO', 'video');
define('APP_EVENT', 'event');
define('APP_GROUP', 'group');
define('APP_CONVERSATION', 'conversation');
define('APP_PAGE', 'page');

define('PLUGIN_USER_ID', 1);
define('PLUGIN_BLOG_ID', 2);
define('PLUGIN_ALBUM_ID', 3);
define('PLUGIN_VIDEO_ID', 4);
define('PLUGIN_TOPIC_ID', 5);
define('PLUGIN_GROUP_ID', 6);
define('PLUGIN_EVENT_ID', 7);
define('PLUGIN_CONVERSATION_ID', 8);
define('PLUGIN_PAGE_ID', 9);

define('ROLE_ADMIN', 1);
define('ROLE_MEMBER', 2);
define('ROLE_GUEST', 3);

define('PRIVACY_EVERYONE', 1);
define('PRIVACY_FRIENDS', 2);
define('PRIVACY_ME', 3);

define('PRIVACY_PUBLIC', 1);
define('PRIVACY_PRIVATE', 2);
define('PRIVACY_RESTRICTED', 3);

/* Event */
define('RSVP_AWAITING', 0);
define('RSVP_ATTENDING', 1);
define('RSVP_NOT_ATTENDING', 2);
define('RSVP_MAYBE', 3);

/* Group */

define('GROUP_USER_INVITED', 0);
define('GROUP_USER_MEMBER', 1);
define('GROUP_USER_REQUESTED', 2);
define('GROUP_USER_ADMIN', 3);

//Plugin
define("PLUGIN_PATH", APP.'Plugin'.DS."%s".DS);
define('PLUGIN_CONFIG_PATH', APP.'Config'.DS.'plugins'.DS.'plugins.xml');
define("PLUGIN_INSTALL_PATH", APP.'Plugin'.DS."%s".DS.'Config'.DS.'install'.DS.'install.sql');
define("PLUGIN_UNINSTALL_PATH", APP.'Plugin'.DS."%s".DS.'Config'.DS.'install'.DS.'uninstall.sql');
define("PLUGIN_UPGRADE_PATH", APP.'Plugin'.DS."%s".DS.'Config'.DS.'install'.DS.'upgrade.xml');
define("PLUGIN_INFO_PATH", APP.'Plugin'.DS."%s".DS.'info.xml');
define("PLUGIN_FILE_PATH", APP.'Plugin'.DS."%s".DS."%sPlugin.php");

//log db
define('LOG_DB_PATH', ROOT . DS . 'database' . DS );

define('VIDEO_WIDTH', 900);
define('VIDEO_HEIGHT', 560);

define('VIDEO_TYPE_YOUTUBE', 'youtube');
define('VIDEO_TYPE_VIMEO', 'vimeo');

define('ACTIVITY_OK', 'ok');
define('ACTIVITY_WAITING', 'waiting');

define('VIDEO_INPROCESS', 1);
define('VIDEO_COMPLETED', 0);

define('COMMENT_RECENT', 0);
define('COMMENT_CHRONOLOGICAL', 1);

define('REGEX_MENTION' , '/@\[(\d+):([^\]]+)\]/');

define('PROFILE_TYPE_DEFAULT', 1);

define('AUTH2_ACCESS_LIFETIME',259200);
define('AUTH2_REFRESH_TOKEN_LIFETIME',1209600);