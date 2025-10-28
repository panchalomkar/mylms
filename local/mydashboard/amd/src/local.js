define(['jquery', 'core/templates', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/ajax', 'core/yui'],
        function ($, Templates, Str, ModalFactory, ModalEvents, Ajax, Y) {


            // Public functions.
            return {
                load: function (args) {


                    //Like in forum
                    $(document).on('click', '#addrow', function (e) {
                        e.preventDefault();
                        var id = $(this).parent().parent().parent().attr('id');

                        id = parseInt(id) + 1;



                        var div = '<div class="m-4 formrow" id="div' + id + '">' +
                                '<div class="row g-4">' +
                                '<div class="col-3">' +
                                '   <input type="text" name="level[]" class="form-control" placeholder="Level Name" required>' +
                                '</div>' +
                                '<div class="col-2">' +
                                '   <input type="number" name="point[]" id="st' + id + '" tagid="' + id + '" class="form-control" placeholder="Point" required>' +
                                '</div>' +
                                '<div class="col-2">' +
                                '    <input type="number" name="grade[]" id="et' + id + '" tagid="' + id + '" class="form-control" placeholder="Grade" required>' +
                                '</div>' +
                                '<div>' +
                                '<input type="file" min="1" name="icon[]" class="form-control" required style="width:230px;">' +
                                '</div>' +
                                '<div class="col-2">' +
                                '   <button class="btn btn-secondary removediv" id="' + id + '">Remove</button>' +
                                '</div>' +
                                '</div>' +
                                '</div>';

                        $('#slotform').append(div);

                    });


                    $(document).on('click', '.removediv', function (e) {
                        e.preventDefault();
                        var id = $(this).attr('id');
                        $('#div' + id).remove();
                    });


                    $(document).on('submit', '#level_form', function (e) {

                        e.preventDefault();
//                        var formData =  new FormData(form[0]);
                        var formData = $('form').serializeArray();
                        $.ajax({
                            type: 'post',
                            url: '../ajax.php?action=save_data',
                            data: new FormData(this),
                            dataType: 'json',
                            contentType: false,
                            cache: false,
                            processData: false,
                            success: function (res) {
                                if (res == 1) {
                                   // window.location.href = 'table.php?success=1';
                                } else {
                                    alert(res);
                                }
                            }
                        });
                    });


                    $('form[id="level_form"]').validate({
                        rules: {
                            level: 'required',
                            point: 'required',
                            grade: 'required',
                            icon: 'required'
                        },
                        messages: {
                            level: 'required',
                            point: 'required',
                            grade: 'required',
                            icon: 'required'
                        },
                        submitHandler: function (form) {
                            form.submit();
                        }
                    });


                },
            }
        });