// Get the event data as stored by PHP on page load
let Events = JSON.parse(sessionStorage.getItem('room_data'));

// QRCode: https://github.com/chuckfairy/VanillaQR.js?files=1
document.addEventListener("click", (e) => {
	// Skip this function if we didn't click on an active anchor tag
	if (!e.target.nodeName) return;
	if (!e.target.classList.contains('action') && !e.target.parentNode.classList.contains('action')) return;


	e.preventDefault();
	e.stopPropagation();
	let target = e.target.closest('.event-link');
	let dialog = document.querySelector('.modal');

	if (target) {
		// Format the available datetime
		// let dateStr = new Date(target.dataset.start*1000).toLocaleString('en-US', {
		// 	// timeZone: 'EST',
		// 	weekday: 'long',
		// 	day: 'numeric',
		// 	// year: 'numeric',
		// 	month: 'long',
		// 	hour: 'numeric',
		// 	minute: '2-digit',
		// 	hour12: true
		// });

		// Set title, location, description, date and time
		let description = '';
		dialog.querySelector('.modal-title').innerHTML = "Event Information";
		description += '<h1>' + Events[target.dataset.start].title + '</h1><hr>';
		if (Events[target.dataset.start].type == 'event') {
			description += Events[target.dataset.start].description ? Events[target.dataset.start].description + Events[target.dataset.start].more_info : '<p class="empty">No description has been provided.</p>';
		} else {
			description += '<p>For information about the purpose of this public group\'s meeting held at the library, please speak with the group\'s liaison, ' + Events[target.dataset.start].firstname + ' ' + Events[target.dataset.start].lastname + '.</p><p><!-- This program or meeting is not sponsored or endorsed by Saratoga Springs Public Library. --> The library is not directly affiliated to public meetings.</p>';
		}
		description = '<div class="event-description-container">' + description + '</div>';
		dialog.querySelector('.description').innerHTML = description;

		// Disable anchor elements from being clickable
		dialog.querySelectorAll('.description a[href]').forEach((i) => {
			i.setAttribute('title', i.getAttribute('href'));
			i.setAttribute('href', `javascript:void(0)`);
		});
		// Set the close button, and the empty space (backdrop) so it can actively close the dialog
		dialog.addEventListener('click', (e) => {
			if (e.target.nodeName === 'DIALOG' || e.target.nodeName === 'BUTTON'){
				// Reset the scroll position to the top
				dialog.scrollTo({
					top: 0,
					left: 0
				});
				dialog.close();
				// Clear the content (garbage collection and prep)
				dialog.querySelector('.description').innerHTML = '';
				dialog.querySelector('.modal-title').innerHTML = description;
			}
		});

		dialog.showModal();
		dialog.querySelector('button.btn-close').focus();
	} else {
		// Menu link
		let dialog = document.querySelector('.modal');
		dialog.querySelector('.modal-title').innerHTML = "Location Space View";
		dialog.querySelector('.description').innerHTML = "";

		// Set the close button, and the empty space (backdrop) so it can actively close the dialog
		dialog.addEventListener('click', (e) => {
			if (e.target.nodeName === 'DIALOG' || e.target.nodeName === 'BUTTON'){
				// Reset the scroll position to the top
				dialog.scrollTo({
					top: 0,
					left: 0
				});
				dialog.close();
			}
		});
		
		if (e.target.textContent === 'Room Setup Report') {
			document.querySelector('.modal .modal-title').innerHTML = 'Room Setup';
		}

		dialog.showModal();
	}
});