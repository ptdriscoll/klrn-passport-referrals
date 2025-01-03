export const errorMessages = [
  'Please fill out this field',
  'Please use this format: yyyy-mm-dd',
  'The end date cannot be earlier than the start date',
  'There must be both a start date and an end date',
  'The start date cannot be earlier than data availability',
  'There was an error',
];

/**
 * Gets data from server for provided data range.
 * @param {object} req - Start and end dates in yyyy-mm-dd format.
 *   Example: { start: '2023-08-02', end: '2023-08-30' }
 * @return {string} - Returns text instead of json so that any
 *   PHP errors (which aren't valid JSON) can print to console during development.
 */
export async function getData(req = { start: 'default', end: 'default' }) {
  //carry over any max-date url query, if it exists, as an added GET request
  const currentUrl = new URL(window.location.href);
  const maxDate = currentUrl.searchParams.get('max-date');
  const fetchGetQuery = maxDate ? `?max-date=${maxDate}` : '';

  //fetch api as POST request
  try {
    const res = await fetch(`api/get-data.php${fetchGetQuery}`, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(req),
    });
    if (!res.ok) throw new Error('Network response was not OK');
    return res.text();
  } catch (err) {
    console.error('Error fetching data:', err.message);
    throw err;
  }
}

/**
 * Parses data to JSON or throws Error.
 * @param {string} data - Data returned from getData in data-helpers.js.
 * @return {object|null}
 */
export function makeJSON(data) {
  try {
    return JSON.parse(data);
  } catch {
    console.log(data);
    throw new Error('Network response was not valid JSON');
  }
}

/**
 * Checks for error message, and if true then sets message on form.
 * @param {object} data - JSON parsed from getData string.
 * @return {boolean} - whether data response reported any errors.
 */
export function errorsExist(data) {
  const errorElem = document.querySelector('#date-error');
  const errors = data.errors;

  if (data.errors.anyErrorsExist) {
    let errorIndex = 5;
    if (errors.datesEmpty) errorIndex = 3;
    else if (errors.datesNotFormatted) errorIndex = 1;
    else if (errors.dataNotAvailable) errorIndex = 4;
    else if (errors.datesNotOrdered) errorIndex = 2;

    errorElem.innerHTML = errorMessages[errorIndex];
    if (errorIndex === 4) errorElem.innerHTML += ': ' + data.availableDates.min;
    errorElem.classList.add('fade-in');
    return true;
  }

  return false;
}

/**
 * Transforms string into a Date with time set to 0.
 * @param {string} dateString - In yyyy-mm-dd format.
 * @return {Date}
 */
export function makeDate(dateString) {
  return new Date(dateString + 'T00:00:00');
}

/**
 * Hides loader <div> and fades in <main> element.
 */
export function fadeInMain() {
  document.querySelector('#loader').classList.add('hide');
  document.querySelector('main').classList.add('fade-in');
}
