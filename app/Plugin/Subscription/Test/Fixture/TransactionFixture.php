<?php
App::uses('MyTransactionFixture','Subscription.Test/Fixture');
class TransactionFixture extends MyTransactionFixture{
    public $import = array('model'=>'Billing.Transaction', 'records' => true);
}