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

                        var d = new Date();

                        var month = d.getMonth() + 1;
                        var day = d.getDate();

                        var today = d.getFullYear() + '-' +
                                (month < 10 ? '0' : '') + month + '-' +
                                (day < 10 ? '0' : '') + day;

                        var div = '<div class="m-4 formrow" id="div' + id + '">' +
                                '<div class="row g-4">' +
                                '<div class="col-2">' +
                                ' <select class="form-control" did="cid' + id + '" name="course[]" id="course-select-' + id + '"  required>' +
                                '</select>' +
                                '</div>' +
                                '<div class="col-3">' +
                                '   <input type="date" name="dates[]" min="' + today + '" class="form-control datepickerclass" placeholder="Date" required>' +
                                '</div>' +
                                '<div class="col-2">' +
                                '   <input type="time" name="starttime[]" id="st' + id + '" tagid="' + id + '" class="form-control timepicker" placeholder="Start Time" required>' +
                                '</div>' +
                                '<div class="col-2">' +
                                '    <input type="time" name="endtime[]" id="et' + id + '" tagid="' + id + '" class="form-control timepicker" placeholder="End Time" required>' +
                                '</div>' +
                                '<div>' +
                                '<input type="number" min="1" name="max_user[]" class="form-control" value="10" required style="width:75px;">' +
                                '</div>' +
                                '<div class="col-2">' +
                                '   <button class="btn btn-secondary removediv" id="' + id + '">Remove</button>' +
                                '</div>' +
                                '</div>' +
                                '</div>';

                        $('#slotform').append(div);

                        $.ajax({
                            url: 'ajax.php',
                            dataType: 'html',
                            data: {id: id, action: 'get_courses_option'},
                            success: function (res) {

                                res = JSON.parse(res);

                                $('body #course-select-' + res[0]).html(res[1]);
                                $('body #course-select-' + res[0]).select2();
                            }
                        });
                    });


                    $(document).on('click', '.removediv', function (e) {
                        e.preventDefault();
                        var id = $(this).attr('id');
                        $('#div' + id).remove();
                    });


//                    $(document).on('change', '.starttime, .endtime', function (e) {
//                        e.preventDefault();
//                        var id = $(this).attr('tagid');
//                        var cid = $('#cid' + id).val();
//                        var stime = $('#st' + id).val();
//                        var etime = $('#et' + id).val();
//                        if (stime != '' && etime != '') {
//                            $.ajax({
//                                url: 'ajax.php',
//                                dataType: 'json',
//                                data: {cid: cid, stime: stime, etime: etime, action: 'checkdate'},
//                                success: function () {
//
//                                }
//                            });
//                        }
//
//                    });


                    $(document).on('click', '.getplist', function (e) {
                        e.preventDefault()
                        var schid = $(this).attr('schid');
                        $('body .participantlist').html('');
                        if (schid) {
                            $.ajax({
                                type: 'post',
                                url: 'ajax.php',
                                data: {schid: schid, action: 'getparticipant'},
                                success: function (res) {
                                    $('body .participantlist').html(res);
                                }
                            });
                        }
                    });


                    $(document).on('submit', '#scheduletask_form', function (e) {

                        e.preventDefault();

                        $.ajax({
                            type: 'post',
                            url: 'ajax.php?action=save_data',
                            data: $('form').serialize(),
                            success: function (res) {
                                if (res == 1) {
                                    window.location.href = 'table.php?success=1';
                                } else {
                                    alert(res);
                                }
                            }
                        });
                    });


                    $('form[id="scheduletask_form"]').validate({
                        rules: {
                            course: 'required',
                            dates: 'required',
                            starttime: 'required',
                            endtime: 'required',
                            max_user: 'required',
                        },
                        messages: {
                            course: 'required',
                            dates: 'required',
                            starttime: 'required',
                            endtime: 'required',
                            max_user: 'required',
                        },
                        submitHandler: function (form) {
                            form.submit();
                        }
                    });


                },
            }
        });