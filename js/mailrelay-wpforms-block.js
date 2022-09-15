wp.blocks.registerBlockType('mailrelay/mailrelay-wpforms', { // phpcs:ignore Squiz.Commenting.FileComment.Missing
	apiVersion: 2,
	title: 'Mailrelay forms',
	category: 'widgets',
	icon: 'email',
	description: 'Select and display one of your forms.',
	attributes: {
		form_id: {
			type: 'integer',
			default: 0
		},
		embedded_form_code: {
			type: 'string'
		}
	},
	keywords: ['mailrelay'],
	edit: wp.compose.compose(wp.compose.withState({ data_loaded: false, error: false }))(function (props) {
		if (!props.data_loaded) {
			const data = new FormData();
			data.append( 'action', 'mailrelay_get_signup_forms' );
			data.append( 'nonce', mailrelay_wpforms_forms.nonce );

			fetch(mailrelay_wpforms_forms.ajax_url, {
				method: "POST",
				credentials: 'same-origin',
				body: data
			})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				mailrelay_wpforms_forms.forms = data
				props.setState({ data_loaded: true, error: false });
			})
			.catch(function(err) {
				if(!props.error) {
					props.setState({ error: true });
				}
			});

			let message = (typeof(wp.components.Spinner) === 'function') ? wp.components.Spinner : wp.components.Spinner.render

			if (props.error) {
				message = wp.element.createElement( 'p', null, "Connection error, check the plugin configuration.");
			}
			
			return wp.element.createElement( 'div', wp.blockEditor.useBlockProps({ style: { backgroundColor: 'white', textAlign: 'center' } }), message );
		}

		let blockProps = wp.blockEditor.useBlockProps();
		let all_forms = mailrelay_wpforms_forms.forms;
		let form_id = props.attributes.form_id;
		
		let all_options = [{label: 'Select a Form', value: 0}];

		for(let i=0;i<all_forms.length;i++) {
			all_options.push({label: all_forms[i].name, value: all_forms[i].id});
		}

		let select_form_on_change = function(value) {
			let form_id = parseInt(value)
			let selected_form = mailrelay_wpforms_forms.forms.find(function(v) { return v.id === form_id })
			if (selected_form) {
				let embedded_form_code = selected_form.embedded_form_code

				props.setAttributes({ form_id: form_id, embedded_form_code: embedded_form_code });
			}
		}
		
		let display = wp.element.createElement('div', { className: 'wpforms-gutenberg-form-selector-wrap'},
			wp.element.createElement(wp.components.SelectControl,
				{
					value: form_id,
					options: all_options,
					onChange: select_form_on_change
				}
			)
		);

		if (all_forms.length > 0) {
			let script_src = all_forms[0].embedded_form_code.match(/<script.*?src="(.*?)"[^>]*>/)[1];
			if (script_src && !document.getElementById('mailrelay-form-script')) {
				let script_el = document.createElement('script');
				script_el.type = "text/javascript";
				script_el.id = 'mailrelay-form-script';
				script_el.src = script_src;

				document.head.appendChild(script_el);
			}
		}
		
		let selected_form = all_forms.find(function(v) { return v.id === props.attributes.form_id });
		if(selected_form) {
			display = [wp.element.createElement( 'div', blockProps, wp.element.RawHTML( { children: selected_form.embedded_form_code } ) )];
			display.push([wp.element.createElement(
				wp.blockEditor.InspectorControls,
				null,
				wp.element.createElement(
					wp.components.PanelBody,
					null,
					wp.element.createElement( wp.components.SelectControl, 
						{
							value: form_id,
							label: 'Select a Form',
							options: all_options,
							onChange: select_form_on_change
						} 
					)
				)
			)]);
		}
		
		return display;
	
	}),
	save: function( props ) {
		if (props.attributes.embedded_form_code) {
			let blockProps = wp.blockEditor.useBlockProps.save();

			return wp.element.createElement( 'div', blockProps, wp.element.RawHTML( { children: props.attributes.embedded_form_code } ) );
		}
	}
})