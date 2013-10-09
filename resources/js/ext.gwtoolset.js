/*global jQuery, mediaWiki */
/*jslint browser: true, plusplus: true, regexp: true, white: true */
(function ( mw, $ ) {
	'use strict';

	var gwtoolset,
		digitTest = /^\d+$/,
		keyBreaker = /([^\[\]]+)|(\[\])/g,
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
					pair = [ pair[0], pair.slice( 1 ).join( '=' ) ];
				}

				key = decodeURIComponent( pair[0].replace( plus, ' ' ) );
				value = decodeURIComponent( pair[1].replace( plus, ' ' ) );
				parts = key.match( keyBreaker );

				for ( j = 0; j < parts.length - 1; j++ ) {
					part = parts[ j ];

					if ( !current[ part ] ) {
						// if what we are pointing to looks like an array
						current[ part ] = digitTest.test( parts[ j + 1 ] ) || parts[ j + 1 ] === '[]' ? [] : {};
					}

					current = current[ part ];
				}

				lastPart = parts[ parts.length - 1 ];

				if ( lastPart === '[]' ) {
					current.push( value );
				} else {
					current[lastPart] = value;
				}
			}

			return data;
		}
	} );

	gwtoolset = {
		$ajaxLoader: $( '<div>' )
			.attr( 'id', 'gwtoolset-loader' )
			.html(
				$( '<p>' )
					.text( mw.message( 'gwtoolset-loading' ).text() )
					.append( $.createSpinner( { size: 'large', type: 'block' } ) )
			),
		$backText: $( '#back-text' ),
		$buttons: {
			$add: $( '<img>' )
				.attr( 'src',
					mw.config.get('wgExtensionAssetsPath') + '/GWToolset/resources/images/b_snewtbl.png'
				)
				.addClass( 'gwtoolset-metadata-button' ),
			$subtract: $( '<img>' )
				.attr( 'src',
					mw.config.get('wgExtensionAssetsPath') + '/GWToolset/resources/images/b_drop.png'
				)
				.addClass( 'gwtoolset-metadata-button' )
		},
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
		$form: $( '#gwtoolset-form' ),
		formName: $('input[name=gwtoolset-form]').val(),
		$globalCategoriesTableTbody: $( '#global-categories-table > tbody' ),
		$itemSpecificCategoriesTableTbody: $( '#item-specific-categories-table > tbody' ),
		$saveMappingButton: $( '<tr>' )
			.html(
				$( '<td>' )
					.attr( 'colspan', 3 )
					.css( 'text-align', 'right' )
					.html(
						$( '<span>' )
							.attr( {
								id: 'save-mapping',
								title: mw.message( 'gwtoolset-save-mapping' ).text()
							} )
							.text( mw.message( 'gwtoolset-save-mapping' ).text() )
					)
			),
		$step2Link: $( '#step2-link' ),
		$templateTableTbody: $( '#template-table > tbody' ),

		addAjaxLoader: function () {
			this.$ajaxLoader.hide();
			this.$form.prepend( this.$ajaxLoader );
		},

		addBackLinks: function () {
			this.$backText
				.replaceWith(
					this.createBackLink( { title: mw.message( 'gwtoolset-back-text-link' ).text() } )
				);
			this.$step2Link
				.replaceWith(
					this.createBackLink( { title: mw.message( 'gwtoolset-step-2-heading' ).text() } )
				);
		},

		addButtons: function () {
			$( '.button-add' )
				.html( this.$buttons.$add.clone().on( 'click', this.handleButtonAddClick ) );
			$( '.button-subtract' )
				.html( this.$buttons.$subtract.clone().on( 'click', this.handleButtonSubtractClick ) );
		},

		addFormListener: function () {
			if ( this.$form.length < 1 ) {
				return;
			}

			this.addAjaxLoader();
			this.$form.on( 'submit', this.handleFormSubmit );
		},

		addSaveMappingButton: function () {
			if ( this.$templateTableTbody.length === 1 ) {
				this.$templateTableTbody.append( this.$saveMappingButton );
				this.$saveMappingButton.on( 'click', this.handleSaveMappingClick );
			}
		},

		closeDialog: function () {
			this.$dialog.dialog( {
				buttons: null,
				dialogClass: null,
				title: null
			} );
		},

		/**
		 * creates an <a> link that will take the user back one page in the browser history
		 * the method uses options.title for the link title and text, yet also allows
		 * you to specify unique values if desired
		 *
		 * @param {Object} options
		 */
		createBackLink: function ( options ) {
			return $( '<a>' )
				.attr( 'href', '#' )
				.attr( 'title', options.title || 'back link' )
				.text( options.text || options.title || 'back link' )
				.on( 'click', function ( evt ) {
					evt.preventDefault();
					evt.stopPropagation();
					history.back();
				} );
		},

		/**
		 * @param {Object} options
		 *   {Boolean} options.deparam
		 *   call $.String.deparam on the options.value
		 *
		 *   {String} options.name
		 *   name of the cookie
		 *
		 *   {Object} options.options
		 *   cookie options
		 *
		 *   {Boolean} options.toJSON
		 *   convert the value to a JSON
		 *
		 *   {String} options.value
		 */
		createCookie: function ( options ) {
			if ( options.deparam === true ) {
				options.value = $.String.deparam( options.value );
			}

			if ( options.toJSON === true ) {
				options.value = $.toJSON( options.value );
			}

			$.cookie(
				options.name,
				options.value,
				options.options
			);
		},

		handleAjaxError: function () {
			gwtoolset.openDialog( { msg: mw.message( 'gwtoolset-developer-issue' ).text() } );
			mw.log( arguments );
		},

		/**
		 * @param {Object} data
		 * @param {string} textStatus
		 * @param {Object} jqXHR
		 */
		handleAjaxSuccess: function ( data, textStatus, jqXHR ) {
			if ( data.ok !== true || !textStatus || !jqXHR ) {
				gwtoolset.openDialog( { msg: mw.message( 'gwtoolset-save-mapping-failed' ).text() } );
			} else {
				gwtoolset.openDialog( { msg: mw.message( 'gwtoolset-save-mapping-succeeded' ).text() } );
			}
		},

		/**
		 * @param {Event} evt
		 * @param {Object} data
		 */
		handleButtonAddClick: function ( evt, data ) {
			var $target = $( this ).closest( 'tr' ),
				$tdButton = $( '<td>' )
					.addClass( 'button-subtract' )
					.html( gwtoolset.$buttons.$subtract.clone()
						.on( 'click', gwtoolset.handleButtonSubtractClick )
					),
				$row = $( '<tr>' );

			evt.preventDefault();

			$target.children().each( function () {
				var $tdElm,
				$elm = $( this );

				if ( $elm.find('label').length === 1 ) {
					$row.append( $( '<td>' ) );
				} else if ( $elm.hasClass( 'button-add' ) ) {
					$row.append( $tdButton );
				} else {
					$tdElm = $elm.clone();

					if ( data && data.value ) {
						$tdElm.find( 'input' ).val( data.value );
					} else {
						$tdElm.find( 'input' ).val( '' );
					}

					if ( data && data.option ) {
						$tdElm.find( 'option:contains(' + data.option + ')' ).prop( 'selected', 'selected' );
					} else {
						$tdElm.find( 'option' ).prop( 'selected', false );
					}

					$row.append( $tdElm );
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
		 * remove cookies on step-1 submit, we don’t want previously stored values to be used.
		 * store cookies on step-2 submit
		 */
		handleFormSubmit: function () {
			if ( gwtoolset.formName === 'metadata-detect' ) {
				gwtoolset.removeCookies();
			} else if ( gwtoolset.formName === 'metadata-mapping' ) {
				gwtoolset.storeJsFormFields();
			}

			gwtoolset.$ajaxLoader.fadeIn();
		},

		/**
		 * @param {Event} evt
		 */
		handleSaveMappingClick: function ( evt ) {
			var $form = $( '<form>' )
					.on( 'submit', gwtoolset.saveMapping ),
				$input = $( '<input>' )
					.attr( 'type', 'text' )
					.attr( 'id', 'mapping-name-to-use' )
					.attr( 'value', $( '#metadata-mapping-name' ).val() );

			evt.preventDefault();

			gwtoolset.openDialog( {
				options: {
					title: mw.message( 'gwtoolset-save-mapping-name' ).text(),
					dialogClass: 'no-close',
					buttons : [
						{
							text: mw.message( 'gwtoolset-save' ).text(),
							click: function () {
								$( this ).dialog( 'close' );
								gwtoolset.saveMapping();
							},
							id: 'button-save-mapping'
						},
						{
							text: mw.message( 'gwtoolset-cancel' ).text(),
							click: function () {
								$( this ).dialog( 'close' );
							}
						}
					]
				},
				msg: $form.append( $input )
			} );
		},

		init: function () {
			this.addBackLinks();
			this.addFormListener();
			this.addSaveMappingButton();
			this.addButtons();
			this.restoreJsFormFields();
		},

		removeCookies: function () {
			$.cookie( 'gwtoolset.global.categories', null );
			$.cookie( 'gwtoolset.item.specific.categories', null );
			$.cookie( 'gwtoolset.metadata.mappings', null );
		},

		/**
		 * restores js added input and select fields from JSONs within document.cookie
		 */
		restoreJsFormFields: function () {
			var buttonAdd,
			fields,
			i,
			x;

			fields = $.secureEvalJSON( $.cookie( 'gwtoolset.global.categories' ) );

			if ( fields && fields.category ) {
				buttonAdd = this.$globalCategoriesTableTbody.find( '.button-add img' );
				for ( i = 1; i < fields.category.length; i ++ ) {
					buttonAdd.trigger( 'click', { value: [fields.category[i]] } );
				}
			}

			fields = $.secureEvalJSON( $.cookie( 'gwtoolset.metadata.mappings' ) );

			if ( fields ) {
				for ( i in fields ) {
					if ( fields.hasOwnProperty( i ) ) {
						buttonAdd = $( '#' + i.replace( ' ', '_' ) ).closest( 'tr' ).find('.button-add img');

						for ( x = 1; x < fields[i].length ; x++ ) {
							buttonAdd.trigger( 'click', { option: fields[i][x] } );
						}
					}
				}
			}

			fields = $.secureEvalJSON( $.cookie( 'gwtoolset.item.specific.categories' ) );

			if ( fields && fields['category-metadata'] ) {
				buttonAdd = this.$itemSpecificCategoriesTableTbody.find( '.button-add img' );

				for ( i = 1; i < fields['category-metadata'].length; i ++ ) {
					buttonAdd.trigger(
						'click',
						{
							option: [fields['category-metadata'][i]],
							value: [fields['category-phrase'][i]]
						}
					);
				}
			}
		},

		/**
		 * stores the js added input and select fields as JSONs document.cookie
		 */
		storeJsFormFields: function () {
			this.createCookie( {
				deparam: true,
				name: 'gwtoolset.global.categories',
				options: { path: '/' },
				toJSON: true,
				value: this.$globalCategoriesTableTbody.find( 'input' ).serialize()
			} );

			this.createCookie( {
				deparam: true,
				name: 'gwtoolset.item.specific.categories',
				options: { path: '/' },
				toJSON: true,
				value: this.$itemSpecificCategoriesTableTbody.find( 'input, select').serialize()
			} );

			this.createCookie( {
				deparam: true,
				name: 'gwtoolset.metadata.mappings',
				options: { path: '/' },
				toJSON: true,
				value: this.$templateTableTbody.find( 'select' ).serialize()
			} );
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
		 *
		 * @param {Event} evt
		 */
		saveMapping: function ( evt ) {
			var mappingNameToUse = $( '#mapping-name-to-use' ).val(),
				mediawikiTemplateName = $( '#mediawiki-template-name' ).val(),
				wpEditToken = mw.user.tokens.get( 'editToken' ),
				metadataMappings = gwtoolset.$form.find( 'select' ).serialize();

			if ( evt ) {
				gwtoolset.$dialog.dialog( 'close' );
				evt.preventDefault();
			}

			metadataMappings = $.String.deparam( metadataMappings );

			if ( mappingNameToUse === null || mappingNameToUse.length < 3 ) {
				return;
			}

			$.ajax( {
				type: 'POST',
				url: mw.util.wikiGetlink( 'Special:GWToolset' ),
				data: {
					'gwtoolset-form': 'metadata-mapping-save',
					'mapping-name-to-use': mappingNameToUse,
					'metadata-mappings': metadataMappings,
					'mediawiki-template-name': mediawikiTemplateName,
					'wpEditToken': wpEditToken
				},
				error: gwtoolset.handleAjaxError,
				success: gwtoolset.handleAjaxSuccess,
				complete: gwtoolset.handleAjaxComplete,
				timeout: 5000
			} );
		}

	};

	gwtoolset.init();

}( mediaWiki, jQuery ));
