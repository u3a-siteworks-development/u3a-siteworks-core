// Scripts for the repeat event admin page

// When the Continue button is clicked, generate a list of candidate events
let startDateInput;
let firstEventDate;
let repeatFrequency;
let datePattern;
let numEventsComment;
let startChoice = 'event';
let evtStartDate = '';
let validPattern = false;

const daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const weeksList = ['0th', '1st', '2nd', '3rd', '4th', '5th'];
const twoWeeksList = ['0th', '1st and 3rd', '2nd and 4th', '1st and 3rd', '2nd and 4th', '5th'];


window.onload = function() {
  startDateInput = document.getElementById('startDateInput');
  firstEventDate = document.getElementById('firstEventDate');
  repeatFrequency = document.getElementById('repeatFrequency');
  datePattern = document.getElementById('datePattern');
  numEventsComment = document.getElementById('numEventsComment');

  repeatFrequency.onchange = setDatePattern;
  firstEventDate.onchange = setDatePattern;
  document.getElementById('use_event').onchange = useEvent;
  document.getElementById('use_date').onchange = useDate;
  document.getElementById('continueButton').onclick = generateEvents;
  document.getElementById('repeatEventReset').onclick = returnToSetupForm;

}

function useEvent() {
  numEventsComment.style.display="block";
  startDateInput.style.display="none";
  startChoice = 'event';
  setDatePattern();
}
function useDate() {
  numEventsComment.style.display="none";
  startDateInput.style.display="block";
  startChoice = 'date';
  setDatePattern();
}

function setDatePattern() {
  evtFrequency = repeatFrequency.value;
  if (startChoice === 'event') {
    evtStartDate = document.getElementById('posteventdate').value;
  } else {
    evtStartDate = firstEventDate.value;
  }
  if (''  === evtStartDate) {
    datePattern.innerText = 'You need to define the start date';
    validPattern = false;
  } else if ('none'  === evtFrequency) {
    datePattern.innerText = 'You need to choose an event frequency';
    validPattern = false;
  } else {
    //    datePattern.innerText is based on evtStartDate and evtFrequency);
    let d = new Date(evtStartDate);
    let weekDay = d.getDay(); // 0 to 6
    let weekOfMonth = Math.floor((d.getDate() +6) / 7); // will be in range 1 to 5
    validPattern = true;
    if ('weekly' == evtFrequency) {
        datePattern.innerText = "Weekly on " + daysList[weekDay] + "s";
    } else if ('fortnightly' == evtFrequency) {
        datePattern.innerText = "Fortnightly on " + daysList[weekDay] + "s";
    } else if ('monthly' == evtFrequency) {
        if (5 != weekOfMonth) {
            datePattern.innerText = "Monthly on the " + weeksList[weekOfMonth] + " " + daysList[weekDay] + " of the month";
        } else {
            let msg = "We cannot schedule Monthly if the start date is in the 5th week of the month."
            datePattern.innerText = msg;
            validPattern = false;
            alert(msg);
        }
    } else if ('twice-monthly' == evtFrequency) {
        if (5 != weekOfMonth) {
            datePattern.innerText = "Monthly on the " + twoWeeksList[weekOfMonth] + " " + daysList[weekDay] + "s of the month";
        } else {
            let msg = "We cannot schedule Twice-monthly if the start date is in the 5th week of the month.";
            datePattern.innerText = msg;
            validPattern = false;
            alert(msg);
        }
    }
  }
}

