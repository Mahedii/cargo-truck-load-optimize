<script type="text/javascript">

    $(document).ready(function () {

        /**
         * Set toastr options
         */
        toastr.options = {
            closeButton: true,
            newestOnTop: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 3000,
        };

        /**
         * Set CSRF token for form request
         */
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        /**
         * Fetch selected inputs information for skills
         * Set them in a modal for update
         */
        $(document).on('click', '.ajax-edit-data-btn', function () {
            var slug = $(this).data('slug');
            var id = $(this).closest('tr').data('id');
            var url = '/cargo/box/info/fetch/'+slug;
            // alert(url);

            $.ajax({
                type:'GET',
                url:url,
                dataType:'json',
                success:function(data){
                    var tableData = data.fetchedData;
                    // console.log(tableData);
                    tableData.forEach(function(row) {
                        var $option = $("#box-cargo-id").find("option[value='" + row.cargo_id + "']");

                        // Check if the option exists
                        if ($option.length > 0) {
                            // Set both the selected option's value and text
                            $("#box-cargo-id").val(row.cargo_id);
                            $("#box-cargo-id option:selected").text($option.text());
                        }
                        $('#slug').val(row.slug);
                        $('#box-dimension').val(row.box_dimension);
                        $('#box-quantity').val(row.quantity);
                    });
                    $('#zoomInEditModal').modal('show');
                }
                ,error: function (xhr, ajaxOptions, thrownError) {
                    toastr.error("Status: "+xhr.status+ " Message: "+thrownError);
                }
            });

        });


        /**
         * Update skills lists data
         */
        // $(document).on("submit", "#cargoInfoUpdateForm", function(e){

        //     e.preventDefault();

        //     $('#cargoInfoUpdateForm .ajax-submit .submit-btn-text').toggleClass('hide');
        //     $('#cargoInfoUpdateForm .ajax-submit .ajax-spinner').toggleClass('hide');
        //     var classNameOrId = "#cargoInfoUpdateForm";

        //     var form = $('#cargoInfoUpdateForm')[0];
        //     let formData = new FormData(form);
        //     var rowId = $("#skills-row-id").val();

        //     // alert(dataString);

        //     $.ajax({
        //         url: "",
        //         type: 'POST',
        //         data: formData,
        //         processData: false,
        //         cache: false,
        //         contentType: false,
        //         success: function(data) {
        //             if(data.status == 200) {
        //                 ajaxSpinnerLoadToggle(classNameOrId);
        //                 ajaxLoadSubmitBtnToggle(classNameOrId);
        //                 toastr.success(data.message);

        //                 var updatedRowData = data.updatedRowData;
        //                 var rowId = updatedRowData[0].id;

        //                 var table = $('#buttons-datatables').DataTable();
        //                 var row = table.row('#row-' + rowId);

        //                 row.cell(row.index(), 1).data(updatedRowData[0].skill_name); // Replace 'text' with the appropriate field name
        //                 row.cell(row.index(), 2).data(updatedRowData[0].icon_name);
        //                 row.cell(row.index(), 3).data(createActions(updatedRowData[0]));
        //                 row.draw(false).node();

        //                 $('#zoomInEditModal').modal('hide');
        //                 $('#cargoInfoUpdateForm')[0].reset();
        //             } else {
        //                 toastr.error(data.message);

        //                 // Display validation error messages if any
        //                 if (data.errors) {
        //                     var errors = data.errors;

        //                     // Clear previous error messages
        //                     $('.error-message').remove();

        //                     // Display the new error messages
        //                     for (var key in errors) {
        //                         if (errors.hasOwnProperty(key)) {
        //                             var errorMessage = errors[key][0];
        //                             $('input[name="' + key + '"]').after('<span class="text-danger error-message">' + errorMessage + '</span>');
        //                         }
        //                     }
        //                 }
        //             }
        //         },
        //         error: function (xhr, ajaxOptions, thrownError) {
        //             toastr.error("Status: "+xhr.status+ " Message: "+thrownError);
        //         }
        //     });

        // });


        /**
         * Show fetched datatable data
         */
        function showData(data){

            var table = $('#buttons-datatables').DataTable();
            var id = 1;

            $.each(data, function( index, value ) {
                var row = table.row.add([id++, value.skill_name, value.icon_name, createActions(value)]).draw(false).node();
                $(row).attr('data-table-secret', value.secret_key); // Set data-table attribute
                $(row).attr('data-id', value.id);
                $(row).attr('id', 'row-'+value.id);
            });

        }


        /**
         * Set action buttons for datatable
         */
        function createActions(data) {
            var actionContent =
                '<div class="dropdown d-inline-block">' +
                    '<button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
                        '<i class="ri-more-fill align-middle"></i>' +
                    '</button>' +
                    '<ul class="dropdown-menu dropdown-menu-end">' +
                        '<li>' +
                            '<a href="javascript:void(0);" data-slug="' + data.slug + '" class="dropdown-item edit-item-btn ajax-edit-data-btn" >' +
                                '<i class="ri-pencil-fill align-bottom me-2 text-muted"></i>' +
                                'Edit' +
                            '</a>' +
                        '</li>' +
                        '<li>' +
                            '<a href="#" data-slug="' + data.slug + '" class="dropdown-item ajax-delete-data-btn">' +
                                '<i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>' +
                                'Delete' +
                            '</a>' +
                        '</li>' +
                    '</ul>' +
                '</div>';

            return actionContent;
        }
    });

</script>
