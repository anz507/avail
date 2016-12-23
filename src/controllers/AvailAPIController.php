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
use Exception;
use DB;

class AvailAPIController extends Controller
{
    protected $calendar = NULL;

    /**
     * Method for getting the calendar
     *
     * @param integer    `take`         (optional)    - the amount of months needed to be displayed (default: 3)
     * @param integer    `page`         (optional)    - indicate the pagination starting from current month (default: 1)
     * @param integer    `calendar_id`  (required)    - the calendar ID
     * @author Ahmad Anshori <anz507@gmail.com>
     *
     */
    public function getCalendar()
    {
        $data = new AvailResponse();
        try {
            $take = Input::get('take', 3);
            $page = Input::get('page', 1);
            $calendarId = Input::get('calendar_id');

            Validator::extend('check_calendar_id', function ($attribute, $value, $parameters) {
                $calendar = AVCalendar::where('status', 'active')
                    ->where('calendar_id', $value)
                    ->first();

                if (empty($calendar)) {
                    return FALSE;
                }

                $this->calendar = $calendar;

                return TRUE;
            });

            $validator = Validator::make(
                array(
                    'take' => $take,
                    'page' => $page,
                    'calendar_id' => $calendarId
                ),
                array(
                    'take' => 'numeric',
                    'page' => 'numeric',
                    'calendar_id' => 'required|check_calendar_id'
                ),
                array(
                    'check_calendar_id' => 'Calendar not found'
                )
            );

            // Run the validation
            if ($validator->fails()) {
                $errorMessage = $validator->messages()->first();
                throw new Exception($errorMessage, 1);
            }

            $page--;
            $skip = ($take * $page);

            $calendarData = array();

            $currentDate = Carbon::now();

            $startingDateStartOfMonth = Carbon::now()->addMonths($skip)->startOfMonth();
            $endDateEndOfMonth = Carbon::now()->addMonths($skip)->addMonths($take-1)->endOfMonth();

            // get all bookings within taken months
            $bookings = AVBooking::with('state')
                ->leftJoin('avail_calendars', function($q) use($calendarId) {
                    $q->on('avail_calendars.calendar_id', '=', 'avail_bookings.calendar_id')
                        ->on('avail_calendars.status', '=', DB::raw("'active'"));
                })
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

                $calendarData[$m] = $monthData;
            }

            $this->calendar->calendar_data = $calendarData;

            $data->data = $this->calendar;
        } catch (Exception $e) {
            $data->code = 1;
            $data->status = 'error';
            $data->message = $e->getMessage();
            $data->data = null;
        }

        return $data->render();
    }
}
