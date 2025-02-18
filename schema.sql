CREATE TABLE calendars (
	id                         INTEGER NOT NULL,
	name                       TEXT NOT NULL,
	url                        TEXT NOT NULL,
	owner                      TEXT NOT NULL,
	visibility                 TEXT NOT NULL
);

CREATE TABLE locations (
	id                         INTEGER NOT NULL,
	name                       TEXT NOT NULL,
	public                     INTEGER NOT NULL DEFAULT 1 CHECK(public IN (0,1)),
	admin_only                 INTEGER NOT NULL DEFAULT 0 CHECK(admin_only IN (0,1))
);

CREATE TABLE events (
	id                         INTEGER NOT NULL UNIQUE,
	allday                     INTEGER NOT NULL DEFAULT 0 CHECK(allday   IN (0,1)),
	multiday                   INTEGER NOT NULL DEFAULT 0 CHECK(multiday IN (0,1)),
	private                    INTEGER NOT NULL DEFAULT 0 CHECK(private  IN (0,1)),
	start                      INTEGER NOT NULL,
	end                        INTEGER NOT NULL,
	setup                      INTEGER,
	breakdown                  INTEGER,
	title                      TEXT NOT NULL,
	description                TEXT,
	more_info                  TEXT,
	event_note                 TEXT,
	internal_notes             TEXT,
	url                        TEXT,
	admin_url                  TEXT,
	location_type              INTEGER NOT NULL DEFAULT 0 CHECK(location_type IN (0,2)), -- In-person (2), or not (0) (as of 2024-10-10)
	location_id                TEXT DEFAULT "0",
	location                   TEXT,
	campus_id                  INTEGER,
	campus                     TEXT,
	audience_id                TEXT,
	audience                   TEXT,
	cat_id                     TEXT,
	category                   TEXT,
	tag_id                     TEXT,
	internal_tags              TEXT,
	owner_id                   INTEGER,
	owner                      TEXT NOT NULL,
	presenter                  TEXT,
	cal_id                     INTEGER,
	calendar                   TEXT,
	color                      TEXT NOT NULL DEFAULT '#000000' CHECK(length(color) == 7),
	image                      TEXT,
	geo_id                     TEXT,                    -- https://www.google.com/maps/place/?q=place_id:<place_id>
	geo_lat                    TEXT,
	geo_long                   TEXT,
	cost                       INTEGER,
	registration               INTEGER NOT NULL DEFAULT 0 CHECK(registration IN (0,1)),
	registration_form_id       INTEGER,
	registration_linked        INTEGER,
	registration_type          TEXT,
	registration_open          INTEGER NOT NULL DEFAULT 0 CHECK(registration IN (0,1)),
	registration_closed        INTEGER NOT NULL DEFAULT 0 CHECK(registration IN (0,1)),
	registration_cost          INTEGER NOT NULL DEFAULT 0,
	attendance_physical        INTEGER,
	attendance_online          INTEGER,
	seats                      INTEGER,
	seats_taken                INTEGER,
	physical_seats             INTEGER,
	physical_seats_taken       INTEGER,
	online_seats               INTEGER,
	online_seats_taken         INTEGER,
	zoom_email                 TEXT,
	online_user_id             INTEGER,
	online_meeting_id          INTEGER,
	online_host_url            TEXT,
	online_join_url            TEXT,
	online_join_password       TEXT,
	online_provider            TEXT,
	wait_list                  INTEGER NOT NULL DEFAULT 0 CHECK(wait_list IN (0,1)),
	future_dates               TEXT,
	PRIMARY KEY(id)
);

CREATE TABLE spaces (
	id                         INTEGER NOT NULL,
	name                       TEXT NOT NULL,
	description                TEXT,
	termsAndConditions         TEXT,
	image                      TEXT,
	capacity                   INTEGER NOT NULL,
	formId                     INTEGER NOT NULL DEFAULT 0,
	isBookableAsWhole          INTEGER NOT NULL DEFAULT 1 CHECK(isBookableAsWhole IN (0,1)),
	isEventLocation            INTEGER NOT NULL DEFAULT 0 CHECK(isEventLocation IN (0,1)),
	zoneId                     INTEGER NOT NULL DEFAULT 0,
	google                     INTEGER NOT NULL DEFAULT 0 CHECK(google IN (0,1)),
	exchange                   INTEGER NOT NULL DEFAULT 0 CHECK(exchange IN (0,1)),
	filter_ids                 TEXT,
	zoneName                   TEXT,
	groupId                    INTEGER,
	groupName                  TEXT,
	groupTermsAndConditions    TEXT,
	locationTermsAndConditions TEXT,
	lid                        INTEGER NOT NULL
);

CREATE TABLE bookings (
	booking_id                 TEXT NOT NULL,           -- matches equipment booking ID when booked together
	id                         TEXT NOT NULL UNIQUE,
	title                      TEXT,                    -- nickname; "required", but can be null
	eid                        INTEGER NOT NULL,        -- space id
	cid                        INTEGER NOT NULL,        -- space category id
	lid                        INTEGER NOT NULL,        -- branch id
	event_id                   INTEGER,
	seat_id                    INTEGER,
	branch                     TEXT NOT NULL,
	category                   TEXT NOT NULL,
	location                   TEXT NOT NULL,           -- item_name
	seat_name                  TEXT,
	start	                   INTEGER NOT NULL,
	end                        INTEGER NOT NULL,
	created                    TEXT,
	firstname                  TEXT NOT NULL,
	lastname                   TEXT NOT NULL,
	email	                   TEXT NOT NULL,
	account                    TEXT NOT NULL,
	status                     TEXT,
	internal_notes             TEXT,
	check_in_code              TEXT,
	check_in_status            TEXT,
	form_answers               TEXT,
	cancelled                  INTEGER NOT NULL DEFAULT 0 CHECK(cancelled IN (0,1))
);

CREATE TABLE space_categories (
	id                         INTEGER NOT NULL,        -- JSON cid value
	lid                        INTEGER NOT NULL,
	location_name              TEXT NOT NULL,
	name                       TEXT NOT NULL,
	formid                     INTEGER,
	public                     INTEGER NOT NULL DEFAULT 1 CHECK(public IN (0,1)),
	admin_only                 INTEGER NOT NULL DEFAULT 0 CHECK(admin_only IN (0,1)),
	terms_and_conditions       TEXT,
	description                TEXT,
	google                     INTEGER NOT NULL DEFAULT 0 CHECK(google IN (0,1))
);

-- Commented out until equipment bookings can be accurately associated via the API results
CREATE TABLE equipment (
	booking_id                 TEXT NOT NULL,           -- matches space booking ID when booked together
	id                         INTEGER NOT NULL,
	eid                        INTEGER NOT NULL,        -- equipment id
	cid                        INTEGER NOT NULL,        -- equipment category id
	lid                        INTEGER NOT NULL,        -- location id
	item_name                  TEXT NOT NULL,           -- equipment name
	category_name              TEXT NOT NULL,           -- equipment category name
	location_name              TEXT NOT NULL,
	start                      INTEGER NOT NULL,
	end                        INTEGER NOT NULL,
	created                    INTEGER NOT NULL,
	firstName                  TEXT,
	lastName                   TEXT,
	email                      TEXT,
	account                    TEXT,
	status                     TEXT,
	internal_notes             TEXT,
	barcode                    INTEGER,
	event_id                   INTEGER,
	event_title                TEXT,
	form_answers               TEXT,
	cancelled                  INTEGER NOT NULL DEFAULT 0 CHECK(cancelled IN (0,1))
);