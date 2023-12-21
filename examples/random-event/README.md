# Random Events

This example was created with the explicit purpose of displaying random (public) events within the next 2 weeks on a TV display (via a Raspberry Pi running [Screenly/Anthias](https://anthias.screenly.io/)) at a resolution of 1920x1080 (1080p). It does not display events that require registration but are also full (we don't want to market events that people can't attend). Further parsing to prevent the display of "Cancelled," "Postponed," or "Rescheduled" events might also be worthwhile. It is essentially a landscape-oriented card within a slide show. Because of its intended purpose, it may not display as expected on other resolutions, devices, or orientations.

If an event requires registration, a JavaScript-based QRCode generator will also embed a QRCode for patrons to scan in order to get to the event's page.

![Random Event Slide](random-event-github.png)

> [!NOTE]
> Images are directly linked from its LibCal-hosted location, and are not resized. Care should be taken to make sure staff are not uploading extremely large sized images.