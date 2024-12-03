# JSON Providers

This folder's *example* contains the logic that is used in other examples. It provides a JSON-based interface to the local SQLite database that stores data as pulled from the LibCal API. Depending on the GET parameters passed to it via the URL, it can provide  slightly filtered data:

- Event Type(s)

  > Choose what is returned in the payload - events, or space reservations not associated to an event (reserved space)

  - Events  
    Example: `?events=true` or `?events=1` or `?events=false` or `?events=0`
  - Meetings (Space reservations that are not associated to any Events)  
    Example: `?meetings=true` or `?meetings=1` or `?meetings=false` or `?meetings=0`

- Timeframe

  > Example: `?days=14&start=2023-12-05&end=December+8+2023`  
  > The start/end parameters will accept any valid date string or number  
  > If a valid date cannot be deciphered, the value will default to the UNIX epoch: January 1, 1970

  - Days of events to return (defaults to 1; current day)
    - The setting of "day" overrides start/end timeframes if both start and end are set
  - Start date
  - End date

- Space

  > Example: `?space=201`  
  > This parameter accepts a singular integer value that relates to a physical location's ID from LibCal's administrative interface; it does not accept a space's *name*, only the ID

For *Event* types, the following options are also available:

- Audience

  > Limit the returned results by the audience(s) provided

  - A comma delimited list of either audience IDs (the ID as provided via LibCal's administrative interface), or the name of the audience (it must match fairly closely)  
    Example: `?audience=adult,children,all+ages` or `?audience=6911,1143,64737`

- Categories

  > Limit the returned results by the category/categories provided

  - A comma delimited list of either category IDs (the ID as provided via LibCal's administrative interface), or the name of the category (it must match fairly closely)  
    Example: `?categories=summer+reading,book+group,cooking` or `?categories=2376,89165,114`

- Images

  > Limit the returned results to only those that have an associated image

  - Provide this parameter (with or without any value at all) and it will prevent events that do not have an associated image from being returned  
    Example: `?images` or `?images=1` or `?images=false`

- Online

  > Limit the returned results to only those that are either available in-person, or available online (hybrid are returned regardless)

  - Provide this parameter with a true (1) or false (0) value and it will filter the events by its online capability  
    Example: `?online=0` or `?online=1`

- All

  > Return events from both public *and private* calendars

  - By default, this JSON provider will only display events from public calendars  
    Example: `?all=true`

An example to explicitly request the next 7 days worth of events, for both public and private calendar-based events, but not meetings:

`https://example.com/events.php?days=7&all=true&events=1&meetings=0`
