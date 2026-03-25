new WOW().init();
(function ($) {
  "use strict";

  $(function () {
    var $menuLinks = $("#menu .nav-link");
    var navOffset = 100;

    $('a[href^="#"]').on("click", function (e) {
      var target = $(this.getAttribute("href"));
      if (target.length) {
        e.preventDefault();
        $("html, body").animate(
          { scrollTop: target.offset().top - navOffset },
          500,
        );
      }
    });

    function revealOnScroll() {
      $(".reveal").each(function () {
        var top = this.getBoundingClientRect().top;
        if (top < window.innerHeight - 70) {
          $(this).addClass("visible");
        }
      });
    }

    revealOnScroll();
    $(window).on("scroll", revealOnScroll);

    function setActiveMenuItem() {
      var scrollPos = $(window).scrollTop() + navOffset + 20;
      var currentId = "";

      $menuLinks.each(function () {
        var section = $($(this).attr("href"));
        if (section.length) {
          var top = section.offset().top;
          var bottom = top + section.outerHeight();
          if (scrollPos >= top && scrollPos < bottom) {
            currentId = section.attr("id");
          }
        }
      });

      $menuLinks.removeClass("active");
      if (currentId) {
        $('#menu .nav-link[href="#' + currentId + '"]').addClass("active");
      } else if ($menuLinks.length) {
        $menuLinks.first().addClass("active");
      }
    }

    setActiveMenuItem();
    $(window).on("scroll", setActiveMenuItem);

    $("#leadForm").on("submit", function (e) {
      e.preventDefault();
      $("#formMsg").text(
        "Gracias. Te contactaremos pronto con la información del curso.",
      );
      this.reset();
    });
  });
})(jQuery);
