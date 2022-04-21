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
		}
	},
	keywords: ['mailrelay'],
	edit: function (props) {
		let blockProps = wp.blockEditor.useBlockProps();
		let form_id = props.attributes.form_id;
		let all_forms = mailrelay_wpforms_forms.forms;
		let all_options = [{label: 'Select a Form', value: 0}];
		
		for(let i=0;i<all_forms.length;i++) {
			all_options.push({label: all_forms[i].name, value: all_forms[i].id});
		}
		
		let display = wp.element.createElement('div', { className: 'wpforms-gutenberg-form-selector-wrap'},
			wp.element.createElement(wp.components.SelectControl,
				{
					value: form_id,
					options: all_options,
					onChange: function( value ) {
						props.setAttributes({ form_id: Number(value) });
					}
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
		
		let selected_form = mailrelay_wpforms_forms.forms.find(function(v) { return v.id === props.attributes.form_id });
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
							onChange: function( value ) {
								props.setAttributes({ form_id: Number(value) });
							}
						} 
					)
				)
			)]);
		}
		
		return display;
	
	},
	save: function( props ) {
		let blockProps = wp.blockEditor.useBlockProps.save();

		let selected_form = mailrelay_wpforms_forms.forms.find(function(v) { return v.id === props.attributes.form_id });
		if (!selected_form) {
			return;
		}

		return wp.element.createElement( 'div', blockProps, wp.element.RawHTML( { children: selected_form.embedded_form_code } ) );
	}
})