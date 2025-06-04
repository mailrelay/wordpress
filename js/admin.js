document.addEventListener('DOMContentLoaded', () => {
	const autoSyncCheckbox = document.getElementById('mailrelay_auto_sync');
	const autoSyncGroupsField = document.querySelector('.mailrelay-auto-sync-groups-field');
	if (!autoSyncCheckbox || !autoSyncGroupsField) return;

	const toggleGroupsField = () => {
		autoSyncGroupsField.classList.toggle('hidden', !autoSyncCheckbox.checked);
	};

	autoSyncCheckbox.addEventListener('change', toggleGroupsField);
	toggleGroupsField();
});