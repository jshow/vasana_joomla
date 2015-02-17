<?php
######################################################################
# Vasana Analytics          	          	          	               #
# Copyright (C) 2015 by nNovation Group  	   	   	   	   	   	   	   #
# Homepage   : www.nnovation.ca		   	   	   	   	   	   		         #
# Author     : Jodi J. Showers	    		   	   	   	   	   	   	   	 #
# Email      : Jodi@nnovation.ca 	   	   	   	   	   	   	           #
# Version    : 1.0                      	   	    	   	   		       #
# License    : http://www.gnu.org/copyleft/gpl.html GNU/GPL          #
######################################################################

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin');
jimport( 'joomla.html.parameter');

jimport('joomla.log.log');

class plgSystemSo_impressionable extends JPlugin
{
	function plgSystemSo_impressionable(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->_plugin = JPluginHelper::getPlugin( 'system', 'so_impressionable' );
		$this->_params = new JRegistry( $this->_plugin->params );
	}
	
	function onAfterRender()
	{
    JLog::addLogger(array());
	  
    // JLog::add('***** Logged 0');
	  
		$mainframe = &JFactory::getApplication();
		$so_impressionable_site_secret = $this->params->get('so_impressionable_site_secret', '');
		$so_impressionable_site_url = $this->params->get('so_impressionable_site_url', '');

    // JLog::add('***** Logged 1');

		if($so_impressionable_site_secret == '' || $so_impressionable_site_url == '' || $mainframe->isAdmin())
		{
			return;
		}
// JLog::add('***** Logged 2');

    // alert('secret = ".$so_impressionable_site_secret."');
    // alert('url = ".$so_impressionable_site_url."');

    // JLog::add('***** Logged 3');

    $impressionable_link_id = JFactory::getApplication()->input->getInt('link_id', 0);
    $user = JFactory::getUser();
    
# JLog::add('***** Link ID = '.$impressionable_link_id); 

    $javascript = "
<script type='text/javascript'>

  // need an exception capture below - don't want to blow up the source page when we have troubles with impressing
  function impress(json_data) {
    try {
      jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        beforeSend : function(xhr){
          xhr.setRequestHeader('Accept', 'application/json')
        },      
        url: '".$so_impressionable_site_url."api/impressions',
        data: json_data
      });
    } catch (e) {
      // ignore if we have troubles talking to api server
      // TODO: add logging
    }
  }
  
  window.addEvent('domready', function(){
  if( jQuery('#listing').length ) {  // listing page
    
    organization = jQuery( '#listing .fields .row0 .output' ).first();
    if (organization) {
      organization_name = jQuery(organization).text();
      // console.log(organization_name);
    }
    
    if ($user->guest) {
      json_data = { impression: { impressionable_type: 'Listing', impressionable_id: ".$impressionable_link_id.", topic: 'visit_listing', impressionable_description: organization_name} };
    } else {
      json_data = { impression: { impressionable_type: 'Listing', impressionable_id: ".$impressionable_link_id.", topic: 'visit_listing', user_id: ".$user->id.", impressionable_description: organization_name } };
    }
    
    impress(json_data);

    www_link = jQuery( '#listing .fields .row0 .fieldRow .output a' ).first();
    if (www_link) {
      topic = 'listing_www_visit_click'
      jQuery(www_link).click(function() { // record when the listings website is clicked on
         if ($user->guest) {
           impress( { impression: { impressionable_type: 'Listing', impressionable_id: ".$impressionable_link_id.", topic: topic, impressionable_description: organization_name } } );
         } else {
           impress( { impression: { impressionable_type: 'Listing', impressionable_id: ".$impressionable_link_id.", topic: topic, user_id: ".$user->id.", impressionable_description: organization_name } } );
         }
       });
    }

    jQuery( '#listing .actions-rating-fav .actions a' ).each(function( index ) {
      // console.log(jQuery(this).text());
      if (jQuery(this).text() == 'Visit') {
        topic = 'listing_www_visit_click'
        jQuery(this).click(function() { // record when the listings website is clicked on
           if ($user->guest) {
             impress( { impression: { impressionable_type: 'Listing', impressionable_id: ".$impressionable_link_id.", topic: topic, impressionable_description: organization_name } } );
           } else {
             impress( { impression: { impressionable_type: 'Listing', impressionable_id: ".$impressionable_link_id.", topic: topic, user_id: ".$user->id.", impressionable_description: organization_name } } );
           }
         });
       }
    });    
  }
  
  
  if( jQuery('#category').length ) { // search results page
    
    // sponsored listings
    var json_data = {impressions:[]}
    jQuery( '.listing-summary.featured' ).each(function( index ) {
       link_id = jQuery(this).data('link-id');
       organization = jQuery(this).find('.header h3 a span').first();
       if (organization) {
         organization_name = jQuery(organization).text();
         // console.log('sponsored listing ' + organization_name);
       }
       
       if ($user->guest) {
         json_data.impressions.push( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: 'search_sponsor_impression', impressionable_description: organization_name } } );
       } else {
         json_data.impressions.push( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: 'search_sponsor_impression', user_id: ".$user->id.", impressionable_description: organization_name } } );
       }

       jQuery(this).find('a').each(function(){
         // console.log(jQuery(this).attr('href'));
         
         //topic = 'search_sponsor_click';
         var parent_tag = jQuery(this).parent('.website');
         // console.log(parent_tag.length);         
         if ( parent_tag.length > 0 ) {
           var topic = 'search_www_visit_click';
         } else {
           var topic = 'search_sponsor_click';
         }
         // console.log('topic: ' + topic);
         
         jQuery(this).click(function() { // record when this featured listing banner is clicked
           if ($user->guest) {
             impress( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: topic, impressionable_description: organization_name } } );
           } else {
             impress( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: topic, user_id: ".$user->id.", impressionable_description: organization_name } } );
           }
         });
       });
       
     });
     if (json_data.impressions.length > 0) {
       impress(json_data); // impress the sponsored search results
     }

    // search result listings
    var json_data = {impressions:[]}
    jQuery( '.listing-summary').not('.featured' ).each(function( index ) {
       link_id = jQuery(this).data('link-id');
       organization = jQuery(this).find('.header h3 a span').first();
       if (organization) {
         organization_name = jQuery(organization).text();
         // console.log('results ' + organization_name);
       }
       
       if ($user->guest) {
         json_data.impressions.push( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: 'search_result', impressionable_description: organization_name } } );
       } else {
         json_data.impressions.push( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: 'search_result', user_id: ".$user->id.", impressionable_description: organization_name } } );
       }

       jQuery(this).find('a').each(function(){
         // console.log(jQuery(this).attr('href'));
         
         var parent_tag = jQuery(this).parent('.website');
         // console.log(parent_tag.length);         
         if ( parent_tag.length > 0 ) {
           var topic = 'search_www_visit_click';
         } else {
           var topic = 'search_result_click';
         }
         // console.log('topic: ' + topic);
         
         jQuery(this).click(function() { // record when this featured listing banner is clicked
           if ($user->guest) {
             impress( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: topic, impressionable_description: organization_name } } );
           } else {
             impress( { impression: { impressionable_type: 'Listing', impressionable_id: link_id, topic: topic, user_id: ".$user->id.", impressionable_description: organization_name } } );
           }
         });
       });
       
       
     });
     if (json_data.impressions.length > 0) {
       impress(json_data); // impress the sponsored search results
     }
  }
  
});
</script>";

		$buffer = JResponse::getBody();		
		$buffer = str_replace ("</head>", $javascript."</head>", $buffer);
		JResponse::setBody($buffer);
		
		return true;
	}
}
?>