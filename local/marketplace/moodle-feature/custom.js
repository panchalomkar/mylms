jQuery(document).ready(function($) {
      
      var owl = $("#owl-demo-new");
      owl.owlCarousel({
      items: 3,
      autoplay: true,
      autoplayHoverPause: true,
      autoplayTimeout: 4000,
      smartSpeed: 800,
      dots: false,
      loop: true,
      nav: true,
      rewindNav: false,
      navText: [
         '<i class="bi bi-arrow-left" aria-hidden="true"></i>',
         '<i class="bi bi-arrow-right" aria-hidden="true"></i>'
      ],
      
      responsive:{
                 0:{ // breakpoint from 0 up - small smartphones
                     items:1,
                     nav:true
                 },
                 480:{  // breakpoint from 480 up - smartphones // landscape
                     items:2,
                     nav:true
                 },
                 768:{ // breakpoint from 768 up - tablets
                     items:2,
                     nav:true,
                     loop:false
                 },
                 992:{ // breakpoint from 992 up - desktop
                     items:3,
                     nav:true,
                     loop:true
                 }
             }
      });
      
      
      });