define(["jquery","local_tenant_appearance/bootstrap-colorpicker","core/ajax","core/templates","core/notification"], function($,colorpicker,ajax,templates,notification) {
  return {
    init: function() {
	  $('#color1').colorpicker({});
    $('#color2').colorpicker({});
    $('#color3').colorpicker({});
    $(document).on('click', ".add_data", function () {
      $("#append_data").append(templates.render('local_tenant_appearance/attach'));
    });

    $(document).on('click', ".close_row", function () {
      var whichtr = $(this).closest("tr");
      whichtr.remove();      
    });

    $(document).on('click', ".show_font_upload_div", function () {
      $(".frm_font_upload").show();    
    });

    $(document).on('change', ".selectfontfile", function () {
      var filename = $(this).val().split('\\').pop();
      $(".custom-file-label").text(filename);
      filename = filename.substr(0, filename.lastIndexOf('.'));
      var splitname = filename.split("-");
      //$(this).closest('tr').find('.fontfamily').val(splitname[0]);
      $(".fonttype").val(splitname[1]);
    });

    $(document).on('click', ".delete_font", function () {
      var filename = $(this).closest('tr').find('.fontfile').val();
      var fileindex = $(this).closest('tr').find('.fileindex').val();
        //ajax call
        ajax.call([{
          methodname: 'local_tenant_appearance_delete_font',
          args: {filename: filename, fileindex: fileindex },
          done: '',
          fail: notification.exception
        }]);
      });

    }
  };
});