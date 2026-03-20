define(['jquery', 'jqueryui','core/ajax', 'core/notification'], function($,jui,ajax, notification) {

    function canvascount() {
        $('.lpd-lp-detail-body canvas').each(function(){
            var canvas = this;

            switch ($(this).data('element')) {
                case 'first':

                var context = canvas.getContext('2d'); 
                context.beginPath();
                context.moveTo(15, 15);
                context.lineTo(15,30);
                context.lineWidth = 2;
                context.strokeStyle = $(this).parent().children('i').data('color');
                context.stroke();  
           
                break; 
                case 'middle':

                    var context = canvas.getContext('2d');
                    context.beginPath();
                    context.moveTo(15, 0);
                    context.lineTo(15,30);
                    context.lineWidth = 2;
                    context.strokeStyle = $(this).parent().children('i').data('color');
                    context.stroke(); 
           
                break;
                case 'last':
           
                    var context = canvas.getContext('2d');
                    context.beginPath();
                    context.moveTo(15, 0);
                    context.lineTo(15,15);
                    context.lineWidth = 2;
                    context.strokeStyle = $(this).parent().children('i').data('color');
                    context.stroke(); 
               
                break;
            }

            //$('.block_lpd_content .tooltipelement_html').tooltip({html:true , placement: "right" });
        });
    }
    function lpdpaginationajax(rpage=0,e){
      var requests = ajax.call([{
        methodname: 'block_lpd_lpviewdetails',
        args: {page:rpage}
      }]);
      requests[0].done(function(data) {
        $(".block_lpd nav").html('');
        if(data.haslp != '') {
          $("#block_lpd_content").html(data.haslp);
        } else {
          $("#block_lpd_content").html(data.table);
        }
        $("aside.block_lpd ul.pagination li > a").removeAttr("href");
      }).fail(notification.exception);
        e.stopImmediatePropagation();
        return false;
    }
    return {
        init: function() {
            
            $(document).on('click', 'aside.block_lpd ul.pagination li:nth-last-child(1) a.next',function(e) {
              $("aside.block_lpd ul.pagination li > a").removeAttr("href");
                var rpage = parseInt($('aside.block_lpd ul.pagination li.active a').html());
                if(rpage === 1){
                 rpage = 1;
                }
                lpdpaginationajax(rpage,e);
              });
            $(document).on('click', 'aside.block_lpd ul.pagination li:first-child a.previous',function(e) {
              $("aside.block_lpd ul.pagination li > a").removeAttr("href");
              var rpage = parseInt($('aside.block_lpd ul.pagination li.active a').html());
              rpage = rpage - parseInt(1);
              if(rpage === 1){
                 rpage = 0;
                }else{
                 rpage = rpage - parseInt(1);
                }
                lpdpaginationajax(rpage,e);
              });
            $("aside.block_lpd ul.pagination li > a").removeAttr("href");
            $(document).on('click', 'aside.block_lpd ul.pagination li:not(.active) > a:not(.next, .previous)',function(e) {
                  var rpage = $(this).html();
                  var rrpage = parseInt($('aside.block_lpd ul.pagination li.active a').html());
                  if(rpage === rrpage){
                    rpage = $(this).html();
                  }else{
                    rpage = rpage - parseInt(1);
                  }
                  
                  lpdpaginationajax(rpage,e);
                    
              });
            $(document).on('click', '#block_lpd_content div.lpd-lp-content-header',function(e) {
                var lpid = $(this).attr('data-lpid');
                var page = get("page");
                var lpid_selected = get("lp_id");
                var lphtmlobject = $(this) ;
                
                if($(this).hasClass('collapsedlpd')) {
                    $(this).removeClass('collapsedlpd');
                    $('#block_lpd_content span.lpd-lp-content-header').addClass('collapsedlpd');

                    var requests = ajax.call([{
                        methodname: 'block_lpd_getlpdetail',
                        args: {action: 'getLpDetail', learningPath: lpid, 'page': page, 'lpid_selected' : lpid_selected}
                    }]);

                    requests[0].done(function(response) {
                        if(response.data && response.data !== '') { 
                            lphtmlobject.parent().find('#lp-detail-'+lpid).html(response.data);
                            lphtmlobject.parent().css('height', 'auto');
                            canvascount();     
                        }
                        paginationAjax();
                    }).fail(notification.exception);         
                } else {
                    $(this).addClass('collapsedlpd');
                    $(this).parent().find('.lpd-lp-detail').empty();
                }

                $('#block_lpd_content div.lpd-lp-content-header').each(function() { 
                    if ($(this).hasClass('collapsedlpd')) {
                        $(this).parent().removeClass('card-box');
                        $(this).parent().find('.lpd-lp-detail').empty();
                    }
                });

                if($(this).hasClass('collapsedlpd')) { 
                    $(this).parent().removeClass('card-box');
                }

                if (!$(this).hasClass('collapsedlpd')) { 
                    $(this).parent().addClass('card-box');
                }
            });

            $(document).on('click', '#block_lpd_content span.lpd-back',function(e) {
                var lpid = $(this).attr('data-lpid');
                $('div.lpd-lp-detail.container-fluid').html('');
                if($(this).hasClass('collapsedlpd')) {
                    $(this).removeClass('collapsedlpd');
                    $('#block_lpd_content div.lpd-lp-content-header').addClass('collapsedlpd');
                }
            }); /* commented the code not in use now
            $('span.lpd-back').on('click', function () {
                // Clear HTML inside div.lpd-lp-detail
                $('div.lpd-lp-detail.container-fluid').html('');
            });
            */
            var lpid_selected = parseInt(get("lp_id"));
            jQuery("#block_lpd_content .lpd-lp-content").each(function(key, value){
                e = jQuery(value).find(".lpd-lp-content-header");
                lpid = e.attr('data-lpid');
                if(lpid > 0 && lpid_selected == lpid){
                    jQuery(e).click();
                }
            });

            $(document).ready(function(){
                paginationAjax();
                $.ajaxPrefilter(function( options, original_Options, jqXHR ) {
                    options.async = true;
                });
                $('div.tbs-content ul li#view-button').click(function(){
                    canvascount();  
                });
                if ($('div.lpd-lp-content-header').hasClass('collapsed')) {
                    $('aside.block.block_lpd').removeClass('backpath');
                }
                
                if ($('div.lpd-lp-content-header.collapsed').hasClass('collapsedlpd')) {
                    $('aside.block.block_lpd').addClass('backpath');
                }
            });

            function get( name ){
              var regexS = "[\\?&]"+name+"=([^&#]*)";
              var regex = new RegExp ( regexS );
              var tmpURL = window.location.href;
              var results = regex.exec( tmpURL );
              if( results == null )
                  return"";
              else
                  return results[1];   
            }
      
      function URLToArray(url) {
          var request = {};
          var pairs = url.substring(url.indexOf('?') + 1).split('&');
          for (var i = 0; i < pairs.length; i++) {
              if(!pairs[i])
                  continue;
              var pair = pairs[i].split('=');
              request[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
           }
           return request;
      }
      
      function paginationAjax(){
              jQuery("#block_lpd_content .pagination-paradiso li.page-number").each(function(){
                 item = this; 
                 /*If finds element*/
                 aElement = jQuery(item).find("a");
                 if(aElement.length > 0){
                     var hrefTmp = jQuery(aElement).attr("href");
                     request = URLToArray(hrefTmp);
                     jQuery(aElement).attr("href","#block_lpd_content")
                     jQuery(aElement).attr("data-page",request.page || 0);
                     jQuery(aElement).attr("data-lp",request.lp_id || 0);
                 }
              });
              jQuery("#block_lpd_content .pagination.pagination-paradiso li.page-number").click(function(){
                  e = $(this);
              /*Get the attrs related to the data*/
              page = jQuery(this).find("a").data("page") || 0;
              lp_id = jQuery(this).find("a").data("lp") || 0;
              if(lp_id > 0){
                  /*Search the lp*/
                  var requests = ajax.call([{
                    methodname: 'block_lpd_getlpdetail',
                    args: {action: 'getLpDetail', learningPath: lp_id, 'page': page, 'lpid_selected' : lp_id}
                }]);
                requests[0].done(function(response) {

                  if(response.view && response.view !== '') {
                    if($('body').attr('data-pagetype') == 'site-index' || $('body').attr('data-pagetype') == 'my-index'){
                        e.closest('.lpd-lp-detail').html(response.view);
                        e.closest('.lpd-lp-detail').css('height', 'auto');
                    }else{
                        jQuery('.lpd-lp-content').html(response.view);
                        jQuery('.lpd-lp-content').css('height', 'auto');
                    }
                    $('.lpd-lp-detail-body canvas').each(function(){
                        var canvas = this;

                        switch ($(this).data('element')) {
                                case 'first':

                                    var context = canvas.getContext('2d'); 
                                    context.beginPath();
                                    context.moveTo(15, 15);
                                    context.lineTo(15,30);
                                    context.lineWidth = 2;
                                    context.strokeStyle = $(this).parent().children('i').data('color');
                                    context.stroke();  
                                   
                                break; 
                                case 'middle':

                                    var context = canvas.getContext('2d');
                                    context.beginPath();
                                    context.moveTo(15, 0);
                                    context.lineTo(15,30);
                                    context.lineWidth = 2;
                                    context.strokeStyle = $(this).parent().children('i').data('color');
                                    context.stroke(); 
                                   
                                break;
                                case 'last':
                                   
                                    var context = canvas.getContext('2d');
                                    context.beginPath();
                                    context.moveTo(15, 0);
                                    context.lineTo(15,15);
                                    context.lineWidth = 2;
                                    context.strokeStyle = $(this).parent().children('i').data('color');
                                    context.stroke(); 
                                   
                                break;
                        }
                        //$('.block_lpd_content .tooltipelement_html').tooltip({html:true , placement: "right" });
                    });    
                }
                paginationAjax();
                }).fail(notification.exception);
              }
          });  
      }
    }
    };
});