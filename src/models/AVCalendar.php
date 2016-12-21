<?php namespace Anzware\Avail;

use Illuminate\Database\Eloquent\Model as Eloquent;

class AVCalendar extends Eloquent
{
    protected $table = 'avail_calendars';
    protected $primaryKey = 'calendar_id';

    public function bookings()
    {
        return $this->hasMany('AVBooking', 'calendar_id', 'calendar_id');
    }
}
