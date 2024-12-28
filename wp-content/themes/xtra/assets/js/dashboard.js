jQuery( function( $ ) {

	var args 		= {},
		allPv 		= 0,
		nonce 		= $( '.xtra-wizard' ).attr( 'data-nonce' ),
		progress 	= $( '.xtra-wizard-progress div' ),
		modalBox 	= $( '.xtra-modal' ),
		rtlOption 	= $( '.xtra-rtl, .codevz-rtl' ),
		is_pro 		= $( '.xtra-pro' ).length,
		importerAJAX = null,
		timeout 	= 0,
		importerDone = function( hasError ) {

			if ( ! hasError ) {

				progress.css( 'width', '99%' ).find( 'span' ).html( '99%' );

				$( '.xtra-importing-parts' ).addClass( 'xtra-imported-parts' ).find( 'i' ).addClass( 'dashicons-yes' );

			}

			setTimeout( function() {

				$( 'body' ).removeClass( 'xtra-importing' );
				$( '.xtra-demo-image' ).css( 'opacity', '1' );
				$( '.xtra-wizard-next' ).trigger( 'click' );

				if ( ! hasError ) {
					$( '.xtra-demo-error' ).hide();
					$( '.xtra-demo-success' ).show();
				}

				$( '.xtra-wizard-progress, .xtra-back, .xtra-importer-spinner, .xtra-wizard-footer' ).hide();

			}, 1500 );

		},
		importerError = function( message ) {

			importerDone( true );

			$( '.xtra-demo-success' ).hide();
			$( '.xtra-demo-error' ).show().find( 'p' ).html( message );

		},
		inViewport = function( e, offset ) {

			var offset 			= offset || 0,
				docViewTop 		= $( window ).scrollTop(),
				docViewBottom 	= docViewTop + $( window ).height(),
				elemTop 		= e.offset().top,
				elemBottom 		= elemTop + e.height();

			return ( ( elemTop <= docViewBottom + offset ) && ( elemBottom >= docViewTop - offset ) );

		},
		progressBar = function( li, allPv, images ) {

			var current = parseFloat( progress.attr( 'data-current' ) );

			if ( images ) {
				var value = ( current + ( ( 100 - allPv ) / images ) );
			} else {
				var value = ( current + ( parseFloat( li.attr( 'data-pv' ) ) * ( 100 / allPv ) ) );
			}

			if ( value > 99 ) {
				value = 99;
			}

			progress.css( 'width', Math.round( value ) + '%' ).attr( 'data-current', value ).find( 'span' ).html( Math.round( value ) + '%' );

		};

		function attachment_importer( xml, li, startCurrent ) {

			var number = 0,
				attachments = {},
				failedAttachments = 0,
				failedAttachments2 = 0,
				importedNumber = 0,
				imageName = $( 'li[data-name="images"] b' );

			// Fix last whitespace.
			xml = xml.replace( /<\/rss>\s+$/, '</rss>' );

			// Read XML items.
			try {
				var parser = new DOMParser();
				var xmlDoc = parser.parseFromString(xml, "text/xml");
				var items = xmlDoc.getElementsByTagName('item');

				Array.from(items).forEach(function(item) {
					var post_type_elem = item.querySelector('wp\\:post_type, post_type');
					var post_type = post_type_elem ? post_type_elem.textContent : '';

					if (post_type === 'attachment') {
						attachments[number++] = {
							url: (item.querySelector('wp\\:attachment_url, attachment_url') || {}).textContent || '',
							post_title: (item.querySelector('title') || {}).textContent || '',
							link: (item.querySelector('link') || {}).textContent || '',
							pubDate: (item.querySelector('pubDate') || {}).textContent || '',
							guid: (item.querySelector('guid') || {}).textContent || '',
							import_id: (item.querySelector('wp\\:post_id, post_id') || {}).textContent || '',
							post_date: (item.querySelector('wp\\:post_date, post_date') || {}).textContent || '',
							post_date_gmt: (item.querySelector('wp\\:post_date_gmt, post_date_gmt') || {}).textContent || '',
							post_name: (item.querySelector('wp\\:post_name, post_name') || {}).textContent || '',
							post_status: (item.querySelector('wp\\:status, status') || {}).textContent || '',
							post_parent: (item.querySelector('wp\\:post_parent, post_parent') || {}).textContent || '',
							post_type: post_type,
						};
					}
				});
			} catch (e) {
				console.error("Error parsing XML:", e);
				console.log("XML Content:", xml);
				console.log("Parsed Document:", xmlDoc);
			}

			var max = Object.keys( attachments ).length;

			function import_attachments( i ) {

				imageName.html( '(' + ( i + 1 ) + ' ' + codevzWizard.of + ' ' + max + ')' );

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'attachment_importer_upload',
						nonce: nonce,
						attachment: attachments[i]
					}
				}).done( function( data, status, xhr ) {

					var obj = JSON.parse( data );

					//console.log( obj );

					// If error shows the server did not respond, try again.
					if( obj.message == "Remote server did not respond" && failedAttachments < 3 ){

						failedAttachments++;

						imageName.html( '(' + ( i + 1 ) + ' ' + codevzWizard.of + ' ' + max + ')' );

						setTimeout( function() {
							import_attachments( i );
						}, 5000 );

					// If a non-fatal error occurs, note it and move on.
					} else if( obj.type == "error" && !obj.fatal ) {

						progressBar( li, startCurrent, max );

						next_image( i );

					// Fatal error.
					} else if( obj.fatal ) {

						importerError( obj.text );

						return false;

					} else {

						progressBar( li, startCurrent, max );

						importedNumber = i + 1;

						next_image( i );

					}

				} ).fail( function( xhr, status, error ) {

					failedAttachments2++;

					if ( failedAttachments2 < 20 ) {

						import_attachments( importedNumber );

					} else if ( xhr.status == 500 ) {

						importerError( codevzWizard.error_500 );

					} else if ( xhr.status == 503 ) {

						importerError( codevzWizard.error_503 );

					} else {

						importerError( error || codevzWizard.ajax_error );

					}

					console.error( xhr, status, error );

				} );
			}

			function next_image( i ) {

				i++;
				failedAttachments = 0;

				var listX = $( '.xtra-list' );

				// Continue next image.
				if ( attachments[i] ) {

					import_attachments( i );

				// Import sldier.
				} else if ( listX.find( 'li[data-name="slider"]' ).length ) {

					var liLast = $( '.xtra-list li' ).length;

					listX.find( 'li:nth-child(' + ( liLast - 1 ) + ')' ).removeClass( 'xtra-current' ).addClass( 'xtra-done' ).prepend( '<span class="checkmark" aria-hidden="true"></span>' );

					importerAJAX( liLast, 'slider', 'import', false );

				} else {

					importerDone();

				}

			}

			if ( attachments[0] ) {

				import_attachments( 0 );

			} else {

				importerError( 'There were no attachment files found in the XML file.' );

			}

		}

	// Lazyload demos.
	$( window ).on( 'scroll.xtra', function() {

		$( '.xtra-lazyload [data-src]' ).each( function() {

			var $this = $( this );

			if ( inViewport( $this, 100 ) ) {

				$this.attr( 'src', $this.attr( 'data-src' ) ).addClass( 'lazyDone' );

				if ( ! $( '.xtra-demos img' ).not( '.lazyDone' ).length ) {

					$( window ).off( 'scroll.xtra' );

				}

			}

		});

	}).trigger( 'scroll.xtra' );

	// Make external link target _blank.
	$( 'a[href*="xtra-docs"],a[href*="xtra-videos"],a[href*="xtra-faq"],a[href*="xtra-support"],a[href*="xtra-changelog"]' ).attr( 'target', '_blank' );

	// Search in demos.
	var searchDemos = function( value ) {

		var timeOut = 0;

		$( '.xtra-demos > div' ).each( function() {

			var $this = $( this );

			if ( $this.text().search( new RegExp( value, 'i' ) ) < 0 ) {
				$this.hide();
			} else {
				$this.show();
			}

			clearTimeout( timeOut );

			timeOut = setTimeout( function() {
				$( window ).trigger( 'scroll.xtra' );
			}, 250 );

		});

	};

	// Search demos.
	$( 'body' ).on( 'keyup', '.xtra-filters [name="search"]', function( e ) {

		searchDemos( $( this ).val() );

		$( '.xtra-filters a' ).removeClass( 'xtra-current' );

		e.preventDefault();

	// Filters.
	}).on( 'click', '.xtra-filters a', function( e ) {

		$( this ).addClass( 'xtra-current' ).siblings().removeClass( 'xtra-current' );

		searchDemos( $( this ).attr( 'data-filter' ) );

		$( '.xtra-filters [name="search"]' ).val( '' );

		e.preventDefault();

	// Demo importer wizard start.
	}).on( 'click', '.xtra-demos a[data-args]', function( e ) {

		args = JSON.parse( $( this ).attr( 'data-args' ) );

		// Scroll to top.
		$( 'html, body' ).animate({ scrollTop: $( '.xtra-dashboard-main' ).offset().top - 100 }, 1000 );

		// Show wizard.
		$( '.xtra-demo-importer' ).slideUp( 'normal', function() {
			$( '.xtra-wizard' ).slideDown( 'normal' );
		});

		// Reset progress.
		progress.css( 'width', '0%' ).find( 'span' ).html( '' );

		// Opacity wizard buttons.
		$( '.xtra-wizard-footer > a' ).css( 'opacity', '1' );

		// Reset to step 1.
		$( '[data-step="1"]' ).addClass( 'xtra-current' ).siblings().removeClass( 'xtra-current' );

		// Show footer.
		$( '.xtra-wizard-footer' ).show();

		// Set image.
		$( '.xtra-demo-image' ).attr( 'src', args.image );

		// Set title.
		$( '.xtra-wizard-selected strong' ).html( args.title ? args.title : args.demo.replace( /-/g, ' ' ) );

		// Set live preview.
		$( '.xtra-live-preview' ).attr( 'href', args.preview );

		if ( args.preview.indexOf( 'arabic' ) >= 0 ) {
			$( '.xtra-live-preview-elementor' ).attr( 'href', args.preview.replace( '/' + args.demo, ( args.only_elementor ? '' : '-elementor/' ) + args.demo ) );
		} else {
			$( '.xtra-live-preview-elementor' ).attr( 'href', args.preview.replace( args.demo, ( args.only_elementor ? '' : 'elementor/' ) + args.demo ) );
		}

		// Hide prev step button.
		$( '.xtra-wizard-footer .xtra-wizard-prev' ).attr( 'disabled', 'disabled' );

		// Check WPBakery.
		$( '[name="pagebuilder"][value="js_composer"]' )[ args.free && $( '.xtra-readonly' ).length ? 'attr' : 'removeAttr' ]( 'disabled', 'disabled' );

		// WPBakery.
		if ( args.js_composer != false ) {

			$( '.xtra-live-preview-wpbakery' ).show();
			$( '[name="pagebuilder"][value="js_composer"]' ).parent().show();

		} else {

			$( '.xtra-live-preview-wpbakery' ).hide();
			$( '[name="pagebuilder"][value="js_composer"]' ).parent().hide();

		}

		// Elementor.
		if ( args.elementor ) {

			// Check Elementor builder.
			$( '[name="pagebuilder"][value="elementor"]' ).trigger( 'click' );

		} else {

			$( '.xtra-live-preview-elementor' ).remove();

			$( '[name="pagebuilder"][value="elementor"]' ).attr( 'disabled', 'disabled' );

			$( '[name="pagebuilder"][value="js_composer"]' ).trigger( 'click' );

		}

		rtlOption.show();

		if ( ! args.rtl || ( args.rtl && ! args.rtl.js_composer && ! args.rtl.elementor ) ) {

			rtlOption.hide();

		}

		// RTL checkbox.
		rtlOption[ args.rtl ? 'removeAttr' : 'attr' ]( 'disabled', 'disabled' ).find( '[name="rtl"]' ).prop( 'checked', false );

		var lang = $( 'html' ).attr( 'lang' );

		if ( args.rtl && $( 'body' ).hasClass( 'rtl' ) && ( lang === 'ar' || lang === 'ary' ) ) {

			rtlOption.find( '[name="rtl"]' ).trigger( 'click' );

		}

		// Check if demo have slider.
		$( '[name="slider"]' ).parent()[ ( args.plugins && args.plugins.revslider == false ) ? 'hide' : 'show' ]();

		e.preventDefault();

	// RTL demo preview.
	}).on( 'click', '[name="pagebuilder"]', function( e ) {

		args.rtl && rtlOption.removeAttr( 'disabled' );

		if ( $( this ).val() === 'elementor' ) {

			if ( args.rtl && ! args.rtl.elementor ) {

				rtlOption.attr( 'disabled', 'disabled' );

				if ( rtlOption.find( '[name="rtl"]' ).is( ':checked' ) ) {
					rtlOption.find( '[name="rtl"]' ).trigger( 'click' );
				}

			}

		}

	// Tooltip.
	}).on( 'mouseenter', '[data-tooltip]', function( e ) {

		var $this = $( this );

		if ( ! $this.find( '.xtra-tooltip' ).length ) {

			$this.append( '<div class="xtra-tooltip">' + $this.attr( 'data-tooltip' ) + '</div>' );

		}

	// RTL demo preview.
	}).on( 'click', '[name="rtl"]', function( e ) {

		if ( $( this ).closest( '.xtra-readonly' ).length ) {
			return false;
		}

		var checked = $( this ).is( ':checked' );

		if ( ! checked && ( ( ! args.elementor && args.rtl.elementor ) || ! args.elementor ) ) {

			$( '[name="pagebuilder"][value="elementor"]' ).attr( 'disabled', 'disabled' );
			$( '[name="pagebuilder"][value="js_composer"]' ).trigger( 'click' );

		} else if ( ( checked && args.rtl.elementor ) || ( ! checked && ! args.rtl.elementor ) ) {

			$( '[name="pagebuilder"][value="elementor"]' ).removeAttr( 'disabled' );

		} else if ( checked && ! args.rtl.elementor ) {

			$( '[name="pagebuilder"][value="elementor"]' ).attr( 'disabled', 'disabled' );

		}

		setTimeout( function() {
			$( '.xtra-demo-image' ).attr( 'src', ( checked ? args.image.replace( 'rtl/', '' ).replace( args.demo, 'rtl/' + args.demo ) : args.image.replace( 'rtl/', '' ) ).replace( 'rtl/rtl', 'rtl' ) );
		}, 250 );

		$( '.xtra-live-preview' ).attr( 'href', checked ? args.preview.replace( 'arabic/', '' ).replace( args.demo, 'arabic/' + args.demo ) : args.preview.replace( 'arabic/', '' ) );

		$( '.xtra-live-preview-elementor' ).attr( 'href', checked ? args.preview.replace( args.demo, 'arabic-elementor/' + args.demo ) : args.preview.replace( '-elementor/', '/' ) );

	// Import full or custom.
	}).on( 'click', '[name="config"]', function( e ) {

		if ( is_pro ) {
			e.preventDefault();
		}

		var val = $( this ).val(),
			box = $( '.xtra-checkboxes:not(.xtra-custom-options)' );

		if ( val === 'full' ) {

			box.show().attr( 'disabled', 'disabled' ).next().hide();

			// Check all features.
			box.find( 'input[type="checkbox"]' ).prop( 'checked', true ).trigger( 'change' );

		} else if ( val === 'custom' ) {

			box.show().removeAttr( 'disabled' ).next().hide();

		} else if ( val === 'options' ) {

			box.hide().next().css( 'display', 'inline-block' );

			// Check only theme options.
			box.find( 'input[type="checkbox"]' ).prop( 'checked', false ).filter( 'input[name="options"]' ).prop( 'checked', true ).trigger( 'change' );

		}

	// Custom import activate.
	}).on( 'click', '.xtra-checkboxes:not(.xtra-custom-options)', function( e ) {

		if ( is_pro ) {
			e.preventDefault();
		}

		$( '[name="config"][value="custom"]' ).trigger( 'click' );

	// Next and prev steps buttons.
	}).on( 'click', '.xtra-wizard-prev, .xtra-wizard-next', function( e ) {

		if ( $( 'body' ).hasClass( 'xtra-importing' ) ) {
			return false;
		}

		var isNext 	= $( this ).hasClass( 'xtra-wizard-next' ),
			current = parseInt( $( '.xtra-wizard-steps .xtra-current' ).attr( 'data-step' ) ),
			step 	= ( isNext ? current + 1 : current - 1 ),
			isFull 	= $( '[name="config"]:checked' ).val() === 'full',
			parts 	= false;

		if ( step >= 1 && step <= 5 ) {

			$( '.xtra-wizard-footer .xtra-wizard-prev' )[ step !== 1 ? 'removeAttr' : 'attr' ]( 'disabled', 'disabled' );

			// Validate check list.
			if ( isNext && step === 4 && ! isFull ) {

				var list = $( '.xtra-checkboxes input:checkbox:checked' ).map( function() {
						return this.value;
					}).get();

				if ( ! list.length ) {

					alert( codevzWizard.features );

					return false;

				}

			}

			// Set step.
			$( '.xtra-wizard-steps li[data-step="' + step + '"]' ).addClass( 'xtra-current' ).siblings().removeClass( 'xtra-current' );

			// Change content step.
			$( '.xtra-wizard-content [data-step="' + step + '"]' ).addClass( 'xtra-current' ).siblings().removeClass( 'xtra-current' );

			// Start importing.
			if ( step === 4 ) {

				// Disable all links and only wait for import.
				$( 'body' ).addClass( 'xtra-importing' );

				// Hide back to demos.
				$( '.xtra-back' ).hide();

				// Opacity preview image.
				$( '.xtra-demo-image' ).css( 'opacity', '.2' );

				// Opacity wizard buttons.
				$( '.xtra-wizard-footer > a' ).css( 'opacity', '.3' );

				// Show progress bar.
				$( '.xtra-wizard-progress, .xtra-importer-spinner' ).show();

				// Checks.
				var list = $( '.xtra-list' ),
					pagebuilder = $( '[name="pagebuilder"]:checked' ).val(),
					isPluginInactive = function( slug ) {
						return codevzWizard.plugins[ slug ];
					},
					pluginBefore = '<span class="xtra-list-before">' + codevzWizard.plugin_before + '</span>',
					pluginAfter = '<span class="xtra-list-after">' + codevzWizard.plugin_after + '</span>',
					importBefore = '<span class="xtra-list-before">' + codevzWizard.import_before + '</span>',
					importAfter = '<span class="xtra-list-after">' + codevzWizard.import_after + '</span>';

				list.empty();

				// Plugins.
				if ( isPluginInactive( 'codevz-plus' ) ) {
					list.append( '<li data-name="codevz-plus" data-type="plugin" data-pv="5" class="xtra-current">' + pluginBefore + codevzWizard.codevz_plus + pluginAfter + '</li>' );
				}

				if ( pagebuilder === 'js_composer' && isPluginInactive( 'js_composer' ) ) {
					list.append( '<li data-name="js_composer" data-type="plugin" data-pv="7">' + pluginBefore + codevzWizard.js_composer + pluginAfter + '</li>' );
				} else if ( pagebuilder === 'elementor' && isPluginInactive( 'elementor' ) ) {
					list.append( '<li data-name="elementor" data-type="plugin" data-pv="5">' + pluginBefore + codevzWizard.elementor + pluginAfter + '</li>' );
				}

				if ( ( ! args.plugins || ( args.plugins && args.plugins.revslider != false ) ) && isPluginInactive( 'revslider' ) && ( isFull || $( '[name="slider"]' ).is( ':checked' ) ) ) {
					list.append( '<li data-name="revslider" data-type="plugin" data-pv="7">' + pluginBefore + codevzWizard.revslider + pluginAfter + '</li>' );
				}

				if ( isPluginInactive( 'contact-form-7' ) ) {
					list.append( '<li data-name="contact-form-7" data-type="plugin" data-pv="3">' + pluginBefore + codevzWizard.cf7 + pluginAfter + '</li>' );
				}

				if ( isPluginInactive( 'woocommerce' ) && ( isFull || $( '[name="woocommerce"]' ).is( ':checked' ) ) ) {
					list.append( '<li data-name="woocommerce" data-type="plugin" data-pv="4">' + pluginBefore + codevzWizard.woocommerce + pluginAfter + '</li>' );
				}

				// Additional Plugins.
				args.plugins && $.each( args.plugins, function( plugin, value ) {

					value && isPluginInactive( plugin ) && list.append( '<li data-name="' + plugin + '" data-type="plugin" data-pv="5">' + pluginBefore + codevzWizard.plugins[ plugin ] + pluginAfter + '</li>' );

				});

				// Download demo file.
				list.append( '<li data-name="download" data-type="download" data-pv="8"><span class="xtra-list-before">' + codevzWizard.downloading + '</span>' + codevzWizard.demo_files + '<span class="xtra-list-after">' + codevzWizard.downloaded + '</span></li>' );

				// Demo features.
				if ( isFull || $( '[name="options"]' ).is( ':checked' ) ) {

					var ulParts = '',
						parts = '';

					if ( $( '[name="config"]:checked' ).val() === 'options' ) {

						ulParts = '<div class="xtra-importing-parts">';
						$( '.xtra-custom-options input[type="checkbox"]:checked' ).each( function() {
							parts += $( this ).attr( 'name' ) + ' ';
							ulParts += '<div><i class="dashicons dashicons-marker"></i> ' + $( this ).closest( 'label' ).text().trim() + '</div>';
						});
						ulParts += '</div>';

					}

					list.append( '<li data-name="options" data-type="import" data-pv="2">' + importBefore + codevzWizard.options + importAfter + '</li>' + ulParts );

				}
				if ( isFull || $( '[name="widgets"]' ).is( ':checked' ) ) {
					list.append( '<li data-name="widgets" data-type="import" data-pv="1">' + importBefore + codevzWizard.widgets + importAfter + '</li>' );
				}
				if ( isFull || $( '[name="content"]' ).is( ':checked' ) ) {
					list.append( '<li data-name="content" data-type="import" data-pv="15">' + importBefore + codevzWizard.posts + importAfter + '<b></b></li>' );
				}
				if ( isFull || $( '[name="images"]' ).is( ':checked' ) ) {
					list.append( '<li data-name="images" data-type="import" data-pv="80">' + importBefore + codevzWizard.images + importAfter + '<b></b></li>' );
				}
				if ( isFull || ( ! args.plugins || ( args.plugins && args.plugins.revslider != false ) ) && $( '[name="slider"]' ).is( ':checked' ) ) {
					list.append( '<li data-name="slider" data-type="import" data-pv="3">' + importBefore + codevzWizard.slider + importAfter + '</li>' );
				}

				var failedAjax = 0,
					folder = '';

				// Change API to RTL.
				if ( $( '[name="rtl"]' ).is( ':checked' ) && args.rtl && args.rtl[ pagebuilder ] ) {
					folder = 'rtl';
				}

				// Change API to elementor.
				if ( pagebuilder === 'elementor' ) {

					folder = folder ? folder + '-' : '';
					folder = folder + 'elementor';

				}

				list.find( 'li' ).each( function() {
					allPv += parseInt( $( this ).attr( 'data-pv' ) );
				});

				// Wizard AJAX function.
				importerAJAX = function( step, name, type, posts ) {

					var li = list.find( 'li:nth-child(' + step + ')' );

					// Add loading spinner.
					if ( ! li.find( '.xtra-loading' ).length ) {

						li.prepend( '<i class="xtra-loading" aria-hidden="true"></i>' );

					}

					// Start.
					li.addClass( 'xtra-current' ).siblings().removeClass( 'xtra-current' );

					// Send.
					$.ajax(
						{
							type: 'POST',
							url: ajaxurl + '?force_delete_kit',
							data: {
								action: 'codevz_wizard',
								demo: args.demo,
								step: step,
								name: name,
								type: type,
								posts: posts,
								nonce: nonce,
								folder: folder,
								parts: parts
							},
							success: function( obj ) {

								//console.log( obj );

								if ( ! obj ) {

									importerError( '1. ' + codevzWizard.ajax_error );

									return false;

								}

								if ( typeof obj !== 'object' ) {

									// Fix redirects after plugin install.
									if ( obj.indexOf( '<body' ) >= 0 ) {

										importerAJAX( step, 'redirect' );

										return false;

									}

									// Sanitize response and extract object.
									obj = JSON.parse( '{' + obj.substring( obj.lastIndexOf( '{' ) + 1, obj.lastIndexOf( '}' ) ) + '}' );

								}

								// Failed step.
								if ( failedAjax == 3 ) {

									importerError( obj.message || codevzWizard.ajax_error );

									return false;

								// Automatic try again upto 3 times.
								} else if ( ! obj || obj.status === '202' || obj.nonce ) {

									failedAjax++;

									importerAJAX( step, name, type );

									return false;
								}

								// Continue content.
								if ( obj.posts ) {

									importerAJAX( step, name, type, obj.posts );

									// Progress bar.
									var current = parseInt( progress.text() ) + ( Math.floor( Math.random() * 2 ) + 1 );

									current = current >= 95 ? 65 : current;

									progress.css( 'width', current + '%' ).attr( 'data-current', current ).find( 'span' ).html( current + '%' );

									return false;

								// Import images.
								} else if ( obj.xml ) {

									attachment_importer( obj.xml, li, parseInt( progress.text() ) + 4 );

									return false;

								}

								// Progress bar.
								progressBar( li, allPv );

								// Add checkmark.
								li.removeClass( 'xtra-current' ).addClass( 'xtra-done' ).prepend( '<span class="checkmark" aria-hidden="true"></span>' );

								// Next item.
								if ( step < list.find( 'li' ).length ) {

									var next = li.next().addClass( 'xtra-current' );

									importerAJAX( ++step, next.attr( 'data-name' ), next.attr( 'data-type' ) );

								} else {

									importerDone();

								}

							},
							error: function( xhr, type, message ) {

								if ( xhr.status == 500 ) {

									importerError( codevzWizard.error_500 );

								} else if ( xhr.status == 503 ) {

									importerError( codevzWizard.error_503 );

								} else {
									
									importerError( message || codevzWizard.ajax_error );

								}

								console.log( xhr, type, message );

							}
						}
					);

				};

				var li = list.find( 'li:nth-child(1)' );

				importerAJAX( 1, li.attr( 'data-name' ), li.attr( 'data-type' ) );

			}

		}

		e.preventDefault();

	// Back to demos.
	}).on( 'click', '.xtra-back, .xtra-back-to-demos', function( e ) {

		if ( $( 'body' ).hasClass( 'xtra-importing' ) ) {
			return false;
		}

		// Hide wizard.
		$( '.xtra-wizard' ).slideUp( 'normal', function() {
			$( '.xtra-demo-importer' ).slideDown( 'normal' );
		});

		e.preventDefault();

	// Uninstall demo.
	}).on( 'click', '.xtra-uninstall-button', function( e ) {

		if ( $( '.xtra-uninstall' ).length ) {

			$( '.xtra-uninstalled' ).hide();
			$( '.xtra-uninstall-msg, .xtra-button-secondary' ).show();

			$( '.xtra-button-primary' ).html( $( '.xtra-button-primary' ).attr( 'data-uninstall' ) );

			modalBox.attr( 'data-demo', $( this ).attr( 'data-demo' ) ).fadeIn();

			e.preventDefault();

		}

	// Uninstalled reload button.
	}).on( 'click', '.xtra-reload', function( e ) {

		window.location.reload( true );

		e.preventDefault();

	// Uninstall demo after confirm.
	}).on( 'click', '.xtra-modal .xtra-button-primary', function( e ) {

		var $this = $( this ),
			title = $this.html(),
			demo = modalBox.attr( 'data-demo' );

		$this.html( $this.attr( 'data-title' ) );

		modalBox.addClass( 'xtra-current' ).find( '.xtra-uninstall-msg .xtra-button-secondary' ).hide();

		$.ajax(
			{
				type: 'POST',
				url: ajaxurl + '?force_delete_kit',
				data: {
					action: 'codevz_wizard',
					nonce: modalBox.attr( 'data-nonce' ),
					demo: demo,
					name: 'uninstall',
					type: 'uninstall'
				},
				success: function( obj ) {

					var msg = obj.message;

					modalBox.find( '.xtra-uninstalled h2' ).html( msg.replace( /-/g, ' ' ) );

					modalBox.removeClass( 'xtra-current' );

					$( '.xtra-uninstalled' ).show();
					$( '.xtra-uninstall-msg' ).hide();

					$( '.xtra-demo .xtra-button-primary[data-demo="' + demo + '"]' ).closest( '.xtra-demo' ).remove();

					console.log( obj );

				},
				error: function( xhr, type, message ) {

					if ( xhr.status == 500 ) {

						modalBox.find( 'p' ).html( codevzWizard.error_500 );

					} else if ( xhr.status == 503 ) {

						modalBox.find( 'p' ).html( codevzWizard.error_503 );

					} else {

						modalBox.find( 'p' ).html( message || codevzWizard.ajax_error );

					}

					console.log( xhr, type, message );

					$this.html( title );

					modalBox.removeClass( 'xtra-current' ).find( '.xtra-uninstall-msg .xtra-button-secondary' ).show();

				}
			}
		);

		e.preventDefault();

	// Modal close
	}).on( 'click', '.xtra-modal .xtra-button-secondary:not(.xtra-reload)', function( e ) {

		modalBox.fadeOut();

		e.preventDefault();

	// Plugins installation error close icon.
	}).on( 'click', '.xtra-error-close', function( e ) {

		$( this ).parent().remove();

		e.preventDefault();

	// Plugins installation.
	}).on( 'click', '.xtra-plugin-footer a', function( e ) {

		var $this = $( this ),
			title = $this.html(),
			pluginError = function( message ) {

				$this.closest( '.xtra-plugin' ).append( '<div class="xtra-dashboard-error"><i class="dashicons dashicons-no-alt" aria-hidden="true"></i><span>' + message + '</span><a href="#" class="xtra-button-secondary xtra-error-close">' + codevzWizard.close + '</a></div>' );

			};

		if ( $this.attr( 'data-plugin' ) ) {

			$this.addClass( 'xtra-button-secondary xtra-current' ).attr( 'disabled', 'disabled' );
			$this.find( 'span' ).html( $this.attr( 'data-title' ) );

			$this.closest( '.xtra-plugin' ).removeClass( 'xtra-plugin-done' ).addClass( 'xtra-plugin-doing' );

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'codevz_wizard',
						demo: null,
						type: 'plugin',
						name: $this.attr( 'data-plugin' ),
						nonce: $this.closest( '.xtra-plugins' ).attr( 'data-nonce' )
					},
					success: function( obj ) {

						// Sanitize response.
						if ( typeof obj !== 'object' ) {
							obj = JSON.parse( '{' + obj.substring( obj.lastIndexOf( '{' ) + 1, obj.lastIndexOf( '}' ) ) + '}' );
						}

						// Check errors.
						if ( obj.status == 202 ) {

							pluginError( obj.message );

							$this.html( title ).removeClass( 'xtra-button-secondary xtra-current' ).removeAttr( 'disabled' );

						} else {

							// Plugin installed successfully.
							$this.html( title ).addClass( 'hidden' ).next().removeClass( 'hidden' );

						}

						$this.closest( '.xtra-plugin' ).addClass( 'xtra-plugin-done' );

					},
					error: function( xhr, type, message ) {

						if ( xhr.status == 500 ) {

							pluginError( codevzWizard.error_500 );

						} else {

							pluginError( message || codevzWizard.ajax_error );

						}

						console.log( xhr, type, message );

						$this.closest( '.xtra-plugin' ).removeClass( 'xtra-plugin-done xtra-plugin-doing' );

						$this.html( title ).removeClass( 'xtra-button-secondary xtra-current' ).removeAttr( 'disabled' );

					}
				}
			);

			e.preventDefault();

		}

	// Feedback form submission.
	}).on( 'click', '.xtra-feedback-form a', function( e ) {

		var $this 	= $( this ),
			message = tinymce.activeEditor.getContent(),
			messageSpan = $( '.xtra-feedback-message' );

		messageSpan.hide();

		if ( ! message ) {

			messageSpan.html( codevzWizard.feedback_empty ).show();

			return false;

		}

		$this.addClass( 'xtra-current' ).attr( 'disabled', 'disabled' );

		$.ajax(
			{
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'codevz_feedback',
					message: message,
					nonce: $this.attr( 'data-nonce' )
				},
				success: function( obj ) {

					messageSpan.html( obj.message ).show();

					$this.removeClass( 'xtra-current' ).removeAttr( 'disabled' );

				},
				error: function( xhr, type, message ) {

					if ( xhr.status == 500 ) {

						messageSpan.html( codevzWizard.error_500 ).show();

					} else {

						messageSpan.html( message || codevzWizard.ajax_error ).show();

					}

					console.log( xhr, type, message );

					$this.removeClass( 'xtra-current' ).removeAttr( 'disabled' );

				}
			}
		);

		e.preventDefault();

	// Single page importer.
	}).on( 'click', '.xtra-page-importer-form .xtra-button-primary', function( e ) {

		var $this 		= $( this ),
			input 		= $( '.xtra-page-importer-form input' ).val(),
			messageSpan = $( '.xtra-page-importer-message' );

		messageSpan.hide();

		if ( ! input ) {

			messageSpan.html( codevzWizard.page_importer_empty ).show();

			return false;

		}

		$this.addClass( 'xtra-current' ).attr( 'disabled', 'disabled' );

		$.ajax(
			{
				type: 'POST',
				url: ajaxurl,
				data: {
					url: input,
					action: 'codevz_page_importer',
					nonce: $this.attr( 'data-nonce' )
				},
				success: function( obj ) {

					if ( obj.link ) {

						obj.message += '<br /><br /><a href="' + obj.link + '" class="xtra-dashboard-icon-box xtra-dashboard-icon-box-info" target="_blank"><i class="dashicons dashicons-admin-links" aria-hidden="true"></i><div>' + obj.link + '</div></a>';

					}

					messageSpan.html( obj.message ).show();

					$this.removeClass( 'xtra-current' ).removeAttr( 'disabled' );

					$( '.xtra-page-importer-form input' ).val( '' );

				},
				error: function( xhr, type, message ) {

					if ( xhr.status == 500 ) {

						messageSpan.html( codevzWizard.error_500 ).show();

					} else {

						messageSpan.html( message || codevzWizard.ajax_error ).show();

					}

					console.log( xhr, type, message );

					$this.removeClass( 'xtra-current' ).removeAttr( 'disabled' );

				}
			}
		);

		e.preventDefault();

	});

});