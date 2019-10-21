

<div class="container">
    <div id="result">
    <div class="title-danhsachlop" style="text-align:center;">DANH SÁCH LỚP HỌC</div>

    <div class="col-md-12" style="text-align:right;">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#taolopmoi" data-whatever="@mdo">Tạo lớp mới</button>
    </div>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                <th scope="col">STT</th>
                <th scope="col">Tên lớp</th>
                <th scope="col">Mô tả lớp</th>
                <th scope="col">Người tạo</th>
                <th scope="col">Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach($data['danhsach'] as $k => $value){
                    $k++;
            ?>
                <tr>
                <th scope="row"><?php echo $k; ?></th>
                <td><?php echo $value['Lophoc']['ten_lop']; ?></td>
                <td><?php echo $value['Lophoc']['mo_ta']; ?></td>
                <td><?php echo $value['Lophoc']['nguoi_tao']; ?></td>
                <td><?php echo $value['Lophoc']['create_date']; ?></td>
                </tr>
            <?php
                }
            ?>
            </tbody>
        </table>
    </div>
</div>


<div class="modal fade" id="taolopmoi" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" action="">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">THÊM LỚP MỚI</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" id="user" value="<?php echo $data['uid']?>" style="display:none">
                <div class="form-group">
                    <label for="recipient-name" class="col-form-label">Tên lớp:</label>
                    <input type="text" class="form-control" id="tenlop">
                </div>
                <div class="form-group">
                    <label for="message-text" class="col-form-label">Mô tả:</label>
                    <textarea class="form-control" id="motalop"></textarea>
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="load_ajax()">Tạo lớp mới</button>
            </div>
        
        </div>
    </form>
  </div>
</div>
<script language="javascript">
            function load_ajax(){
                var tenlop=document.getElementById('tenlop').value;
                var motalop=document.getElementById('motalop').value;
                var nguoitao=document.getElementById('user').value;
                $.ajax({
                    url : "lophocs/taolopmoi",
                    type : "post",
                    data : {
                        ten_lop:tenlop, mo_ta:motalop, nguoi_tao:nguoitao
                    },
                    success : function (result){
                        $('#result').html(result);
                        window.location.href = "<?php echo BASE_URL.'/lophocs';?>";
                        
                    }
                });
            }
        </script>