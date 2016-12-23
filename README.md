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
`GET /avail/api/get-calendar`

**Parameters**
integer    `take`         (optional)    - the amount of months needed to be displayed (default: 3)

integer    `page`         (optional)    - indicate the pagination starting from current month (default: 1)

integer    `calendar_id`  (required)    - the calendar ID

