@extends('layouts.app')
@section('left-bar')
    @include('layouts.danhmuc')
@endsection
@section('content')
    <div class="search-area">
        <div class="title-bar">
            Tạo mới khối đối tượng đoàn viên
            <div class="arrow-down"></div>
        </div>
        <div class="content-area">
            <form class="search-form" method="POST" action="{{route('blockmember.store')}}">
                @csrf
                <label>Tên </label>
                <input type="text" name="name" class="search-code" style="width:300px">
                <input type="submit" value='Thêm'>
            </form>
        </div>
    </div>
    <div class="body">
        <a id="edit-user" class="addnew" href="#" style="display:none">Sửa </a>
        <button class="delete" id="removeItems">Xoá</button>
        <form id="member_form" method="POST">
            @csrf
            <table>
                <thead>
                <td style="width:20px"><input type="checkbox" id="checkAll"></td>
                <td>TÊN </td>
                </thead>
                <tbody>
                @foreach ($list as $item)
                    <tr>
                        <td><input type="checkbox" name="block_member_ids[]" value="{{$item->id}}"></td>
                        <td>{{$item->name}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </form>
    </div>
@endsection
@push('script')
    <script>
        $("#removeItems").click(function(e) {
            var confirma = confirm("Bạn chắc chắn muốn xoá ? ");
            if (confirma){
                e.preventDefault();
                $('#member_form').prepend('<input type="hidden" name="_method" value="DELETE">');
                $('#member_form').attr('action', "{{route('blockmember.delete')}}").submit();
            }
        });
        $("#checkAll").click(function() {
            $('input:checkbox').not(this).prop('checked', this.checked);
        });

        var $checkboxes = $('#member_form tbody input[type="checkbox"]');

        $checkboxes.change(function(){
            var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
            var url = "/blockmember/" + this.value + "/edit";
            if (countCheckedCheckboxes === 1){
                $("#edit-user").attr("href", url)
                $('#edit-user').show();
            }
            else{
                $('#edit-user').hide();
            }
        });
    </script>
@endpush