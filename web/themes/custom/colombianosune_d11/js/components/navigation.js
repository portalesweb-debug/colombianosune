/*
Made by JorgBot
*/

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.navigationEnhanced = {
    attach: function (context, settings) {

      // Mega menú
      once('mega-menu', '.navbar-nav .dropdown', context).forEach(function (el) {
        var $dropdown = $(el);
        var $toggle = $dropdown.find('.dropdown-toggle');
        var $menu = $dropdown.find('.dropdown-menu');

        if (window.innerWidth > 991) {
          $dropdown.on('mouseenter', function () {
            $toggle.dropdown('show');
          });

          $dropdown.on('mouseleave', function () {
            $toggle.dropdown('hide');
          });
        }

        $menu.on('click', function (e) {
          e.stopPropagation();
        });
      });

      // Navegación sticky
      once('sticky-nav', 'window', context).forEach(function () {
        var $navbar = $('.navbar');
        if ($navbar.length) {
          $(window).on('scroll', function () {
            var scrollTop = $(window).scrollTop();
            if (scrollTop > 100) {
              $navbar.addClass('navbar-scrolled');
            } else {
              $navbar.removeClass('navbar-scrolled');
            }
          });
        }
      });

      // Indicador de página activa
      once('active-indicator', '.navbar-nav .nav-link', context).forEach(function (el) {
        var $link = $(el);
        var href = $link.attr('href');
        var currentPath = window.location.pathname;

        if (href === currentPath || (href !== '#' && currentPath.includes(href))) {
          $link.addClass('active');
          $link.closest('.dropdown').find('.dropdown-toggle').addClass('active');
        }
      });

      generateDynamicBreadcrumb();

      // Mobile menu toggle
      once('mobile-menu', '.sidebar-menu-toggle', context).forEach(function (el) {
        $(el).on('click', function () {
          $(this).closest('.sidebar-menu').toggleClass('menu-open');
        });
      });

      // Search in navbar
      once('navbar-search', '.navbar-search .form-control', context).forEach(function (el) {
        $(el).on('focus blur', function (e) {
          var $searchContainer = $(this).closest('.navbar-search');
          if (e.type === 'focus') {
            $searchContainer.addClass('search-focused');
          } else {
            $searchContainer.removeClass('search-focused');
          }
        });
      });

      // Keyboard navigation
      once('keyboard-nav', '.navbar-nav', context).forEach(function (el) {
        $(el).on('keydown', '.nav-link', function (e) {
          var $current = $(this);
          var $links = $('.navbar-nav .nav-link');
          var currentIndex = $links.index($current);

          switch (e.keyCode) {
            case 37: 
              e.preventDefault();
              if (currentIndex > 0) {
                $links.eq(currentIndex - 1).focus();
              }
              break;
            case 39: 
              e.preventDefault();
              if (currentIndex < $links.length - 1) {
                $links.eq(currentIndex + 1).focus();
              }
              break;
          }
        });
      });
    }
  };

  function generateDynamicBreadcrumb() {
    var $breadcrumb = $('.breadcrumb');
    if ($breadcrumb.length && $breadcrumb.children().length <= 1) {
      var pathArray = window.location.pathname.split('/').filter(Boolean);
      var $breadcrumbList = $('<ol class="breadcrumb"></ol>');

      $breadcrumbList.append('<li class="breadcrumb-item"><a href="/"><i class="bi bi-house"></i> Inicio</a></li>');

      var currentPath = '';
      pathArray.forEach(function (segment, index) {
        currentPath += '/' + segment;
        var isLast = index === pathArray.length - 1;
        var segmentTitle = segment.replace(/-/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });

        if (isLast) {
          $breadcrumbList.append('<li class="breadcrumb-item active">' + segmentTitle + '</li>');
        } else {
          $breadcrumbList.append('<li class="breadcrumb-item"><a href="' + currentPath + '">' + segmentTitle + '</a></li>');
        }
      });

      $breadcrumb.replaceWith($breadcrumbList);
    }
  }

  Drupal.behaviors.navigationTabs = {
    attach: function (context, settings) {
      once('tab-navigation', '.nav-tabs .nav-link', context).forEach(function (el) {
        $(el).on('click', function (e) {
          e.preventDefault();
          var $tab = $(this);
          var targetId = $tab.attr('href');
          var $tabContent = $(targetId);

          if ($tabContent.length) {
            $tab.closest('.nav-tabs').find('.nav-link').removeClass('active');
            $tab.addClass('active');
            $tabContent.siblings('.tab-pane').removeClass('active show');
            $tabContent.addClass('active show');
            $(document).trigger('tabChanged', [$tab, $tabContent]);
          }
        });
      });

      once('tab-keyboard', '.nav-tabs', context).forEach(function (el) {
        $(el).on('keydown', '.nav-link', function (e) {
          var $current = $(this);
          var $tabs = $current.closest('.nav-tabs').find('.nav-link');
          var currentIndex = $tabs.index($current);

          switch (e.keyCode) {
            case 37:
              e.preventDefault();
              if (currentIndex > 0) {
                $tabs.eq(currentIndex - 1).click().focus();
              }
              break;
            case 39:
              e.preventDefault();
              if (currentIndex < $tabs.length - 1) {
                $tabs.eq(currentIndex + 1).click().focus();
              }
              break;
          }
        });
      });
    }
  };

  Drupal.behaviors.backToTop = {
    attach: function (context, settings) {
      once('back-to-top', 'html', context).forEach(function () {
        if (!$('#back-to-top').length) {
          var $backToTop = $('<button id="back-to-top" class="btn btn-primary btn-floating" title="Volver arriba" aria-label="Volver arriba"><i class="bi bi-arrow-up"></i></button>');
          $('body').append($backToTop);

          $backToTop.css({
            position: 'fixed',
            bottom: '2rem',
            right: '2rem',
            width: '3rem',
            height: '3rem',
            borderRadius: '50%',
            zIndex: 1000,
            display: 'none'
          });
        }

        var $btn = $('#back-to-top');

        $(window).on('scroll', function () {
          if ($(window).scrollTop() > 300) {
            $btn.fadeIn();
          } else {
            $btn.fadeOut();
          }
        });

        $btn.on('click', function () {
          $('html, body').animate({ scrollTop: 0 }, 600);
        });
      });
    }
  };

  Drupal.behaviors.ajaxPagination = {
    attach: function (context, settings) {
      once('ajax-pagination', '.pager .pager__link', context).forEach(function (el) {
        $(el).on('click', function (e) {
          e.preventDefault();
          var $link = $(this);
          var url = $link.attr('href');
          var $container = $link.closest('[data-ajax-container]');

          if ($container.length) {
            $container.addClass('loading');
            $.get(url)
              .done(function (data) {
                var $newContent = $(data).find('[data-ajax-container]').html();
                $container.html($newContent);
                $(document).trigger('contentUpdated', [$container]);
              })
              .fail(function () {
                if (Drupal.theme.showNotification) {
                  Drupal.theme.showNotification('Error al cargar el contenido', 'danger');
                }
              })
              .always(function () {
                $container.removeClass('loading');
              });
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);