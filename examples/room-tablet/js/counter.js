$ = function(sel) {
	return document.querySelector(sel);
};

document.addEventListener("DOMContentLoaded", function() {
	let clock, hours, minutes, ampm, day, date, month = null;
	let pageMinutes  = $('.clock-minute').textContent;
	let pageHours    = $('.clock-hour').textContent;
	let pageMeridian = $('.clock-meridian').textContent;
	let pageDay      = $('.clock-day').textContent;
	let pageMonth    = $('.clock-month').textContent;
	let pageDate     = $('.clock-date').textContent;

	//alert(hours + ':' + minutes + ampm);
	var clockUpdate = setInterval(function(){
		clock   = new Date();
		hours   = clock.getHours();
		minutes = clock.getMinutes() < 10 ? '0' + clock.getMinutes().toString() : clock.getMinutes();
		day     = new Intl.DateTimeFormat('en-US', {weekday: 'long'}).format(clock);
		month   = new Intl.DateTimeFormat('en-US', {month: 'long'}).format(clock);
		date    = clock.getDate();
		ampm    = hours > 11 ? 'pm' : 'am';

		if (hours > 12) {
			hours -= 12;
		} else if (hours == 0) {
			hours = 12;
		}
		if (minutes != pageMinutes) {
			pageMinutes = minutes;
			$('.clock-minute').textContent = pageMinutes;
		}
		if (hours != pageHours) {
			pageHours = hours;
			$('.clock-hour').textContent = pageHours;
			//location.reload(true);
		}
		if (ampm != pageMeridian) {
			pageMeridian = ampm;
			$('.clock-meridian').textContent = pageMeridian;
		}
		if (day != pageDay) {
			pageDay = day;
			$('.clock-day').textContent = pageDay;
		}
		if (month != pageMonth) {
			pageMonth = month;
			$('.clock-month').textContent = pageMonth;
		}
		if (date != pageDate) {
			pageDate = date;
			$('.clock-date').textContent = pageDate;
		}

		if (minutes % 15 == 0) {
			// Update data for events from source, and/or refresh page?
		}
	}, 1 * 1000);
});