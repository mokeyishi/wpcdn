import { ToggleControl, Button, Tooltip, ComboboxControl, TextControl } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

import { isArray, isObject } from 'lodash';
import { SelectPostType, SelectPost, MultiSelect, SelectUser } from '@edge22/components';
import { Control } from '@edge22/styles-builder';

import { TaxonomyParameterControl } from './TaxonomyParameterControl';
import { DateQueryControl } from './DateQueryControl';

import getIcon from '@utils/get-icon';

function ControlComponent( props ) {
	const {
		postType,
		...standardProps
	} = props;

	switch ( props?.type ) {
		case 'text':
			return <Control { ...standardProps } as={ TextControl } />;
		case 'number':
			return (
				<TextControl
					{ ...standardProps }
					value={ standardProps.value }
					type="number"
					min="-1"
					onChange={ ( newValue ) => {
						if ( parseInt( newValue, 10 ) < -1 ) {
							standardProps.onChange( -1 );
							return;
						}

						standardProps.onChange( newValue );
					} }
				/>
			);
		case 'postTypeSelect':
			return <SelectPostType { ...standardProps } />;
		case 'select':
			return <ComboboxControl { ...standardProps } />;
		case 'multiSelect':
			return <MultiSelect { ...standardProps } />;
		case 'authorsSelect':
			return (
				<SelectUser
					{ ...standardProps }
					multiple={ true }
					currentLabel={ __( 'Current author', 'generateblocks' ) }
				/>
			);
		case 'taxonomySelect':
			return <TaxonomyParameterControl postType={ postType } { ...standardProps } />;
		case 'includePosts':
			return (
				<SelectPost
					{ ...standardProps }
					includeCurrent={ false }
					multiple={ true }
					postType={ postType }
				/>
			);
		case 'excludePosts':
			return <SelectPost multiple={ true } postType={ postType } { ...standardProps } />;
		case 'dateQuery':
			return <DateQueryControl { ...standardProps } />;
		case 'toggleControl':
			return <ToggleControl { ...standardProps } />;
	}
}

export function ControlBuilder( props ) {
	const {
		id,
		type,
		label,
		description,
		selectOptions = [],
		isSticky,
		value,
		default: defaultValue,
		onChange,
		onClickRemove,
		dependencies,
		placeholder,
		postType,
	} = props;

	let controlDescription = description;

	if ( 'posts_per_page' === id && ( '-1' === value || parseInt( value ) > parseInt( generateBlocksInfo.queryLoopEditorPostsCap ) ) ) {
		controlDescription += ' ' + sprintf(
			'Editor only: A maximum of %s posts can be previewed in the editor.',
			generateBlocksInfo.queryLoopEditorPostsCap
		);
	}

	const defaultValuePlaceholder = !! defaultValue && ( ! isArray( defaultValue ) || ! isObject( defaultValue ) )
		? defaultValue
		: undefined;

	const controlPlaceholder = placeholder || defaultValuePlaceholder;
	const isPostsPerPage = 'number' === type && 'posts_per_page' === id;

	const controlProps = {
		id,
		type,
		label,
		help: controlDescription,
		options: selectOptions,
		value,
		placeholder: controlPlaceholder,
		onChange,
		min: isPostsPerPage ? -1 : undefined,
		postType,
		...dependencies,
	};

	return (
		<div className={ 'gb-parameter-component' }>
			<ControlComponent { ...controlProps } />
			{ ! isSticky && (
				<Tooltip text={ __( 'Delete parameter', 'generateblocks-pro' ) }>
					<Button
						className="gb-remove-parameter"
						onClick={ () => {
							// eslint-disable-next-line
							if ( window.confirm( __( 'This will permanently delete this parameter.', 'generateblocks' ) ) ) {
								onClickRemove( id );
							}
						} }
						icon={ getIcon( 'trash' ) }
					/>
				</Tooltip>
			) }
		</div>
	);
}
