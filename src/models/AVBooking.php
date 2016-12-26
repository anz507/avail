<?php namespace Anzware\Avail;

use Illuminate\Database\Eloquent\Model as Eloquent;

class AVBooking extends Eloquent
{
    protected $table = 'avail_bookings';
    protected $primaryKey = 'booking_id';

    public function calendar()
    {
        return $this->belongsTo('Anzware\Avail\AVCalendar', 'calendar_id', 'calendar_id');
    }

    public function state()
    {
        return $this->belongsTo('Anzware\Avail\AVState', 'state_id', 'state_id');
    }
}
