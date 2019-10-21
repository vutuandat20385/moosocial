<?php
App::uses('CorePage', 'Model');

class CorePageTest extends CakeTestCase {
    public $fixtures = array('app.corepage', 'app.corecontent','app.theme');

    public function setUp() {
        parent::setUp();
        $this->CorePage = ClassRegistry::init('CorePage');
        $this->CoreContent = ClassRegistry::init('CoreContent');
    }
    public function testSavePage(){
        $oldResult = $this->CorePage->find('count');
        $data = array('name'=>'test page 1','displayname'=>'Test Page','url'=>'/test');
        $this->CorePage->savePage($data);
        $newResult = $this->CorePage->find('count');
        $this->assertNotEqual($oldResult,$newResult);
    }
    public function testFindPage(){
        $pages = array(
            array('name'=>'home_index'),
            array('name'=>'home_contact'),
            array('name'=>'users_index'),
            array('name'=>'users_view'),
            array('name'=>'users_register'),
            array('name'=>'blogs_index'),
            array('name'=>'blogs_view'),
            array('name'=>'photos_index'),
            array('name'=>'albums_view'),
            array('name'=>'photos_view'),
            array('name'=>'videos'),
            array('name'=>'videos_view'),
            array('name'=>'topics_view'),
            array('name'=>'groups'),
            array('name'=>'groups_view'),
            array('name'=>'events'),
            array('name'=>'events_view'),
            array('name'=>'user_recover'),
            array('name'=>'topics'),
        );
        foreach($pages as $page){
            $result = $this->CorePage->find('first',array(
                'conditions'=> array('CorePage.name'=>$page['name'])
            ));
            $this->assertNotEmpty($result);
        }
    }
    public function testClearContent(){
        $data = array(
            array(
                'core_page_id'=>1,
                'name'=>'Content Page 1 #1'
            ),
            array(
                'core_page_id'=>1,
                'name'=>'Content Page 1 #2'
            )
        );
        $this->CoreContent->saveMany($data);
        $this->CorePage->clearContent(1);
        $count = $this->CorePage->find('first',array(
            'conditions'=> array('CorePage.id'=>1),
            'fields'=>array('CorePage.core_content_count'),
        ));
        $this->assertEqual($count['CorePage']['core_content_count'],0);
    }
    public function testSaveContent(){
        $this->CorePage->clearContent(1);
        $data = array(
            array(
                'core_page_id'=>1,
                'name'=>'Content Page 1 #1'
            ),
            array(
                'core_page_id'=>1,
                'name'=>'Content Page 1 #2'
            )
        );
        $this->CorePage->saveContent($data);
        $count = $this->CorePage->find('first',array(
            'conditions'=> array('CorePage.id'=>1),
            'fields'=>array('CorePage.core_content_count'),
        ));
        $this->assertNotEqual($count['CorePage']['core_content_count'],0);
    }
    public function testGetContent(){
        $this->CorePage->clearContent(1);
        $contents = $this->CorePage->getContent(1);
        $this->assertEmpty($contents);
        $data = array(
            array(
                'core_page_id'=>1,
                'name'=>'Content Page 1 #1'
            ),
            array(
                'core_page_id'=>1,
                'name'=>'Content Page 1 #2'
            )
        );
        $this->CorePage->saveContent($data);
        $contents = $this->CorePage->getContent(1);
        $this->assertNotEmpty($contents);
    }
    public function testSave(){
        $this->CorePage->clearContent(1);
        $data = array(

                'core_page_id'=>1,
                'name'=>'Content Page 1 #1'


        );
        //$this->CorePage->saveAsso($data);
    }
}
?>