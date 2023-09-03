{{-- <script type="text/javascript">

    $(document).ready(function () {

        /**
         * Set CSRF token for form request
         */
        // $.ajaxSetup({
        //     headers: {
        //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //     }
        // });

        /**
         * Fetch selected inputs information for work-history list
         * Set them in a modal for update
         */
        // $('#workHistoryListData').on('click', '.ajax-edit-data-btn', function () {
        //     var slug = $(this).data('slug');
        //     var table_secret_key = $(this).closest('tr').data('table-secret');
        //     var id = $(this).closest('tr').data('id');
        //     var url = '/fetch/'+table_secret_key+'/'+slug;
        //     // alert(url);

        //     $.ajax({
        //         type:'GET',
        //         url:url,
        //         dataType:'json',
        //         success:function(data){
        //             $('#zoomInEditModal .table_secret_key').val(data.table_secret_key);
        //             $('#zoomInEditModal .edit-row-id').val("row-"+id);
        //             var tableData = data.field;
        //             tableData.forEach(function(row) {
        //                 $('#zoomInEditModal .slug').val(row.slug);
        //                 $('#zoomInEditModal .company_name').val(row.company_name);
        //                 $('#zoomInEditModal .role').val(row.role);
        //                 $('#zoomInEditModal .duration').val(row.duration);
        //                 $('.role_description').val(row.role_description);
        //             });
        //             $('#zoomInEditModal').modal('show');
        //         }
        //         ,error: function (xhr, ajaxOptions, thrownError) {
        //             toastr.error("Status: "+xhr.status+ " Message: "+thrownError);
        //         }
        //     });

        // });

        /**
         * Add work-history list data
         */
        $(".cargo_id").on("change", function(){
            var input = $(this);
            var cargo_id = input.val();
            var url = '/cargo/info/fetch/'+cargo_id;
            // alert(url);

            $.ajax({
                type:'get',
                url:url,
                dataType:'json',
                success:function(data){
                    if(data.status == 200) {
                        if(data.count > 0) {
                            table = $('#buttons-datatables').DataTable();
                            table.clear();
                            var rows = showData(data.fetchedData);
                            var optimizedData = getOptimizedData(cargo_id);
                            $('#available-box-info').removeClass('hide');
                        } else {
                            $('#available-box-info').addClass('hide');
                        }

                    } else {
                        alert(data.message);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert("Status: "+xhr.status+ " Message: "+thrownError);
                }
            });

        });

        function getOptimizedData(cargo_id) {
            var url = '/cargo/distribute/'+cargo_id+'/optimize';

            $.ajax({
                type:'get',
                url:url,
                dataType:'json',
                success:function(data){
                    if(data.status == 200) {
                        alert("It worked");
                        console.log(data);
                        console.log(data.consolidatedCargo);

                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert("Status: "+xhr.status+ " Message: "+thrownError);
                }
            });

        }

        /**
         * Show fetched datatable data
         */
        function showData(data){

            var table = $('#buttons-datatables').DataTable();
            var id = 1;

            $.each(data, function( index, value ) {
                var row = table.row.add([id++, value.box_dimension, value.quantity]).draw(false).node();
                $(row).attr('data-id', value.id);
                $(row).attr('class', 'cargo-box-'+value.id);
            });

        }
    });

</script> --}}
