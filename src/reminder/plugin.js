import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

const reminderTimeGapDefault = activityPubEventBridge.reminderTypeGap;

const Reminder = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const reminderTimeGap = meta?.activitypub_event_bridge_reminder_time_gap ? meta?.activitypub_event_bridge_reminder_time_gap : reminderTimeGapDefault;

	return (
		<PluginDocumentSettingPanel
			name="activitypub"
			title={ __( 'Send reminder before event\'s start', 'activitypub' ) }
		>
			<SelectControl
				label={ __( 'Time gap', 'activitypub' ) }
				value={ reminderTimeGap }
				options={ [
					{ label: __( 'Disabled', 'activitypub-event-bridge' ), value: 0 },
					{ label: __( '6 hours', 'activitypub-event-bridge' ), value: 21600 },
					{ label: __( '1 day', 'activitypub-event-bridge' ), value: 86400 },
					{ label: __( '3 days', 'activitypub-event-bridge' ), value: 259200 },
					{ label: __( '1 week', 'activitypub-event-bridge' ), value: 604800 }
				] }
				onChange={ ( value ) => {
					setMeta( { ...meta, activitypub_event_bridge_reminder_time_gap: value } );
				} }
				__nextHasNoMarginBottom
			/>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'activitypub-event-bridge-reminder', { render: Reminder } );