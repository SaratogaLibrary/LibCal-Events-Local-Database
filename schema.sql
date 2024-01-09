CREATE TABLE calendars (
	id                   INTEGER NOT NULL,
	name                 TEXT NOT NULL,
	url                  TEXT NOT NULL,
	owner                TEXT NOT NULL,
	visibility           TEXT NOT NULL
);

CREATE TABLE locations (
	id                   INTEGER NOT NULL,
	name                 TEXT NOT NULL,
	public               INTEGER NOT NULL DEFAULT 1 CHECK(public IN (0,1)),
	admin_only           INTEGER NOT NULL DEFAULT 0 CHECK(admin_only IN (0,1))
);

CREATE TABLE events (
	id                   INTEGER NOT NULL UNIQUE,
	allday               INTEGER NOT NULL DEFAULT 0 CHECK(allday IN (0,1)),
	multiday             INTEGER NOT NULL DEFAULT 0 CHECK(allday IN (0,1)),
	private              INTEGER NOT NULL DEFAULT 0 CHECK(private IN (0,1)),
	start                INTEGER NOT NULL,
	end                  INTEGER NOT NULL,
	setup                INTEGER,
	breakdown            INTEGER,
	title                TEXT NOT NULL,
	description          TEXT,
	more_info            TEXT,
	url                  TEXT,
	admin_url            TEXT,
	location_id          TEXT DEFAULT "0",
	location             TEXT,
	audience_id          INTEGER,
	audience             TEXT,
	cat_id               INTEGER,
	category             TEXT,
	owner                TEXT NOT NULL,
	presenter            TEXT,
	cal_id               INTEGER,
	calendar             TEXT,
	color                TEXT NOT NULL DEFAULT '#000000' CHECK(length(color) == 7),
	image                TEXT,
	geo_id               TEXT,                    -- https://www.google.com/maps/place/?q=place_id:<place_id>
	geo_lat              TEXT,
	geo_long             TEXT,
	cost                 INTEGER,
	registration         INTEGER NOT NULL DEFAULT 0 CHECK(registration IN (0,1)),
	registration_open    INTEGER NOT NULL DEFAULT 0 CHECK(registration IN (0,1)),
	seats                INTEGER,
	seats_taken          INTEGER,
	physical_seats       INTEGER,
	physical_seats_taken INTEGER,
	online_seats         INTEGER,
	online_seats_taken   INTEGER,
	zoom_email           TEXT,
	online_user_id       INTEGER,
	online_meeting_id    INTEGER,
	online_host_url      TEXT,
	online_join_url      TEXT,
	online_join_password TEXT,
	online_provider      TEXT,
	wait_list            INTEGER NOT NULL DEFAULT 0 CHECK(wait_list IN (0,1)),
	future_dates         TEXT,
	PRIMARY KEY(id)
);

CREATE TABLE spaces (
	id                   INTEGER NOT NULL,
	name                 TEXT NOT NULL,
	bookableAsWhole      INTEGER NOT NULL DEFAULT 1 CHECK(bookableAsWhole IN (0,1)),
	currentOccupancy     INTEGER NOT NULL,
	currentCapacity      INTEGER NOT NULL,
	maxCapacity          INTEGER NOT NULL,
	lid                  INTEGER NOT NULL
);

CREATE TABLE bookings (
	booking_id           TEXT NOT NULL,
	id                   TEXT NOT NULL UNIQUE,
	title                TEXT,                    -- nickname; "required", but can be null
	eid                  INTEGER NOT NULL,        -- space id
	cid                  INTEGER NOT NULL,        -- space category id
	lid                  INTEGER NOT NULL,        -- branch id
	event_id             INTEGER,
	seat_id              INTEGER,
	branch               TEXT NOT NULL,
	category             TEXT NOT NULL,
	location             TEXT NOT NULL,
	seat_name            TEXT,
	start	             INTEGER NOT NULL,
	end                  INTEGER NOT NULL,
	created              TEXT,
	firstname            TEXT NOT NULL,
	lastname             TEXT NOT NULL,
	email	             TEXT NOT NULL,
	account              TEXT NOT NULL,
	status               TEXT,
	check_in_code        TEXT,
	check_in_status      TEXT
);

CREATE TABLE space_categories (
	id                   INTEGER NOT NULL,        -- JSON cid value
	lid                  INTEGER NOT NULL,
	location_name        TEXT NOT NULL,
	name                 TEXT NOT NULL,
	formid               INTEGER,
	public               INTEGER NOT NULL DEFAULT 1 CHECK(public IN (0,1)),
	admin_only           INTEGER NOT NULL DEFAULT 0 CHECK(admin_only IN (0,1)),
	terms_and_conditions TEXT,
	description          TEXT,
	google               INTEGER NOT NULL DEFAULT 0 CHECK(google IN (0,1))
);

/*
-- Commented out until equipment bookings can be accurately associated via the API results
CREATE TABLE equipment (
	booking_id           TEXT NOT NULL,
	id                   INTEGER NOT NULL,
	eid                  INTEGER NOT NULL,        -- ?? id
	cid                  INTEGER NOT NULL,        -- equipment category id
	lid                  INTEGER NOT NULL,        -- location id
	location_name        TEXT NOT NULL,
	category_name        TEXT NOT NULL,
	item_name            TEXT NOT NULL,
	from_date            TEXT NOT NULL,
	to_date              TEXT NOT NULL,
	created              TEXT NOT NULL,
	firstName            TEXT,
	lastName             TEXT,
	email                TEXT,
	account              TEXT,
	status               TEXT
);
*/