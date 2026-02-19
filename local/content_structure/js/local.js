function readURL(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      $("#blah").attr("src", e.target.result);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function readURLmodule(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      $("#modimage").attr("src", e.target.result);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function getLesson(input) {
  var id = $("#moduleid").val();
  $("#less_name").val("");
  $("#hiddenid").val("");
  var url = "ajax.php";
  $.ajax({
    url: url,
    dataType: "html",
    type: "POST",
    data: { id: id, archtype: "lesson", action: "getlessons" },
    success: function (data) {
      data = JSON.parse(data);
      $("#lessonid").html(data[0]);
      if ($("#lessonid").length > 0) {
        $(".listing").html("");
      } else {
        $(".listing").html(data[1]);
      }
    },
    error: function () {
      alert("Data parsing error");
    },
  });
}

function getlearning(input) {
  var id = $("#lessonid").val();
  $("#loid_name").val("");
  $("#hiddenid").val("");
  var url = "ajax.php";
  $.ajax({
    url: url,
    dataType: "html",
    type: "POST",
    data: { id: id, archtype: "lesson", action: "getlearning" },
    success: function (data) {
      $(".listing").html(data);
    },
    error: function () {
      alert("Data parsing error");
    },
  });
}

$(document).ready(function () {
  $("body").on("click", ".editmod", function (e) {
    e.preventDefault();
    var id = $(this).attr("id");
    var name = $(this).text();
    var path = $(this).attr("path");
    var image = $(this).attr("image");

    $("#cid_name").val(name);
    $("#hiddenid").val(id);
    $("#modimage").attr(
      "src",
      path + "/local/content_structure/images/madia/" + image
    );
  });

  $("body").on("click", ".editless", function (e) {
    e.preventDefault();
    var id = $(this).attr("id");
    var name = $(this).text();
    var path = $(this).attr("path");
    var image = $(this).attr("image");

    $("#less_name").val(name);
    $("#hiddenid").val(id);
    $("#modimage").attr(
      "src",
      path + "/local/content_structure/images/madia/" + image
    );
  });

  $("body").on("click", ".editlo", function (e) {
    e.preventDefault();
    var id = $(this).attr("id");
    var name = $(this).text();
    var path = $(this).attr("path");
    var image = $(this).attr("image");

    $("#loid_name").val(name);
    $("#hiddenid").val(id);
    $("#modimage").attr(
      "src",
      path + "/local/content_structure/images/madia/" + image
    );
  });

  $("body").on("click", ".closemodal", function () {
    $(".modal-body").html("");
  });

  $("body").on("click", ".add-module", function (e) {
    e.preventDefault();
    $("body #loading").css("display", "block");
    //validate allowed mark
    var form = $("#addmoduleform")[0];
    var data = new FormData(form);
    var url = "ajax.php?action=addmodule";
    $.ajax({
      url: url,
      dataType: "html",
      type: "POST",
      enctype: "multipart/form-data",
      data: data,
      processData: false,
      contentType: false,
      cache: false,
      success: function (data) {
        if (data == "1") {
          $(".manual-mark").css("display", "none");
          $("#exampleModalLabel").hide();
          $(".modal-body").html(
            '<h3 style="text-align:center;">Record successfully saved</h3>'
          );
        } else {
          $(".modal-body").html("Something went wrong, Please try again");
        }
      },
      complete: function () {
        $("#loading").hide();
      },
      error: function () {
        alert("Data saving error");
      },
    });
  });

  //$('body').on('keyup', '.search-query', function () {
  //    alert()
  //    var query = $.trim($(this).prevAll('.search-query').val()).toLowerCase();
  //    $('#gallery .img-container').each(function () {
  //        var $this = $(this);
  //        if ($this.text().toLowerCase().indexOf(query) === -1)
  //            $this.closest('div.img-container').fadeOut();
  //        else
  //            $this.closest('div.img-container').fadeIn();
  //    });
  //});
});
