<?php 
class LophocsController extends LophocAppController{
    var $uses = array('Lophoc.Lophoc');
    public function admin_index(){
    }
    public function index(){
        $data = $this->Lophoc->danhsachlop();
        $this->set("data",$data);
    }
    public function taolopmoi(){
        //Nhận toàn bộ data từ ajax truyền sang
        $data=$this->request->data;

        $ds=$this->Lophoc->taolopmoi($data);
        $this->set('data',$ds);
    }
}