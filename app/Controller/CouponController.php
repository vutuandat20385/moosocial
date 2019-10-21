<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */

class CouponController extends AppController 
{
	public $components = array('Paginator');	
	
    public function beforeFilter(){
        parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => true));
        $this->loadModel("Coupon");
    }
    
    public function admin_ajax_create($id = null)
    {
    	$bIsEdit = false;
    	if (!empty($id))
    	{
    		$coupon = $this->Coupon->findById($id);
    		$bIsEdit = true;
    	}
    	else
    	{
    		$coupon= $this->Coupon->initFields();
    		$coupon['Coupon']['actived'] = true;
    	}
    		
    	$this->set('bIsEdit',$bIsEdit);
    	$this->set('coupon', $coupon);
    }
    
    public function admin_index()
    {
    	if ( !empty( $this->request->data['code'] ) )
    	{
    		$this->redirect( '/admin/coupon/index/code:' . $this->request->data['code'] );
    	}
    	$cond = array();
    	if ( !empty( $this->request->named['code'] ) )
    	{
    		$cond = array(
    			'Coupon.code like' => '%'.$this->request->named['code'].'%' 
    		);
    	}
    	$this->Paginator->settings = array( 'order' => 'Coupon.id desc', 'limit' => RESULTS_LIMIT );
    	$coupons= $this->Paginator->paginate( 'Coupon', $cond );
    	
    	$this->set('coupons', $coupons);
    	$this->set('title_for_layout', __('Coupons Manager'));
    	
    }
    
    public function admin_ajax_save( )
    {
    	$this->autoRender = false;
    	$bIsEdit = false;
    	if ( !empty( $this->data['id'] ) )
    	{
    		$bIsEdit = true;
    		$this->Coupon->id = $this->request->data['id'];
    	}
    	
    	$this->Coupon->set( $this->request->data );
    	$this->_validateData( $this->Coupon);
    	
    	$this->Coupon->save( $this->request->data );
    	    	
    	$this->Session->setFlash(__('Coupon has been successfully saved'),'default',
    			array('class' => 'Metronic-alerts alert alert-success fade in' ));
    	
    	$response['result'] = 1;
    	echo json_encode($response);
    }
    
    public function admin_detail($id)
    {
    	$this->loadModel("CouponUse");
    	if ( !empty( $this->request->data['name'] ) )
    	{
    		$this->redirect( '/admin/coupon/detail/'.$id.'/name:' . $this->request->data['name'] );
    	}
    	$cond = array('CouponUse.coupon_id'=>$id);
    	if ( !empty( $this->request->named['name'] ) )
    	{
    		$cond['User.name like'] = '%'.$this->request->named['name'].'%';
    	}
    	$this->Paginator->settings = array( 'order' => 'CouponUse.id desc', 'limit' => RESULTS_LIMIT );
    	
    	$items= $this->Paginator->paginate( 'CouponUse', $cond );
    	
    	$this->set('items', $items);
    	$this->set('title_for_layout', __('Coupon Detail'));
    	$this->set('id',$id);
    }
    
    
    public function admin_delete($id)
    {
    	$this->loadModel("CouponUse");
    	$this->autoRender = false;
    	$this->Coupon->delete( $id );
    	
    	$this->CouponUse->deleteAll( array( 'CouponUse.coupon_id' => $id ), false, false );
    	
    	$this->Session->setFlash(__('Coupon deleted'),'default',
    			array('class' => 'Metronic-alerts alert alert-success fade in' ));
    	$this->redirect( $this->referer() );
    }
}
