jQuery(function(){
  jQuery('.nav').setup_navigation();
});

jQuery.fn.setup_navigation = function(settings) {
  settings = jQuery.extend({
    menuHoverClass: 'show-menu',
  }, settings);
  
  // Set tabIndex to -1 so that links can't receive focus until menu is open
  jQuery(this).find('> li > a').next('ul').find('a').attr('tabIndex',-1);
  //jQuery('.NAV').find('> li > a').next('ul').find('a').attr('tabIndex',-1);


  jQuery('ul > li > ul > li > a').hover(function(){
    jQuery(this).closest('ul').find('.'+settings.menuHoverClass).attr('aria-hidden','true').removeClass(settings.menuHoverClass).find('a').attr('tabIndex',-1);jQuery(this).next('ul').attr('aria-hidden','false').addClass(settings.menuHoverClass).find('a').attr('tabIndex',0);
  });
  jQuery('ul > li > ul > li > a').focus(function(){
    jQuery(this).closest('ul').find('.'+settings.menuHoverClass).removeClass(settings.menuHoverClass).find('a').attr('tabIndex',-1);
    jQuery(this).next('ul').addClass(settings.menuHoverClass).find('a').attr('tabIndex',0);
  });

  
  jQuery(this).find('> li > a').hover(function(){
    jQuery(this).closest('ul').find('.'+settings.menuHoverClass).removeClass(settings.menuHoverClass).find('a').attr('tabIndex',-1);
  });
  jQuery(this).find('> li > a').focus(function(){
    jQuery(this).closest('ul').find('.'+settings.menuHoverClass).removeClass(settings.menuHoverClass).find('a').attr('tabIndex',-1);
    jQuery(this).next('ul')
      .addClass(settings.menuHoverClass)
      .find('a').attr('tabIndex',0);
  });
    
  // Hide menu if click or focus occurs outside of navigation
  jQuery(this).find('a').last().keydown(function(e){ 
    if(e.keyCode == 9) {
      // If the user tabs out of the navigation hide all menus
      jQuery('.'+settings.menuHoverClass).removeClass(settings.menuHoverClass).find('a').attr('tabIndex',-1);
    }
  });
  jQuery(document).click(function(){ jQuery('.'+settings.menuHoverClass).removeClass(settings.menuHoverClass).find('a').attr('tabIndex',-1); });
  
  jQuery(this).click(function(e){
    e.stopPropagation();
  });
}