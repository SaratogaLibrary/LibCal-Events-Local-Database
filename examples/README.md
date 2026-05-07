# Example Uses of the Local LibCal Events Database

To get some creative ideas started, here are a few projects that can take advantage of the local LibCal events database:

1. [JSON Providers](json-providers/)  
   This project doesn't display anything visually or graphically, but is the heart and soul of the other example projects. This provides an interface to query and filter, in a simple way, data from the database, and then generate data in a consumable fashion for other programs.
2. [A Random Event Slide](random-event/)  
   This project shows how simple data can be retrieved, and then displayed in a custom layout. Some level of data massaging and filtering is used, but it's not too crazy.
3. [Hover-Style Cards](hover-cards/) (Widget?)  
   This project showcases how one might be able to pull data from the database (via the JSON Providers), and then embed some customized, and nicely laid out event widgets onto their own website's content area. You have full control over the templates that can be created and rendered. All you need is the data, and the rest is totally under your control! This example aims to show that possibility.
4. [Brochure Generator](brochure/)  
   This project offers your patrons a way to generate an event brochure that is customized to their preferences based on available attributes from your
   event calendar's data. This could be the audience type, category of event, or location (space). These are, by default, the provided attributes that
   can be filtered, but this can fairly easily be extended to consider other attributes as found in the SQLite database that this project creates. This uses the [mPDF](https://mpdf.github.io/) open source project to generate the Word Documents.  
     
   (**NOTE:** The generated PDF is _not_ fully accessible for online use [printed documents should be fine]. PDF libraries for PHP cannot currently generate PDF/UA (ISO 14289) compatible PDF documents. This project is still a good showcase of what is possible using the LibCal API.)
6. [Room Setup Report](room-setup-report/)  
   It is often convenient to be able to provide a quick view (optionally printable) of what setup and equipment an event may require. Although limited by the capabilities of the API, this example can pull all events and public bookings from the Events and Spaces modules, and attach any equipment requested that is directly associated to each item. Room setup is pulled from the Event's "Event Note" field. Options provided are the start and end date, the output format (HTML or DOCX), and if the output should show *all* rooms, or just those that have equipment/setup needs. This uses the [PhpOffice/PhpWord](https://github.com/PHPOffice/PHPWord) open source project to generate the Word Documents.

## Contributing

> **NOTE:** Contributing to repositories on Github requires a Github account. It's free, quick, and easy.

Do you have any example projects, using this solution, that you'd like to share with others? Send a pull request!


