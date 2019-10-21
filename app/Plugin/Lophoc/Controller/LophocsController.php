<?php 

class LophocsController extends LophocAppController{
    
    var $uses = array('Lophoc.Lophoc');
    public function admin_index(){
    }
    public function index(){
        // $this->layout = 'Lophoc.index';
        $s=$this->Session->read();
        $data['uid']=$s['Auth']['User']['id'];
        $data['danhsach'] = $this->Lophoc->danhsachlop();
        // foreach($data['danhsach'] as $key=>$value){
        //     $value['Lophoc']['mo_ta']=$this->Check->rename( $value['Lophoc']['mo_ta'], 10, 3);
        // }
        $this->set("data",$data);
    }
    public function taolopmoi(){
        //Nhận toàn bộ data từ ajax truyền sang
        $data=$this->request->data;

        $ds=$this->Lophoc->taolopmoi($data);
        $this->set('data',$ds);
    }
}