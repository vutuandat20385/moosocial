<?php
/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('Topic', 'Topic.Model');

class TopicTest extends CakeTestCase {

    public $fixtures = array('plugin.topic.topic');

    public function setUp() {
        parent::setUp();
        $this->Topic = ClassRegistry::init('Topic');
    }

    public function testDeleteTopic() {
        $this->Topic->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));
        $before_delete = $this->Topic->findById(1);
        $this->Topic->deleteTopic(1);
        $after_delete = $this->Topic->findById(1);
        $this->assertNotEqual($after_delete, $before_delete);
    }

    public function testDeleteAllTopic() {
        $this->Topic->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));

        $this->Topic->deleteAll(true);
        $count_after = $this->Topic->find('count');
        $this->assertEqual($count_after, 0);
    }

    public function testSaveTopic() {
        $this->Topic->deleteAll(true);
        $this->Topic->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));
        $topics = $this->Topic->findById(1);
        $this->assertNotEqual($topics, array());
    }

    public function testGetTopic() {
        $this->Topic->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 0,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));
        $topics = $this->Topic->getTopics();
        $this->assertNotEqual($topics, array());
    }

    public function testUpdateTopic() {
        $this->Topic->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));
        
        $topic_before = $this->Topic->findById(1);
        $this->Topic->id = 1;
        $this->Topic->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title Upudated',
            'body' => 'Body Upudated',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));
        $topic_after = $this->Topic->findById(1);
        $this->assertNotEqual($topic_before, $topic_after);
    }

}

?>