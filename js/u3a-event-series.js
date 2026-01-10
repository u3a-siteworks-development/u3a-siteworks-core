let startDateInput;
let frequencyInput;
let eventNumberInput;
let eventCutoffDateInput;

window.onload = function() {
    startDateInput = document.getElementById("eventStartDate");
    frequencyInput = document.getElementById("eventFrequency");
    eventNumberInput = document.getElementById("eventNumber");
    eventCutoffDateInput = document.getElementById("eventCutoffDate");
    startDateInput.onchange = function(){
        setDatePattern();
        checkSeriesEnd(); // Since startDate is a required field, this check will be called sometime!
    };
    frequencyInput.onchange = function(){
        setDatePattern();
    };
    eventNumberInput.onchange = function(){
        checkSeriesEnd();
    };
    eventCutoffDateInput.onchange = function(){
        checkSeriesEnd();
    };
};

    // Display message in datePattern heading element according to event frequency, and lock if invalid.
function setDatePattern(){
    evtStartDate = startDateInput.value;
    evtFrequency = frequencyInput.value;
    if ((''  === evtStartDate) || (''  === evtFrequency)) {
        return;
    };
    d = new Date(evtStartDate);
    weekDay = d.getDay(); // 0 to 6
    weekOfMonth = Math.floor((d.getDate() +6) / 7); // will be in range 1 to 5
    daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    weeksList = ['0th', '1st', '2nd', '3rd', '4th', '5th'];
    twoWeeksList = ['0th', '1st and 3rd', '2nd and 4th', '1st and 3rd', '2nd and 4th', '5th'];

    validFrequency = true;
    if ('weekly' == evtFrequency) {
        msg = "Weekly on " + daysList[weekDay] + "s";
    } else if ('fortnightly' == evtFrequency) {
        msg = "Fortnightly on " + daysList[weekDay] + "s";
    } else if ('monthly' == evtFrequency) {
        if (5 != weekOfMonth) {
            msg = "Monthly on the " + weeksList[weekOfMonth] + " " + daysList[weekDay] + " of the month";
        } else {
            msg = "You cannot choose Monthly if the start date is in the 5th week of the month.";
            validFrequency = false;
        }
    } else if ('twice-monthly' == evtFrequency) {
        if (5 != weekOfMonth) {
            msg = "Monthly on the " + twoWeeksList[weekOfMonth] + " " + daysList[weekDay] + "s of the month";
        } else {
            msg = "You cannot choose Twice monthly if the start date is in the 5th week of the month.";
            validFrequency = false;
        }
    }
    el = document.getElementById("datePattern");
    if (validFrequency) {
        el.innerText = "INFO: The event dates will be : " + msg;
        el.className = "rwmb-required";
    } else {
        el.innerText = "WARNING: "  + msg;
        el.className = "rwmb-required"; // Can't use rwmb-error as that only applies to <p> tags. but this class produces the same red style. 
    }
    lock(!validFrequency, 'evtDatePattern', 'Invalid start date and frequency');
}

// Set lock if no series end is present, or unset it if ok.
function checkSeriesEnd() {
    invalidSeriesEnd = ('' == eventNumberInput.value) && ('' == eventCutoffDateInput.value);
    lock(invalidSeriesEnd, 'evtSeriesEnd', 'At least one of Number of events and Series cutoff date must be set.');
}

// Locking thanks to https://bdwm.be/gutenberg-how-to-prevent-post-from-being-saved/

// Keep track of our locks
const locks = [];

// Adds or removes lock preventing post from being saved.
function lock( lockIt, handle, message ) {
  if ( lockIt ) {
    if ( ! locks[ handle ] ) {
      locks[ handle ] = true;
      wp.data.dispatch( 'core/editor' ).lockPostSaving( handle );
      wp.data.dispatch( 'core/notices' ).createNotice(
        'error',
        message,
        { id: handle, isDismissible: false }
      );
    }
  } else if ( locks[ handle ] ) {
    locks[ handle ] = false;
    wp.data.dispatch( 'core/editor' ).unlockPostSaving( handle );
    wp.data.dispatch( 'core/notices' ).removeNotice( handle );
  }
}

