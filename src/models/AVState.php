<?php namespace Anzware\Avail;

use Illuminate\Database\Eloquent\Model as Eloquent;

class AVState extends Eloquent
{
    protected $table = 'avail_states';
    protected $primaryKey = 'state_id';

    public function bookings()
    {
        return $this->hasMany('AVBooking', 'state_id', 'state_id');
    }
}