function generateEvents() {
    // Check data
    const numEvents = document.getElementById('numEvents').value;
    const eventCutoffDate = document.getElementById('eventCutoffDate').value;

    // Is a valid date pattern set?
    if (!validPattern) {
      alert('You have not set a valid date and frequency pattern.\n\nPlease correct.');
      return;
    }

    // Is numEvents valid?
    if (numEvents !== '' && (numEvents < 1 || numEvents > 13)) {
      alert('Please enter a number between 1 and 13');
      return;
    }

    // Is an cut-off date set before the calculated first date?
    if ((eventCutoffDate != '') && (eventCutoffDate < evtStartDate)) {
        alert('You have set a cut-off date earlier than the date\nof the first event that would be in the series.\n\nPlease alter the cut-off date.');
        return;
    }

    // Do we have either a number of events to create or a cut-off date
    if (numEvents == '' && eventCutoffDate == '') {
        alert('Please provide either the number of events in the series \n or a cut-off date.');
        return;
    }

    // Hide setup form and continue button and show heading for generated events
    document.getElementById('setup-form').style.display = 'none';
    document.getElementById('repeatEntriesSection').style.display = 'block';



    // Create proposed events
    const startdate = new Date(evtStartDate);
    // assumes evtStartDate is not in 5th week of month.
    const required_weekOfMonth = Math.floor((startdate.getDate() + 6) / 7); // relevant if monthly 
    const requiredPattern = [1, 3].includes(required_weekOfMonth) ? [1,3] : [2,4]; // relevant if twice-monthly
    let cutoffdate = '';
    if (eventCutoffDate != '') {
      cutoffdate = new Date(eventCutoffDate);
    }
    const evtTitle = document.getElementById('posttitle').value;

    let weekOfMonth = null;
    let maxevents = (numEvents == '' || numEvents > 13) ? 13 : numEvents;
    let newdate = startdate;

    // Add first event, but not editable if it is the exsiting template event. 
    add_event_to_table(newdate, evtTitle, (startChoice == 'event'));
    --maxevents;

    for (let i = 0; i < maxevents; i++) {
        // Calculate next date
        switch (evtFrequency) {
            case 'weekly':
                newdate.setDate(newdate.getDate() + 7); // the necessary way to do this in Javascript ;-(
                break;
            case 'fortnightly':
                newdate.setDate(newdate.getDate() + 14);
                break;
            case 'monthly':
                newdate.setDate(newdate.getDate() + 28);
                // but sometimes need an extra week
                weekOfMonth = Math.floor((newdate.getDate() + 6) / 7);
                if (weekOfMonth != required_weekOfMonth) {
                    newdate.setDate(newdate.getDate() + 7);
                }
                break;
            case 'twice-monthly':
                newdate.setDate(newdate.getDate() + 14);
                // but sometimes need an extra week
                weekOfMonth = Math.floor((newdate.getDate() + 6) / 7);
                if (!requiredPattern.includes(weekOfMonth)) {
                    newdate.setDate(newdate.getDate() + 7);
                }
        }
        // Stop if we're now past the cut-off date
        if ((eventCutoffDate != '') && (newdate > cutoffdate)) {
            break;
        }
        // Add a table row for each proposed date to the table
        add_event_to_table(newdate, evtTitle);
    }
}

function returnToSetupForm() {
  document.getElementById('repeatEntries').innerHTML = 
    "<tr><th>Date</th><th>Event title</th><th></th></tr>"; 
  document.getElementById('setup-form').style.display = 'block';
  document.getElementById('repeatEntriesSection').style.display = 'none';
  scrollTo(0,0);
}

// Add a row for each proposed date to the table

function add_event_to_table(eventDate, eventTitle,isExistingEvent = false) {
    const table = document.getElementById('repeatEntries');
    let dbdate = eventDate.toISOString().split('T')[0] // or just get first ten characters since our years have four digits
    let evdate = eventDate.toDateString();

    if (isExistingEvent) {
      table.insertAdjacentHTML('beforeend',
        `<tr>
             <td>${evdate}</td>
             <td class="new-ev-title">${eventTitle}</td>
             <td>Existing event</td>
        </tr>`);
    } else {
      table.insertAdjacentHTML('beforeend',
        `<tr><input type="hidden" name="newdates[]" value="${dbdate}">
             <td>${evdate}</td>
             <td><input type="text" name="newtitles[]" value="${eventTitle}" class="new-ev-title"></td>
             <td><input type="button" value="Remove this event" class="button-secondary" onclick="removeEvent(this)"></td>
         </tr>`);
    }
}

// Remove the parent <tr> which contains the 'remove' button
function removeEvent(id) {
    id.parentNode.parentNode.remove();
}
