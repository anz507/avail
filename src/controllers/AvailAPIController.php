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
     * @todo validator
     */
    public function getCalendar()
    {
        try {
            $page = 1;
            $take = Input::get('take', 3);
            $page = Input::get('page', 1) - 1;
            $skip = ($take * $page);

            $calendarId = Input::get('calendar_id');

            // @todo: add validator here

            $calendar = array();

            $currentDate = Carbon::now();

            $startingDateStartOfMonth = Carbon::now()->addMonths($skip)->startOfMonth();
            $endDateEndOfMonth = Carbon::now()->addMonths($skip)->addMonths($take-1)->endOfMonth();

            // get all bookings within taken months
            $bookings = AVBooking::with('state')
                ->where('calendar_date', '>=', $startingDateStartOfMonth)
                ->where('calendar_date', '<=', $endDateEndOfMonth)
                ->get();

            for ($m = 0; $m < $take; $m++) {
                $month = Carbon::now()->addMonths($skip)->startOfMonth()->addMonths($m);
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
                    $day->stringDate = Carbon::now()->addMonths($skip)->startOfMonth()->addMonths($m)->addDays($d-1)->toDateString();
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
