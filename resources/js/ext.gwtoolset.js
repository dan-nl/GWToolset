/*global jQuery, mw */
/*jslint browser: true, continue: true, white: true, devel: true, regexp: true, todo: true */
(function ( $ ) {
	'use strict';

	var gwtoolset,
		digitTest = /^\d+$/,
		keyBreaker = /([^\[\]]+)|(\[\])/g,
		messages = {
			back_link: mw.message( 'gwtoolset-back-link-option' ).escaped(),
			cancel: mw.message( 'gwtoolset-cancel' ).escaped(),
			developer_issue: mw.message( 'gwtoolset-developer-issue' ).escaped(),
			loading: mw.message( 'gwtoolset-loading' ).escaped(),
			save: mw.message( 'gwtoolset-save' ).escaped(),
			save_mapping: mw.message( 'gwtoolset-save-mapping' ).escaped(),
			save_mapping_failed: mw.message( 'gwtoolset-save-mapping-failed' ).escaped(),
			save_mapping_name: mw.message( 'gwtoolset-save-mapping-name' ).escaped(),
			save_mapping_succeeded: mw.message( 'gwtoolset-save-mapping-succeeded' ).escaped(),
			step_2_heading: mw.message( 'gwtoolset-step-2-heading' ).escaped()
		},
		paramTest = /([^?#]*)(#.*)?$/,
		plus = /\+/g;

	/**
	 * @add jQuery.String
	 * @author justinbmeyer
	 * @see https://github.com/jupiterjs/jquerymx/blob/master/lang/string/deparam/deparam.js
	 */
	$.String = $.extend( $.String || {}, {
		/**
		 * @function deparam
		 *
		 * Takes a string of name value pairs and returns a Object literal that represents those params.
		 *
		 * @param {string} params a string like <code>"foo=bar&person[age]=3"</code>
		 * @return {Object} A JavaScript Object that represents the params:
		 *
		 *     {
		 *       foo: "bar",
		 *       person: {
		 *         age: "3"
		 *       }
		 *     }
		 */
		deparam: function ( params ) {
			if ( !params || !paramTest.test( params ) ) {
				return {};
			}

			var current,
				i,
				j,
				key,
				lastPart,
				pair,
				part,
				parts,
				value,
				data = {},
				pairs = params.split( '&' );

			for ( i = 0; i < pairs.length; i++ ) {
				current = data;
				pair = pairs[i].split( '=' );

				// if we find foo=1+1=2
				if ( pair.length !== 2 ) {
					pair = [ pair[0], pair.slice( 1 ).join( "=" ) ];
				}

				key = decodeURIComponent( pair[0].replace( plus, " " ) );
				value = decodeURIComponent( pair[1].replace( plus, " " ) );
				parts = key.match( keyBreaker );

				for ( j = 0; j < parts.length - 1; j++ ) {
					part = parts[ j ];

					if ( !current[ part ] ) {
						// if what we are pointing to looks like an array
						current[ part ] = digitTest.test( parts[ j + 1 ] ) || parts[ j + 1 ] === "[]" ? [] : {};
					}

					current = current[ part ];
				}

				lastPart = parts[ parts.length - 1 ];

				if ( lastPart === "[]" ) {
					current.push( value );
				} else {
					current[lastPart] = value;
				}
			}

			return data;
		}
	} );

	gwtoolset = {

		display_debug_output: true,
		$dialog: $( '<div>' )
			.attr( 'id', 'dialog' )
			.dialog( {
				autoOpen: false,
				modal: true,
				resizable: false,
				close: function () {
					gwtoolset.closeDialog();
				}
			} ),
		empty_console: {
			log: function () {
			}
		},
		$form: $( '#gwtoolset-form' ),
		$ajax_loader: $( '<div>' )
			.attr( 'id', 'gwtoolset-loader' )
			.html(
				$( '<p>' )
					.append( $.createSpinner( { size: 'large', type: 'block' } ) )
					.append( messages.loading )
			),
		$template_table_tbody: $( '#template-table > tbody' ),
		$save_mapping_button: $( '<tr>' )
			.html(
				$( '<td>' )
					.attr( 'colspan', 3 )
					.css( 'text-align', 'right' )
					.html(
						$( '<span>' )
							.attr( {
								id: 'save-mapping',
								title: messages.save_mapping
							} )
							.text( messages.save_mapping )
					)
			),
		$buttons: {
			$add: $( '<img>' )
				.attr( 'src', mw.config.get('wgExtensionAssetsPath') + '/GWToolset/resources/images/b_snewtbl.png' )
				.addClass( 'gwtoolset-metadata-button' ),
			$subtract: $( '<img>' )
				.attr( 'src', mw.config.get('wgExtensionAssetsPath') + '/GWToolset/resources/images/b_drop.png' )
				.addClass( 'gwtoolset-metadata-button' )
		},
		$back_text: $( '#back-text' ),
		$step2_link: $( '#step2-link' ),

		addAjaxLoader: function () {
			this.$ajax_loader.hide();
			this.$form.prepend( this.$ajax_loader );
		},

		addBackLinks: function () {
			this.$back_text.replaceWith( this.createBackLink( { title: messages.back_link } ) );
			this.$step2_link.replaceWith( this.createBackLink( { title: messages.step_2_heading } ) );
		},

		addButtons: function () {
			$( '.button-add' ).html( this.$buttons.$add.clone().on( 'click', this.handleButtonAddClick ) );
			$( '.button-subtract' ).html( this.$buttons.$subtract.clone().on( 'click', this.handleButtonSubtractClick ) );
		},

		addFormListener: function () {
			if ( this.$form.length < 1 ) {
				return;
			}

			this.addAjaxLoader();
			this.$form.on( 'submit', this.handleFormSubmit );
		},

		addSaveMappingButton: function () {
			if ( this.$template_table_tbody.length === 1 ) {
				this.$template_table_tbody.append( this.$save_mapping_button );
				this.$save_mapping_button.on( 'click', this.handleSaveMappingClick );
			}
		},

		closeDialog: function () {
			gwtoolset.$dialog.dialog( {
				buttons: null,
				dialogClass: null,
				title: null
			} );

			$( document ).off( 'keyup', gwtoolset.handleDialogKeyUp );
		},

		/**
		 * creates an <a> link that will take the user back one page in the browser history
		 * the method uses options.title for the link title and text, yet also allows
		 * you to specify unique values if desired
		 *
		 * @param {Object} options
		 *
		 */
		createBackLink: function ( options ) {
			return $( '<a>' )
				.attr( 'href', '#' )
				.attr( 'title', options.title || '' )
				.text( options.text || options.title || '' )
				.on( 'click', function ( evt ) {
					evt.preventDefault();
					history.back();
				} );
		},

		handleAjaxError: function () {
			gwtoolset.openDialog( { msg: messages.developer_issue } );
			console.log( arguments );
		},

		/**
		 * @param {Object} data
		 * @param {string} textStatus
		 * @param {Object} jqXHR
		 */
		handleAjaxSuccess: function ( data, textStatus, jqXHR ) {
			if ( data.ok !== true || !textStatus || !jqXHR ) {
				gwtoolset.openDialog( { msg: messages.save_mapping_failed } );
			} else {
				gwtoolset.openDialog( { msg: messages.save_mapping_succeeded } );
			}
		},

		/**
		 * @param {Event} evt
		 */
		handleButtonAddClick: function ( evt ) {
			var $target = $( this ).closest( 'tr' ),
				$td_button = $( '<td>' )
					.addClass( 'button-subtract' )
					.html( gwtoolset.$buttons.$subtract.clone().on( 'click', gwtoolset.handleButtonSubtractClick ) ),
				$row = $( '<tr>' );

			evt.preventDefault();

			$target.children().each( function () {
				var $td_elm,
				$elm = $( this );

				if ( $elm.find('label').length === 1 ) {
					$row.append( $( '<td>' ) );
				} else if ( $elm.hasClass( 'button-add' ) ) {
					$row.append( $td_button );
				} else {
					$td_elm = $elm.clone();
					$td_elm.find( 'input' ).val( '' );
					$td_elm.find( 'option' ).prop( 'selected', false );
					$row.append( $td_elm );
				}
			});

			$row.insertAfter( $target );
		},

		/**
		 * @param {Event} evt
		 */
		handleButtonSubtractClick: function ( evt ) {
			evt.preventDefault();
			$( this ).closest( 'tr' ).remove();
		},

		/**
		 * @param {Event} evt
		 */
		handleDialogKeyUp: function ( evt ) {
			var buttons = evt.data.buttons;

			if ( evt.keyCode === 13 ) {
				buttons[0].click.apply( dialog );
			}
		},

		handleFormSubmit: function () {
			gwtoolset.$ajax_loader.fadeIn();
		},

		/**
		 * @param {Event} evt
		 */
		handleSaveMappingClick: function ( evt ) {
			var buttons,
				$input = $( '<input>' )
					.attr( {
						id: 'mapping-name-to-use',
						value: $( '#metadata-mapping-name' ).val()
					} );

			evt.preventDefault();

			gwtoolset.openDialog( {
				options: {
					title: messages.save_mapping_name,
					dialogClass: 'no-close',
					buttons : [
						{
							text: messages.save,
							click: function () {
								$( this ).dialog( 'close' );
								gwtoolset.saveMapping();
							}
						},
						{
						 text: messages.cancel,
						 click: function () {
								$( this ).dialog( 'close' );
							}
						}
					]
				},
				msg: $input
			} );

			buttons = gwtoolset.$dialog.dialog( 'option', 'buttons' );
			$( document ).on( 'keyup', { buttons: buttons }, gwtoolset.handleDialogKeyUp );
		},

		init: function () {
			gwtoolset.setConsole();
			gwtoolset.addBackLinks();
			gwtoolset.addFormListener();
			gwtoolset.addSaveMappingButton();
			gwtoolset.addButtons();
		},

		/**
		 * @param {Object} options
		 */
		openDialog: function ( options ) {
			gwtoolset.$dialog.html( options.msg );

			if ( options.options !== undefined ) {
				gwtoolset.$dialog.dialog( options.options );
			}

			gwtoolset.$dialog.dialog( 'open' );
		},

		/**
		 * sends the appropriate data to the server for the mapping to be created/updated
		 */
		saveMapping: function () {
			var mapping_name_to_use = $( '#mapping-name-to-use' ).val(),
				mediawiki_template_name = $( '#mediawiki-template-name' ).val(),
				wpEditToken = mw.user.tokens.get( 'editToken' ),
				metadata_mappings = gwtoolset.$form.find( 'select' ).serialize();

			metadata_mappings = $.String.deparam( metadata_mappings );

			if ( mapping_name_to_use === null || mapping_name_to_use.length < 3 ) {
				return;
			}

			$.ajax( {
				type: 'POST',
				url: mw.util.wikiGetlink( 'Special:GWToolset' ),
				data: {
					'gwtoolset-form': 'metadata-mapping-save',
					'mapping-name-to-use': mapping_name_to_use,
					'metadata-mappings': metadata_mappings,
					'mediawiki-template-name': mediawiki_template_name,
					'wpEditToken': wpEditToken
				},
				error: gwtoolset.handleAjaxError,
				success: gwtoolset.handleAjaxSuccess,
				complete: gwtoolset.handleAjaxComplete,
				timeout: 5000
			} );
		},

		setConsole: function () {
			if ( window.console === undefined || !this.display_debug_output ) {
				window.console = this.empty_console;
			}
		}

	};

	gwtoolset.init();

}( jQuery ));
