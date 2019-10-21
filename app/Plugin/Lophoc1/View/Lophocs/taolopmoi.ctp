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
                foreach($data as $k => $value){
                    $k++;
            ?>
                <tr>
                <th scope="row"><?php echo $k; ?></th>
                <td><?php echo $value['Lophoc']['ten_lop']; ?></td>
                <td><?php echo $value['Lophoc']['mo_ta']; ?></td>
                <td>@mdo</td>
                <td>@mdo</td>
                </tr>
            <?php
                }
            ?>
            </tbody>
        </table>