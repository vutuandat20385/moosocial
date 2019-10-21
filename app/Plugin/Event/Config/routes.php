<?php
if(Configure::read('Event.event_enabled')){
    Router::connect("/events/:action/*",array('plugin'=>'Event','controller'=>'events'));
    Router::connect("/events/*",array('plugin'=>'Event','controller'=>'events','action'=>'index'));
}
