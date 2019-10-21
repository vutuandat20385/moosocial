<?php
App::uses('CoreContent', 'Model');

class CoreContentTest extends CakeTestCase {
    public $fixtures = array('app.corepage', 'app.corecontent');

    public function setUp() {
        parent::setUp();
        $this->CorePage = ClassRegistry::init('CorePage');
        $this->CoreContent = ClassRegistry::init('CoreContent');
    }
    public function testFindChild(){
        $result = $this->CoreContent->find('child',array(
            'parent' => array('CoreContent.id' => 7)// find child of the content with id of 7
        ));
        $this->assertNotEmpty($result);
    }
    public function testFindParent(){
        $result = $this->CoreContent->find('parent',array(
            'child' => array('CoreContent.id'=>8) //find parent of the content with id of 8
        ));
        $this->assertNotEmpty($result);
    }
    public function testDeleteChild(){
        $oldResult = $this->CoreContent->find('count',array(
            'conditions' => array('CoreContent.parent_id'=>7),
        ));
        $this->CoreContent->deleteChild(7);

        $newResult = $this->CoreContent->find('count',array(
            'conditions' => array('CoreContent.parent_id'=>7),
        ));
        $this->assertNotEqual($newResult,$oldResult);
    }
}