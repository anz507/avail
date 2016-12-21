<?php namespace Anzware\Avail;
/**
 * All in one API for Available Calendar
 *
 */

use Illuminate\Routing\Controller;
use Anzware\Avail\Helper\AvailResponse;
use Carbon\Carbon;
use Anzware\Avail\AVBooking;
use Anzware\Avail\AVCalendar;
use Anzware\Avail\AVState;
use Input;
use Validator;

class AvailAPIController extends Controller
{
    /**
     * Method for getting the calendar
     *
     * @param integer    `take`    (optional)    - the amount of months needed to be displayed (default: 3)
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     * @todo implement pagination
     */
    public function getCalendar()
    {
        try {
            $take = Input::get('take', 3);
            $calendarId = Input::get('calendar_id');

            // @todo: add validator here

            $calendar = array();

            $currentDate = Carbon::now();
            $currentDateStartOfDay = Carbon::now()->startOfDay();
            $currentDateStartOfMonth = Carbon::now()->startOfMonth();
            $endDateEndOfMonth = Carbon::now()->startOfMonth()->addMonths($take-1)->endOfMonth();

            // get all bookings within taken months
            $bookings = AVBooking::with('state')
                ->where('calendar_date', '>=', $currentDateStartOfMonth)
                ->where('calendar_date', '<=', $endDateEndOfMonth)
                ->get();

            for ($m = 0; $m < $take; $m++) {
                $month = Carbon::now()->startOfMonth()->addMonths($m);
                $monthData = new \stdclass();
                $monthData->name = $month->format('F');
                $monthData->month = $month->month;
                $monthData->year = $month->year;
                $monthData->days = array();

                for ($d = 1; $d <= $month->daysInMonth; $d++) {
                    $day = new \stdclass();
                    $day->day = $d;
                    $day->month = $month->month;
                    $day->year = $month->year;
                    $day->stringDate = Carbon::now()->startOfMonth()->addMonths($m)->addDays($d-1)->toDateString();
                    // check for today date
                    if ($currentDate->day === $d &&
                        $currentDate->month === $month->month &&
                        $currentDate->year === $month->year
                    ) {
                        // set the 'current' flag
                        $day->current = TRUE;
                    }

                    $day->state = 'Available';

                    // override state from bookings table
                    foreach ($bookings as $booking) {
                        if ($day->stringDate === $booking->calendar_date) {
                            $day->state = $booking->state->state;
                        }
                    }

                    $monthData->days[] = $day;
                }

                $calendar[$m] = $monthData;
            }

            $data = new AvailResponse();
            $data->data = $calendar;
        } catch (Exception $e) {

        }

        return $data->render();
    }
}
