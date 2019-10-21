<?php
App::uses('LophocAppModel','Lophoc.Model');
class Lophoc extends LophocAppModel{
    public function danhsachlop(){
        return $this->find('all');
    }
    public function taolopmoi($data){
        $ten_lop=$data['ten_lop'];
        $mo_ta=$data['mo_ta'];
        $sql="INSERT INTO lophocs(ten_lop,mo_ta) VALUES('$ten_lop','$mo_ta')";
        $result=$this->query($sql);
        return $this->find('all');
    }
}