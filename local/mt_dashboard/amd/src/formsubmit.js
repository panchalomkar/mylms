// Standard license block omitted.
/*
 * @package    local_mt_dashboard
 * @copyright  2015 Someone cool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module local_mt_dashboard/formsubmit
 */
define(['jquery', 'theme_remui/select2', 'jqueryui', 'core/str','core/ajax'], function ($, select2, jqueryui, str,ajax) {

    var strings = str.get_strings([
        { key: 'backtotenant', component: 'local_mt_dashboard' },
        { key: 'norecordfound', component: 'local_mt_dashboard' }

    ]);

/*    function getcompanylist(txt, suspend) {
        var pagename = document.location.pathname.match(/[^\/]+$/)[0];
        $.ajax({
            url: M.cfg.wwwroot + "/local/mt_dashboard/ajax/ajax.php",
            data: { 'searchdata': txt, 'suspend': suspend },
            dataType: "json",
            type: "POST",
            success: function (data) {
                alert(data);
                if (data != "") {
                    var html = "";
                    html += "<ul class='companylist'>";
                    $.each(data, function (key, value) {
                        if (value !== '') {
                            var url = M.cfg.wwwroot + "/local/mt_dashboard/edit.php?companyss=" + key + "&company=" + key;
                            var link = "<a id='ui-id-'" + key + "'  href=" + url + ">" + value + "</a>";
                            html += "<li>" + link + "</li>";
                        }
                    });
                    html += "</ul>";
                    $(".getlist").append(html);
                } else {
                    var nohtml = "";
                    nohtml += "<ul class='companylist'><li>";
                    if (pagename !== 'index.php') {
                        nohtml += "<li class='back'><a href='" + M.cfg.wwwroot + "/local/mt_dashboard/index.php?companyss=0&company=0'>" + M.util.get_string('backtotenant', 'local_mt_dashboard') + "</a></li>";
                    }
                    nohtml += "<li>" + M.util.get_string('norecordfound', 'local_mt_dashboard') + "</li>";
                    nohtml += "</ul>";
                    $(".getlist").append(nohtml);
                }
            }
        });
    }*/

        function getcompanylist(txt, suspend) {
        var pagename = document.location.pathname.match(/[^\/]+$/)[0];

        ajax.call([{
            methodname: 'local_mt_dashboard_searchtenant',
            args: {searchdata: txt, suspend: suspend},
            done: function(data) {

              //  alert(data);

            //console.log(data);
            var responsedata = JSON.parse(JSON.stringify(data));
            var htmldata = responsedata['id'];
            var htmldata1 = responsedata['val'];
            console.log(htmldata);
            console.log(htmldata1);
            if (htmldata1 != "") {
                console.log('hi');
            }
            else{
                console.log('bye');
            }

            //var data_is = JSON.parse(data);    
            //window.alert(data_is);

             if (data != "") {
                    var html = "";
                    html += "<ul class='companylist'>";
                    //$.each(data, function (key, value) {
                        if (htmldata1 !== '') {
                            var url = M.cfg.wwwroot + "/local/mt_dashboard/edit.php?companyss=" + htmldata + "&company=" + htmldata;
                            var link = "<a id='ui-id-'" + htmldata + "'  href=" + url + ">" + htmldata1 + "</a>";
                            html += "<li>" + link + "</li>";
                        }
                   // });
                    html += "</ul>";
                    $(".getlist").append(html);
                } else {
                    var nohtml = "";
                    nohtml += "<ul class='companylist'><li>";
                    if (pagename !== 'index.php') {
                        nohtml += "<li class='back'><a href='" + M.cfg.wwwroot + "/local/mt_dashboard/index.php?companyss=0&company=0'>" + M.util.get_string('backtotenant', 'local_mt_dashboard') + "</a></li>";
                    }
                    nohtml += "<li>" + M.util.get_string('norecordfound', 'local_mt_dashboard') + "</li>";
                    nohtml += "</ul>";
                    $(".getlist").append(nohtml);
                }




            
            },
           
        }]);

/*        $.ajax({
            url: M.cfg.wwwroot + "/local/mt_dashboard/ajax/ajax.php",
            data: { 'searchdata': txt, 'suspend': suspend },
            dataType: "json",
            type: "POST",
            success: function (data) {
                if (data != "") {
                    var html = "";
                    html += "<ul class='companylist'>";
                    $.each(data, function (key, value) {
                        if (value !== '') {
                            var url = M.cfg.wwwroot + "/local/mt_dashboard/edit.php?companyss=" + key + "&company=" + key;
                            var link = "<a id='ui-id-'" + key + "'  href=" + url + ">" + value + "</a>";
                            html += "<li>" + link + "</li>";
                        }
                    });
                    html += "</ul>";
                    $(".getlist").append(html);
                } else {
                    var nohtml = "";
                    nohtml += "<ul class='companylist'><li>";
                    if (pagename !== 'index.php') {
                        nohtml += "<li class='back'><a href='" + M.cfg.wwwroot + "/local/mt_dashboard/index.php?companyss=0&company=0'>" + M.util.get_string('backtotenant', 'local_mt_dashboard') + "</a></li>";
                    }
                    nohtml += "<li>" + M.util.get_string('norecordfound', 'local_mt_dashboard') + "</li>";
                    nohtml += "</ul>";
                    $(".getlist").append(nohtml);
                }
            }
        });*/
    }


    return {
        init: function () {
            $(document).ready(function () {
                $('input#companyid').val(jQuery('select#menucompanyss option:selected').val());
                $('input#showsuspendedcompanies').change(function () {
                    this.form.submit();
                });
                $('select#menucompanyss').change(function () {
                    $('input#companyid').val(this.value);
                    this.form.submit();
                });

                /**
                * Show company list in search box with link   
                * 
                * @author Bhagyavant S Panhalkar
                * @since 17-july-2019
                * @paradiso
                */
                if ($('#showsuspendedcompanies').is(':checked')) {
                    var suspend = $('#showsuspendedcompanies').val();
                } else {
                    var suspend = '0';
                }

                /**
                * Show all company list on click at search box   
                * 
                * @author Manisha M
                * @since 20-08-2019
                * @paradiso
                */
                $(document).on('click', '#search-mt', function (e) {
                    //console.log('one');
                    $(".getlist").html('');
                    $(".getlist").show();
                    var txt = $(this).val();
                    getcompanylist(txt, suspend)
                })
                $(document).on('keyup', '#search-mt', function (e) {
                  //  console.log('two');
                    $(".getlist").html('');
                    $(".getlist").addClass('showli');
                    var txt = $(this).val();
                    getcompanylist(txt, suspend)
                })
                $(document).click(function (e) {
                    var container = $("#search-mt");
                    if (!container.is(e.target) && container.has(e.target).length === 0) {
                        $(".getlist").hide();
                    }
                })
            });
        }
    }
});