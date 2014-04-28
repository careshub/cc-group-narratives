/*globals window, document, $, jQuery, _, Backbone */
(function ($, _, Backbone) {
	// "use strict";

	// wp.media.controller.Custom = wp.media.controller.State.extend({

	// 	defaults: {
	// 		id: 'thing-details',
	// 		title: 'Thing Details!',
	// 		toolbar: 'thing-details',
	// 		content: 'thing-details',
	// 		menu: 'thing-details',
	// 		router: false,
	// 		priority: 60
	// 	},

	//     initialize: function(){
	//         // this model contains all the relevant data needed for the application
	//         this.props = new Backbone.Model({ custom_data: '' });
	//         this.props.on( 'change:custom_data', this.refresh, this );
	//     },
	    
	//     // called each time the model changes
	//     refresh: function() {
	//         // update the toolbar
	//     	this.frame.toolbar.get().refresh();
	// 	},
		
	// 	// called when the toolbar button is clicked
	// 	customAction: function(){
	// 	    console.log(this.props.get('custom_data'));
	// 	}
	    
	// });

	wp.media.controller.ThingDetailsController = wp.media.controller.State.extend({
		defaults: {
			id: 'thing-details',
			title: 'Thing Details!',
			toolbar: 'thing-details',
			content: 'thing-details',
			menu: 'thing-details',
			router: false,
			priority: 60
		},

		initialize: function( options ) {
			this.thing = options.thing;
			wp.media.controller.State.prototype.initialize.apply( this, arguments );
		}
	});

}(jQuery, _, Backbone));