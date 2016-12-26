# avail
An API based availability calendar for Laravel 4.2

Require Carbon\Carbon but already supplied by Laravel 4.2

## avail schema
**__avail_calendars__**
```
$table->bigIncrements('calendar_id');
$table->string('calendar_name', 255);
$table->string('status', 15);
$table->timestamps();
```

**__avail_bookings__**
```
$table->bigIncrements('booking_id');
$table->bigInteger('calendar_id');
$table->date('calendar_date');
$table->bigInteger('state_id');
// external_booking_ids could be integer or string depending on external id data type
// assuming string as data type for good measure
$table->string('external_booking_id', 255)->nullable();
$table->timestamps();
```
Use *external_booking_id* to link your actual booking ID

**__avail_states__**
```
$table->bigIncrements('state_id');
$table->string('state', 100);
$table->tinyInteger('state_order')->nullable()->default(0);
$table->timestamps();
```

## APIs

### Getting calendar with data

`GET /avail/api/get-calendar-with-data`

**Parameters**

integer    `take`         (optional)    - the amount of months needed to be displayed (default: 3)

integer    `page`         (optional)    - indicate the pagination starting from current month (default: 1)

integer    `calendar_id`  (required)    - the calendar ID

### Getting calendar list

`GET /avail/api/get-calendar`

**Parameters**

integer    `calendar_id`  (optional)    - the calendar ID

### Getting state list

`GET /avail/api/get-state`

**Parameters**

integer    `state_id`        (optional)    - the state ID

### Creating new calendar

`POST /avail/api/post-new-calendar`

**Parameters**

string    `name`         (required)    - the calendar name

string    `status`       (required)    - calendar status ('active', 'inactive')

### Update existing calendar

`POST /avail/api/post-update-calendar`

**Parameters**

integer   `calendar_id`  (required)    - the calendar ID

string    `name`         (optional)    - the calendar name

string    `status`       (optional)    - calendar status ('active', 'inactive')

### Creating new state

`POST /avail/api/post-new-state`

**Parameters**

integer   `calendar_id`  (required)    - the calendar ID

string    `name`         (optional)    - the calendar name

string    `status`       (optional)    - calendar status ('active', 'inactive')

### Update existing state

`POST /avail/api/post-update-state`

**Parameters**

integer   `state_id`     (required)    - the state ID

string    `state`        (optional)    - the name of the state

integer   `state_order`  (optional)    - the order of the state, for displaying purpose

### Creating booking items

`POST /avail/api/post-new-booking`

**Parameters**

integer   `calendar_id`           (required)    - the calendar ID

integer   `state_id`              (required)    - the state ID

array     `dates`                 (required)    - selected dates

string    `external_booking_id`   (optional)    - external booking ID (your actual booking detail ID)

### Deleting booking items

`POST /avail/api/post-release-booking`

**Parameters**

integer `calendar_id`      (required)    - the calendar ID

array   `dates`            (required)    - selected dates