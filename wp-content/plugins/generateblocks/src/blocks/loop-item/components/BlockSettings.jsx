import { __ } from '@wordpress/i18n';
import { BaseControl, Notice } from '@wordpress/components';

import { OpenPanel } from '@edge22/components';

import {
	ApplyFilters,
	URLControls,
	TagNameControl,
} from '@components/index.js';
import { useBlockStyles } from '@hooks/useBlockStyles';

export function BlockSettings( {
	getStyleValue,
	onStyleChange,
	name,
	attributes,
	setAttributes,
	htmlAttributes,
	styles,
	context,
} ) {
	const {
		tagName,
	} = attributes;

	const {
		currentAtRule,
	} = useBlockStyles();

	const panelProps = {
		name,
		attributes,
		setAttributes,
	};

	return (
		<ApplyFilters
			name="generateblocks.editor.blockControls"
			blockName={ name }
			getStyleValue={ getStyleValue }
			onStyleChange={ onStyleChange }
			currentAtRule={ currentAtRule }
			attributes={ attributes }
			setAttributes={ setAttributes }
		>
			<OpenPanel
				{ ...panelProps }
				shouldRender={ 'a' === tagName && '' === currentAtRule }
				panelId="link-destination"
			>
				<URLControls
					label={ __( 'Link Destination', 'generateblocks' ) }
					setAttributes={ setAttributes }
					htmlAttributes={ htmlAttributes }
					context={ context }
					tagName={ tagName }
				/>
			</OpenPanel>

			<OpenPanel
				{ ...panelProps }
				shouldRender={ '' === currentAtRule }
				panelId="settings"
			>
				<TagNameControl
					blockName="generateblocks/loop-item"
					value={ tagName }
					onChange={ ( value ) => {
						setAttributes( { tagName: value } );

						if ( 'a' === value && ! styles?.display ) {
							onStyleChange( 'display', 'block' );
						}
					} }
				/>

				{ 'a' === tagName && (
					<BaseControl>
						<Notice
							status="warning"
							isDismissible={ false }
						>
							{ __( 'This container is now a link element. Be sure not to add any interactive elements inside of it, like buttons or other links.', 'generateblocks' ) }
						</Notice>
					</BaseControl>
				) }
			</OpenPanel>
		</ApplyFilters>
	);
}
