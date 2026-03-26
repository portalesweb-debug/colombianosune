/*
Made by JorgBot
*/

/*Menu movil click*/

(function ($, Drupal, once) {
  Drupal.behaviors.menuAccordion = {
    attach: function (context) {
      $(once('menuAccordion', '.nav-item.dropdown > a', context)).on('click', function (e) {
        if (window.innerWidth < 992) {
          e.preventDefault();
          var $parent = $(this).parent();
          
          $parent.toggleClass('show');
          $(this).next('.dropdown-menu').toggleClass('show');
          
          $parent.siblings('.nav-item.dropdown').removeClass('show')
            .find('.dropdown-menu').removeClass('show');
        }
      });
    }
  };
})(jQuery, Drupal, once);

/*Alertas*/

(function (Drupal) {
  Drupal.behaviors.customAlerts = {
    attach: function (context) {

      const alerts = context.querySelectorAll('.alert-success, .alert-danger');

      alerts.forEach(function (alert) {

        if (alert.classList.contains('processed')) return;
        alert.classList.add('processed');

        const closeAlert = () => {
          alert.style.transition = "opacity 0.6s ease, transform 0.6s ease";
          alert.style.opacity = "0";
          alert.style.transform = "translateY(20px)";
          setTimeout(() => alert.remove(), 600);
        };

        setTimeout(closeAlert, 5000);

        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn) {
          closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeAlert();
          });
        }
      });
    }
  };
})(Drupal);



(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.colombianosUneTheme = {
    attach: function (context, settings) {

      once('colombianos-une-init', 'html', context).forEach(function () {

        initMobileNavigation();

        enhanceFormElements();

        initScrollEffects();

        if (typeof bootstrap !== 'undefined') {
          var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
          tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
          });
        }

        if (typeof bootstrap !== 'undefined') {
          var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
          popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
          });
        }

        initSmoothScroll();

        initLazyLoading();

        initAnalytics();
      });
    }
  };

  function initMobileNavigation() {
    var $navbar = $('.navbar');
    var $navbarToggler = $('.navbar-toggler');
    var $navbarCollapse = $('.navbar-collapse');

    $(document).on('click', function (e) {
      if (!$navbar.is(e.target) && $navbar.has(e.target).length === 0) {
        if ($navbarCollapse.hasClass('show')) {
          $navbarToggler.click();
        }
      }
    });

    $('.navbar-nav .nav-link').on('click', function () {
      if ($navbarCollapse.hasClass('show')) {
        $navbarToggler.click();
      }
    });

    $(window).on('scroll', function () {
      if ($(window).scrollTop() > 100) {
        $navbar.addClass('navbar-scrolled');
      } else {
        $navbar.removeClass('navbar-scrolled');
      }
    });
  }

  function enhanceFormElements() {
    $('.form-item input[type="text"], .form-item input[type="email"], .form-item input[type="password"], .form-item textarea, .form-item select')
      .addClass('form-control');

    $('.form-item input[type="checkbox"]').addClass('form-check-input');
    $('.form-item input[type="radio"]').addClass('form-check-input');

    $('.form-control').each(function () {
      var $input = $(this);
      var $wrapper = $input.closest('.form-item');

      if ($wrapper.find('label').length > 0) {
        $wrapper.addClass('form-floating');
      }
    });

    $('.form-control').on('blur', function () {
      validateField($(this));
    });

    $('form').on('submit', function () {
      var $form = $(this);
      var $submitBtn = $form.find('input[type="submit"], button[type="submit"]');

      $submitBtn.addClass('loading').prop('disabled', true);

      if (!$submitBtn.find('.spinner-border').length) {
        $submitBtn.prepend('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
      }
    });
  }

  function validateField($field) {
    var value = $field.val().trim();
    var isValid = true;
    var errorMessage = '';

    if ($field.prop('required') && !value) {
      isValid = false;
      errorMessage = 'Este campo es requerido';
    } else if ($field.attr('type') === 'email' && value) {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        isValid = false;
        errorMessage = 'Formato de email inválido';
      }
    }

    if (isValid) {
      $field.removeClass('is-invalid').addClass('is-valid');
      $field.siblings('.invalid-feedback').remove();
    } else {
      $field.removeClass('is-valid').addClass('is-invalid');

      if (!$field.siblings('.invalid-feedback').length) {
        $field.after('<div class="invalid-feedback">' + errorMessage + '</div>');
      }
    }
  }

  function initScrollEffects() {
    if (!$('#back-to-top').length) {
      var $backToTop = $('<button id="back-to-top" class="btn btn-primary btn-floating" title="Volver arriba"><i class="bi bi-arrow-up"></i></button>');
      $('body').append($backToTop);

      $(window).on('scroll', function () {
        if ($(window).scrollTop() > 300) {
          $backToTop.fadeIn();
        } else {
          $backToTop.fadeOut();
        }
      });

      $backToTop.on('click', function () {
        $('html, body').animate({ scrollTop: 0 }, 600);
      });
    }

    if (typeof IntersectionObserver !== 'undefined') {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
          }
        });
      });

      document.querySelectorAll('.node, .block, .view').forEach(function (el) {
        observer.observe(el);
      });
    }
  }

  function initSmoothScroll() {
    $('a[href^="#"]').on('click', function (e) {
      var target = $(this.getAttribute('href'));

      if (target.length) {
        e.preventDefault();
        $('html, body').animate({
          scrollTop: target.offset().top - 100
        }, 600);
      }
    });
  }

  function initLazyLoading() {
    if (typeof IntersectionObserver !== 'undefined') {
      var imageObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var img = entry.target;
            if (img.dataset.src) {
              img.src = img.dataset.src;
              img.classList.remove('lazy');
              img.classList.add('loaded');
              imageObserver.unobserve(img);
            }
          }
        });
      });

      document.querySelectorAll('img[data-src]').forEach(function (img) {
        imageObserver.observe(img);
      });
    }
  }

  function initAnalytics() {
    $('a[href^="http"]:not([href*="' + location.hostname + '"])').on('click', function () {
      var url = $(this).attr('href');
      if (typeof gtag !== 'undefined') {
        gtag('event', 'click', {
          event_category: 'external_link',
          event_label: url
        });
      }
    });

    $('a[href$=".pdf"], a[href$=".doc"], a[href$=".docx"], a[href$=".xls"], a[href$=".xlsx"], a[href$=".zip"]').on('click', function () {
      var url = $(this).attr('href');
      if (typeof gtag !== 'undefined') {
        gtag('event', 'download', {
          event_category: 'file_download',
          event_label: url
        });
      }
    });
  }

  Drupal.theme.showNotification = function (message, type) {
    type = type || 'info';
    var alertClass = 'alert-' + type;

    var $notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show notification-custom" role="alert">')
      .html(message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');

    $('.main-content').prepend($notification);

    setTimeout(function () {
      $notification.alert('close');
    }, 5000);
  };

})(jQuery, Drupal, once);