/**
 * Google Login Admin Javascript.
 */

document.addEventListener('DOMContentLoaded', function () {
	// 1. Tab Switching Functionality
	const tabs = document.querySelectorAll('.google-login-nav-tabs .nav-tab');
	const contents = document.querySelectorAll('.google-login-tab-content');

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function (e) {
			e.preventDefault();

			const targetTab = tab.getAttribute('data-tab');

			// Deactivate all tabs and active contents
			tabs.forEach(t => t.classList.remove('nav-tab-active'));
			contents.forEach(c => c.classList.remove('active'));

			// Activate current tab and target content
			tab.classList.add('nav-tab-active');
			document.getElementById('tab-' + targetTab).classList.add('active');

			// Keep active tab in URL hash for persistent tab selections on reload
			window.location.hash = 'tab-' + targetTab;
		});
	});

	// Support direct linking to tabs on load via hash (e.g. #tab-settings)
	const currentHash = window.location.hash;
	if (currentHash && currentHash.indexOf('#tab-') === 0) {
		const tabKey = currentHash.replace('#tab-', '');
		const targetTab = document.querySelector('.google-login-nav-tabs .nav-tab[data-tab="' + tabKey + '"]');
		if (targetTab) {
			targetTab.click();
		}
	}

	// 2. Redirect behavior visibility toggle
	const redirectTypeSelect = document.getElementById('login_redirect_type');
	const customRedirectRow = document.getElementById('custom-redirect-row');

	if (redirectTypeSelect && customRedirectRow) {
		redirectTypeSelect.addEventListener('change', function () {
			if (this.value === 'custom') {
				customRedirectRow.style.display = '';
			} else {
				customRedirectRow.style.display = 'none';
			}
		});
	}
});

/**
 * Copy Redirect Callback URI to Clipboard.
 */
function googleLoginCopyToClipboard() {
	const copyText = document.getElementById('google_login_redirect_uri');
	if (!copyText) return;

	copyText.select();
	copyText.setSelectionRange(0, 99999); // For mobile devices

	// Copy command
	navigator.clipboard.writeText(copyText.value).then(function () {
		const copyBtn = document.querySelector('.google-login-copy-btn');
		const originalText = copyBtn.innerText;

		copyBtn.innerText = 'Copied!';
		copyBtn.style.backgroundColor = '#10b981'; // Green transition feedback

		setTimeout(function () {
			copyBtn.innerText = originalText;
			copyBtn.style.backgroundColor = '';
		}, 2000);
	}).catch(function (err) {
		console.error('Failed to copy text: ', err);
	});
}


