wp.blocks.registerBlockType('mailrelay/mailrelay-wpforms', {
	title: 'Mailrelay forms',
	category: 'widgets',
	icon: 'email',
	description: 'Select and display one of your forms.',
	attributes: {
		form_id: {
			type: 'string',
			default: null
		}
	},
	keywords: ['mailrelay'],
	edit: (props) => {
		var form_id = props.attributes.form_id;
		var all_forms = mailrelay_wpforms_forms.forms;
		var all_options = [{label: 'Select a Form', value: 0}];
		for(var i=0;i<all_forms.length;i++) {
			all_options.push({label: all_forms[i].name, value: all_forms[i].id});
		}
		
		return wp.element.createElement('div', { className: 'wpforms-gutenberg-form-selector-wrap'},
			wp.element.createElement(wp.components.SelectControl,
				{
					value: form_id,
					options: all_options,
					onChange: function( value ) {
						props.setAttributes({ form_id: value });
					}
				}
			)
		);
	
	},
	save: () => { return null }
});